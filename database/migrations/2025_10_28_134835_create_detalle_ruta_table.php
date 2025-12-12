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
        Schema::create('detalleRuta', function (Blueprint $table) {
            $table->unsignedInteger('iddetalleRuta');
            $table->unsignedInteger('ruta_codruta');
            $table->unsignedInteger('segmento_codsegmento');
            $table->integer('velocidadPermitida')->nullable();
            $table->string('mensaje', 500)->nullable();

            $table->primary('iddetalleRuta');

            $table->index('ruta_codruta', 'fk_detalleRuta_ruta_idx');
            $table->index('segmento_codsegmento', 'fk_detalleRuta_segmento1_idx');

            $table->foreign('ruta_codruta', 'fk_detalleRuta_ruta')
                  ->references('codruta')
                  ->on('ruta')
                  ->onUpdate('restrict')
                  ->onDelete('restrict');

            $table->foreign('segmento_codsegmento', 'fk_detalleRuta_segmento1')
                  ->references('codsegmento')
                  ->on('segmento')
                  ->onUpdate('restrict')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

 Schema::table('detalleRuta', function (Blueprint $table) {
            $table->dropForeign('fk_detalleRuta_ruta');
            $table->dropForeign('fk_detalleRuta_segmento1');
            $table->dropIndex('fk_detalleRuta_ruta_idx');
            $table->dropIndex('fk_detalleRuta_segmento1_idx');
        });

        Schema::dropIfExists('detalleRuta');
    }
};
