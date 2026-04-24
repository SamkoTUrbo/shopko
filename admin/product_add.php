<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'auth_check.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $imageUrl    = trim($_POST['image_url'] ?? '');

    if (!$name || !$price) {
        $error = 'Vyplň aspoň názov a cenu.';
    } else {
        $stmt = $db->prepare("
            INSERT INTO products (name, description, price, stock, category_id, image_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $stock, $categoryId ?: null, $imageUrl ?: null]);
        $success = 'Produkt bol úspešne pridaný!';
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Pridať produkt – SHOPKO Admin</title>
    <link rel="stylesheet" href="/shopko/public/css/style.css">
    <link rel="stylesheet" href="/shopko/public/css/admin.css">
</head>
<body class="admin-body">

<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">SHOPKO</div>
    <nav class="admin-sidebar__nav">
        <a href="/shopko/admin/index.php">📦 Produkty</a>
        <a href="/shopko/admin/orders.php">📋 Objednávky</a>
        <a href="/shopko/admin/product_add.php" class="active">➕ Pridať produkt</a>
    </nav>
    <a href="/shopko/pages/logout.php" class="admin-sidebar__logout">Odhlásiť sa</a>
</aside>

<main class="admin-main">
    <div class="admin-form">
        <h1>Pridať nový produkt</h1>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($success) ?>
                <a href="/shopko/admin/index.php">← Späť na zoznam</a>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Názov produktu *</label>
                <input type="text" name="name" required placeholder="napr. Biele tričko">
            </div>
            <div class="form-group">
                <label>Popis</label>
                <textarea name="description" rows="4" placeholder="Popis produktu..."></textarea>
            </div>
            <div class="form-group">
                <label>Cena (€) *</label>
                <input type="number" name="price" step="0.01" min="0" required placeholder="19.99">
            </div>
            <div class="form-group">
                <label>Počet na sklade</label>
                <input type="number" name="stock" min="0" value="0">
            </div>
            <div class="form-group">
                <label>Kategória</label>
                <select name="category_id">
                    <option value="">— Vyber kategóriu —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>URL obrázka (napr. images/tricko.jpg)</label>
                <input type="text" name="image_url" placeholder="images/produkt.jpg">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <button type="submit" class="btn btn--dark">Pridať produkt</button>
                <a href="/shopko/admin/index.php" class="btn btn--outline">Zrušiť</a>
            </div>
        </form>
    </div>
</main>

</body>
</html>
