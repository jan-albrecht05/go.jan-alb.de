<?php

// Hauptdatenbankverbindung
$db_path = __DIR__ . '/database.db';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$serverName = $_SERVER['SERVER_NAME'];

// create table if it doesn't exist

$pdo->exec("
    CREATE TABLE IF NOT EXISTS shortlinks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hash VARCHAR(9) NOT NULL UNIQUE,
        target TEXT NOT NULL,
        type TEXT NOT NULL DEFAULT 'url',
        url TEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        clicks INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        password_hash VARCHAR(255) NULL,
        max_clicks INT NULL,
        last_click DATETIME NULL,
        created_by INT NULL
    )
");



$hash = $_GET['hash'] ?? '';
if($hash != '') {

if (!preg_match('/^[A-Za-z0-9]{6,9}$/', $hash)) {
    http_response_code(404);
    exit('Link nicht gefunden');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM shortlinks
    WHERE hash = ?
    AND active = 1
    LIMIT 1
");
$stmt->execute([$hash]);

$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    http_response_code(404);
    exit('Link nicht gefunden');
}

// Klick zählen
$pdo->prepare("
    UPDATE shortlinks
    SET clicks = clicks + 1
    WHERE id = ?
")->execute([$link['id']]);

// Letzten Klickzeitpunkt aktualisieren
$pdo->prepare("
    UPDATE shortlinks
    SET last_click = CURRENT_TIMESTAMP
    WHERE id = ?
")->execute([$link['id']]);

if ($link['type'] === 'url') {

    header('Location: ' . $link['target'], true, 302);
    exit;

}

if ($link['type'] === 'file') {

    $file = $link['target'];

    if (!file_exists($file)) {
        http_response_code(404);
        exit('Datei nicht gefunden');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));

    readfile($file);
    exit;
}

http_response_code(500);
echo 'Ungültiger Linktyp';
}
function generateHash(int $length = 6): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $hash = '';

    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $hash;
}

// no hash provided, show normal HTML

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short-Link erstellen</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
    <div id="message">
        <?php if (isset($message)) echo htmlspecialchars($message); ?>
    </div>
    <div id="error">
        <?php if (isset($error)) echo htmlspecialchars($error); ?>
    </div>
    <div id="success">
        <?php if (isset($success)) echo htmlspecialchars($success); ?>
    </div>
    <div id="shortlink">
        <?php if (isset($shortlink)) echo htmlspecialchars($shortlink); ?>
    </div>
    <div id="shortlink_qr">
        <?php if (isset($shortlink_qr)) echo $shortlink_qr; ?>
    </div>
    <div id="box">
        <h1>Short-Link erstellen</h1>
        <form action="index.php" method="post">
            <label for="target">Ziel-URL:</label>
            <input type="url" id="target" name="target" required>
            <input type="hidden" name="type" value="url">
            <input type="hidden" name="hash" value="">
            <input type="hidden" name="active" value="1">
            <input type="hidden" name="timecode" value="<?php echo time(); ?>">
            <div id="settings">
                <input type="checkbox" id="password_protect" name="password_protect">
                <label for="password_protect">Passwortschutz aktivieren</label>
                <div id="password_field" style="display: none;">
                    <label for="password">Passwort:</label>
                    <input type="password" id="password" name="password">
                </div>
                <input type="checkbox" id="expiration" name="expiration">
                <label for="expiration">Ablaufdatum aktivieren</label>
                <div id="expiration_field" style="display: none;">
                    <label for="expires_at">Ablaufdatum:</label>
                    <input type="datetime-local" id="expires_at" name="expires_at">
                </div>
                <input type="checkbox" id="max_clicks" name="max_clicks">
                <label for="max_clicks">Maximale Klicks aktivieren</label>
                <div id="max_clicks_field" style="display: none;">
                    <label for="max_clicks_value">Maximale Klicks:</label>
                    <input type="number" id="max_clicks_value" name="max_clicks_value" min="1">
                </div>
                <div id="custom_hash_field">
                    <label for="custom_hash">Benutzerdefinierter Hash (optional):</label>
                    <input type="text" id="custom_hash" name="custom_hash" pattern="[A-Za-z0-9]{6,9}" title="6-9 Zeichen, nur Buchstaben und Zahlen">
                </div>
                <script>
                    document.getElementById('password_protect').addEventListener('change', function() {
                        document.getElementById('password_field').style.display = this.checked ? 'block' : 'none';
                    });
                    document.getElementById('expiration').addEventListener('change', function() {
                        document.getElementById('expiration_field').style.display = this.checked ? 'block' : 'none';
                    });
                    document.getElementById('max_clicks').addEventListener('change', function() {
                        document.getElementById('max_clicks_field').style.display = this.checked ? 'block' : 'none';
                    });
                </script>
            </div>
            <button type="submit">Link erstellen</button>
        </form>
    </div>
    <div id="footer">
        <p>&copy; <?php echo date('Y'); ?> Short-Link Service</p>
        <div id="mode-toggle" class="center">
            <span class="material-symbols-outlined">light_mode</span>
            <label class="switch">
                <input type="checkbox" id="toggle-checkbox">
                <span class="slider round"></span>
            </label>
            <span class="material-symbols-outlined">dark_mode</span>
        <script src="mode.js"></script>
    </div>
    </div>
