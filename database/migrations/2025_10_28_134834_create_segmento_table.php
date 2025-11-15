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
        Schema::create('segmento', function (Blueprint $table) {
            $table->unsignedInteger('codsegmento');
            $table->string('nombre', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('cordenadas', 1000)->nullable();
            $table->string('bounds', 1000)->nullable();
            $table->primary('codsegmento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segmento');
    }
};
