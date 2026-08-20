ALTER TABLE leads MODIFY status ENUM('new','abandoned','completed','to_contact','contacted','quoted','won','lost','spam') NOT NULL DEFAULT 'new';
