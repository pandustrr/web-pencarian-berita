<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('original_text');
            $table->text('translated_text')->nullable();
            $table->text('processed_text');
            $table->string('category')->nullable();
            $table->string('source')->default('Kaggle');
            $table->timestamps();

            $table->fullText(['processed_text']);
            $table->index(['category']);
            $table->index(['source']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('news');
    }
};
