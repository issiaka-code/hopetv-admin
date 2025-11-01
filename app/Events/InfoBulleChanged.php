<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class InfoBulleChanged implements ShouldBroadcast
{
    use SerializesModels;

    public $action; // create, update, delete
    public $infoBulleId;

    public function __construct(string $action, int $infoBulleId)
    {
        $this->action = $action;
        $this->infoBulleId = $infoBulleId;
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
