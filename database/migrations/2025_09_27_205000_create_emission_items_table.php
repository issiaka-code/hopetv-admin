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
        Schema::create('emission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_Emission')->constrained('emissions');
            $table->string('titre_video');
            $table->text('description_video')->nullable();
            $table->enum('type_video', ['video', 'link']);
            $table->string('video_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('insert_by')->constrained('users');
            $table->foreignId('update_by')->constrained('users');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_items');
    }
};
