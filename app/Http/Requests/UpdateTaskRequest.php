<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', new Enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', new Enum(TaskPriority::class)],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
        ];
    }
}
