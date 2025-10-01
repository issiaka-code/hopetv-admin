<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emission extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'insert_by',
        'update_by',
        'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    /**
     * Relation avec les items d'émission
     */
    public function items()
    {
        return $this->hasMany(EmissionItem::class, 'id_Emission')->where('is_deleted', false);
    }

    /**
     * Relation avec les items actifs
     */
    public function activeItems()
    {
        return $this->hasMany(EmissionItem::class, 'id_Emission')
                    ->where('is_deleted', false)
                    ->where('is_active', true)
                    ->orderBy('position', 'asc');
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
     * Scope pour exclure les émissions supprimées
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope pour les émissions actives
     */
    // scopeActive supprimé (pas de colonne etat)

    /**
     * Accessor pour le statut
     */
    // getStatusAttribute supprimé (pas de colonne etat)

    /**
     * Accessor pour la classe CSS du statut
     */
    // getStatusClassAttribute supprimé (pas de colonne etat)

    /**
     * Compter le nombre de vidéos dans cette émission
     */
    public function getVideosCountAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Compter le nombre de vidéos actives
     */
    public function getActiveVideosCountAttribute()
    {
        return $this->items()->where('is_active', true)->count();
    }

    /**
     * Calculer la durée totale des vidéos
     */
    public function getTotalDurationAttribute()
    {
        $totalSeconds = 0;
        
        foreach ($this->activeItems as $item) {
            if ($item->duree_video) {
                $timeParts = explode(':', $item->duree_video);
                if (count($timeParts) === 3) {
                    $hours = (int)$timeParts[0];
                    $minutes = (int)$timeParts[1];
                    $seconds = (int)$timeParts[2];
                    $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                }
            }
        }
        
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}