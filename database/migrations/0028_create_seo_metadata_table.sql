-- Site-wide SEO defaults (singleton row, enforced at application level).
CREATE TABLE IF NOT EXISTS seo_metadata (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(150) NULL,
    default_title_pattern VARCHAR(255) NULL,
    default_meta_description VARCHAR(500) NULL,
    gsc_verification_code VARCHAR(255) NULL,
    sitemap_last_generated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
