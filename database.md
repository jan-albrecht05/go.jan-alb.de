Table `shortlinks`
```SQL
CREATE TABLE shortlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hash VARCHAR(9) NOT NULL UNIQUE,
    target TEXT NOT NULL,
    type ENUM('url', 'file') NOT NULL DEFAULT 'url',
    active TINYINT(1) NOT NULL DEFAULT 1,
    clicks INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL
    password_hash VARCHAR(255) NULL
    max_clicks INT NULL
    last_click DATETIME NULL
    created_by INT NULL
);
```