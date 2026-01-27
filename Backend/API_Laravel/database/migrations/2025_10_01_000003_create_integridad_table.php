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
        Schema::create('integridad', function (Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('nombre', 32)->comment('ejemplo, nuevo, de segunda pero bueno, en mal estado (digamos que vende un PC malo para sacarle componentes)');
            $blueprint->string('descripcion', 128);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integridad');
    }
};