</body>
</html>
<?php
// form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $target = $_POST['target'] ?? '';
    $type = $_POST['type'] ?? 'url';
    // extract the URL from the target if needed
    if ($type === 'url') {
        $url = filter_var($target, FILTER_VALIDATE_URL);
        if ($url === false) {
            $error = 'Ungültige URL.';
            return;
        }
    }
    $active = $_POST['active'] ?? 1;
    $expires_at = $_POST['expires_at'] ?? null;
    $password_protect = isset($_POST['password_protect']) ? true : false;
    $password = $_POST['password'] ?? null;
    $max_clicks_enabled = isset($_POST['max_clicks']) ? true : false;
    $max_clicks_value = $_POST['max_clicks_value'] ?? null;
    $custom_hash = $_POST['custom_hash'] ?? null;

    if ($target === '') {
        $error = 'Ziel-URL darf nicht leer sein.';
        return;
    }

    if ($password_protect && empty($password)) {
        $error = 'Passwort darf nicht leer sein, wenn Passwortschutz aktiviert ist.';
        return;
    }

    if ($max_clicks_enabled && (empty($max_clicks_value) || !is_numeric($max_clicks_value) || $max_clicks_value < 1)) {
        $error = 'Maximale Klicks muss eine positive Zahl sein.';
        return;
    }

    // Generate hash
    if (!empty($custom_hash)) {
        if (!preg_match('/^[A-Za-z0-9]{6,9}$/', $custom_hash)) {
            $error = 'Benutzerdefinierter Hash muss 6-9 Zeichen lang sein und nur Buchstaben und Zahlen enthalten.';
            return;
        }
        // Check if custom hash already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlinks WHERE hash = ?");
        $stmt->execute([$custom_hash]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Benutzerdefinierter Hash ist bereits vergeben. Bitte wählen Sie einen anderen.';
            return;
        }
        $hash = $custom_hash;
    } else {
        do {
            $hash = generateHash();
            // Check if hash already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlinks WHERE hash = ?");
            $stmt->execute([$hash]);
        } while ($stmt->fetchColumn() > 0);
    }

    // Hash password if password protection is enabled
    $password_hash = null;
    if ($password_protect) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO shortlinks (hash, target, type, url, active, expires_at, password_hash, max_clicks, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $hash,
        $target,
        $type,
        $url,
        $active,
        $expires_at,
        $password_hash,
        $max_clicks_enabled ? $max_clicks_value : null,
        $_SERVER['REMOTE_ADDR']
    ]);

    $shortlink = "https://" . $serverName . "/" . $hash;

}
?>