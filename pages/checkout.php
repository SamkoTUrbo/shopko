<?php
require_once '../config/database.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /shopko/pages/login.php');
    exit;
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// Načítaj košík
$stmt = $db->prepare("
    SELECT ci.id, ci.quantity, p.id AS product_id, p.name, p.price, p.stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// Ak je košík prázdny
if (empty($items)) {
    header('Location: /shopko/pages/cart.php');
    exit;
}

$total     = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$shipping  = 3.90;
$grandTotal = $total + $shipping;

$error   = '';
$success = false;

// Spracuj objednávku
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $street    = trim($_POST['street']     ?? '');
    $city      = trim($_POST['city']       ?? '');
    $zip       = trim($_POST['zip']        ?? '');

    if (!$firstName || !$lastName || !$street || !$city || !$zip) {
        $error = 'Vyplň všetky povinné polia.';
    } else {
        $address = "$firstName $lastName, $street, $zip $city";

        try {
            $db->beginTransaction();

            // Vytvor objednávku
            $stmt = $db->prepare("
                INSERT INTO orders (user_id, total_price, status, shipping_address)
                VALUES (?, ?, 'pending', ?)
            ");
            $stmt->execute([$userId, $grandTotal, $address]);
            $orderId = $db->lastInsertId();

            // Pridaj položky a znížiž sklad
            foreach ($items as $item) {
                $db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ")->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

                $db->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
                ")->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Vymaž košík
            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);

            $db->commit();
            $success  = true;
            $orderId  = $orderId;

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Nastala chyba pri spracovaní objednávky. Skús znova.';
        }
    }
}

$pageTitle = 'Pokladňa – SHOPKO';
?>

<div class="container" style="padding: 32px 24px;">
    <h1 class="section-title">Dokončenie objednávky</h1>

    <?php if ($success): ?>
        <!-- ÚSPECH -->
        <div style="text-align:center;padding:60px 0;">
            <div style="font-size:64px;margin-bottom:16px;">✅</div>
            <h2 style="font-size:26px;font-weight:700;margin-bottom:12px;">Objednávka prijatá!</h2>
            <p style="color:#555;font-size:16px;margin-bottom:8px;">
                Číslo objednávky: <strong>#<?= $orderId ?></strong>
            </p>
            <p style="color:#888;font-size:14px;margin-bottom:32px;">
                Ďakujeme za nákup. O stave objednávky ťa budeme informovať.
            </p>
            <a href="/shopko/index.php" class="btn btn--dark">Pokračovať v nákupe</a>
        </div>

    <?php else: ?>
        <div class="checkout-layout">

            <!-- FORMULÁR -->
            <div class="checkout-form">
                <h3 style="margin-bottom:20px;font-size:18px;">Doručovacia adresa</h3>

                <?php if ($error): ?>
                    <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Meno *</label>
                            <input type="text" name="first_name"
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                   required placeholder="Ján">
                        </div>
                        <div class="form-group">
                            <label>Priezvisko *</label>
                            <input type="text" name="last_name"
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                   required placeholder="Novák">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ulica a číslo *</label>
                        <input type="text" name="street"
                               value="<?= htmlspecialchars($_POST['street'] ?? '') ?>"
                               required placeholder="Hlavná 12">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
                        <div class="form-group">
                            <label>PSČ *</label>
                            <input type="text" name="zip"
                                   value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>"
                                   required placeholder="010 01">
                        </div>
                        <div class="form-group">
                            <label>Mesto *</label>
                            <input type="text" name="city"
                                   value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
                                   required placeholder="Žilina">
                        </div>
                    </div>

                    <!-- Zhrnutie v mobile -->
                    <div style="background:#f8f8f8;border:1px solid #e8e8e8;border-radius:8px;padding:20px;margin:24px 0;">
                        <h4 style="margin-bottom:14px;font-size:15px;">Zhrnutie</h4>
                        <?php foreach ($items as $item): ?>
                            <div style="display:flex;justify-content:space-between;font-size:13px;color:#555;padding:4px 0;">
                                <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                                <span><?= number_format($item['price'] * $item['quantity'], 2) ?> €</span>
                            </div>
                        <?php endforeach; ?>
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:#888;padding:8px 0 4px;border-top:1px solid #e0e0e0;margin-top:8px;">
                            <span>Doprava</span><span><?= number_format($shipping, 2) ?> €</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding:8px 0 0;">
                            <span>Celkom</span><span><?= number_format($grandTotal, 2) ?> €</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn--dark btn--full" style="font-size:16px;padding:14px;">
                        ✅ Potvrdiť objednávku
                    </button>
                    <a href="/shopko/pages/cart.php" style="display:block;text-align:center;margin-top:12px;font-size:13px;color:#888;">
                        ← Späť do košíka
                    </a>
                </form>
            </div>

            <!-- ZHRNUTIE OBJEDNÁVKY -->
            <div class="cart-summary">
                <h3>Objednávka</h3>
                <?php foreach ($items as $item): ?>
                    <div class="cart-summary__row">
                        <span><?= htmlspecialchars($item['name']) ?> <small style="color:#888">×<?= $item['quantity'] ?></small></span>
                        <span><?= number_format($item['price'] * $item['quantity'], 2) ?> €</span>
                    </div>
                <?php endforeach; ?>
                <div class="cart-summary__row">
                    <span>Doprava</span>
                    <span><?= number_format($shipping, 2) ?> €</span>
                </div>
                <div class="cart-summary__row cart-summary__total">
                    <span>Celkom</span>
                    <span><?= number_format($grandTotal, 2) ?> €</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
