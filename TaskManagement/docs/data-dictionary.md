
# Database Data Dictionary

## 1. Overview

The Kasebify Task Management API uses PostgreSQL as its relational database.

The database contains six core business tables:

1. `users`
2. `stores`
3. `store_memberships`
4. `tasks`
5. `task_comments`
6. `task_events`

It also contains supporting tables used by Laravel and its packages for authentication, sessions, and background job processing.

Primary keys use generated BIGINT identifiers unless otherwise specified.

Timestamps that have been converted by the migrations use PostgreSQL `TIMESTAMPTZ`.

---

# 2. Core Business Tables

## 2.1. users

**Purpose:** Stores user account information.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique user identifier |
| `name` | VARCHAR(255) | NOT NULL | User's name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | User's email address |
| `email_verified_at` | TIMESTAMPTZ | Nullable | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Hashed user password |
| `remember_token` | VARCHAR(100) | Nullable | Token used for remember-me authentication |
| `created_at` | TIMESTAMPTZ | Nullable | Account creation timestamp |
| `updated_at` | TIMESTAMPTZ | Nullable | Last account update timestamp |

**Indexes and constraints**

- Primary key on `id`.
- Unique constraint on `email`.

**Relationships**

- One user can have multiple store memberships.
- One user can create multiple tasks.
- One user can be assigned multiple tasks.
- One user can write multiple task comments.
- One user can generate multiple task events.
- One user can own multiple Sanctum personal access tokens.

## 2.2. stores

**Purpose:** Represents stores in which users collaborate and manage tasks.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique store identifier |
| `name` | VARCHAR(255) | NOT NULL | Store name |
| `created_at` | TIMESTAMPTZ | Nullable | Store creation timestamp |
| `updated_at` | TIMESTAMPTZ | Nullable | Last store update timestamp |

**Relationships**

- A store can have multiple memberships.
- A store can contain multiple tasks.
- A store is referenced by comments and events through their `store_id` columns.

## 2.3. store_memberships

**Purpose:** Connects users to stores and defines their roles within each store.

This table implements the many-to-many relationship between users and stores.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique membership identifier |
| `store_id` | BIGINT | NOT NULL, FK → `stores.id` | Store associated with the membership |
| `user_id` | BIGINT | NOT NULL, FK → `users.id` | User associated with the membership |
| `role` | VARCHAR(20) | NOT NULL, CHECK | User's role in the store |
| `created_at` | TIMESTAMPTZ | Nullable | Membership creation timestamp |
| `updated_at` | TIMESTAMPTZ | Nullable | Last membership update timestamp |

**Constraints**

| Constraint | Definition |
|---|---|
| Primary key | `id` |
| Unique | `(store_id, user_id)` |
| Foreign key | `store_id` references `stores.id` |
| Foreign key | `user_id` references `users.id` |
| Check | `role IN ('manager', 'staff')` |

All foreign keys use `ON DELETE RESTRICT`.

**Business rules**

- A user can belong to multiple stores.
- A user can have different roles in different stores.
- A user can have only one membership per store.
- The allowed roles are `manager` and `staff`.

## 2.4. tasks

**Purpose:** Stores tasks belonging to individual stores.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique task identifier |
| `store_id` | BIGINT | NOT NULL, FK → `stores.id` | Store to which the task belongs |
| `title` | VARCHAR(150) | NOT NULL, CHECK | Task title |
| `description` | TEXT | Nullable | Detailed task description |
| `status` | VARCHAR(20) | NOT NULL, DEFAULT `todo`, CHECK | Current task status |
| `priority` | VARCHAR(20) | NOT NULL, DEFAULT `medium`, CHECK | Task priority |
| `created_by` | BIGINT | NOT NULL, FK → `users.id`, composite FK | User who created the task |
| `assigned_to` | BIGINT | Nullable, FK → `users.id`, composite FK | User assigned to the task |
| `due_at` | TIMESTAMPTZ | Nullable | Task deadline |
| `completed_at` | TIMESTAMPTZ | Nullable, CHECK | Time when the task was completed |
| `created_at` | TIMESTAMPTZ | Nullable | Task creation timestamp |
| `updated_at` | TIMESTAMPTZ | Nullable | Last task update timestamp |

