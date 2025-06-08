<?php

namespace Database\Seeders;

use App\Domain\Task\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        Task::factory()->count(50)->create();
    }
}
