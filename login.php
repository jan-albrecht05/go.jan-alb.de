<?php
    session_start();
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        header('Location: admin.php');
        exit();
    }

    // Datenbankverbindung
    $db_path = __DIR__ . '/database.db';
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $message = null;
    $error = null;
    $serverName = $_SERVER['SERVER_NAME'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Überprüfen Sie die Anmeldeinformationen
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['is_admin'] = true;
            header('Location: admin.php');
            exit();
        } else {
            $error = "Ungültiger Benutzername oder Passwort.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Login</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
    <div id="box">
        <h1>Admin-Bereich</h1>
        <?php if ($message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="username">Benutzername:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Passwort:</label>
            <input type="password" id="password" name="password" required><br><br>
            <button type="submit">Anmelden</button>
        </form>
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
