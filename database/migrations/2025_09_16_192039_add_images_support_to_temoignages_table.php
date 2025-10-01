<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'enum de la colonne type dans la table medias pour ajouter 'image'
        DB::statement("ALTER TABLE medias MODIFY COLUMN type ENUM('audio', 'video', 'link', 'pdf', 'images') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {        
        // Remettre l'enum original
        DB::statement("ALTER TABLE medias MODIFY COLUMN type ENUM('audio', 'video', 'link', 'pdf') NOT NULL");
    }
};
