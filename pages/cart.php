<?php
require_once '../config/database.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /shopko/pages/login.php');
    exit;
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// Spracuj aktualizáciu množstva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $itemId => $qty) {
        $qty = (int)$qty;
        if ($qty < 1) {
            $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$itemId, $userId]);
        } else {
            $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$qty, $itemId, $userId]);
        }
    }
    header('Location: /shopko/pages/cart.php');
    exit;
}

// Vymaž položku
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$removeId, $userId]);
    header('Location: /shopko/pages/cart.php');
    exit;
}

// Načítaj položky košíka
$stmt = $db->prepare("
    SELECT ci.id, ci.quantity, p.id AS product_id, p.name, p.price, p.stock, p.image_url
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// Vypočítaj celkovú cenu
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
$shipping  = count($items) > 0 ? 3.90 : 0;
$grandTotal = $total + $shipping;

$pageTitle = 'Košík – SHOPKO';
?>

<div class="container" style="padding: 32px 24px;">
    <h1 class="section-title">Váš košík</h1>

    <?php if (empty($items)): ?>
        <div class="cart-empty">
            <p>Váš košík je prázdny.</p>
            <a href="/shopko/index.php" class="btn btn--dark" style="margin-top: 16px;">
                Pokračovať v nákupe
            </a>
        </div>
    <?php else: ?>

        <div class="cart-layout">
            <!-- Položky -->
            <div class="cart-items">
                <form method="POST">
                    <table class="cart-table">
                        <thead>
                        <tr>
                            <th colspan="2">Produkt</th>
                            <th>Cena</th>
                            <th>Množstvo</th>
                            <th>Spolu</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="cart-table__img">
                                    <?php if ($item['image_url']): ?>
                                        <img src="/shopko/public/<?= htmlspecialchars($item['image_url']) ?>"
                                             alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <div class="cart-table__placeholder"></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/shopko/pages/product.php?id=<?= $item['product_id'] ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($item['price'], 2) ?> €</td>
                                <td>
                                    <input type="number"
                                           name="quantity[<?= $item['id'] ?>]"
                                           value="<?= $item['quantity'] ?>"
                                           min="0"
                                           max="<?= $item['stock'] ?>"
                                           class="cart-qty-input">
                                </td>
                                <td><strong><?= number_format($item['price'] * $item['quantity'], 2) ?> €</strong></td>
                                <td>
                                    <a href="?remove=<?= $item['id'] ?>" class="cart-remove">✕</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 12px; display: flex; gap: 12px;">
                        <button type="submit" name="update" class="btn btn--outline">
                            Aktualizovať košík
                        </button>
                        <a href="/shopko/index.php" class="btn btn--outline">
                            ← Pokračovať v nákupe
                        </a>
                    </div>
                </form>
            </div>

            <!-- Zhrnutie -->
            <div class="cart-summary">
                <h3>Zhrnutie objednávky</h3>
                <div class="cart-summary__row">
                    <span>Medzisúčet</span>
                    <span><?= number_format($total, 2) ?> €</span>
                </div>
                <div class="cart-summary__row">
                    <span>Doprava</span>
                    <span><?= number_format($shipping, 2) ?> €</span>
                </div>
                <div class="cart-summary__row cart-summary__total">
                    <span>Celkom</span>
                    <span><?= number_format($grandTotal, 2) ?> €</span>
                </div>
                <a href="/shopko/pages/checkout.php" class="btn btn--dark btn--full" style="margin-top: 20px;">
                    Pokračovať k platbe
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
