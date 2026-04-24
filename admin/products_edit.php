<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'auth_check.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: /shopko/admin/index.php');
    exit;
}

// Načítaj produkt
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /shopko/admin/index.php');
    exit;
}

$categories = $db->query("SELECT * FROM categories")->fetchAll();
$error   = '';
$success = '';

// Ulož zmeny
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
            UPDATE products
            SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $price, $stock, $categoryId ?: null, $imageUrl ?: null, $id]);
        $success = 'Produkt bol úspešne upravený!';

        // Obnov dáta
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Upraviť produkt – SHOPKO Admin</title>
    <link rel="stylesheet" href="/shopko/public/css/style.css">
    <link rel="stylesheet" href="/shopko/public/css/admin.css">
</head>
<body class="admin-body">

<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">SHOPKO</div>
    <nav class="admin-sidebar__nav">
        <a href="/shopko/admin/index.php" class="active">📦 Produkty</a>
        <a href="/shopko/admin/orders.php">📋 Objednávky</a>
        <a href="/shopko/admin/product_add.php">➕ Pridať produkt</a>
    </nav>
    <a href="/shopko/pages/logout.php" class="admin-sidebar__logout">Odhlásiť sa</a>
</aside>

<main class="admin-main">
    <div class="admin-form">
        <h1>Upraviť produkt #<?= $id ?></h1>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Názov produktu *</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="form-group">
                <label>Popis</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Cena (€) *</label>
                <input type="number" name="price" step="0.01" min="0" required
                       value="<?= $product['price'] ?>">
            </div>
            <div class="form-group">
                <label>Počet na sklade</label>
                <input type="number" name="stock" min="0"
                       value="<?= $product['stock'] ?>">
            </div>
            <div class="form-group">
                <label>Kategória</label>
                <select name="category_id">
                    <option value="">— Vyber kategóriu —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>URL obrázka</label>
                <input type="text" name="image_url"
                       value="<?= htmlspecialchars($product['image_url'] ?? '') ?>"
                       placeholder="images/produkt.jpg">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <button type="submit" class="btn btn--dark">Uložiť zmeny</button>
                <a href="/shopko/admin/index.php" class="btn btn--outline">← Späť</a>
            </div>
        </form>
    </div>
</main>

</body>
</html>