**Allowed status values**

| Value | Description |
|---|---|
| `todo` | Task has not been started |
| `in_progress` | Task is being worked on |
| `done` | Task has been completed |
| `cancelled` | Task has been cancelled |

**Allowed priority values**

- `low`
- `medium`
- `high`

**Constraints**

| Constraint | Definition |
|---|---|
| Primary key | `id` |
| Unique | `(store_id, id)` |
| Foreign key | `store_id` references `stores.id` |
| Foreign key | `created_by` references `users.id` |
| Foreign key | `assigned_to` references `users.id` |
| Composite FK | `(store_id, created_by)` references `store_memberships(store_id, user_id)` |
| Composite FK | `(store_id, assigned_to)` references `store_memberships(store_id, user_id)` |
| Check | Status must be one of `todo`, `in_progress`, `done`, `cancelled` |
| Check | Priority must be one of `low`, `medium`, `high` |
| Check | Trimmed title length must be between 3 and 150 characters |
| Check | `done` tasks must have a non-null `completed_at`; all other statuses must have a null `completed_at` |

All foreign keys use `ON DELETE RESTRICT`.

**Indexes**

| Indexed columns | Purpose |
|---|---|
| `(store_id, id)` | Unique constraint; supports composite foreign key references |
| `(store_id, status, created_at, id)` | Supports store task listing, status filtering, and ordering |
| `(store_id, assigned_to, status)` | Supports assignment and status queries |
| `(store_id, due_at)` | Supports deadline-related queries |

**Business rules**

- Every task belongs to exactly one store.
- The creator must be a member of that store.
- The assignee, if present, must also be a member of that store.
- A task can be unassigned.
- Overdue status is calculated by the application; it is not stored as a database column.

## 2.5. task_comments

**Purpose:** Stores comments written by users on tasks.

Comments are designed as append-only records. The table does not contain an `updated_at` column.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique comment identifier |
| `store_id` | BIGINT | NOT NULL, FK → `stores.id` | Store associated with the comment |
| `task_id` | BIGINT | NOT NULL, FK → `tasks.id`, composite FK | Task being commented on |
| `user_id` | BIGINT | NOT NULL, FK → `users.id`, composite FK | Comment author |
| `body` | TEXT | NOT NULL, CHECK | Comment content |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Comment creation timestamp |

**Constraints**

| Constraint | Definition |
|---|---|
| Primary key | `id` |
| Foreign key | `store_id` references `stores.id` |
| Foreign key | `task_id` references `tasks.id` |
| Foreign key | `user_id` references `users.id` |
| Composite FK | `(store_id, task_id)` references `tasks(store_id, id)` |
| Composite FK | `(store_id, user_id)` references `store_memberships(store_id, user_id)` |
| Check | Trimmed body length must be between 1 and 2000 characters |

All foreign keys use `ON DELETE RESTRICT`.

**Index**

`(store_id, task_id, created_at, id)`

Supports retrieving comments for a task in chronological order.

## 2.6. task_events

**Purpose:** Stores activity and audit history for tasks.

Events record actions performed by users. The optional `changes` column stores structured information about modified values using PostgreSQL JSONB.

Events are designed as append-only records. The table does not contain an `updated_at` column.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique event identifier |
| `store_id` | BIGINT | NOT NULL, FK → `stores.id` | Store associated with the event |
| `task_id` | BIGINT | NOT NULL, FK → `tasks.id`, composite FK | Task associated with the event |
| `actor_id` | BIGINT | NOT NULL, FK → `users.id`, composite FK | User who performed the action |
| `event_type` | VARCHAR(50) | NOT NULL | Type of recorded event |
| `changes` | JSONB | Nullable | Structured before-and-after change information |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Event creation timestamp |

**Constraints**

