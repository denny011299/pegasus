<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ping ringan ke channel gudang — client soft-reload PP/Kanban/WO (bukan poll).
 */
class ProductionFloorChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $warehouseId,
        public string $reason = 'changed'
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('pp.warehouse.'.$this->warehouseId);
    }

    public function broadcastAs(): string
    {
        return 'floor.changed';
    }

    /**
     * @return array{warehouse_id: int, reason: string}
     */
    public function broadcastWith(): array
    {
        return [
            'warehouse_id' => $this->warehouseId,
            'reason' => $this->reason,
        ];
    }
}
