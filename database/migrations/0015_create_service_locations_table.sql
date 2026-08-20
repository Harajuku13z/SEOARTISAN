CREATE TABLE IF NOT EXISTS service_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_service_id INT UNSIGNED NOT NULL,
    city_id INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_locations_company_service FOREIGN KEY (company_service_id) REFERENCES company_services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_locations_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE,
    UNIQUE KEY uniq_service_city (company_service_id, city_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
