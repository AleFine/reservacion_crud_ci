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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id('id_reserva'); 
            $table->date('fecha');
            $table->time('hora');
            $table->integer('numero_de_personas');
            $table->foreignId('id_comensal')->references('id_comensal')->on('comensales')->onDelete('RESTRICT');
            $table->foreignId('id_mesa')->references('id_mesa')->on('mesas')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
