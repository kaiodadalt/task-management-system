<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Task $task) {}

    public function broadcastOn(): array
    {
        $channels[] = new PrivateChannel("App.Domain.Task.{$this->task->created_by}");
        if ($this->task->assigned_to != $this->task->created_by) {
            $channels[] = new PrivateChannel("App.Domain.Task.{$this->task->assigned_to}");
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'created_by' => $this->task->created_by,
            'assigned_to' => $this->task->assigned_to,
            'title' => $this->task->title,
            'description' => $this->task->description,
            'status' => $this->task->status->value,
            'priority' => $this->task->priority->value,
            'due_date' => $this->task->due_date,
            'created_at' => $this->task->created_at,
            'updated_at' => $this->task->updated_at,
        ];
    }
}
