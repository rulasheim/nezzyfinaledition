<x-filament-panels::page>
    <x-filament::section>
        <div class="max-w-md mx-auto py-4">
            <h2 class="text-xl font-bold mb-4">Ingresa tu código nezzychk</h2>
            <p class="text-sm text-gray-500 mb-6">Al canjear el código, tu suscripción se actualizará automáticamente.</p>
            
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="key"
                    placeholder="nezzychk-XXXXXXXXXXXX"
                />
            </x-filament::input.wrapper>

            <div class="mt-6">
                <x-filament::button wire:click="redeem" class="w-full">
                    Activar Ahora
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>