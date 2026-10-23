# Laravel Task Management App

A simple task management web application built with **Laravel** and **PostgreSQL** as a hands-on project for learning Laravel's core concepts and database integration.

The application allows users to view tasks, create new tasks, view individual task details, and change task completion status.

## Features

- View all tasks
- View individual task details
- Create new tasks
- Mark tasks as completed
- Mark completed tasks as pending
- Form validation
- Database persistence using PostgreSQL
- Server-side rendering with Blade
- Responsive UI using Tailwind CSS

## Technologies

- **PHP**
- **Laravel**
- **PostgreSQL**
- **Eloquent ORM**
- **Blade**
- **Tailwind CSS**
- **Git / GitHub**

## Project Structure

The project follows Laravel's standard structure. Some of the main components used in this project are:

```text
app/
├── Models/
│   └── Task.php

resources/
└── views/
    └── tasks/
        ├── index.blade.php
        ├── show.blade.php
        └── create.blade.php

routes/
└── web.php

database/
└── migrations/
    └── create_tasks_table.php
```

## Database

The application uses PostgreSQL as its database.

The main `tasks` table contains:

| Column | Type | Description |
|---|---|---|
| `id` | Big Integer | Primary key |
| `title` | String | Task title |
| `description` | Text | Short task description |
| `long_description` | Text | Optional detailed description |
| `completed` | Boolean | Task completion status |
| `created_at` | Timestamp | Creation time |
| `updated_at` | Timestamp | Last update time |

The `completed` column defaults to `false`, so newly created tasks are initially marked as pending.

## Main Laravel Concepts Practiced

### Routing

The project uses Laravel routes to handle different operations:

```text
GET     /tasks
GET     /tasks/create
POST    /tasks
GET     /tasks/{task}
PATCH   /tasks/{task}/toggle
```

Route constraints are also used to ensure that task IDs are numeric and do not conflict with routes such as `/tasks/create`.

### Eloquent ORM

The `Task` model is used to communicate with the database.

For example:

```php
$tasks = Task::all();
```

retrieves all tasks from the database.

New tasks are created using:

```php
Task::create($validated);
```

### Implicit Route Model Binding

The project uses Laravel's implicit model binding:

```php
Route::get('/tasks/{task}', function (Task $task) {
    return view('tasks.show', ['task' => $task]);
});
```

Laravel automatically resolves the task ID from the URL and retrieves the corresponding `Task` model.

### Blade Templates

Blade is used to generate the application's HTML pages.

Examples of Blade features used include:

```blade
@foreach
@if
@else
@forelse
{{ $task->title }}
{{ route(...) }}
```

### Form Validation

Task creation uses Laravel's built-in validation system:

```php
$validated = request()->validate([
    'title' => 'required|max:255',
    'description' => 'required',
    'long_description' => 'nullable',
]);
```

Only validated data is passed to the Eloquent model.

### Mass Assignment

The `Task` model defines its allowed mass-assignable attributes using `$fillable`:

```php
protected $fillable = [
    'title',
    'description',
    'long_description',
    'completed',
];
```

### Database Migrations

The database schema is managed using Laravel migrations.

The task table is created with fields such as:

```php
$table->id();
$table->string('title');
$table->text('description');
$table->text('long_description')->nullable();
$table->boolean('completed')->default(false);
$table->timestamps();
```

### CSRF Protection

Forms that modify application data use Laravel's CSRF protection:

```blade
@csrf
```

## Application Flow

The basic task creation flow is:

```text
User
 │
 ▼
GET /tasks/create
 │
 ▼
Create Task Form
 │
 ▼
POST /tasks
 │
 ▼
Validation
 │
 ▼
Eloquent Model
 │
 ▼
PostgreSQL
 │
 ▼
Redirect to /tasks
```

The task status flow works similarly:

```text
User clicks "Mark as Completed"
 │
 ▼
PATCH /tasks/{task}/toggle
 │
 ▼
Retrieve Task using Model Binding
 │
 ▼
Toggle completed value
 │
 ▼
Save with Eloquent
 │
 ▼
PostgreSQL
 │
 ▼
Redirect to Task List
```

## Installation

### 1. Clone the repository

```bash
git clone <your-repository-url>
cd <project-directory>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

```bash
cp .env.example .env
```

On Windows, you can also copy `.env.example` manually and rename it to `.env`.

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure PostgreSQL

Update the database section of `.env` with your PostgreSQL configuration:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Run the migrations

```bash
php artisan migrate
```

### 7. Start the development server

```bash
php artisan serve
```

The application will normally be available at:

```text
http://127.0.0.1:8000
```

## Learning Goals

This project was created as a practical introduction to Laravel. The main goals were to understand:

- Laravel project structure
- Routing
- Route parameters
- Implicit route model binding
- Blade templating
- HTML forms
- CSRF protection
- Request handling
- Form validation
- Eloquent models and ORM
- Mass assignment
- Database migrations
- PostgreSQL integration
- CRUD fundamentals
- HTTP methods such as GET, POST, and PATCH

## Future Improvements

Possible improvements for the project include:

- Edit task functionality
- Delete tasks
- User authentication
- Authorization
- Task filtering and searching
- Task categories
- Due dates
- Pagination
- Flash success/error messages
- Controllers instead of route closures
- Automated tests
- RESTful API endpoints

## Project Status

This is a **learning project** developed to gain practical experience with Laravel, PHP, and PostgreSQL.

The application currently implements the core task management functionality and serves as a foundation for exploring more advanced Laravel concepts.