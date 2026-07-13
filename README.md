# PHP ShortURL

A lightweight and self-hosted URL shortener written in PHP.

Create short links like:

```text
https://go.example.com/AaBbCc
```

and redirect them to websites, files, downloads, internal tools, or any other target.

---

## Features

* 🚀 Lightweight and fast
* 🔗 Short URLs with custom hashes
* 📁 File download support
* 📊 Click counter
* 🔒 Simple and secure database lookup
* 🛠 Easy to integrate into existing PHP environments
* 📦 No framework required

---

## Example

### Short URL

```text
https://go.example.com/AaBbCc
```

### Database Entry

| Hash   | Type | Target                 |
| ------ | ---- | ---------------------- |
| AaBbCc | url  | https://www.google.com |
| XyZ123 | file | /downloads/manual.pdf  |

### Result

* `AaBbCc` redirects to Google
* `XyZ123` downloads a file

---

## Requirements

* PHP 8.0+
* MySQL / MariaDB
* Apache with mod_rewrite enabled

---

## Installation

### Clone Repository

```bash
git clone https://github.com/jan-albrecht05/go.jan-alb.de.git
```

### Create Database

```sql
CREATE TABLE shortlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hash VARCHAR(9) NOT NULL UNIQUE,
    target TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'url',
    url TEXT NULL,
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

### Configure Database Connection

Edit the connection settings in `index.php`.

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=shorturl;charset=utf8mb4',
    'username',
    'password'
);
```

### Enable URL Rewriting

Create a `.htaccess` file:

```apache
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^([A-Za-z0-9]+)$ index.php?hash=$1 [L,QSA]
```

---

## Project Structure

```text
/
├── index.php
├── .htaccess
├── README.md
└── database.sql
```

---

## Security Recommendations

For downloadable files, do not store absolute file paths directly in the database.

Instead of:

```text
/var/www/downloads/manual.pdf
```

store:

```text
manual.pdf
```

and prepend the download directory within the application.

This prevents path traversal attacks and accidental exposure of server files.

---

## Roadmap

Planned features:

* [ ] Expiration dates
* [ ] Password protected links
* [ ] Maximum click limits
* [ ] QR code generation
* [ ] Admin panel
* [ ] Link analytics

---

## License

MIT License

Feel free to use, modify, and distribute this project.

---

## Contributing

Pull requests, feature suggestions, and bug reports are always welcome.

If you find this project useful, consider giving it a ⭐ on GitHub.
