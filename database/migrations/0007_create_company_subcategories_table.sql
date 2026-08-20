CREATE TABLE IF NOT EXISTS company_subcategories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    business_subcategory_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_company_subcategories_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_company_subcategories_subcategory FOREIGN KEY (business_subcategory_id) REFERENCES business_subcategories (id) ON DELETE CASCADE,
    UNIQUE KEY uniq_company_subcategory (company_id, business_subcategory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
