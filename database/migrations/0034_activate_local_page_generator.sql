ALTER TABLE local_pages
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER page_id,
    ADD COLUMN content_mode ENUM('ai', 'manual') NOT NULL DEFAULT 'ai' AFTER is_active,
    ADD COLUMN content_payload JSON NULL AFTER content_mode,
    ADD COLUMN error_message TEXT NULL AFTER generation_status;

