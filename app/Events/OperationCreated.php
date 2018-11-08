<?php

namespace App\Events;

use App\Operation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Событие: новая операция добавлена в БД.
 *
 * @package App\Events
 */
class OperationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var Operation
     */
    public $operation;

    /**
     * Create a new event instance.
     *
     * @param Operation $operation
     */
    public function __construct(Operation $operation)
    {
        $this->operation = $operation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
