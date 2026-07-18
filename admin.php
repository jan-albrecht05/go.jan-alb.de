<?php
    session_start();
    date_default_timezone_set('Europe/Berlin');
    $serverName = $_SERVER['SERVER_NAME'];
    $allLinks = null;
    $activeLinks = null;
    $inactiveLinks = null;
    $urls = null;
    $files = null;
    $ips = null;

    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: login.php');
        exit();
    }

    // Hauptdatenbankverbindung
    $db_path = __DIR__ . '/database.db';
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $message = null;
    $error = null;
    $errors = [];
    $success = null;

    // Statistiken abrufen
    $stmt = $pdo->query("SELECT COUNT(*) FROM shortlinks");
    $allLinks = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM shortlinks WHERE active = 1");
    $activeLinks = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM shortlinks WHERE active = 0");
    $inactiveLinks = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(DISTINCT url) FROM shortlinks"); // not case-sensitive
    $urls = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM shortlinks WHERE type = 'file'");
    $files = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(DISTINCT created_by) FROM shortlinks");
    $ips = $stmt->fetchColumn();

    // Benutzer hinzufügen
    if (isset($_POST['add_user'])) {
        $newUsername = trim($_POST['new_username']);
        $newPassword = $_POST['new_password'];
        $newRole = $_POST['new_role'];

        if (empty($newUsername) || empty($newPassword) || empty($newRole)) {
            $errors[] = "Alle Felder müssen ausgefüllt werden.";
        } else {
            // Überprüfen, ob der Benutzername bereits existiert
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute([':username' => $newUsername]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Der Benutzername existiert bereits.";
            } else {
                // Passwort hashen und Benutzer hinzufügen
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
                $stmt->execute([
                    ':username' => $newUsername,
                    ':password' => $hashedPassword,
                    ':role' => $newRole
                ]);
                $success = "Benutzer erfolgreich hinzugefügt.";
            }
        }
    }

    // Benutzer löschen
    if (isset($_POST['delete_user'])) {
        $userIdToDelete = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $userIdToDelete]);
        $success = "Benutzer erfolgreich gelöscht.";
    }

    // Benutzerrolle ändern
    if (isset($_POST['update_user'])) {
        $userIdToUpdate = $_POST['edit_user_id'];
        $newUsername = trim($_POST['edit_username']);
        $newRole = $_POST['edit_role'];

        if (empty($newUsername) || empty($newRole)) {
            $errors[] = "Alle Felder müssen ausgefüllt werden.";
        } else {
            // Überprüfen, ob der Benutzername bereits existiert (außer für den aktuellen Benutzer)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :id");
            $stmt->execute([':username' => $newUsername, ':id' => $userIdToUpdate]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Der Benutzername existiert bereits.";
            } else {
                // Benutzerrolle und Name aktualisieren
                $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
                $stmt->execute([
                    ':username' => $newUsername,
                    ':role' => $newRole,
                    ':id' => $userIdToUpdate
                ]);
                $success = "Benutzer erfolgreich aktualisiert.";
            }
        }
    }

    // Benutzerpasswort ändern
    if (isset($_POST['change_password'])) {
        $userIdToChangePassword = $_POST['change_password_user_id'];
        $newPassword = $_POST['new_password'];

        if (empty($newPassword)) {
            $errors[] = "Das Passwortfeld darf nicht leer sein.";
        } else {
            // Passwort hashen und aktualisieren
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $userIdToChangePassword
            ]);
            $success = "Passwort erfolgreich geändert.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Panel</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            loadLinks();
        });
        function openEditModal(userId) {
            const modal = document.getElementById('edit-user-modal');
            const editUserIdInput = document.getElementById('edit_user_id');
            const editUsernameInput = document.getElementById('edit_username');
            const editRoleSelect = document.getElementById('edit_role');

            // Set the user ID in the hidden input
            editUserIdInput.value = userId;

            // Fetch user data from the server (you can implement an API endpoint for this)
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    editUsernameInput.value = data.username;
                    editRoleSelect.value = data.role;
                })
                .catch(error => console.error('Error fetching user data:', error));

            modal.style.display = 'block';
        }

        // Close the modal when clicking on the close button
        document.querySelector('.close').addEventListener('click', () => {
            document.getElementById('edit-user-modal').style.display = 'none';
        });

        // Close the modal when clicking outside of it
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('edit-user-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        // open password change modal
        function openChangePasswordModal(userId) {
            const modal = document.getElementById('change-password-modal');
            const changePasswordUserIdInput = document.getElementById('change_password_user_id');

            // Set the user ID in the hidden input
            changePasswordUserIdInput.value = userId;

            modal.style.display = 'block';
        }
    </script>
    <style>
        #footer {
            position: relative;
            margin-top: 5rem;
        }
    </style>
    <link rel="stylesheet" href="admin.css">
