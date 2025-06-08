<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
