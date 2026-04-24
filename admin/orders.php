<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'auth_check.php';


$db = getDB();

// Aktualizuj stav objednávky
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $status  = $_POST['status'];
    if (in_array($status, $allowed)) {
        $db->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$status, (int)$_POST['order_id']]);
    }
    header('Location: /shopko/admin/orders.php');
    exit;
}

// Načítaj objednávky
$orders = $db->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll();

$statusLabels = [
    'pending'    => ['label' => 'Nová',        'badge' => 'badge--yellow'],
    'processing' => ['label' => 'Spracováva sa','badge' => 'badge--yellow'],
    'shipped'    => ['label' => 'Odoslaná',    'badge' => 'badge--green'],
    'delivered'  => ['label' => 'Doručená',    'badge' => 'badge--green'],
    'cancelled'  => ['label' => 'Zrušená',     'badge' => 'badge--red'],
];
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Objednávky – SHOPKO Admin</title>
    <link rel="stylesheet" href="/shopko/public/css/style.css">
    <link rel="stylesheet" href="/shopko/public/css/admin.css">
</head>
<body class="admin-body">

<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">SHOPKO</div>
    <nav class="admin-sidebar__nav">
        <a href="/shopko/admin/index.php">📦 Produkty</a>
        <a href="/shopko/admin/orders.php" class="active">📋 Objednávky</a>
        <a href="/shopko/admin/product_add.php">➕ Pridať produkt</a>
    </nav>
    <a href="/shopko/pages/logout.php" class="admin-sidebar__logout">Odhlásiť sa</a>
</aside>

<main class="admin-main">
    <div class="admin-toolbar">
        <h1>Objednávky</h1>
    </div>

    <?php if (empty($orders)): ?>
        <div style="background:#fff;border:1px solid #e8e8e8;border-radius:8px;padding:48px;text-align:center;color:#888;">
            Zatiaľ žiadne objednávky.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Zákazník</th>
                <th>Adresa</th>
                <th>Celkom</th>
                <th>Dátum</th>
                <th>Stav</th>
                <th>Zmeniť stav</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <?php $s = $statusLabels[$order['status']] ?? ['label'=>$order['status'],'badge'=>'badge--yellow']; ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($order['user_name'] ?? 'Hosť') ?></strong><br>
                        <small style="color:#888"><?= htmlspecialchars($order['user_email'] ?? '') ?></small>
                    </td>
                    <td style="font-size:13px;color:#555">
                        <?= htmlspecialchars($order['shipping_address'] ?? '—') ?>
                    </td>
                    <td><strong><?= number_format($order['total_price'], 2) ?> €</strong></td>
                    <td style="font-size:13px;color:#888">
                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                    </td>
                    <td>
                        <span class="badge <?= $s['badge'] ?>"><?= $s['label'] ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" style="padding:5px 8px;border:1px solid #ccc;border-radius:4px;font-size:13px;">
                                <?php foreach ($statusLabels as $val => $info): ?>
                                    <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>>
                                        <?= $info['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn--dark" style="padding:5px 12px;font-size:12px;">
                                Uložiť
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

</body>
</html>
