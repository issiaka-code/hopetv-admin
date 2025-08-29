<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
    use HasFactory;

    protected $fillable = ['id_media', 'titre', 'description', 'insert_by', 'update_by', 'is_deleted'];

    // Relations
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