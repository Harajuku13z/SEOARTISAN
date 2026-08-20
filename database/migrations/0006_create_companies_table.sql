CREATE TABLE IF NOT EXISTS companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_category_id INT UNSIGNED NULL,

    -- Identite
    trade_name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(150) NULL,
    slogan VARCHAR(255) NULL,
    short_description VARCHAR(500) NULL,
    long_description TEXT NULL,
    founded_year SMALLINT UNSIGNED NULL,

    -- Contact
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    public_email VARCHAR(191) NULL,
    leads_email VARCHAR(191) NULL,

    -- Adresse
    address VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    city VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    region VARCHAR(150) NULL,

    -- Legal / reassurance
    siret VARCHAR(20) NULL,
    certifications JSON NULL,
    opening_hours JSON NULL,
    social_links JSON NULL,
    gbp_url VARCHAR(500) NULL,
    legal_info TEXT NULL,

    -- Zone / offre
    service_radius_km SMALLINT UNSIGNED NULL,
    offers_emergency TINYINT(1) NOT NULL DEFAULT 0,
    offers_free_quote TINYINT(1) NOT NULL DEFAULT 1,

    -- Identite visuelle
    primary_color VARCHAR(7) NULL,
    secondary_color VARCHAR(7) NULL,
    accent_color VARCHAR(7) NULL,
    button_style VARCHAR(30) NULL,
    font_primary VARCHAR(60) NULL,
    font_secondary VARCHAR(60) NULL,
    theme_style VARCHAR(30) NULL,
    logo_main_media_id INT UNSIGNED NULL,
    logo_light_media_id INT UNSIGNED NULL,
    logo_dark_media_id INT UNSIGNED NULL,
    favicon_media_id INT UNSIGNED NULL,
    hero_media_id INT UNSIGNED NULL,
    og_media_id INT UNSIGNED NULL,

    -- Informations redactionnelles reelles (section 10 du cahier des charges).
    -- Seule source utilisable par l'IA - jamais d'invention au-dela de ces champs.
    editorial_presentation TEXT NULL,
    editorial_history TEXT NULL,
    editorial_experience TEXT NULL,
    editorial_values TEXT NULL,
    editorial_work_method TEXT NULL,
    editorial_advantages TEXT NULL,
    editorial_guarantees TEXT NULL,
    editorial_client_types TEXT NULL,
    editorial_achievements TEXT NULL,
    editorial_brands_used TEXT NULL,
    editorial_typical_delays TEXT NULL,
    editorial_commitments TEXT NULL,
    editorial_differentiators TEXT NULL,
    editorial_priority_areas TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_companies_category FOREIGN KEY (business_category_id) REFERENCES business_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_logo_main FOREIGN KEY (logo_main_media_id) REFERENCES media (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_logo_light FOREIGN KEY (logo_light_media_id) REFERENCES media (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_logo_dark FOREIGN KEY (logo_dark_media_id) REFERENCES media (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_favicon FOREIGN KEY (favicon_media_id) REFERENCES media (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_hero FOREIGN KEY (hero_media_id) REFERENCES media (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_og FOREIGN KEY (og_media_id) REFERENCES media (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
