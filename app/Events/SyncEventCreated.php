<?php

namespace App\Events;

use App\Models\SyncEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SyncEvent $syncEvent
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->syncEvent->user_id . '.sync'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sync.event';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->syncEvent->event_id,
            'entity_type' => $this->syncEvent->entity_type,
            'entity_gid' => $this->syncEvent->entity_gid,
            'action' => $this->syncEvent->action,
            'payload' => $this->syncEvent->payload,
            'version' => $this->syncEvent->version,
            'device_id' => $this->syncEvent->device_id,
            'timestamp' => $this->syncEvent->event_timestamp->toIso8601String(),
        ];
    }
}
