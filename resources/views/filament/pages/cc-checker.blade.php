<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="border-t pt-6 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-md font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                    </span>
                    Live Debug Console
                </h3>
                <span class="text-[10px] font-mono text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                    QUEUE_SIZE: {{ count($queue) }}
                </span>
            </div>

            <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($logs as $log)
                    <div class="p-3 border rounded-lg bg-white dark:bg-gray-900 dark:border-gray-700 border-l-4 {{ $log['color'] === 'success' ? 'border-l-success-500' : ($log['color'] === 'danger' ? 'border-l-danger-500' : 'border-l-warning-500') }} shadow-sm">
                        
                        {{-- Fila Superior: CC y Tiempo --}}
                        <div class="flex justify-between items-center mb-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100 select-all">
                                    {{ $log['cc'] }}
                                </span>
                                
                                <span class="px-1.5 py-0.5 text-[9px] rounded font-black uppercase {{ $log['color'] === 'success' ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700' }}">
                                    {{ $log['result'] }}
                                </span>
                            </div>
                            <span class="text-[10px] font-mono text-gray-400">{{ $log['time'] }}</span>
                        </div>

                        {{-- Fila Media: Mensaje y Referencia de Servidor --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="text-xs font-semibold {{ $log['color'] === 'success' ? 'text-success-600' : ($log['color'] === 'danger' ? 'text-danger-600' : 'text-warning-600') }}">
                                {{ $log['message'] }}
                            </div>

                            @if(isset($log['server_ref']) && $log['server_ref'] !== 'N/A')
                                <span class="text-[9px] font-mono text-gray-400 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                    ref: {{ $log['server_ref'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Fila Inferior: Detalles del Payload --}}
                        <details class="mt-2 group">
                            <summary class="list-none cursor-pointer text-[10px] text-gray-400 hover:text-primary-500 transition-colors flex items-center gap-1 font-bold uppercase tracking-tighter">
                                <x-filament::icon 
                                    icon="heroicon-m-chevron-right" 
                                    class="h-3 w-3 group-open:rotate-90 transition-transform text-gray-500" 
                                />
                                <span>Raw Payload</span>
                            </summary>
                            
                            <div class="mt-2 p-3 bg-gray-950 rounded border border-gray-800 shadow-inner overflow-x-auto max-h-48">
                                <pre class="text-[10px] font-mono text-green-500 leading-relaxed">{{ $log['raw'] }}</pre>
                            </div>
                        </details>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed rounded-xl border-gray-300 dark:border-gray-700 text-gray-500">
                        <x-filament::icon icon="heroicon-o-circle-stack" class="h-8 w-8 mb-2 opacity-20" />
                        <span class="text-sm italic">Esperando inicialización del Gate...</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; }
    </style>
</x-filament-panels::page>