<?php

namespace App\Domain\Task;

use App\Domain\Task\Observers\TaskObserver;
use App\Domain\User\User;
use Carbon\CarbonImmutable;
use Database\Factories\Domain\Task\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $created_by
 * @property int $assigned_to
 * @property int $id
 * @property string $title
 * @property string $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonImmutable $due_date
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @method static findOrFail(int $id)
 * @method static find(mixed $id)
 */
#[ObservedBy([TaskObserver::class])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    #[Scope]
    public function status(Builder $query, ?TaskStatus $status = null): Builder
    {
        return $status
            ? $query->where('status', $status?->value)
            : $query;
    }

    #[Scope]
    public function forUser(Builder $query, int $user_id): Builder
    {
        return $query->where(function ($query) use ($user_id) {
            $query->where('created_by', $user_id)
                ->orWhere('assigned_to', $user_id);
        });
    }


    #[Scope]
    public function priority(Builder $query, ?TaskPriority $priority = null): Builder
    {
        return $priority
            ? $query->where('priority', $priority?->value)
            : $query;
    }

    #[Scope]
    public function createdAtBetween(Builder $query, ?string $start = null, ?string $end = null): Builder
    {
        if ($start && $end) {
            return $query->whereBetween('created_at', [$start, $end]);
        }

        if ($start) {
            return $query->where('created_at', '>=', $start);
        }

        if ($end) {
            return $query->where('created_at', '<=', $end);
        }

        return $query;
    }
}