</head>
<body style="height:auto; min-height:100vh; overflow-x:visible;">
    <div id="main">
        <button id="logout-button" class="btn-secondary" onclick="window.location.href='logout.php'">Abmelden</button>
        <h1>Admin-Panel</h1>
        <div class="section" id="overview">
            <h2>Übersicht</h2>
            <div class="row">
                <div class="card">
                    <p><?php echo htmlspecialchars($allLinks); ?></p>
                    <h3>Alle Links</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($activeLinks); ?></p>
                    <h3>Aktive Links</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($inactiveLinks); ?></p>
                    <h3>Inaktive Links</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($urls); ?></p>
                    <h3>unique URLs</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($allLinks - $files); ?></p>
                    <h3>Links</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($files); ?></p>
                    <h3>Dateien</h3>
                </div>
                <div class="card">
                    <p><?php echo htmlspecialchars($ips); ?></p>
                    <h3>IPs</h3>
                </div>
            </div>
        </div>

        <div class="section" id="link-management">
            <h2>Link-Verwaltung</h2>
            <div id="link-filter">
                <div class="group">
                    <label for="status-select">Status:</label>
                    <select id="status-select" onchange="loadLinks()">
                        <option value="all">Alle</option>
                        <option value="active">Aktiv</option>
                        <option value="inactive">Inaktiv</option>
                    </select>
                </div>
                <div class="group">
                    <label for="type-select">Typ:</label>
                    <select id="type-select" onchange="loadLinks()">
                        <option value="all">Alle Typen</option>
                        <option value="link">Links</option>
                        <option value="file">Dateien</option>
                    </select>
                </div>
                <div class="group">
                    <label for="sort-select">Sortieren nach:</label>
                    <select id="sort-select" onchange="loadLinks()">
                        <option value="created_at_desc">Erstellt (neueste zuerst)</option>
                        <option value="created_at_asc">Erstellt (älteste zuerst)</option>
                        <option value="clicks_desc">Klicks (höchste zuerst)</option>
                        <option value="clicks_asc">Klicks (niedrigste zuerst)</option>
                    </select>
                </div>
                <div class="group search">
                    <label for="search-input">Suche:</label>
                    <input type="text" id="search-input" placeholder="Suche..." oninput="loadLinks()">
                </div>
                <div class="group">
                    <button id="reset-button" onclick="resetFilters()">Filter zurücksetzen</button>
                </div>
            </div>
            <div id="link-list">
                <div class="link-item header">
                    <div class="short-url">Kurze URL</div>
                    <div class="original-url">Original URL</div>
                    <div class="type">Typ</div>
                    <div class="clicks">Klicks</div>
                    <div class="status">Status</div>
                    <div class="created-at">Erstellt am</div>
                    <div class="ip">IP</div>
                    <div class="actions">Aktionen</div>
                </div>
                <div id="link-list-content">
                    <script src="links.js"></script>
                    
                    <!-- Links werden hier dynamisch geladen -->
                </div>

            </div>
        </div>

        <div class="section" id="user-management">
            <h2>Benutzerverwaltung</h2>
            <div id="user-list">
                <div class="user-item header">
                    <div class="user-name">Nutzername</div>
                    <div class="user-role">Rolle</div>
                </div>
                <?php
                    $stmt = $pdo->query("SELECT id, username, role FROM users");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="user-item">';
                            echo '<div class="user-name">' . htmlspecialchars($row['username']) . '</div>';
                            echo '<div class="user-role">' . htmlspecialchars($row['role']) . '</div>';
                            echo '<div class="user-actions">';
                                echo '<form method="POST" action="admin.php" class="user-action-form">';
                                    echo '<input type="hidden" name="user_id" value="' . $row['id'] . '">';
                                    echo '<button type="submit" name="delete_user" class="delete-button"><span class="material-symbols-outlined">delete</span></button>';
                                    echo '<button type="button" name="change_role" class="change-role-button" onclick="openEditModal(' . $row['id'] . ')"><span class="material-symbols-outlined">edit</span></button>';
                                    echo '<button type="button" name="change_password" class="change-password-button" onclick="openChangePasswordModal(' . $row['id'] . ')"><span class="material-symbols-outlined">lock</span></button>';
                                echo '</form>';
                            echo '</div>';
                        echo '</div>';
                    }
                ?>
            </div>
            <form method="POST" action="admin.php" id="add-user-form">
                <h3>Neuen Benutzer hinzufügen</h3>
                <div class="row">
                    <input type="text" name="new_username" placeholder="Nutzername" required>
                    <input type="password" name="new_password" placeholder="Passwort" required>
                    <select name="new_role" required>
                        <option value="">Rolle auswählen</option>
                        <option value="user">Benutzer</option>
                        <option value="admin">Administrator</option>
                    </select>
                    <button type="submit" name="add_user">Hinzufügen</button>
                </div>
            </form>
        </div>
    </div>
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close material-symbols-outlined" onclick="document.getElementById('edit-user-modal').style.display='none'">close</span>
            <h2>Benutzer bearbeiten</h2>
            <form method="POST" action="admin.php" id="edit-user-form">
                <input type="hidden" name="edit_user_id" id="edit_user_id">
                <div class="row">
                    <label for="edit_username">Nutzername:</label>
                    <input type="text" name="edit_username" id="edit_username" required value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>">
                </div>
                <div class="row">
                    <label for="edit_role">Rolle:</label>
                    <select name="edit_role" id="edit_role" required>
                        <option value="">Rolle auswählen</option>
                        <option value="user">Benutzer</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button type="submit" name="update_user">Aktualisieren</button>
            </form>
        </div>
    </div>
    <div id="change-password-modal" class="modal">
        <div class="modal-content">
            <span class="close material-symbols-outlined" onclick="document.getElementById('change-password-modal').style.display='none'">close</span>
            <h2>Passwort ändern</h2>
            <form method="POST" action="admin.php" id="change-password-form">
                <input type="hidden" name="change_password_user_id" id="change_password_user_id">
                <div class="row">
                    <label for="new_password">Neues Passwort:</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <button type="submit" name="change_password">Passwort ändern</button>
            </form>
        </div>
    </div>
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
</body>
</html>