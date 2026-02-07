<x-filament-panels::page>
    <div class="space-y-6">
        @if(count($announcements) > 0)
            <div class="swiper mySwiper rounded-xl shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                <div class="swiper-wrapper">
                    @foreach($announcements as $announcement)
                        <div class="swiper-slide relative bg-black">
                            @php
                                // Lógica para detectar si es video por extensión
                                $extension = pathinfo($announcement->image_path, PATHINFO_EXTENSION);
                                $isVideo = in_array(strtolower($extension), ['mp4', 'mov', 'webm', 'ogv']);
                                $fileUrl = \Illuminate\Support\Facades\Storage::url($announcement->image_path);
                            @endphp

                            @if($isVideo)
                                {{-- Renderizado de Video --}}
                                <video 
                                    src="{{ $fileUrl }}" 
                                    class="w-full h-[400px] object-cover" 
                                    autoplay 
                                    muted 
                                    loop 
                                    playsinline>
                                </video>
                            @else
                                {{-- Renderizado de Imagen --}}
                                <img src="{{ $fileUrl }}" 
                                     class="w-full h-[400px] object-cover md:object-fill"
                                     alt="{{ $announcement->title }}">
                            @endif
                            
                            {{-- Capa de texto sobre el medio --}}
                            <div class="absolute bottom-0 w-full bg-gradient-to-t from-black/90 to-transparent p-6">
                                <h3 class="text-xl font-bold text-white">{{ $announcement->title }}</h3>
                                @if($announcement->content)
                                    <p class="text-gray-200 text-sm mt-1">{{ $announcement->content }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Controles del Swiper --}}
                <div class="swiper-button-next text-white"></div>
                <div class="swiper-button-prev text-white"></div>
                <div class="swiper-pagination"></div>
            </div>
        @else
            <x-filament::section>
                <div class="text-center py-6 text-gray-500">
                    No hay anuncios o promociones activas en este momento.
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- Estilos y Scripts de Swiper --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <style>
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 20px !important; font-weight: bold; }
        .swiper-pagination-bullet-active { background: #f59e0b !important; } /* Color Amber de tu tema */
        
        /* Asegura que los videos ocupen bien el espacio */
        .swiper-slide video {
            display: block;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Swiper(".mySwiper", {
                loop: true,
                spaceBetween: 0,
                centeredSlides: true,
                speed: 800, // Transición más suave
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
            });
        });
    </script>
</x-filament-panels::page>