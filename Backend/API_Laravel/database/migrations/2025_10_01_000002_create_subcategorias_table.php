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
        Schema::create('subcategorias', function (Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('nombre', 32)->comment('por ejemplo, para la categoria ropa tenemos: calzado, pantalon, camisa, sombrero, etc');
            $blueprint->unsignedInteger('categoria_id')->comment('a que categoria pertenece la subcategoria');
            
            $blueprint->foreign('categoria_id', 'subcategoria_categoria')
                      ->references('id')->on('categorias')
                      ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
