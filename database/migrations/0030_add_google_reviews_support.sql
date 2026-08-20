-- Supports fetching real Google reviews via SerpApi (App\Services\Reviews\SerpApiReviewsService)
-- and displaying them as testimonials. google_maps_data_id caches the resolved
-- Google Maps listing so re-syncing doesn't re-search every time.
ALTER TABLE companies
    ADD COLUMN google_maps_data_id VARCHAR(191) NULL AFTER gbp_url;

ALTER TABLE testimonials
    ADD COLUMN google_review_id VARCHAR(191) NULL AFTER source,
    ADD COLUMN author_city VARCHAR(150) NULL AFTER author_name,
    ADD COLUMN reviewed_at DATE NULL AFTER rating,
    ADD UNIQUE KEY uniq_testimonials_google_review_id (google_review_id);
