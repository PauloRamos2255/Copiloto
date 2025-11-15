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
        Schema::create('usuario', function (Blueprint $table) {

            $table->unsignedInteger('codusuario');
            $table->unsignedInteger('empresa_codempresa');
            $table->string('nombre', 100)->nullable();
            $table->string('clave', 500)->nullable();
            $table->string('tipo', 1)->nullable();
            $table->dateTime('ultimoIngreso')->nullable();
            $table->string('identificador', 30)->nullable();

            $table->primary('codusuario');
            $table->index('empresa_codempresa', 'fk_usuario_empresa1_idx');

            $table->foreign('empresa_codempresa', 'fk_usuario_empresa1')
                  ->references('codempresa')
                  ->on('empresa')
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
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_empresa1');
            $table->dropIndex('fk_usuario_empresa1_idx');
        });

        Schema::dropIfExists('usuario');
    }
};
