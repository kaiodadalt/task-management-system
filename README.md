# Task Management System

**Task Management System** is a web application that allows users to create and assign tasks.

## Features

- **Task Management:** Create, view, update, delete, and search tasks.
- **Authentication and Authorization:** through Sanctum and Policies.

## Getting Started

Follow these instructions to set up the backend on your local machine for development and testing purposes.

### Prerequisites

- **PHP:** Version 8.2 or higher.
- **Composer:** Dependency management for PHP.

### Installation

1. **Clone the Repository:**
   ```bash
   git clone git@github.com:kaiodadalt/task-management-system.git
   cd bite-my-greens
   ```
2. **Install Dependencies and execute the project:**

   Install PHP dependencies using Composer:
   ```bash
   composer install
   ```

   Copy the example environment file and generate an application key:
    ```bash
    cp .env.example .env
    php artisan key:generate
   ```
   
    Install Laravel Reverb for broadcasting:
   ```bash
   php artisan install:broadcasting --reverb
   ```

   Start the project:
    ```bash
    php artisan serve
    ```

### Next steps

- Create a Service and Repository to handle Task actions.
- Configure Laravel Octane to boost performance by keeping the application running in memory.
- Configure Laravel Sail to make it easier to create Dockerized environments.
- Create the front-end pages to interact with the Tasks API routes.


### License
Bite My Greens is open-sourced software licensed under the [MIT License](https://opensource.org/license/MIT).

<a href="https://www.buymeacoffee.com/kaiodadalt" target="_blank">
<img data-lazyloaded="1" src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" decoding="async" data-src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me a Coffee" style="height: 35px; text-align:center;" data-ll-status="loaded" class="entered litespeed-loaded">
</a>
