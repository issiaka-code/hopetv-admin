<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory; 

    // Table name
    protected $table = 'medias';

    protected $fillable = ['nom', 'url_fichier', 'thumbnail', 'type', 'insert_by', 'update_by', 'is_deleted', 'is_published'];

    // Relations
    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'insert_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    public function video()
    {
        return $this->hasOne(Video::class, 'id_media');
    }

    public function podcast()
    {
        return $this->hasOne(Podcast::class, 'id_media');
    }

    public function temoignage()
    {
        return $this->hasOne(Temoignage::class, 'id_media');
    }

}