-- ============================================
-- SHOPKO - dbs
-- ============================================

USE shopko;

-- --------------------------------------------
-- 1. USERS
-- --------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 2. CATEGORIES
-- --------------------------------------------
CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)        NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 3. PRODUCTS
-- --------------------------------------------
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200)        NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)       NOT NULL,
    stock       INT                 NOT NULL DEFAULT 0,
    category_id INT,
    image_url   VARCHAR(300),
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 4. ORDERS
-- --------------------------------------------
CREATE TABLE orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT,
    total_price      DECIMAL(10,2)   NOT NULL,
    status           ENUM('pending','processing','shipped','delivered','cancelled')
                     NOT NULL DEFAULT 'pending',
    shipping_address VARCHAR(300),
    created_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 5. ORDER ITEMS
-- --------------------------------------------
CREATE TABLE order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT             NOT NULL,
    product_id  INT,
    quantity    INT             NOT NULL,
    unit_price  DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 6. CART ITEMS
-- --------------------------------------------
CREATE TABLE cart_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT             NOT NULL,
    product_id  INT             NOT NULL,
    quantity    INT             NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TESTOVACIE DÁTA
-- ============================================

-- Kategórie
INSERT INTO categories (name, description) VALUES
('Tričká',    'Každodenné a štýlové tričká'),
('Nohavice',  'Džínsy, chinos a elegantné nohavice'),
('Bundy',     'Zimné bundy, parky a vetrovky'),
('Šaty',      'Letné a spoločenské šaty'),
('Topánky',   'Tenisky, čižmy a sandále'),
('Doplnky',   'Čiapky, šály a ďalšie doplnky');

-- Admin používateľ (heslo: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@shopko.sk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Testovacie produkty
INSERT INTO products (name, description, price, stock, category_id, image_url) VALUES
('Biele tričko – Basic Fit',   '100% bavlna, prané pri 30°C',              19.99,  42, 1, 'images/tricko_biele.jpg'),
('Čierne tričko – Slim',       'Slim fit, 95% bavlna, 5% elastan',         22.99,  35, 1, 'images/tricko_cierne.jpg'),
('Čierne džínsy – Slim Fit',   'Slim džínsy, pohodlné nosenie',            39.99,  18, 2, 'images/jeans_cierne.jpg'),
('Béžové chinos',              'Klasické chinos nohavice',                 44.99,  25, 2, 'images/chinos_bezove.jpg'),
('Zimná bunda – Parka',        'Vodoodpudivá, teplá výplň',                89.99,   0, 3, 'images/bunda_parka.jpg'),
('Kožená bunda – Biker',       'Eko koža, zipsové vrecká',                 99.99,   5, 3, 'images/bunda_kozena.jpg'),
('Letné šaty – Floral',        'Ľahká látka, kvetinový vzor',              49.99,   7, 4, 'images/saty_floral.jpg'),
('Minimalistické šaty',        'Jednofarebné, vhodné do práce',            59.99,  12, 4, 'images/saty_mini.jpg'),
('Sivá mikina – Oversize',     'Oversize striih, mäkká látka',             34.99,  24, 1, 'images/mikina_siva.jpg'),
('Elegantné nohavice',         'Formálny štýl, pohodlný strih',            59.99,  31, 2, 'images/nohavice_elegant.jpg');
