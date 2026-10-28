USE studenthub;

ALTER TABLE events
    ADD COLUMN category VARCHAR(80) NOT NULL DEFAULT 'General',
    ADD COLUMN status ENUM('open', 'closed') NOT NULL DEFAULT 'open';
