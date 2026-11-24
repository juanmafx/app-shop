-- App Shop relational schema for MySQL 8.4, equivalent to the Laravel migrations in backend/database/migrations.
-- Use either this file or `php artisan migrate` on an empty database, not both.

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid VARCHAR(255) NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY personal_access_tokens_token_unique (token),
    KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
