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

$product = null;
if (isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id=$id LIMIT 1");
    $product = mysqli_fetch_assoc($result);
}

if (!$product) {
    echo "<p style='padding:40px;font-family:sans-serif'>Product not found. <a href='user_home.php'>Go back</a></p>";
    exit();
}

$stock     = (int)$product['stock'];
$dot_class = $stock > 10 ? '' : ($stock > 0 ? 'low' : 'out');
$stock_lbl = $stock > 0 ? $stock . ' in stock' : 'Out of stock';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> – Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
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
        body { font-family: 'DM Sans', sans-serif; background: var(--light-bg); color: var(--text); min-height: 100vh; }

        /* NAVBAR */
        .navbar {
            background: var(--dark); padding: 18px 48px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 999;
            box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
        .navbar .brand { display: flex; align-items: center; gap: 12px; }
        .navbar .brand span { font-family: 'Playfair Display', serif; font-size: 22px; color: #fff; }
        .navbar .brand .dot { color: var(--accent); }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .menu-container { position: relative; }
        .menu-btn {
            background: rgba(255,255,255,0.1); color: #fff;
            border: 1px solid rgba(255,255,255,0.15); padding: 9px 18px;
            border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;
            display: flex; align-items: center; gap: 8px; transition: background .2s;
            font-family: 'DM Sans', sans-serif;
        }
        .menu-btn:hover { background: rgba(255,255,255,0.18); }
        .menu-btn .arrow { font-size: 10px; transition: transform .3s; display: inline-block; }
        .menu-btn.open .arrow { transform: rotate(180deg); }
        .dropdown {
            position: absolute; top: calc(100% + 10px); left: 0;
            background: #fff; border-radius: 12px; box-shadow: 0 16px 48px rgba(0,0,0,0.18);
            min-width: 220px; opacity: 0; visibility: hidden;
            transform: translateY(-8px); transition: all .25s ease; overflow: hidden; z-index: 1000;
        }
        .dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown a {
            display: flex; align-items: center; gap: 12px; padding: 14px 18px;
            color: #333; text-decoration: none; font-size: 14px; font-weight: 500;
            border-left: 3px solid transparent; transition: all .2s;
        }
        .dropdown a:hover { background: #fff8f5; border-left-color: var(--accent); color: var(--accent); padding-left: 22px; }
        .dropdown a .menu-icon {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
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
            background: var(--mid); color: #ccc; padding: 7px 16px;
            border-radius: 30px; font-size: 13px; font-weight: 500;
        }
        .user-pill strong { color: #fff; }
        .logout-btn {
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; padding: 9px 22px; border-radius: 8px;
            text-decoration: none; font-weight: 500; font-size: 14px;
            transition: opacity .2s, transform .2s;
        }
        .logout-btn:hover { opacity: .88; transform: translateY(-1px); }

        /* BREADCRUMB */
        .breadcrumb {
            background: var(--card-bg); border-bottom: 1px solid var(--border);
            padding: 14px 48px; display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--muted);
        }
        .breadcrumb a { color: var(--accent); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* MAIN CONTENT */
        .container {
            max-width: 1100px; margin: 0 auto; padding: 48px 24px;
        }
        .product-layout {
            display: grid; grid-template-columns: 1fr 1fr; gap: 48px;
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.06);
        }

        /* IMAGE SIDE */
        .img-side {
            background: #f0ede8; display: flex; align-items: center;
            justify-content: center; min-height: 440px; position: relative; overflow: hidden;
        }
        .img-side::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 4px; background: linear-gradient(90deg, var(--accent2), var(--accent));
        }
        .img-side img { max-width: 85%; max-height: 380px; object-fit: contain; }
        .img-side .no-img { font-size: 80px; opacity: .3; }

        /* INFO SIDE */
        .info-side { padding: 44px 44px 44px 0; display: flex; flex-direction: column; gap: 18px; }
        .cat-tag {
            display: inline-block; background: #f0eee9; color: var(--muted);
            font-size: 11px; text-transform: uppercase; letter-spacing: .8px;
            padding: 5px 12px; border-radius: 5px; align-self: flex-start;
        }
        .info-side h1 {
            font-family: 'Playfair Display', serif; font-size: 30px;
            color: var(--dark); line-height: 1.2;
        }
        .price-row { display: flex; align-items: baseline; gap: 10px; }
        .price { font-size: 36px; font-weight: 700; color: var(--accent); }
        .price-label { font-size: 13px; color: var(--muted); }

        .stock-row { display: flex; align-items: center; gap: 8px; }
        .stock-dot { width: 10px; height: 10px; border-radius: 50%; background: #34c46a; }
        .stock-dot.low { background: #f5a623; }
        .stock-dot.out { background: #ff6b6b; }
        .stock-row span { font-size: 14px; color: var(--muted); }

        .divider { height: 1px; background: var(--border); }

        .desc-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
        .desc-text { font-size: 15px; line-height: 1.7; color: #555; }

        /* ACTIONS */
        .actions { display: flex; gap: 12px; margin-top: 8px; }
        .btn-cart {
            flex: 1; padding: 14px 0;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; border: none; border-radius: 9px; font-size: 15px;
            font-weight: 600; cursor: pointer; text-align: center;
            text-decoration: none; transition: opacity .2s, transform .2s;
            font-family: 'DM Sans', sans-serif; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-cart:hover { opacity: .88; transform: translateY(-2px); }
        .btn-cart.out-of-stock { background: #ddd; color: #999; cursor: not-allowed; pointer-events: none; }
        .btn-back {
            padding: 14px 22px; border: 1px solid var(--border); background: #fff;
            color: var(--text); border-radius: 9px; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: all .2s; font-family: 'DM Sans', sans-serif;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { background: var(--light-bg); border-color: var(--accent); color: var(--accent); }

        /* META BADGES */
        .meta-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .meta-badge {
            background: var(--light-bg); border: 1px solid var(--border);
            border-radius: 8px; padding: 10px 16px; font-size: 12px; color: var(--muted);
            display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 90px;
        }
        .meta-badge strong { font-size: 14px; color: var(--dark); font-weight: 600; }

        @media (max-width: 768px) {
            .navbar { padding: 16px 20px; }
            .breadcrumb { padding: 12px 20px; }
            .product-layout { grid-template-columns: 1fr; }
            .img-side { min-height: 280px; }
            .info-side { padding: 28px 24px; }
            .container { padding: 24px 16px; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">
        <span>🛍️ Shop<span class="dot">.</span>Go</span>
    </div>
    <div class="nav-right">
        <div class="menu-container">
            <button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
                ☰ Menu <span class="arrow">▼</span>
            </button>
            <div class="dropdown" id="dropdownMenu">
                <a href="user_home.php">
                    <div class="menu-icon">🛍️</div>
                    <div class="menu-label"><span class="title">Home</span></div>
                </a>
                <a href="invoice.php">
                    <div class="menu-icon">🧾</div>
                    <div class="menu-label"><span class="title">Invoice</span><span class="sub">View your billing</span></div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="my_orders.php">
                    <div class="menu-icon">📦</div>
                    <div class="menu-label"><span class="title">My Orders</span><span class="sub">Track your purchases</span></div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="product_description.php">
                    <div class="menu-icon">🔍</div>
                    <div class="menu-label"><span class="title">Product Description</span><span class="sub">Detailed product info</span></div>
                </a>
                <div class="dropdown-divider"></div>
                <a href="cart.php">
                    <div class="menu-icon">🛒</div>
                    <div class="menu-label"><span class="title">Add to Cart</span><span class="sub">View your cart</span></div>
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

<div class="breadcrumb">
    <a href="user_home.php">🏠 Home</a>
    <span>›</span>
    <span><?php echo htmlspecialchars($product['category']); ?></span>
    <span>›</span>
    <span><?php echo htmlspecialchars($product['product_name']); ?></span>
</div>

<div class="container">
    <div class="product-layout">
        <!-- Image -->
        <div class="img-side">
            <?php if ($product['image']): ?>
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <span class="no-img">🖼️</span>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="info-side">
            <span class="cat-tag"><?php echo htmlspecialchars($product['category']); ?></span>
            <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

            <div class="price-row">
                <span class="price">₹<?php echo number_format((float)$product['price'], 2); ?></span>
                <span class="price-label">incl. all taxes</span>
            </div>

            <div class="stock-row">
                <div class="stock-dot <?php echo $dot_class; ?>"></div>
                <span><?php echo $stock_lbl; ?></span>
            </div>

            <div class="divider"></div>

            <div class="meta-row">
                <div class="meta-badge">
                    <span>Product ID</span>
                    <strong>#<?php echo $product['id']; ?></strong>
                </div>
                <div class="meta-badge">
                    <span>Category</span>
                    <strong><?php echo htmlspecialchars($product['category']); ?></strong>
                </div>
                <div class="meta-badge">
                    <span>Stock</span>
                    <strong><?php echo $stock; ?> units</strong>
                </div>
            </div>

            <div class="divider"></div>

            <div class="desc-label">Description</div>
            <div class="desc-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>

            <div class="actions">
                <a href="user_home.php" class="btn-back">← Back</a>
                <?php if ($stock > 0): ?>
                    <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn-cart">🛒 Add to Cart</a>
                <?php else: ?>
                    <span class="btn-cart out-of-stock">✕ Out of Stock</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMenu() {
    document.getElementById('menuBtn').classList.toggle('open');
    document.getElementById('dropdownMenu').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!document.querySelector('.menu-container').contains(e.target)) {
        document.getElementById('menuBtn').classList.remove('open');
        document.getElementById('dropdownMenu').classList.remove('open');
    }
});
</script>
</body>
</html>