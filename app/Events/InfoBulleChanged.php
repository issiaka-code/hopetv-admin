<?php

namespace App\Events;

use App\Models\InfoBulle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class InfoBulleChanged implements ShouldBroadcast
{
    use SerializesModels;

    public $action; // create, update, delete, toggle
    public $infoBulleId;
    public $infoBulle; // Données complètes de l'info-bulle

    public function __construct(string $action, int $infoBulleId)
    {
        $this->action = $action;
        $this->infoBulleId = $infoBulleId;
        
        // Charger les données complètes de l'info-bulle
        $this->infoBulle = InfoBulle::where('id', $infoBulleId)
            ->where('is_deleted', false)
            ->first();
    }

    public function broadcastOn()
    {
        return new Channel('info-bulles');
    }

    public function broadcastAs()
    {
        return 'info-bulle.changed';
    }
}
