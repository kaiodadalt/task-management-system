
<?php

use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Domain\User\User;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->anotherUser = User::factory()->create();
});

test('user can see only their own tasks', function () {
    $userTasks = Task::factory()->count(3)->create([
        'created_by' => $this->user->id,
    ]);


    Task::factory()->count(2)->create([
        'created_by' => $this->anotherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(3, 'data');

    $returnedTaskIds = collect($response->json('data'))->pluck('id')->toArray();
    $expectedTaskIds = $userTasks->pluck('id')->toArray();

    expect($returnedTaskIds)->toEqual($expectedTaskIds);
});

test('user can see tasks assigned to them', function () {
    $assignedTasks = Task::factory()->count(2)->create([
        'created_by' => $this->anotherUser->id,
        'assigned_to' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(2, 'data');

    $returnedTaskIds = collect($response->json('data'))->pluck('id')->toArray();
    $expectedTaskIds = $assignedTasks->pluck('id')->toArray();

    expect($returnedTaskIds)->toEqual($expectedTaskIds);
});

test('user can see both owned and assigned tasks', function () {
    $ownedTasks = Task::factory()->count(2)->create([
        'created_by' => $this->user->id,
    ]);

    $assignedTasks = Task::factory()->count(3)->create([
        'created_by' => $this->anotherUser->id,
        'assigned_to' => $this->user->id,
    ]);

    Task::factory()->count(2)->create([
        'created_by' => $this->anotherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(5, 'data');

    $returnedTaskIds = collect($response->json('data'))->pluck('id')->sort()->values()->toArray();
    $expectedTaskIds = $ownedTasks->concat($assignedTasks)->pluck('id')->sort()->values()->toArray();

    expect($returnedTaskIds)->toEqual($expectedTaskIds);
});

test('pagination metadata is correct', function () {
    Task::factory()->count(25)->create([
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 3,
                'per_page' => 10,
                'to' => 10,
                'total' => 25,
            ]
        ]);
});

test('pagination links are correct', function () {
    Task::factory()->count(25)->create([
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ]
        ]);

    $links = $response->json('links');
    expect($links['next'])->not->toBeNull()
        ->and($links['prev'])->toBeNull();
});

test('can navigate to second page', function () {
    Task::factory()->count(25)->create([
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', ['page' => 2]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'meta' => [
                'current_page' => 2,
                'from' => 11,
                'to' => 20,
            ]
        ]);
});

test('can change items per page', function () {
    Task::factory()->count(25)->create([
        'created_by' => $this->user->id,
    ]);

    $perPage = 5;
    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', ['per_page' => $perPage]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount($perPage, 'data')
        ->assertJson([
            'meta' => [
                'per_page' => $perPage,
                'last_page' => 5,  // 25 items ÷ 5 per page = 5 pages
            ]
        ]);
});

test('can filter by status', function () {
    Task::factory()->count(3)->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING,
    ]);

    Task::factory()->count(2)->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', ['status' => TaskStatus::PENDING->value]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(3, 'data');

    foreach ($response->json('data') as $task) {
        expect($task['status'])->toBe(TaskStatus::PENDING->value);
    }
});

test('can filter by priority', function () {
    Task::factory()->count(2)->create([
        'created_by' => $this->user->id,
        'priority' => TaskPriority::LOW,
    ]);

    Task::factory()->count(3)->create([
        'created_by' => $this->user->id,
        'priority' => TaskPriority::HIGH,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', ['priority' => TaskPriority::HIGH->value]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(3, 'data');

    foreach ($response->json('data') as $task) {
        expect($task['priority'])->toBe(TaskPriority::HIGH->value);
    }
});

test('can filter by date range', function () {
    Task::factory()->create([
        'created_by' => $this->user->id,
        'created_at' => now()->subDays(5),
    ]);

    Task::factory()->create([
        'created_by' => $this->user->id,
        'created_at' => now()->addDays(3),
    ]);

    Task::factory()->create([
        'created_by' => $this->user->id,
        'created_at' => now()->addDays(10),
    ]);

    $startDate = now()->addDays(1)->toDateString();
    $endDate = now()->addDays(5)->toDateString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(1, 'data');

    $dueDate = Carbon::parse($response->json('data.0.created_at'));
    expect($dueDate->isAfter($startDate) || $dueDate->isSameDay($startDate))->toBeTrue()
        ->and($dueDate->isBefore($endDate) || $dueDate->isSameDay($endDate))->toBeTrue();
});

test('guest cannot access search', function () {
    $response = $this->getJson(route('tasks.search'));

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

test('returns empty result when no matching tasks', function () {
    Task::factory()->count(3)->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', ['status' => TaskStatus::COMPLETED->value]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(0, 'data')
        ->assertJson([
            'meta' => [
                'total' => 0,
            ],
        ]);
});

test('can combine multiple filters', function () {
    Task::factory()->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING,
        'priority' => TaskPriority::HIGH,
        'due_date' => now()->addDays(3),
    ]);

    Task::factory()->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING,
        'priority' => TaskPriority::LOW,
        'due_date' => now()->addDays(4),
    ]);

    Task::factory()->create([
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS,
        'priority' => TaskPriority::HIGH,
        'due_date' => now()->addDays(2),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('tasks.search', [
            'status' => TaskStatus::PENDING->value,
            'priority' => TaskPriority::HIGH->value,
        ]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(1, 'data');

    $task = $response->json('data.0');
    expect($task['status'])->toBe(TaskStatus::PENDING->value)
        ->and($task['priority'])->toBe(TaskPriority::HIGH->value);
});
