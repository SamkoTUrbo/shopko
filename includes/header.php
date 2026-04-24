<?php
// Spusti session ak ešte nie je
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Počet položiek v košíku
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'SHOPKO' ?></title>
    <link rel="stylesheet" href="/shopko/public/css/style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar__inner">

        <!-- Logo -->
        <a href="/shopko/index.php" class="navbar__logo">SHOPKO</a>

        <!-- Navigácia -->
        <nav class="navbar__nav">
            <a href="/shopko/index.php">Domov</a>
            <a href="/shopko/pages/products.php?cat=1">Muži</a>
            <a href="/shopko/pages/products.php?cat=4">Ženy</a>
            <a href="/shopko/pages/products.php?sale=1">Výpredaj</a>
        </nav>

        <!-- Vyhľadávanie -->
        <form class="navbar__search" action="/shopko/pages/search.php" method="GET">
            <input type="text" name="q" placeholder="Hľadaj produkt...">
            <button type="submit">🔍</button>
        </form>

        <!-- Používateľ + košík -->
        <div class="navbar__actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="font-size:13px;color:#666;">
                    👤 <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/shopko/admin/index.php"
                       style="background:#111;color:#fff;padding:5px 12px;border-radius:4px;font-size:12px;font-weight:600;">
                        ⚙ Admin
                    </a>
                <?php endif; ?>
                <a href="/shopko/pages/logout.php">Odhlásiť</a>
            <?php else: ?>
                <a href="/shopko/pages/login.php">Prihlásiť</a>
            <?php endif; ?>

            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <a href="/shopko/pages/cart.php" class="navbar__cart">
                    🛒 Košík (<?= $cartCount ?>)
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>
