# coalition-developer

# Coalition Technologies  :: / Test Project /
#### Author: [Ignatius N](https://ignatius-n.com)
#### Demo Project: [Hosted with Dokploy](https://coalition.ignatius-n.com)
A task management application built for the Coalition Technologies Laravel developer assessment.

## Features
- DB seeders for Projects and Tasks to get you started quickly
- Task management (complete CRUD) with drag-and-drop ordering
- Assign and filter tasks to projects
- Move tasks between projects
- Support for tasks unassigned to any projects
- Automated feature and unit tests

## Tech Stack
- PHP 8.4
- Laravel 13
- Livewire 4
- Tailwind CSS
- Alpine.js
- SortableJS
- SQLite/MySQL compatible Eloquent models
- Pest
- PHPStan
- Laravel Pint
- Vite

## Setup / Installation
1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database connection
4. Run `php artisan migrate --seed` to create the database tables and seed them with sample data
5. Run `npm install` to install the frontend dependencies
6. Run `composer run dev` to start:
   1. The Laravel development server by default on [http://127.0.0.1:8000](http://127.0.0.1:8000)
   2. The queue worker
   3. The Vite development server
   4. The log tailer


## Architecture

The application keeps UI concerns, business logic and persistence responsibilities separated. 
I have kept the implementation as lean as possible foregoing Auth scaffolding, 
AI features with boost or things like setting up queues.

I decided to use Livewire for the UI layer because it allows me to build modern dynamic interfaces without writing a 
lot of JavaScript. I've used the default Livewire single-file component structure, which keeps the component's 
view and logic together in one file. This makes it easy to understand and maintain the code.

N/B: As for AI assisted development, I have developed the project on PHPStorm IDE with the only assistance being
default IDE code auto-completion at my disposal.
I have included a few tests to validate a few functionalities in the code. You can run tests with:
```bash
php artisan test
```
