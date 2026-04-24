<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$db = getDB();

// Filtre z URL
$catId  = (int)($_GET['cat']    ?? 0);
$search = trim($_GET['q']       ?? '');
$sort   = $_GET['sort']         ?? 'newest';

// Načítaj kategórie pre filter
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Zostav SQL
$sql    = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
$params = [];

if ($catId) {
    $sql    .= " AND p.category_id = ?";
    $params[] = $catId;
}
if ($search) {
    $sql    .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY p.price ASC";  break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    default:           $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Názov aktívnej kategórie
$activeCat = '';
if ($catId) {
    foreach ($categories as $cat) {
        if ($cat['id'] === $catId) { $activeCat = $cat['name']; break; }
    }
}

$pageTitle = ($activeCat ?: ($search ? "Hľadanie: $search" : 'Všetky produkty')) . ' – SHOPKO';
?>

<div class="container" style="padding: 28px 24px;">

    <!-- Nadpis + počet -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <h1 class="section-title" style="margin:0">
            <?= $activeCat ?: ($search ? "Výsledky: „$search"" : 'Všetky produkty') ?>
            <span style="font-size:15px;font-weight:400;color:#888;margin-left:8px;">
                (<?= count($products) ?> produktov)
            </span>
        </h1>

        <!-- Zoradenie -->
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <?php if ($catId):  ?><input type="hidden" name="cat" value="<?= $catId ?>"> <?php endif; ?>
            <?php if ($search): ?><input type="hidden" name="q"   value="<?= htmlspecialchars($search) ?>"> <?php endif; ?>
            <label style="font-size:13px;color:#555;">Zoradiť:</label>
            <select name="sort" onchange="this.form.submit()"
                    style="padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px;">
                <option value="newest"     <?= $sort==='newest'     ?'selected':'' ?>>Najnovšie</option>
                <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':'' ?>>Cena: nízka → vysoká</option>
                <option value="price_desc" <?= $sort==='price_desc' ?'selected':'' ?>>Cena: vysoká → nízka</option>
            </select>
        </form>
    </div>

    <div class="products-layout">

        <!-- SIDEBAR – kategórie -->
        <aside class="products-sidebar">
            <h3>Kategórie</h3>
            <ul>
                <li>
                    <a href="/shopko/pages/products.php" class="<?= !$catId ? 'active' : '' ?>">
                        Všetky produkty
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/shopko/pages/products.php?cat=<?= $cat['id'] ?>"
                           class="<?= $catId === $cat['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- PRODUKTOVÁ MRIEŽKA -->
        <div>
            <?php if (empty($products)): ?>
                <div style="padding:60px;text-align:center;color:#888;background:#fff;border-radius:8px;border:1px solid #e8e8e8;">
                    Žiadne produkty sa nenašli.
                </div>
            <?php else: ?>
                <div class="products__grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="/shopko/pages/product.php?id=<?= $product['id'] ?>">
                                <div class="product-card__image">
                                    <?php if ($product['image_url']): ?>
                                        <img src="/shopko/public/<?= htmlspecialchars($product['image_url']) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="product-card__placeholder">Bez obrázka</div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <div class="product-card__body">
                                <span class="product-card__category">
                                    <?= htmlspecialchars($product['category_name'] ?? '') ?>
                                </span>
                                <h3 class="product-card__name">
                                    <a href="/shopko/pages/product.php?id=<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>
                                <p class="product-card__price"><?= number_format($product['price'], 2) ?> €</p>
                                <?php if ($product['stock'] > 0): ?>
                                    <form action="/shopko/pages/cart_add.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity"   value="1">
                                        <button type="submit" class="btn btn--dark btn--full">
                                            Pridať do košíka
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn--disabled btn--full" disabled>Vypredané</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
