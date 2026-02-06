<x-filament-panels::page>
    <form wire:submit.prevent="runGeneration">
        {{ $this->form }}
    </form>
    
    <div class="mt-4 text-sm text-gray-500 italic">
        * Este generador utiliza el Algoritmo de Luhn para asegurar que los números de tarjeta sean estructuralmente válidos.
    </div>
</x-filament-panels::page>