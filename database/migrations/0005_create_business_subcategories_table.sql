CREATE TABLE IF NOT EXISTS business_subcategories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_category_id INT UNSIGNED NOT NULL,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subcategories_category FOREIGN KEY (business_category_id) REFERENCES business_categories (id) ON DELETE CASCADE,
    UNIQUE KEY uniq_subcategory_per_category (business_category_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
