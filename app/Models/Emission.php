<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emission extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_media',
        'nom',
        'description',
        'is_published',
        'insert_by',
        'update_by',
        'is_deleted'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relation avec le modèle Media
     */
    public function media()
    {
        return $this->belongsTo(Media::class, 'id_media');
    }

    /**
     * Relation avec le modèle User (créateur)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'insert_by');
    }

    /**
     * Relation avec le modèle User (modificateur)
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    /**
     * Scope pour exclure les émissions supprimées
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope pour les émissions publiées
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope pour les émissions non publiées
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Accessor pour le statut de publication
     */
    public function getStatusAttribute()
    {
        return $this->is_published ? 'Publié' : 'Non publié';
    }

    /**
     * Accessor pour la classe CSS du statut
     */
    public function getStatusClassAttribute()
    {
        return $this->is_published ? 'success' : 'warning';
    }
}