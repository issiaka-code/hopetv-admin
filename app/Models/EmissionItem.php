<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_Emission',
        'titre_video',
        'description_video',
        'type_video', // 'video' (upload) ou 'link'
        'video_url', // Lien externe ou nom de fichier uploadé
        'thumbnail',
        'is_active',
        'insert_by',
        'update_by',
        'is_deleted'
    ];

    // Constantes pour les types de vidéo
    const TYPE_UPLOAD = 'video';
    const TYPE_LINK = 'link';

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relation avec l'émission
     */
    public function emission()
    {
        return $this->belongsTo(Emission::class, 'id_Emission');
    }

    /**
     * Obtenir l'URL complète du fichier vidéo uploadé
     */
    public function getVideoFileUrlAttribute()
    {
        if ($this->type_video === self::TYPE_UPLOAD && $this->video_url) {
            return asset('storage/emissions/videos/' . $this->video_url);
        }
        return null;
    }

    /**
     * Obtenir l'URL complète de la miniature
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            // Si c'est une URL distante (oEmbed, YouTube, Vimeo), la retourner telle quelle
            if (stripos($this->thumbnail, 'http://') === 0 || stripos($this->thumbnail, 'https://') === 0) {
                return $this->thumbnail;
            }
            // Sinon, considérer comme un fichier local stocké
            return asset('storage/emissions/thumbnails/' . $this->thumbnail);
        }
        return null;
    }

    /**
     * Vérifier si c'est une vidéo uploadée
     */
    public function isUploadedVideo()
    {
        return $this->type_video === self::TYPE_UPLOAD;
    }

    /**
     * Vérifier si c'est une vidéo lien
     */
    public function isLinkVideo()
    {
        return $this->type_video === self::TYPE_LINK;
    }

    /**
     * Relation avec le modèle User (créateur)
     */
    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'insert_by');
    }

    /**
     * Relation avec le modèle User (modificateur)
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    /**
     * Scope pour exclure les items supprimés
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope pour les items actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Plus de position: colonne non utilisée dans le schéma actuel
}