| Constraint | Definition |
|---|---|
| Primary key | `id` |
| Foreign key | `store_id` references `stores.id` |
| Foreign key | `task_id` references `tasks.id` |
| Foreign key | `actor_id` references `users.id` |
| Composite FK | `(store_id, task_id)` references `tasks(store_id, id)` |
| Composite FK | `(store_id, actor_id)` references `store_memberships(store_id, user_id)` |

All foreign keys use `ON DELETE RESTRICT`.

**Index**

`(store_id, task_id, created_at, id)`

Supports retrieving a task's events in chronological order.

**JSONB field**

The `changes` column stores structured JSON data. Its exact structure depends on the event type and the application service that creates the event.

---

# 3. Supporting Laravel and Package Tables

These tables support authentication, sessions, and background processing. They are not part of the core task-management domain.

## 3.1. personal_access_tokens

**Purpose:** Stores personal access tokens used by Laravel Sanctum.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique token record identifier |
| `tokenable_type` | VARCHAR(255) | NOT NULL | Polymorphic model type |
| `tokenable_id` | BIGINT | NOT NULL | Identifier of the token owner |
| `name` | TEXT | NOT NULL | Token name |
| `token` | VARCHAR(64) | NOT NULL, UNIQUE | Hashed token value |
| `abilities` | TEXT | Nullable | Token permissions |
| `last_used_at` | TIMESTAMPTZ | Nullable | Last token usage timestamp |
| `expires_at` | TIMESTAMPTZ | Nullable | Token expiration timestamp |
| `created_at` | TIMESTAMPTZ | Nullable | Record creation timestamp |
| `updated_at` | TIMESTAMPTZ | Nullable | Last record update timestamp |

**Indexes and constraints**

- Primary key on `id`.
- Unique constraint on `token`.
- Index on `(tokenable_type, tokenable_id)`.
- Index on `expires_at`.

**Relationship**

Uses a polymorphic relationship to identify the model that owns a token. It does not use a conventional foreign key to `users`.

## 3.2. password_reset_tokens

**Purpose:** Stores password reset tokens.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `email` | VARCHAR(255) | Primary key | Email associated with the reset request |
| `token` | VARCHAR(255) | NOT NULL | Password reset token |
| `created_at` | TIMESTAMPTZ | Nullable | Token creation timestamp |

**Constraints**

- Primary key on `email`.

This table has no foreign key to `users` in the supplied migration.

## 3.3. sessions

**Purpose:** Stores application session information when database-backed sessions are used.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | VARCHAR(255) | Primary key | Session identifier |
| `user_id` | BIGINT | Nullable, indexed | Associated user identifier |
| `ip_address` | VARCHAR(45) | Nullable | Client IP address |
| `user_agent` | TEXT | Nullable | Client user-agent string |
| `payload` | TEXT | NOT NULL | Serialized session data |
| `last_activity` | INTEGER | NOT NULL, indexed | Session activity timestamp |

**Important:** The `user_id` column has no foreign key in the supplied migration.

## 3.4. jobs

**Purpose:** Stores queued jobs when Laravel's database queue driver is used.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique job identifier |
| `queue` | VARCHAR(255) | NOT NULL, indexed | Queue name |
| `payload` | TEXT | NOT NULL | Serialized job data |
| `attempts` | SMALLINT | NOT NULL | Number of execution attempts |
| `reserved_at` | INTEGER | Nullable | Reservation timestamp |
| `available_at` | INTEGER | NOT NULL | Earliest execution timestamp |
| `created_at` | INTEGER | NOT NULL | Job creation timestamp |

## 3.5. job_batches

