<?php

use App\Domain\Task\Events\TaskSaved;
use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Domain\User\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;


beforeEach(function () {
    Event::fake([TaskSaved::class]);
    Queue::fake();
    $this->creator = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->task = Task::factory()->create([
        'created_by' => $this->creator->id,
        'title' => 'Original Title',
        'description' => 'Original Description',
        'status' => TaskStatus::PENDING,
        'priority' => TaskPriority::LOW,
        'due_date' => now()->addDays(5),
    ]);
});

test('task owner can update all fields', function () {
    Sanctum::actingAs($this->creator);

    $updateData = [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
        'due_date' => now()->addDays(10)->toDateTimeString(),
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'task' => [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
                'status' => TaskStatus::IN_PROGRESS->value,
                'priority' => TaskPriority::HIGH->value,
            ]
        ]);

    expect(Task::find($this->task->id))
        ->title->toBe('Updated Title')
        ->description->toBe('Updated Description')
        ->status->toBe(TaskStatus::IN_PROGRESS)
        ->priority->toBe(TaskPriority::HIGH);
});

test('partial update only changes specified fields', function () {
    Sanctum::actingAs($this->creator);

    $updateData = [
        'title' => 'Only Title Updated',
        'priority' => TaskPriority::URGENT->value,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK);

    $updatedTask = Task::find($this->task->id);

    expect($updatedTask)
        ->title->toBe('Only Title Updated')
        ->description->toBe('Original Description') // unchanged
        ->status->toBe(TaskStatus::PENDING) // unchanged
        ->priority->toBe(TaskPriority::URGENT);
});

test('task can be assigned to another user', function () {
    Sanctum::actingAs($this->creator);
    $assignee = User::factory()->create();

    $updateData = [
        'assigned_to' => $assignee->id,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('task.assigned_to', $assignee->id);

    expect(Task::find($this->task->id))
        ->assigned_to->toBe($assignee->id);
});

test('task assignment can be removed', function () {
    $this->task->update(['assigned_to' => $this->otherUser->id]);
    Sanctum::actingAs($this->creator);

    $updateData = [
        'assigned_to' => null,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK);

    expect(Task::find($this->task->id))
        ->assigned_to->toBeNull();
});

test('task description can be cleared', function () {
    Sanctum::actingAs($this->creator);

    $updateData = [
        'description' => null,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK);

    expect(Task::find($this->task->id))
        ->description->toBeNull();
});

test('due date can be cleared', function () {
    Sanctum::actingAs($this->creator);

    $updateData = [
        'due_date' => null,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK);

    expect(Task::find($this->task->id))
        ->due_date->toBeNull();
});


test('validation errors prevent task update', function () {
    Sanctum::actingAs($this->creator);

    // Test empty title
    $response = $this->putJson(route('tasks.update', $this->task), [
        'title' => '',
    ]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['title']);

    // Test invalid status
    $response = $this->putJson(route('tasks.update', $this->task), [
        'status' => 'invalid_status',
    ]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['status']);

    // Test invalid priority
    $response = $this->putJson(route('tasks.update', $this->task), [
        'priority' => 'invalid_priority',
    ]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['priority']);

    // Test non-existent user
    $response = $this->putJson(route('tasks.update', $this->task), [
        'assigned_to' => 9999,
    ]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['assigned_to']);

    // Test past due date
    $response = $this->putJson(route('tasks.update', $this->task), [
        'due_date' => now()->subDays(1)->toDateTimeString(),
    ]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['due_date']);

    // Verify task was not changed
    expect(Task::find($this->task->id))
        ->title->toBe('Original Title')
        ->description->toBe('Original Description')
        ->status->toBe(TaskStatus::PENDING)
        ->priority->toBe(TaskPriority::LOW);
});

test('unauthorized user cannot update task', function () {
    Sanctum::actingAs($this->otherUser);

    $updateData = [
        'title' => 'Unauthorized Update',
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    expect(Task::find($this->task->id))
        ->title->toBe('Original Title');
});

test('unauthenticated user cannot update task', function () {
    $updateData = [
        'title' => 'Unauthenticated Update',
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);

    expect(Task::find($this->task->id))
        ->title->toBe('Original Title');
});

test('task owner can change status', function () {
    Sanctum::actingAs($this->creator);

    $updateData = [
        'status' => TaskStatus::COMPLETED->value,
    ];

    $response = $this->putJson(route('tasks.update', $this->task), $updateData);

    $response->assertStatus(Response::HTTP_OK);

    expect(Task::find($this->task->id))
        ->status->toBe(TaskStatus::COMPLETED);
});

test('task saved event is dispatched when task is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Original Title',
        'description' => 'Original Description',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'due_date' => now()->addDays(5),
    ]);

    $task->update([
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'status' => TaskStatus::IN_PROGRESS->value,
    ]);

    Event::assertDispatched(TaskSaved::class, function (TaskSaved $event) use ($task) {
        return $event->task->id === $task->id &&
            $event->task->title === 'Updated Title' &&
            $event->task->description === 'Updated Description' &&
            $event->task->status === TaskStatus::IN_PROGRESS;
    });
});
