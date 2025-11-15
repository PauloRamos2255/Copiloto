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
        Schema::create('evento', function (Blueprint $table) {
            $table->unsignedInteger('codevento');
            $table->string('nombre', 10)->nullable();
            $table->dateTime('inicio')->nullable();
            $table->dateTime('fin')->nullable();
            $table->string('tipo', 1)->nullable();
            $table->unsignedInteger('historicoViaje_codhistorioViaje');

            $table->primary('codevento');
            $table->index('historicoViaje_codhistorioViaje', 'fk_evento_historicoViaje1_idx');

            $table->foreign('historicoViaje_codhistorioViaje', 'fk_evento_historicoViaje1')
                  ->references('codhistorioViaje')->on('historicoViaje')
                  ->onUpdate('restrict')->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropForeign('fk_evento_historicoViaje1');
            $table->dropIndex('fk_evento_historicoViaje1_idx');
        });

        Schema::dropIfExists('evento');
    }
};
