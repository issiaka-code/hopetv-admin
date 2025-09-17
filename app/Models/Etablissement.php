<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', // siege | annexe
        'nom',
        'telephone',
        'email',
        'localisation',
        'image_path',
        'is_active',
        'is_deleted',
        'insert_by',
        'update_by',
    ];
}
