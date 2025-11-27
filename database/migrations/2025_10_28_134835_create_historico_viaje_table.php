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
        Schema::create('historicoViaje', function (Blueprint $table) {
            $table->unsignedInteger('codhistorioViaje');
            $table->unsignedInteger('asignacion_codAsignacion');
            $table->dateTime('inicio')->nullable();
            $table->dateTime('fin')->nullable();
            $table->string('estado', 1)->nullable();

            $table->primary('codhistorioViaje');
            $table->index('asignacion_codAsignacion', 'fk_historicoViaje_asignacion1_idx');

            $table->foreign('asignacion_codAsignacion', 'fk_historicoViaje_asignacion1')
                  ->references('codasignacion')->on('asignacion')
                  ->onUpdate('restrict')->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historicoViaje', function (Blueprint $table) {
            $table->dropForeign('fk_historicoViaje_asignacion1');
            $table->dropIndex('fk_historicoViaje_asignacion1_idx');
        });

        Schema::dropIfExists('historicoViaje');
    }
};
