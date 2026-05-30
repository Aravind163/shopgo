<?php
session_start();

if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

include("config.php");

if (isset($_SESSION['user'])) {
    $name     = $_SESSION['user'];
    $userType = 'user';
} else {
    $name     = $_SESSION['admin'];
    $userType = 'admin';
}

// Fetch all products grouped by category
$all_products   = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$total_products = mysqli_num_rows($all_products);

$electronics = mysqli_query($conn, "SELECT * FROM products WHERE category='Electronics' ORDER BY id DESC");
$clothing    = mysqli_query($conn, "SELECT * FROM products WHERE category='Clothing'    ORDER BY id DESC");
$books       = mysqli_query($conn, "SELECT * FROM products WHERE category='Books'       ORDER BY id DESC");
$furniture   = mysqli_query($conn, "SELECT * FROM products WHERE category='Furniture'  ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop – Browse Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent:   #f2630a;
            --accent2:  #ff6b6b;
            --dark:     #1a1a2e;
            --mid:      #2d2d44;
            --light-bg: #f7f5f2;
            --card-bg:  #ffffff;
            --text:     #2c2c2c;
            --muted:    #888;
            --border:   #e8e4df;
            --radius:   14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--light-bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--dark);
            padding: 18px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
        .navbar .brand { display: flex; align-items: center; gap: 12px; }
        .navbar .brand span {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: #fff;
            letter-spacing: .5px;
        }
        .navbar .brand .dot { color: var(--accent); }
        .nav-right { display: flex; align-items: center; gap: 20px; }

        /* ── DROPDOWN MENU ── */
        .menu-container { position: relative; }
        .menu-btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background .2s;
            font-family: 'DM Sans', sans-serif;
        }
        .menu-btn:hover { background: rgba(255,255,255,0.18); }
        .menu-btn .arrow {
            font-size: 10px;
            transition: transform .3s;
            display: inline-block;
        }
        .menu-btn.open .arrow { transform: rotate(180deg); }

        .dropdown {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.18);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all .25s ease;
            overflow: hidden;
            z-index: 1000;
        }
        .dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .dropdown a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .2s;
        }
        .dropdown a:hover {
            background: #fff8f5;
            border-left-color: var(--accent);
            color: var(--accent);
            padding-left: 22px;
        }
        .dropdown a .menu-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .dropdown a:nth-child(1) .menu-icon { background: #fff0e8; }
        .dropdown a:nth-child(2) .menu-icon { background: #e8f5ff; }
        .dropdown a:nth-child(3) .menu-icon { background: #edf7ee; }
        .dropdown a:nth-child(4) .menu-icon { background: #fff3e0; }
        .dropdown a .menu-label { display: flex; flex-direction: column; }
        .dropdown a .menu-label .title { font-weight: 500; font-size: 14px; }
        .dropdown a .menu-label .sub { font-size: 11px; color: #aaa; font-weight: 400; margin-top: 1px; }
        .dropdown-divider { height: 1px; background: #f0ede8; margin: 0 14px; }

        .user-pill {
            background: var(--mid);
            color: #ccc;
            padding: 7px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .3px;
        }
        .user-pill strong { color: #fff; }
        .logout-btn {
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff;
            padding: 9px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: opacity .2s, transform .2s;
        }
        .logout-btn:hover { opacity: .88; transform: translateY(-1px); }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, var(--dark) 0%, var(--mid) 100%);
            color: #fff;
            padding: 52px 48px 44px;
            position: relative;
            overflow: hidden;
        }
        .hero::after {
            content: '';
            position: absolute;
            right: -80px; top: -80px;
            width: 340px; height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(242,99,10,.25) 0%, transparent 70%);
        }
        .hero h2 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            line-height: 1.15;
            margin-bottom: 10px;
        }
        .hero h2 em { color: var(--accent); font-style: normal; }
        .hero p { color: #aaa; font-size: 15px; font-weight: 300; }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            gap: 0;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 0 48px;
        }
        .stat-item {
            padding: 18px 32px 18px 0;
            margin-right: 32px;
            border-right: 1px solid var(--border);
        }
        .stat-item:last-child { border: none; }
        .stat-item .num {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: var(--accent);
        }
        .stat-item .lbl {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── MAIN ── */
        .container {
            max-width: 1360px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .section-header .icon-box {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .section-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--dark);
        }
        .section-header .count-badge {
            background: var(--light-bg);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .products-section { margin-bottom: 56px; }

        /* ── GRID ── */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 22px;
        }

        /* ── PRODUCT CARD ── */
        .product-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            display: flex;
            flex-direction: column;
            transition: transform .25s, box-shadow .25s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .product-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent2), var(--accent));
            opacity: 0;
            transition: opacity .25s;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0,0,0,.09);
        }
        .product-card:hover::before { opacity: 1; }

        .product-img-wrap {
            width: 100%;
            height: 200px;
            background: #f0ede8;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            transition: transform .4s ease;
            display: block;
        }
        .product-card:hover .product-img-wrap img { transform: scale(1.06); }
        .product-img-wrap .no-img-placeholder {
            font-size: 52px;
            opacity: .35;
        }

        .product-body {
            padding: 18px 20px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-card h4 {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .cat-tag {
            display: inline-block;
            background: #f0eee9;
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            padding: 4px 10px;
            border-radius: 5px;
            margin-bottom: 12px;
        }
        .price {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .stock-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }
        .stock-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #34c46a;
            flex-shrink: 0;
        }
        .stock-dot.low { background: #f5a623; }
        .stock-dot.out { background: #ff6b6b; }
        .stock-row span { font-size: 12px; color: var(--muted); }
        .description {
            font-size: 13px;
            color: #999;
            line-height: 1.5;
            flex: 1;
            margin-top: 4px;
        }

        /* ── CARD ACTION BUTTONS ── */
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .btn-desc {
            flex: 1;
            padding: 8px 0;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            border-radius: 7px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all .2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-desc:hover { background: var(--light-bg); border-color: var(--accent); color: var(--accent); }
        .btn-cart {
            flex: 1;
            padding: 8px 0;
            border: none;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: opacity .2s, transform .2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-cart:hover { opacity: .88; transform: translateY(-1px); }
        .btn-cart.out-of-stock { background: #ddd; color: #999; cursor: not-allowed; pointer-events: none; }

        /* ── EMPTY STATE ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: var(--card-bg);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            color: var(--muted);
            font-size: 14px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .navbar  { padding: 16px 20px; }
            .hero    { padding: 36px 20px 30px; }
            .hero h2 { font-size: 28px; }
            .stats-bar { padding: 0 20px; overflow-x: auto; }
            .container { padding: 28px 16px; }
            .products-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .products-grid { grid-template-columns: 1fr; }
            .nav-right { gap: 10px; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">
        <span>🛍️ Shop<span class="dot">. </span>Go</span>
    </div>
    <div class="nav-right">

        <!-- Dropdown Menu -->
        <div class="menu-container">
            <button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
                ☰ Menu <span class="arrow">▼</span>
            </button>
            <div class="dropdown" id="dropdownMenu">
                <a href="user_home.php">
                    <div class="menu-icon">🛍️</div>
                    <div class="menu-label">
                        <span class="title">Home</span>
                        
                    </div>
                <a href="invoice.php">
                    <div class="menu-icon">🧾</div>
                    <div class="menu-label">
                        <span class="title">Invoice</span>
                        <span class="sub">View your billing</span>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="my_orders.php">
                    <div class="menu-icon">📦</div>
                    <div class="menu-label">
                        <span class="title">My Orders</span>
                        <span class="sub">Track your purchases</span>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="product_description.php">
                    <div class="menu-icon">🔍</div>
                    <div class="menu-label">
                        <span class="title">Product Description</span>
                        <span class="sub">Detailed product info</span>
                    </div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="cart.php">
                    <div class="menu-icon">🛒</div>
                    <div class="menu-label">
                        <span class="title">Add to Cart</span>
                        <span class="sub">View your cart</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="user-pill">
            👤 <strong><?php echo htmlspecialchars($name); ?></strong>
            &nbsp;·&nbsp; <?php echo $userType === 'admin' ? '🔐 Admin' : 'Customer'; ?>
        </div>
        <a href="user_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="hero">
    <h2>Welcome back,<br><em><?php echo htmlspecialchars($name); ?></em></h2>
    <p>Browse our full product catalogue below. Prices are in Indian Rupees (₹).</p>
</div>

<div class="stats-bar">
    <div class="stat-item">
        <div class="num"><?php echo $total_products; ?></div>
        <div class="lbl">Total Products</div>
    </div>
    <div class="stat-item">
        <div class="num"><?php echo mysqli_num_rows($electronics); ?></div>
        <div class="lbl">Electronics</div>
    </div>
    <div class="stat-item">
        <div class="num"><?php echo mysqli_num_rows($clothing); ?></div>
        <div class="lbl">Clothing</div>
    </div>
    <div class="stat-item">
        <div class="num"><?php echo mysqli_num_rows($books); ?></div>
        <div class="lbl">Books</div>
    </div>
    <div class="stat-item">
        <div class="num"><?php echo mysqli_num_rows($furniture); ?></div>
        <div class="lbl">Furniture</div>
    </div>
</div>

<div class="container">

    <?php
    function render_products($result) {
        if (mysqli_num_rows($result) === 0) {
            echo "<div class='empty-state'>No products in this category yet.</div>";
            return;
        }
        echo "<div class='products-grid'>";
        while ($p = mysqli_fetch_assoc($result)) {
            $stock     = (int)$p['stock'];
            $dot_class = $stock > 10 ? '' : ($stock > 0 ? 'low' : 'out');
            $stock_lbl = $stock > 0 ? $stock . ' in stock' : 'Out of stock';
            $cart_class = $stock === 0 ? 'btn-cart out-of-stock' : 'btn-cart';
            $cart_lbl   = $stock === 0 ? '✕ Out of Stock' : '🛒 Add to Cart';

            $img_block = ($p['image'])
                ? "<img src='" . htmlspecialchars($p['image']) . "' alt='" . htmlspecialchars($p['product_name']) . "'>"
                : "<span class='no-img-placeholder'>🖼️</span>";

            echo "
            <div class='product-card' onclick=\"window.location='cart.php?add=" . $p['id'] . "'\" title='Add to Cart'>
                <div class='product-img-wrap'>{$img_block}</div>
                <div class='product-body'>
                    <h4>" . htmlspecialchars($p['product_name']) . "</h4>
                    <div class='cat-tag'>" . htmlspecialchars($p['category']) . "</div>
                    <div class='price'>₹" . number_format((float)$p['price'], 2) . "</div>
                    <div class='stock-row'>
                        <div class='stock-dot {$dot_class}'></div>
                        <span>{$stock_lbl}</span>
                    </div>
                    <div class='description'>" . htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '…' : '') . "</div>
                </div>
            </div>";
        }
        echo "</div>";
    }
    ?>

    <div class="products-section">
        <div class="section-header">
            <div class="icon-box">📦</div>
            <h3>All Products</h3>
            <span class="count-badge"><?php echo $total_products; ?> items</span>
        </div>
        <?php render_products($all_products); ?>
    </div>

    <div class="products-section">
        <div class="section-header">
            <div class="icon-box">⚡</div>
            <h3>Electronics</h3>
            <span class="count-badge"><?php echo mysqli_num_rows($electronics); ?> items</span>
        </div>
        <?php render_products($electronics); ?>
    </div>

    <div class="products-section">
        <div class="section-header">
            <div class="icon-box">👕</div>
            <h3>Clothing</h3>
            <span class="count-badge"><?php echo mysqli_num_rows($clothing); ?> items</span>
        </div>
        <?php render_products($clothing); ?>
    </div>

    <div class="products-section">
        <div class="section-header">
            <div class="icon-box">📚</div>
            <h3>Books</h3>
            <span class="count-badge"><?php echo mysqli_num_rows($books); ?> items</span>
        </div>
        <?php render_products($books); ?>
    </div>

    <div class="products-section">
        <div class="section-header">
            <div class="icon-box">🪑</div>
            <h3>Furniture</h3>
            <span class="count-badge"><?php echo mysqli_num_rows($furniture); ?> items</span>
        </div>
        <?php render_products($furniture); ?>
    </div>

</div>

<script>
function toggleMenu() {
    const btn  = document.getElementById('menuBtn');
    const menu = document.getElementById('dropdownMenu');
    btn.classList.toggle('open');
    menu.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const container = document.querySelector('.menu-container');
    if (!container.contains(e.target)) {
        document.getElementById('menuBtn').classList.remove('open');
        document.getElementById('dropdownMenu').classList.remove('open');
    }
});
</script>
</body>
</html>