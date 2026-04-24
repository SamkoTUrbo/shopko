<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$db = getDB();

// Získaj ID produktu z URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: /shopko/index.php');
    exit;
}

// Načítaj produkt
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /shopko/index.php');
    exit;
}

$pageTitle = htmlspecialchars($product['name']) . ' – SHOPKO';

// Flash správa
$message = $_SESSION['message'] ?? null;
$messageType = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<div class="container" style="padding-top: 16px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/shopko/index.php">Domov</a> &rsaquo;
        <a href="/shopko/pages/products.php?cat=<?= $product['category_id'] ?>">
            <?= htmlspecialchars($product['category_name'] ?? 'Produkty') ?>
        </a> &rsaquo;
        <span><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <?php if ($message): ?>
        <div class="alert alert--<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Hlavný obsah -->
    <div class="product-detail">

        <!-- Obrázok -->
        <div class="product-detail__image">
            <?php if ($product['image_url']): ?>
                <img src="/shopko/public/<?= htmlspecialchars($product['image_url']) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="product-detail__placeholder">Bez obrázka</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="product-detail__info">
            <span class="product-detail__category">
                <?= htmlspecialchars($product['category_name'] ?? '') ?>
            </span>
            <h1 class="product-detail__name"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="product-detail__price"><?= number_format($product['price'], 2) ?> €</p>

            <p class="product-detail__stock">
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge badge--green">Na sklade (<?= $product['stock'] ?> ks)</span>
                <?php else: ?>
                    <span class="badge badge--red">Vypredané</span>
                <?php endif; ?>
            </p>

            <p class="product-detail__description">
                <?= nl2br(htmlspecialchars($product['description'] ?? '')) ?>
            </p>

            <?php if ($product['stock'] > 0): ?>
                <form action="/shopko/pages/cart_add.php" method="POST" class="product-detail__form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="form-group">
                        <label for="quantity">Množstvo</label>
                        <input type="number" id="quantity" name="quantity"
                               value="1" min="1" max="<?= $product['stock'] ?>"
                               style="width: 80px;">
                    </div>
                    <button type="submit" class="btn btn--dark" style="width: 260px;">
                        🛒 Pridať do košíka
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn--disabled" disabled>Vypredané</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
