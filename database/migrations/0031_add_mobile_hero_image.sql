ALTER TABLE companies
    ADD COLUMN hero_mobile_media_id INT UNSIGNED NULL AFTER hero_media_id,
    ADD CONSTRAINT fk_companies_hero_mobile FOREIGN KEY (hero_mobile_media_id) REFERENCES media (id) ON DELETE SET NULL;
