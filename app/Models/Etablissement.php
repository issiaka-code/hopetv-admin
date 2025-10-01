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
        'adresse',
        'image_path',
        'is_active',
        'is_deleted',
        'insert_by',
        'update_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Accessor pour l'URL de l'image
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * Obtenir l'URL Google Maps pour cet établissement
     */
    public function getGoogleMapsUrlAttribute()
    {
        if ($this->adresse) {
            return "https://www.google.com/maps/search/" . urlencode($this->adresse);
        }
        
        return null;
    }
}
