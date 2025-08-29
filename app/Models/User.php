<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function mediasInserted()
    {
        return $this->hasMany(Media::class, 'insert_by');
    }

    public function mediasUpdated()
    {
        return $this->hasMany(Media::class, 'update_by');
    }

    public function videosInserted()
    {
        return $this->hasMany(Video::class, 'insert_by');
    }

    public function videosUpdated()
    {
        return $this->hasMany(Video::class, 'update_by');
    }

    public function podcastsInserted()
    {
        return $this->hasMany(Podcast::class, 'insert_by');
    }

    public function podcastsUpdated()
    {
        return $this->hasMany(Podcast::class, 'update_by');
    }

    public function temoignagesInserted()
    {
        return $this->hasMany(Temoignage::class, 'insert_by');
    }

    public function temoignagesUpdated()
    {
        return $this->hasMany(Temoignage::class, 'update_by');
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class, 'id_user');
    }

    public function playlistItemsInserted()
    {
        return $this->hasMany(PlaylistItem::class, 'insert_by');
    }

    public function playlistItemsUpdated()
    {
        return $this->hasMany(PlaylistItem::class, 'update_by');
    }

    public function infoBullesInserted()
    {
        return $this->hasMany(InfoBulle::class, 'insert_by');
    }

    public function infoBullesUpdated()
    {
        return $this->hasMany(InfoBulle::class, 'update_by');
    }

    public function parametresInserted()
    {
        return $this->hasMany(Parametre::class, 'insert_by');
    }

    public function parametresUpdated()
    {
        return $this->hasMany(Parametre::class, 'update_by');
    }

    public function liensUtilesInserted()
    {
        return $this->hasMany(LienUtile::class, 'insert_by');
    }

    public function liensUtilesUpdated()
    {
        return $this->hasMany(LienUtile::class, 'update_by');
    }

    // App/Models/Video.php
    public function scopeSearch($query, $term)
    {
        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }
    }
}
