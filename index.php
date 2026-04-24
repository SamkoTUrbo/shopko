<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$pageTitle = 'SHOPKO – Módne oblečenie';
$db = getDB();

// Načítaj kategórie
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Načítaj najnovšie produkty (max 8)
$products = $db->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll();
?>

<!-- HERO BANNER -->
<section class="hero">
    <div class="hero__content">
        <h1>Jarná kolekcia 2025</h1>
        <p>Štýlové oblečenie za výborné ceny</p>
        <a href="pages/products.php" class="btn btn--white">Nakupovať teraz</a>
    </div>
    <div class="hero__image">
        <img src="public/images/hero.jpg" alt="Hero banner">
    </div>
</section>

<!-- KATEGÓRIE -->
<section class="categories">
    <div class="container">
        <h2 class="section-title">Kategórie</h2>
        <div class="categories__grid">
            <?php foreach ($categories as $cat): ?>
                <a href="pages/products.php?cat=<?= $cat['id'] ?>" class="category-card">
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- NOVÉ PRODUKTY -->
<section class="products-section">
    <div class="container">
        <h2 class="section-title">Nové produkty</h2>
        <div class="products__grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <!-- Obrázok -->
                    <a href="pages/product.php?id=<?= $product['id'] ?>">
                        <div class="product-card__image">
                            <?php if ($product['image_url']): ?>
                                <img src="public/<?= htmlspecialchars($product['image_url']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <div class="product-card__placeholder">Bez obrázka</div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Info -->
                    <div class="product-card__body">
                        <span class="product-card__category">
                            <?= htmlspecialchars($product['category_name'] ?? '') ?>
                        </span>
                        <h3 class="product-card__name">
                            <a href="pages/product.php?id=<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['name']) ?>
                            </a>
                        </h3>
                        <p class="product-card__price"><?= number_format($product['price'], 2) ?> €</p>

                        <?php if ($product['stock'] > 0): ?>
                            <form action="pages/cart_add.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
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
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
