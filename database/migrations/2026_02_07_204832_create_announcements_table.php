<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('announcements', function (Blueprint $table) {
        $table->id();
        $table->string('title'); // Título de la noticia/promoción
        $table->string('image_path'); // Ruta del flyer/foto
        $table->text('content')->nullable(); // Texto descriptivo
        $table->boolean('is_active')->default(true); // Para ocultar o mostrar
        $table->integer('sort_order')->default(0); // Orden en el carrusel
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
