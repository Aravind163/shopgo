<?php
session_start();

if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

include("config.php");

// Handle Buy Now
if (isset($_POST['buy_now'])) {
    $product_id = intval($_POST['product_id']);
    $quantity   = max(1, intval($_POST['quantity']));
    $user_id    = $_SESSION['user_id'] ?? 0;

    $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id='$product_id'"));
    if ($prod && $prod['stock'] >= $quantity && $user_id) {
        $total = $prod['price'] * $quantity;
        mysqli_query($conn, "INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES ('$user_id','$product_id','$quantity','$total','pending')");
        $order_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO transactions (order_id, user_id, amount, payment_method, status) VALUES ('$order_id','$user_id','$total','COD','pending')");
        mysqli_query($conn, "UPDATE products SET stock=stock-$quantity WHERE id='$product_id'");
        $order_success = "✅ Order placed for <strong>" . htmlspecialchars($prod['product_name']) . "</strong>! Total: ₹" . number_format($total,2) . " (COD)";
    } else {
        $order_error = "❌ Could not place order. Item may be out of stock.";
    }
}

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

        /* ── PRODUCT IMAGE ── */
        .product-img-wrap {
            width: 100%;
            height: 200px;
            background: #ffffff;
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

        /* ── CARD BODY ── */
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

        .readonly-badge {
            margin-top: 18px;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ccc;
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

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
        }
        .buy-btn {
            margin-top: 14px;
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
        }
        .buy-btn:hover { opacity:.88; transform:translateY(-1px); }
        .buy-btn:disabled { opacity:.4; cursor:not-allowed; transform:none; }
        .qty-input { width:60px; padding:5px 8px; border:1px solid var(--border); border-radius:6px; font-size:13px; text-align:center; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">
        <span>🛍️ Shop<span class="dot">.</span></span>
    </div>
    <div class="nav-right">
        <div class="user-pill">
            👤 <strong><?php echo htmlspecialchars($name); ?></strong>
            &nbsp;·&nbsp; <?php echo $userType === 'admin' ? '🔐 Admin' : 'Customer'; ?>
        </div>
        <a href="user_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<?php if(isset($order_success)): ?>
<div style="background:#d4edda;color:#155724;padding:15px 48px;font-weight:bold;border-bottom:2px solid #28a745;"><?php echo $order_success; ?></div>
<?php endif; ?>
<?php if(isset($order_error)): ?>
<div style="background:#f8d7da;color:#721c24;padding:15px 48px;font-weight:bold;border-bottom:2px solid #ff6b6b;"><?php echo $order_error; ?></div>
<?php endif; ?>
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

            // Image block
            $img_block = ($p['image'] && file_exists(__DIR__ . '/' . $p['image']))
                ? "<img src='" . htmlspecialchars($p['image']) . "' alt='" . htmlspecialchars($p['product_name']) . "'>"
                : "<span class='no-img-placeholder'>🖼️</span>";

            echo "
            <div class='product-card'>
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
                    <form method='POST' style='margin-top:14px;'>
                        <input type='hidden' name='product_id' value='" . $p['id'] . "'>
                        <div style='display:flex;gap:8px;align-items:center;margin-bottom:8px;'>
                            <label style='font-size:12px;color:#999;'>Qty:</label>
                            <input type='number' name='quantity' value='1' min='1' max='" . $stock . "' class='qty-input'>
                        </div>
                        <button type='submit' name='buy_now' class='buy-btn' " . ($stock===0?"disabled":"") . ">
                            " . ($stock===0 ? '❌ Out of Stock' : '🛒 Buy Now (COD)') . "
                        </button>
                    </form>
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
</body>
</html>