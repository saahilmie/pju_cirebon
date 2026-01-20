<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PjuDataUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public string $idpel;
    public string $updatedBy;
    public ?int $dataId;
    public string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, string $idpel, string $updatedBy, ?int $dataId = null)
    {
        $this->action = $action;
        $this->idpel = $idpel;
        $this->updatedBy = $updatedBy;
        $this->dataId = $dataId;
        $this->message = $this->generateMessage();
    }

    /**
     * Generate notification message
     */
    private function generateMessage(): string
    {
        return match ($this->action) {
            'created' => "New PJU data added for IDPEL {$this->idpel} by {$this->updatedBy}",
            'updated' => "PJU data updated for IDPEL {$this->idpel} by {$this->updatedBy}",
            'deleted' => "PJU data deleted for IDPEL {$this->idpel} by {$this->updatedBy}",
            'photo_uploaded' => "Photo uploaded for IDPEL {$this->idpel} by {$this->updatedBy}",
            default => "PJU data changed for IDPEL {$this->idpel}",
        };
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pju-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pju.updated';
    }
}
