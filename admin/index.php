<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'auth_check.php';

$db = getDB();

// Štatistiky
$stats = [
    'products'  => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders'    => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'revenue'   => $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

// Produkty s filtrom
$search   = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);

$sql    = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
$params = [];

if ($search) {
    $sql    .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if ($catFilter) {
    $sql    .= " AND p.category_id = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Vymaž produkt
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$delId]);
    header('Location: /shopko/admin/index.php');
    exit;
}

$pageTitle = 'Admin Panel – SHOPKO';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/shopko/public/css/style.css">
    <link rel="stylesheet" href="/shopko/public/css/admin.css">
</head>
<body class="admin-body">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">SHOPKO</div>
    <nav class="admin-sidebar__nav">
        <a href="/shopko/admin/index.php" class="active">📦 Produkty</a>
        <a href="/shopko/admin/orders.php">📋 Objednávky</a>
        <a href="/shopko/admin/product_add.php">➕ Pridať produkt</a>
    </nav>
    <a href="/shopko/pages/logout.php" class="admin-sidebar__logout">Odhlásiť sa</a>
</aside>

<!-- HLAVNÝ OBSAH -->
<main class="admin-main">

    <!-- ŠTATISTIKY -->
    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-card__label">Produkty</span>
            <span class="stat-card__value"><?= $stats['products'] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__label">Objednávky</span>
            <span class="stat-card__value"><?= $stats['orders'] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__label">Zákazníci</span>
            <span class="stat-card__value"><?= $stats['customers'] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__label">Tržby</span>
            <span class="stat-card__value"><?= number_format($stats['revenue'], 2) ?> €</span>
        </div>
    </div>

    <!-- HLAVIČKA + FILTER -->
    <div class="admin-toolbar">
        <h1>Správa produktov</h1>
        <form method="GET" class="admin-filters">
            <input type="text" name="search" placeholder="Hľadaj produkt..."
                   value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
                <option value="">Všetky kategórie</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catFilter === $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--dark">Filtrovať</button>
        </form>
        <a href="/shopko/admin/product_add.php" class="btn btn--dark">+ Pridať produkt</a>
    </div>

    <!-- TABUĽKA PRODUKTOV -->
    <table class="admin-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Obrázok</th>
            <th>Názov</th>
            <th>Kategória</th>
            <th>Cena</th>
            <th>Skladom</th>
            <th>Stav</th>
            <th>Akcie</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td>#<?= $p['id'] ?></td>
                <td>
                    <?php if ($p['image_url']): ?>
                        <img src="/shopko/public/<?= htmlspecialchars($p['image_url']) ?>"
                             alt="" class="admin-table__thumb">
                    <?php else: ?>
                        <div class="admin-table__no-img">—</div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td><strong><?= number_format($p['price'], 2) ?> €</strong></td>
                <td><?= $p['stock'] ?> ks</td>
                <td>
                    <?php if ($p['stock'] > 5): ?>
                        <span class="badge badge--green">Aktívny</span>
                    <?php elseif ($p['stock'] > 0): ?>
                        <span class="badge badge--yellow">Málo</span>
                    <?php else: ?>
                        <span class="badge badge--red">Vypredané</span>
                    <?php endif; ?>
                </td>
                <td class="admin-table__actions">
                    <a href="/shopko/admin/product_edit.php?id=<?= $p['id'] ?>" class="link-blue">✏ Upraviť</a>
                    <a href="?delete=<?= $p['id'] ?>"
                       onclick="return confirm('Naozaj vymazať produkt?')"
                       class="link-red">🗑 Zmazať</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</main>

<script src="/shopko/public/js/main.js"></script>
</body>
</html>
