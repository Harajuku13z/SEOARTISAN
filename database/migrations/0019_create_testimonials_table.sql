CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(150) NOT NULL,
    role_or_service VARCHAR(150) NULL,
    content TEXT NOT NULL,
    rating TINYINT UNSIGNED NULL,
    source ENUM('manual', 'google') NOT NULL DEFAULT 'manual',
    avatar_media_id INT UNSIGNED NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_testimonials_avatar FOREIGN KEY (avatar_media_id) REFERENCES media (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