**Purpose:** Stores information about batches of queued jobs.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | VARCHAR(255) | Primary key | Batch identifier |
| `name` | VARCHAR(255) | NOT NULL | Batch name |
| `total_jobs` | INTEGER | NOT NULL | Total number of jobs |
| `pending_jobs` | INTEGER | NOT NULL | Number of pending jobs |
| `failed_jobs` | INTEGER | NOT NULL | Number of failed jobs |
| `failed_job_ids` | TEXT | NOT NULL | Failed job identifiers |
| `options` | TEXT | Nullable | Batch options |
| `cancelled_at` | INTEGER | Nullable | Cancellation timestamp |
| `created_at` | INTEGER | NOT NULL | Batch creation timestamp |
| `finished_at` | INTEGER | Nullable | Batch completion timestamp |

## 3.6. failed_jobs

**Purpose:** Stores jobs that failed during execution.

| Column | PostgreSQL type | Constraints | Description |
|---|---|---|---|
| `id` | BIGINT | Primary key, generated | Unique failed job identifier |
| `uuid` | VARCHAR(255) | NOT NULL, UNIQUE | Unique job UUID |
| `connection` | VARCHAR(255) | NOT NULL | Queue connection name |
| `queue` | VARCHAR(255) | NOT NULL | Queue name |
| `payload` | TEXT | NOT NULL | Serialized job data |
| `exception` | TEXT | NOT NULL | Exception information |
| `failed_at` | TIMESTAMPTZ | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Failure timestamp |

**Indexes and constraints**

- Primary key on `id`.
- Unique constraint on `uuid`.
- Composite index on `(connection, queue, failed_at)`.

---

# 4. Database Design Decisions

## 4.1. Composite foreign keys

Composite foreign keys enforce store-level data integrity.

For example, a conventional foreign key from `tasks.created_by` to `users.id` only guarantees that the creator exists.

The additional constraint:

`(store_id, created_by) → store_memberships(store_id, user_id)`

also guarantees that the creator belongs to the task's store.

The same principle applies to assignees, comment authors, and event actors.

## 4.2. Historical data protection

Foreign keys use `ON DELETE RESTRICT` to prevent deleting referenced records.

This protects task comments and event history from being removed through cascading deletes.

## 4.3. Timestamp handling

The application uses UTC timestamps.

PostgreSQL `TIMESTAMPTZ` stores timestamps as instants and displays them according to the database session time zone.

The application is responsible for returning timestamps in the required API format, such as ISO 8601.

## 4.4. Application-level and database-level validation

Some rules are enforced at both the application and database levels, while others are enforced only by the application.

| Rule | Application validation | Database constraint |
|---|---|---|
| Task title length: 3–150 | Yes | Yes |
| Comment body length: 1–2000 | Yes | Yes |
| Valid task statuses | Yes | Yes |
| Valid task priorities | Yes | Yes |
| Valid membership roles | Yes | Yes |
| Creator belongs to task store | Application authorization/business logic | Yes, composite FK |
| Assignee belongs to task store | Yes | Yes, composite FK |
| Comment author belongs to store | Application authorization | Yes, composite FK |
| Event actor belongs to store | Application logic | Yes, composite FK |
| Due date must be in the future when supplied | Yes | No constraint shown |
| Valid task status transitions | Yes | No transition constraint shown |

## 4.5. Indexing strategy

The main task-related indexes support common API queries.

| Table | Indexed columns | Purpose |
|---|---|---|
| `store_memberships` | `(store_id, user_id)` | Unique membership lookup |
| `tasks` | `(store_id, id)` | Unique task identity within a store |
| `tasks` | `(store_id, status, created_at, id)` | Store task listing and status filtering |
| `tasks` | `(store_id, assigned_to, status)` | Assignment and status filtering |
| `tasks` | `(store_id, due_at)` | Deadline queries |
| `task_comments` | `(store_id, task_id, created_at, id)` | Comment history |
| `task_events` | `(store_id, task_id, created_at, id)` | Event history |

PostgreSQL automatically creates indexes for primary keys and unique constraints.

Foreign key declarations do not automatically create indexes on referencing columns.

---

# 5. Notes on Schema Maintenance

This document describes the schema produced by the migrations supplied for the project.

If the database schema changes, this data dictionary and the ER diagram should be updated accordingly.

Supporting tables may vary depending on the Laravel configuration and packages enabled in the application.
