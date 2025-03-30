<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraEventUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $event;

    public function __construct(Notification $event)
    {
        $this->event = $event;
    }

    public function broadcastOn()
    {
        return new Channel('events');
    }

    public function broadcastAs()
    {
        return 'create';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => $this->event
        ];
    }
}