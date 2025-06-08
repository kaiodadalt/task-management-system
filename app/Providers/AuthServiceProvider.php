<?php
declare(strict_types=1);

namespace App\Providers;

use App\Domain\Task\Policies\TaskPolicy;
use App\Domain\Task\Task;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Task::class => TaskPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
