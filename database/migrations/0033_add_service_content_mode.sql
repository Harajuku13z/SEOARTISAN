ALTER TABLE company_services
    ADD COLUMN content_mode ENUM('ai', 'manual') NOT NULL DEFAULT 'ai' AFTER description,
    ADD COLUMN manual_content LONGTEXT NULL AFTER content_mode;

