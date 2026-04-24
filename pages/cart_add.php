<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Musí byť prihlásený
if (!isset($_SESSION['user_id'])) {
    header('Location: /shopko/pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /shopko/index.php');
    exit;
}

$db         = getDB();
$userId     = (int)$_SESSION['user_id'];
$productId  = (int)($_POST['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

if (!$productId) {
    header('Location: /shopko/index.php');
    exit;
}

// Skontroluj či produkt existuje a je na sklade
$stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['stock'] < 1) {
    $_SESSION['message']      = 'Produkt nie je dostupný.';
    $_SESSION['message_type'] = 'error';
    header('Location: /shopko/pages/cart.php');
    exit;
}

// Skontroluj či produkt už je v košíku
$stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
$existing = $stmt->fetch();

if ($existing) {
    // Aktualizuj množstvo
    $newQty = min($existing['quantity'] + $quantity, $product['stock']);
    $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQty, $existing['id']]);
} else {
    // Pridaj novú položku
    $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $productId, $quantity]);
}

$_SESSION['message']      = 'Produkt bol pridaný do košíka!';
$_SESSION['message_type'] = 'success';

// Vráť späť na stránku odkiaľ prišiel
$referer = $_SERVER['HTTP_REFERER'] ?? '/shopko/index.php';
header('Location: ' . $referer);
exit;
