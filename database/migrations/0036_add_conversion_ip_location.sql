ALTER TABLE conversion_events
    ADD COLUMN ip_encrypted TEXT NULL AFTER ip_hash,
    ADD COLUMN location_label VARCHAR(255) NULL AFTER page_path;
