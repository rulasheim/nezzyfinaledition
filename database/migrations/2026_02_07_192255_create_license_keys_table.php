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
    Schema::create('license_keys', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique(); // Formato nezzychk-xxxxxxxxxxxx
        $table->foreignId('subscription_id')->constrained()->cascadeOnDelete(); // Plan asociado
        $table->boolean('is_used')->default(false);
        $table->timestamp('used_at')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Quién la canjeó
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_keys');
    }
};
