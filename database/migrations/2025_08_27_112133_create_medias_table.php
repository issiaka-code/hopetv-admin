<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->text('url_fichier');
            $table->enum('type', ['video', 'audio', 'pdf', 'link']);
            $table->foreignId('insert_by')->constrained('users');
            $table->foreignId('update_by')->constrained('users');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('medias');
    }
};