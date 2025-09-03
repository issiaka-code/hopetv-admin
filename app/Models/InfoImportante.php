<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoImportante extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id_media',
        'nom',
        'description',
        'is_active',
        'insert_by',
        'update_by',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class, 'id_media');
    }

    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'insert_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }
}
