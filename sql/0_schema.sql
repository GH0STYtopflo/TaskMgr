CREATE TYPE STATUS AS ENUM (
    'SUBMITTED',
    'ONGOING',
    'FINISHED'
);


CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS tasks (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    title VARCHAR(255) NOT NULL,
    "desc" VARCHAR,
    priority INT CHECK ( priority BETWEEN 1 AND 20),
    deadline TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ,
    status STATUS NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    title VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS comments (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    body VARCHAR NOT NULL,
    submission_time TIMESTAMPTZ NOT NULL DEFAULT now(),
    user_id BIGINT REFERENCES users(id) NOT NULL,
    task_id BIGINT REFERENCES tasks(id) ON DELETE CASCADE NOT NULL
);

CREATE TABLE IF NOT EXISTS sub_tasks (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    title VARCHAR(255) NOT NULL UNIQUE,
    is_done BOOLEAN NOT NULL DEFAULT FALSE,
    task_id BIGINT REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_tasks (
    user_id BIGINT REFERENCES users(id),
    task_id BIGINT REFERENCES tasks(id) ON DELETE CASCADE,

    CONSTRAINT pk_user_tasks PRIMARY KEY (task_id, user_id)
);

CREATE TABLE IF NOT EXISTS task_categories (
    task_id BIGINT REFERENCES tasks(id) ON DELETE CASCADE,
    category_id BIGINT REFERENCES categories(id),

    CONSTRAINT pk_task_categories PRIMARY KEY (task_id, category_id)
);

CREATE TABLE IF NOT EXISTS token_black_list (
    token VARCHAR NOT NULL UNIQUE
);

CREATE INDEX idx_tasks_priority ON tasks(priority);
CREATE INDEX idx_tasks_title ON tasks(title);
CREATE INDEX idx_tasks_deadline ON tasks(deadline);
CREATE INDEX idx_tasks_created_at ON tasks(created_at);
CREATE INDEX idx_tasks_status ON tasks(status);

CREATE INDEX idx_sub_tasks_task_id ON sub_tasks(task_id);

CREATE INDEX idx_comments_user_id ON users(id);
CREATE INDEX idx_comments_task_id ON tasks(id);

CREATE INDEX idx_categories_title ON categories(title);
CREATE INDEX idx_token_black_list ON token_black_list(token);