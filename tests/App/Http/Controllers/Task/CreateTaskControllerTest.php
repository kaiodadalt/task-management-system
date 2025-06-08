<?php

use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Domain\User\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Event::fake([TaskCreated::class]);
    Queue::fake();
});

test('authenticated user can create task', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $taskData = [
        'title' => 'Test Task',
        'description' => 'This is a test task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'due_date' => now()->addDays(7)->toDateTimeString(),
    ];

    $response = $this->postJson(route('tasks.create'), $taskData);

    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure([
        'task' => [
            'id',
            'title',
            'description',
            'status',
            'priority',
            'due_date',
            'created_by',
            'created_at',
            'updated_at',
        ]
    ]);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'description' => 'This is a test task',
        'created_by' => $user->id,
    ]);
});

test('unauthenticated user cannot create task', function () {
    $taskData = [
        'title' => 'Unauthorized Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::LOW->value,
    ];

    $response = $this->postJson(route('tasks.create'), $taskData);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized Task']);
});

test('validation errors are returned', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson(route('tasks.create'), [
        'description' => 'Missing required fields',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['title', 'status', 'priority']);

    // Invalid status value
    $response = $this->postJson(route('tasks.create'), [
        'title' => 'Invalid Status Task',
        'status' => 'invalid_status',
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['status']);

    // Invalid priority value
    $response = $this->postJson(route('tasks.create'), [
        'title' => 'Invalid Priority Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => 'invalid_priority',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['priority']);

    // Invalid assignee
    $response = $this->postJson(route('tasks.create'), [
        'title' => 'Invalid Assignee Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'assigned_to' => 999, // Non-existent user ID
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['assigned_to']);
});

test('due date must not be in the past', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $taskData = [
        'title' => 'Past Due Date Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'due_date' => now()->subDays(1)->toDateTimeString(), // Yesterday
    ];

    $response = $this->postJson(route('tasks.create'), $taskData);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['due_date']);
});

test('created_by is automatically set to authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $taskData = [
        'title' => 'Auto Created By Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'created_by' => 999, // Trying to set a different created_by
    ];

    $response = $this->postJson(route('tasks.create'), $taskData);

    $response->assertStatus(Response::HTTP_CREATED);

    $task = Task::where('title', 'Auto Created By Task')->first();
    expect($task)->not->toBeNull()
        ->and($task->created_by)->toBe($user->id)
        ->and($task->created_by)->not->toBe(999);
});

test('task is created with minimal required fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $taskData = [
        'title' => 'Minimal Task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::LOW->value,
    ];

    $response = $this->postJson(route('tasks.create'), $taskData);

    $response->assertStatus(Response::HTTP_CREATED);
    $this->assertDatabaseHas('tasks', [
        'title' => 'Minimal Task',
        'created_by' => $user->id,
        'description' => null,
        'assigned_to' => null,
        'due_date' => null,
    ]);
});

test('task created event is dispatched over broadcast channels when task is created', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $this->actingAs($creator);

    $task = Task::create([
        'title' => 'Test Task',
        'description' => 'Test Description',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'due_date' => now()->addDays(5)->toDateTimeString(),
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($task, $creator, $assignee) {
        if ($event->task->id !== $task->id) {
            return false;
        }

        $channels = $event->broadcastOn();

        // one for creator and one for assignee
        if (count($channels) !== 2) {
            return false;
        }

        $payload = $event->broadcastWith();

        return $payload['id'] === $task->id &&
            $payload['title'] === $task->title &&
            $payload['created_by'] === $creator->id &&
            $payload['assigned_to'] === $assignee->id &&
            $payload['status'] === TaskStatus::PENDING->value &&
            $payload['priority'] === TaskPriority::MEDIUM->value;
    });
});
