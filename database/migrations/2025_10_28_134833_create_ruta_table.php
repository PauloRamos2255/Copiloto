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
        Schema::create('ruta', function (Blueprint $table) {
             $table->unsignedInteger('codruta')->autoIncrement(); 
            $table->string('nombre', 100)->nullable();
            $table->integer('limiteGeneral')->nullable();
            $table->string('fechaCreacion', 45)->nullable();
            $table->string('icono', 1000)->nullable();
            $table->string('tipo', 1)->nullable();
            $table->primary('codruta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruta');
    }
};
