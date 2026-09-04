# Task Manager API

A REST API for managing tasks, subtasks, categories, and comments. Built with MVC architecture and JWT authentication.

## Features

- User registration and login
- JWT authentication with token blacklisting on logout
- Administrator and regular-user authorization
- User management
- Task CRUD operations with search and filtering
- Task deadlines and status management
- Task assignment to users
- Subtasks with completion tracking and duplicate prevention
- Task categories with duplicate assignment prevention
- Task comments with edit and delete functionality
- Pagination support (for users and tasks)

### Task Statuses

Tasks can have one of three statuses:
- `SUBMITTED`
- `ONGOING`
- `FINISHED`

A task cannot be marked as `FINISHED` if it has incomplete subtasks.

### Task Priorities

Priority must be an integer between 1 and 20 (inclusive).

### Date Format

Deadlines use ISO 8601 format: `YYYY-MM-DDTHH:mm:ss`

Example: `2026-12-21T15:45:55`

## Authentication & Authorization

The API uses JWT for authentication.

Protected endpoints require the `Authorization` header:
```
Authorization: Bearer <JWT_TOKEN>
```

The API distinguishes between administrators and regular users:
- **Administrators** can perform all operations
- **Regular users** can access resources they are authorized to access (e.g., tasks assigned to them)


### Token Blacklisting on Logout

The logout endpoint invalidates tokens by adding them to a blacklist stored in the database. Blacklisted tokens cannot be used for future requests.

**Note:** JWT authentication is stateless by design, making logout counterintuitive. The blacklist approach introduces statefulness to work around this.

To prevent the blacklist table from growing indefinitely, expired tokens should be pruned periodically. My recommended approach is to set up a cron job or systemd timer that runs a SQL query to delete blacklisted tokens older than the token expiration time. This cleanup mechanism has not been implemented.

## Architecture

The application follows the **Model-View-Controller (MVC)** pattern with an additional **Service** layer:

- **Models** handle application data and database interactions
- **Controllers** receive HTTP requests and coordinate operations
- **Views** produce API responses (JSON) rather than HTML
- **Services** contain application logic and sit between Controllers and Models

The Service layer organizes code and works within the MVC structure rather than replacing it.

## Request Execution Flow

```
HTTP Request
    ↓
api/index.php
    ↓
init.php
    ↓
Router
    ↓
Controller
    ↓
Service
    ↓
Model
    ↓
PostgreSQL (Could be any DBMS)
    ↓
Model
    ↓
Service
    ↓
Controller
    ↓
Router
    ↓
api/index.php
    ↓
HTTP Response
```

**Flow Details:**
1. Requests enter through `api/index.php`
2. `index.php` calls `init.php`
3. `init.php` creates and returns a Router instance
4. `index.php` passes the request to the Router
5. The Router finds the appropriate Controller
6. The Controller calls the corresponding Service method
7. The Service interacts with the appropriate Model
8. The result travels back through the same layers
9. `index.php` returns the response to the client

## Database

The project uses **PostgreSQL**.

### Main Entities

- **Users** — Application users with role-based access
- **Tasks** — Core task objects with priorities, deadlines, and status
- **Subtasks** — Subtasks linked to tasks with completion tracking
- **Categories** — Task categories for organization
- **Comments** — Comments on tasks with edit and delete support

Relationships:
- Users can be assigned to tasks
- Tasks can have multiple subtasks, categories, and comments

## Requirements

- **PHP 8.4** or higher
- **Composer** for dependency management
- **PostgreSQL** database
- **Docker & Docker Compose**

### PHP Dependencies

The project uses the following PHP dependency:
- `firebase/php-jwt` (^6.10) — For JWT token generation and validation

Install dependencies using Composer (no need for this step. Happens in container):
```bash
composer install
```

## API Documentation

For detailed endpoint documentation, parameters, request bodies, authentication requirements, and response formats, see the Postman documentation:

[see postman API documentation](https://documenter.getpostman.com/view/55882195/2sBYAvwBHB)

## Quick Start

1. Clone the repository:
   ```bash
   git clone https://github.com/GH0STYtopflo/TaskMgr
   ```

2. Enter the repository directory:
   ```bash
   cd TaskMgr
   ```

3. Start the application:
   ```bash
   docker-compose up --build
   ```

The API will be available at `http://localhost:8088`
