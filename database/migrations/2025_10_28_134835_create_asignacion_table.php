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
        Schema::create('asignacion', function (Blueprint $table) {
            $table->unsignedInteger('codasignacion');
            $table->unsignedInteger('ruta_codruta');
            $table->unsignedInteger('usuario_codusuario');
            $table->dateTime('ultimaActualizacion')->nullable();

            $table->primary('codasignacion');

            $table->index('ruta_codruta', 'fk_asignacion_ruta1_idx');
            $table->index('usuario_codusuario', 'fk_asignacion_usuario1_idx');

            $table->foreign('ruta_codruta', 'fk_asignacion_ruta1')
                  ->references('codruta')->on('ruta')
                  ->onUpdate('restrict')->onDelete('restrict');

            $table->foreign('usuario_codusuario', 'fk_asignacion_usuario1')
                  ->references('codusuario')->on('usuario')
                  ->onUpdate('restrict')->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignacion', function (Blueprint $table) {
            $table->dropForeign('fk_asignacion_ruta1');
            $table->dropForeign('fk_asignacion_usuario1');
            $table->dropIndex('fk_asignacion_ruta1_idx');
            $table->dropIndex('fk_asignacion_usuario1_idx');
        });

        Schema::dropIfExists('asignacion');
    }
};
