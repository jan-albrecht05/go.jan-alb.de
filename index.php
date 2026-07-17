<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Berlin');
$is_admin = $_SESSION['is_admin'] ?? false;

// Hauptdatenbankverbindung
$db_path = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$message = null;
$error = null;
$errors = [];
$success = null;
$shortlink = null;
$show_normal_form = true;
$show_password_form = false;

$serverName = $_SERVER['SERVER_NAME'];

// create table if it doesn't exist

$pdo->exec("
    CREATE TABLE IF NOT EXISTS shortlinks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hash VARCHAR(255) NOT NULL UNIQUE,
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


// get hash from URL
$hash = $_GET['hash'] ?? '';
if($hash !== '') {

    // validate hash format
    if (!preg_match('/^[A-Za-z0-9]{1,255}$/', $hash)) {
        http_response_code(404);
    $errors[] = 'Link nicht gefunden';
    $show_normal_form = false;
    } else {


// fetch link from database
$stmt = $pdo->prepare("
    SELECT *
    FROM shortlinks
    WHERE hash = ?
    LIMIT 1
");
$stmt->execute([$hash]);

$link = $stmt->fetch(PDO::FETCH_ASSOC);

// check if link exists
if (!$link) {
    http_response_code(404);
    $errors[] = 'Link nicht gefunden';
    $show_normal_form = false;
} else {

    // check for Admin access
    if($is_admin) {
        // admins can view the link without restrictions
        header('Location: ' . $link['target'], true, 302);
        exit;
    }

// status prüfen
if ($link['active'] == '0') {
    http_response_code(410);
    $errors[] = 'Link ist deaktiviert';
    $show_normal_form = false;
}

// Ablaufdatum prüfen
if (!empty($link['expires_at'])) {
    $expires = strtotime($link['expires_at']);
    if ($expires !== false && $expires <= time()) {
        if ((int)$link['active'] === 1) {
            $pdo->prepare("UPDATE shortlinks SET active = 0 WHERE id = ?")->execute([$link['id']]);
        }
        http_response_code(410);
        $errors[] = 'Dieser Link ist abgelaufen.';
        $show_normal_form = false;
    }
}

// Passwortschutz prüfen
if ($link['password_hash'] && empty($errors)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $show_password_form = true;
    } else {
        if (!password_verify($_POST['password'] ?? '', $link['password_hash'])) {
            $show_password_form = true;
            $errors[] = 'Falsches Passwort';
        }
    }
}

if(!$show_password_form && empty($errors)) {
    // Maximale Klicks prüfen
    if ($link['max_clicks'] !== null && $link['clicks'] >= $link['max_clicks']) {
        http_response_code(410);
        if ((int)$link['active'] === 1) {
            $pdo->prepare("
                UPDATE shortlinks
                SET active = 0
                WHERE id = ?
            ")->execute([$link['id']]);
        }
        $errors[] = 'Maximale Aufrufe erreicht';
        $show_normal_form = false;
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
}

if ($link['type'] === 'file') {

    $file = $link['target'];

    if (!file_exists($file)) {
        http_response_code(404);
        $errors[] = 'Datei nicht gefunden';
        $show_normal_form = false;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));

    readfile($file);
    $show_normal_form = false;
}}
if (!$show_password_form && empty($errors)) {
    if ($link['type'] === 'url') {
        header('Location: ' . $link['target'], true, 302);
        $show_normal_form = false;
        exit;
    }
    if ($link['type'] === 'file') {
        readfile($file);
        exit;
    }

    http_response_code(500);
    $errors[] = "Unbekannter Linktyp.";
    $show_normal_form = false;
}}

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


<?php
// form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    
    $target = $_POST['target'] ?? '';
    if (!str_starts_with($target, 'http://') && !str_starts_with($target, 'https://')) {
    $target = 'https://' . $target;
}
    $type = $_POST['type'] ?? 'url';
    // validate URL
    if (!filter_var($target, FILTER_VALIDATE_URL)) {
        $errors [] = 'Bitte eine gültige URL eingeben.';
    }
    // extract the URL from the target if needed
    if ($type === 'url') {
        $url = parse_url($target, PHP_URL_HOST); // parse URL for type 'url' from the target
    }
    $active = $_POST['active'] ?? 1;
    $expires_at = $_POST['expires_at'] ?? null;
    $password_protect = isset($_POST['password_protect']) ? true : false;
    $password = $_POST['password'] ?? null;
    $max_clicks_enabled = isset($_POST['max_clicks']) ? true : false;
    $max_clicks_value = $_POST['max_clicks_value'] ?? null;
    $custom_hash = $_POST['custom_hash'] ?? null;

    if ($target === '') {
        $errors[] = 'Ziel-URL darf nicht leer sein.';
    }

    if ($password_protect && empty($password)) {
        $errors[] = 'Passwort darf nicht leer sein, wenn Passwortschutz aktiviert ist.';
    }

    if ($max_clicks_enabled && (empty($max_clicks_value) || !is_numeric($max_clicks_value) || $max_clicks_value < 1)) {
        $errors[] = 'Maximale Klicks muss eine positive Zahl sein.';
    }

    // Generate hash
    if (!empty($custom_hash)) {
        if (!preg_match('/^[A-Za-z0-9]{1,255}$/', $custom_hash)) {
            $errors[] = 'Benutzerdefinierter Hash darf max. 255 Zeichen lang sein und nur Buchstaben und Zahlen enthalten.';
        }
        // Check if custom hash already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlinks WHERE hash = ?");
        $stmt->execute([$custom_hash]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Benutzerdefinierter Hash ist bereits vergeben. Bitte wählen Sie einen anderen.';
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

    if(empty($errors)){
        try{
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
        } catch (PDOException $e) {
            $errors[] = 'Fehler beim Erstellen des Short-Links: ' . $e->getMessage();
        }
        $success = 'Short-Link erfolgreich erstellt!';
        $shortlink = "https://" . $serverName . "/" . $hash;
        if($shortlink) {
            $show_normal_form = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short-Link erstellen</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("target").focus();
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Short-Link in die Zwischenablage kopiert: ' + text);
            }, function(err) {
                console.error('Fehler beim Kopieren in die Zwischenablage: ', err);
            });
        }
    </script>
</head>
<body>
    <?php if($message): ?>
        <div id="message" class="notification"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if($errors): ?>
        <?php foreach($errors as $error): ?>
            <div class="notification error center">
                <span class="material-symbols-outlined">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if($shortlink): ?>
        <div id="shortlink" class="notification success">
            <div class="row">
                <a id="shortlink-url" href="<?= htmlspecialchars($shortlink) ?>" target="_blank"><?= htmlspecialchars($shortlink) ?></a>
                <button onclick="copyToClipboard('<?= htmlspecialchars($shortlink) ?>')">📋 Kopieren</button>
            </div>
            <div id="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($shortlink) ?>" alt="QR Code">
            </div>
            <div id="back-link">
                <a href="index.php">Neuen Link erstellen</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if($show_password_form): ?>
        <!-- Password form -->
        <div id="box">
            <h1>🔒 Passwort erforderlich</h1>
            <form method="post">
                <input type="password" name="password" placeholder="Passwort" autofocus required>
                <input type="hidden" name="action" value="unlock">
                <button>Weiter</button>
            </form>
        </div>

    <?php else:  ?>
    <?php if($show_normal_form): ?>
    <!-- normales Formular -->
    <div id="box">
        <h1>Short-Link erstellen</h1>
        <form action="index.php" method="post">
            <input type="hidden" name="action" value="create">
            <label for="target">Ziel-URL:</label>
            <div class="row">
                <input type="text" id="target" name="target" required>
                <button type="submit">Link erstellen</button>
            </div>
            <input type="hidden" name="type" value="url">
            <input type="hidden" name="hash" value="">
            <input type="hidden" name="active" value="1">
            <input type="hidden" name="timecode" value="<?php echo time(); ?>">
            <details id="settings">
                <summary>
                    <h3>Optionen</h3>
                    <span class="material-symbols-outlined center">expand_more</span>
                </summary>
                <div class="row">
                    <label class="switch" for="password_protect">
                        <input type="checkbox" id="password_protect" name="password_protect">
                        <span class="slider round"></span>
                    </label>
                    <span >Passwortschutz aktivieren</span>
                </div>
                <div id="password_field" class="hidden-input" style="display: none;">
                    <label for="password">Passwort:</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="row">
                    <label class="switch" for="expiration">
                        <input type="checkbox" id="expiration" name="expiration">
                        <span class="slider round"></span>
                    </label>
                    <span >Ablaufdatum aktivieren</span>
                </div>
                <div id="expiration_field" class="hidden-input" style="display: none;">
                    <label for="expires_at">Ablaufdatum:</label>
                    <input type="datetime-local" id="expires_at" name="expires_at">
                </div>
                <div class="row">
                    <label class="switch" for="max_clicks">
                        <input type="checkbox" id="max_clicks" name="max_clicks">
                        <span class="slider round"></span>
                    </label>
                    <span >Maximale Klicks aktivieren</span>
                </div>
                <div id="max_clicks_field" class="hidden-input" style="display: none;">
                    <label for="max_clicks_value">Maximale Klicks:</label>
                    <input type="number" id="max_clicks_value" name="max_clicks_value" min="1">
                </div>
                <div class="row">
                    <label class="switch" for="custom_hash_toggle">
                        <input type="checkbox" id="custom_hash_toggle" name="custom_hash_toggle">
                        <span class="slider round"></span>
                    </label>
                    <span >Benutzerdefinierter Hash aktivieren</span>
                </div>
                <div id="custom_hash_field" class="hidden-input" style="display: none;">
                    <label for="custom_hash">Benutzerdefinierter Hash (optional):</label>
                    <input type="text" id="custom_hash" name="custom_hash" pattern="[A-Za-z0-9]{3,255}" title="max. 255 Zeichen, nur Buchstaben und Zahlen">
                </div>
                
            </details>
        </form>
    </div>
<?php endif; ?>
<?php endif; ?>
    <div id="footer">
        <p>&copy; <?php echo date('Y') . ' ' . $serverName; ?> </p>
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
    <a id="login-link" href="admin.php">
        <span class="material-symbols-outlined">admin_panel_settings</span>
    </a>
    </div>
</body>
</html>