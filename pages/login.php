<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Ak už je prihlásený, presmeruj
if (isset($_SESSION['user_id'])) {
    header('Location: /shopko/index.php');
    exit;
}

$db = getDB();
$error = '';
$success = '';

// -----------------------------------------------
// PRIHLÁSENIE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Vyplň email a heslo.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            header('Location: /shopko/index.php');
            exit;
        } else {
            $error = 'Nesprávny email alebo heslo.';
        }
    }
}

// -----------------------------------------------
// REGISTRÁCIA
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Vyplň všetky polia.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Neplatný formát emailu.';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo musí mať aspoň 6 znakov.';
    } elseif ($password !== $confirm) {
        $error = 'Heslá sa nezhodujú.';
    } else {
        // Skontroluj či email existuje
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Tento email je už registrovaný.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hash]);
            $success = 'Registrácia úspešná! Môžeš sa prihlásiť.';
        }
    }
}

$pageTitle = 'Prihlásenie – SHOPKO';
require_once '../includes/header.php';
?>

<div class="container" style="padding: 40px 24px;">
    <div class="auth-wrapper">

        <!-- PRIHLÁSENIE -->
        <div class="auth-box">
            <h2>Prihlásiť sa</h2>

            <?php if ($error): ?>
                <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert--success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="vas@email.com" required>
                </div>
                <div class="form-group">
                    <label>Heslo</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn--dark btn--full">Prihlásiť sa</button>
            </form>
        </div>

        <!-- REGISTRÁCIA -->
        <div class="auth-box">
            <h2>Registrácia</h2>

            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Meno</label>
                    <input type="text" name="name" placeholder="Ján Novák" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="jan@email.com" required>
                </div>
                <div class="form-group">
                    <label>Heslo</label>
                    <input type="password" name="password" placeholder="min. 6 znakov" required>
                </div>
                <div class="form-group">
                    <label>Potvrdiť heslo</label>
                    <input type="password" name="confirm" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn--dark btn--full">Vytvoriť účet</button>
            </form>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
