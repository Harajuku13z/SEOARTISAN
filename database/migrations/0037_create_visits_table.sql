-- Suivi de visites avec classification des robots (humains / moteurs / IA / social / outils)
CREATE TABLE IF NOT EXISTS artisan_visits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  path VARCHAR(500) NOT NULL DEFAULT '/',
  is_bot TINYINT(1) NOT NULL DEFAULT 0,
  bot_category ENUM('human','search','ai','social','tool') NOT NULL DEFAULT 'human',
  bot_name VARCHAR(80) NULL,
  user_agent VARCHAR(255) NULL,
  ip_hash CHAR(64) NULL,
  referrer VARCHAR(500) NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_visits_visited_at (visited_at),
  INDEX idx_visits_bot (is_bot, bot_category),
  INDEX idx_visits_path (path(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
