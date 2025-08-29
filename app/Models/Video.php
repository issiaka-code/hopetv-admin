<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Video extends Model
{
    use HasFactory;

    protected $fillable = ['id_media', 'nom', 'description', 'couleur', 'duree', 'insert_by', 'update_by', 'is_deleted'];

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

    public function playlistItems()
    {
        return $this->hasMany(PlaylistItem::class, 'id_video');
    }

}
