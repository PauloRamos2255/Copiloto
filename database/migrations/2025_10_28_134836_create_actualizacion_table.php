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
        Schema::create('actualizacion', function (Blueprint $table) {
            $table->unsignedInteger('codactualizacion');
            $table->string('incio', 45)->nullable();     
            $table->dateTime('fin')->nullable();
            $table->dateTime('estado')->nullable();   
            $table->unsignedInteger('usuario_codusuario');

            $table->primary('codactualizacion');
            $table->index('usuario_codusuario', 'fk_actualizacion_usuario1_idx');

            $table->foreign('usuario_codusuario', 'fk_actualizacion_usuario1')
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
        Schema::table('actualizacion', function (Blueprint $table) {
            $table->dropForeign('fk_actualizacion_usuario1');
            $table->dropIndex('fk_actualizacion_usuario1_idx');
        });

        Schema::dropIfExists('actualizacion');
    }
};
