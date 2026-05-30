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

// Initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add item
if (isset($_GET['add'])) {
    $pid = (int)$_GET['add'];
    $res = mysqli_query($conn, "SELECT * FROM products WHERE id=$pid LIMIT 1");
    $p   = mysqli_fetch_assoc($res);
    if ($p && (int)$p['stock'] > 0) {
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty']++;
        } else {
            $_SESSION['cart'][$pid] = [
                'id'    => $pid,
                'name'  => $p['product_name'],
                'price' => (float)$p['price'],
                'image' => $p['image'],
                'stock' => (int)$p['stock'],
                'qty'   => 1,
            ];
        }
    }
    header("Location: cart.php");
    exit();
}

// Remove item
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$pid]);
    header("Location: cart.php");
    exit();
}

// Update quantity
if (isset($_POST['update_qty']) || isset($_POST['checkout'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $pid = (int)$pid; $qty = (int)$qty;
        if (isset($_SESSION['cart'][$pid])) {
            if ($qty < 1) unset($_SESSION['cart'][$pid]);
            else $_SESSION['cart'][$pid]['qty'] = min($qty, $_SESSION['cart'][$pid]['stock']);
        }
    }
    if (isset($_POST['checkout'])) {
        header("Location: invoice.php");
    } else {
        header("Location: cart.php");
    }
    exit();
}

// Clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}

$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];
$item_count = array_sum(array_column($cart, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart – ShopGo</title>
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
            position: sticky; top: 0; z-index: 999; box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
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
        .menu-btn .arrow { font-size: 10px; transition: transform .3s; }
        .menu-btn.open .arrow { transform: rotate(180deg); }
        .dropdown {
            position: absolute; top: calc(100% + 10px); left: 0; background: #fff;
            border-radius: 12px; box-shadow: 0 16px 48px rgba(0,0,0,0.18);
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
        .dropdown a .menu-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .dropdown a:nth-child(1) .menu-icon { background: #fff0e8; }
        .dropdown a:nth-child(2) .menu-icon { background: #e8f5ff; }
        .dropdown a:nth-child(3) .menu-icon { background: #edf7ee; }
        .dropdown a:nth-child(4) .menu-icon { background: #fff3e0; }
        .dropdown a .menu-label { display: flex; flex-direction: column; }
        .dropdown a .menu-label .title { font-weight: 500; font-size: 14px; }
        .dropdown a .menu-label .sub { font-size: 11px; color: #aaa; margin-top: 1px; }
        .dropdown-divider { height: 1px; background: #f0ede8; margin: 0 14px; }
        .user-pill { background: var(--mid); color: #ccc; padding: 7px 16px; border-radius: 30px; font-size: 13px; }
        .user-pill strong { color: #fff; }
        .logout-btn {
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; padding: 9px 22px; border-radius: 8px; text-decoration: none;
            font-weight: 500; font-size: 14px; transition: opacity .2s, transform .2s;
        }
        .logout-btn:hover { opacity: .88; transform: translateY(-1px); }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, var(--dark) 0%, var(--mid) 100%);
            color: #fff; padding: 44px 48px 36px; position: relative; overflow: hidden;
        }
        .hero::after {
            content: ''; position: absolute; right: -80px; top: -80px;
            width: 340px; height: 340px; border-radius: 50%;
            background: radial-gradient(circle, rgba(242,99,10,.25) 0%, transparent 70%);
        }
        .hero h2 { font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 6px; }
        .hero h2 em { color: var(--accent); font-style: normal; }
        .hero p { color: #aaa; font-size: 14px; font-weight: 300; }

        /* MAIN */
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 24px; }
        .cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 28px; }

        /* CART ITEMS */
        .cart-items-box {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .cart-header {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .cart-header h3 { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--dark); }
        .clear-btn {
            font-size: 12px; color: var(--muted); text-decoration: none;
            padding: 5px 12px; border: 1px solid var(--border); border-radius: 6px;
            transition: all .2s;
        }
        .clear-btn:hover { color: var(--accent2); border-color: var(--accent2); }

        .cart-item {
            display: flex; align-items: center; gap: 18px; padding: 20px 24px;
            border-bottom: 1px solid var(--border); transition: background .2s;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item:hover { background: #fdfcfb; }
        .item-img {
            width: 72px; height: 72px; border-radius: 10px;
            background: #f0ede8; overflow: hidden; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border);
        }
        .item-img img { width: 100%; height: 100%; object-fit: contain; }
        .item-img .no-img { font-size: 28px; opacity: .3; }
        .item-info { flex: 1; min-width: 0; }
        .item-info h4 { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--dark); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-info .item-price { font-size: 13px; color: var(--muted); }
        .item-info .item-price strong { color: var(--accent); font-size: 15px; }
        .qty-control { display: flex; align-items: center; gap: 0; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .qty-btn {
            width: 30px; height: 30px; border: none; background: var(--light-bg);
            cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;
            color: var(--text); transition: background .2s;
        }
        .qty-btn:hover { background: var(--border); }
        .qty-input {
            width: 36px; height: 30px; border: none; border-left: 1px solid var(--border);
            border-right: 1px solid var(--border); text-align: center; font-size: 13px;
            font-family: 'DM Sans', sans-serif; color: var(--dark); background: #fff;
            -moz-appearance: textfield;
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; }
        .item-subtotal { font-size: 15px; font-weight: 600; color: var(--dark); min-width: 80px; text-align: right; }
        .remove-btn {
            color: #ddd; text-decoration: none; font-size: 18px; transition: color .2s;
            line-height: 1; margin-left: 6px;
        }
        .remove-btn:hover { color: var(--accent2); }

        /* EMPTY STATE */
        .empty-cart {
            padding: 60px 24px; text-align: center; color: var(--muted);
        }
        .empty-cart .empty-icon { font-size: 60px; margin-bottom: 16px; opacity: .4; }
        .empty-cart p { font-size: 15px; margin-bottom: 20px; }
        .empty-cart a {
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; padding: 12px 28px; border-radius: 9px; text-decoration: none;
            font-weight: 500; font-size: 14px; transition: opacity .2s;
        }
        .empty-cart a:hover { opacity: .88; }

        /* SUMMARY SIDEBAR */
        .summary-box {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 28px; height: fit-content;
            position: sticky; top: 80px;
        }
        .summary-box h3 { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--dark); margin-bottom: 20px; }
        .summary-line { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: var(--muted); }
        .summary-line.total { border-top: 1px solid var(--border); margin-top: 16px; padding-top: 16px; font-size: 18px; font-weight: 700; color: var(--dark); }
        .summary-line.total span:last-child { color: var(--accent); }
        .checkout-btn {
            width: 100%; padding: 14px; margin-top: 20px;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; border: none; border-radius: 9px; font-size: 15px;
            font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif;
            text-decoration: none; display: block; text-align: center;
            transition: opacity .2s, transform .2s;
        }
        .checkout-btn:hover { opacity: .88; transform: translateY(-2px); }
        .continue-btn {
            width: 100%; padding: 11px; margin-top: 10px;
            background: #fff; color: var(--text); border: 1px solid var(--border);
            border-radius: 9px; font-size: 14px; font-weight: 500; cursor: pointer;
            font-family: 'DM Sans', sans-serif; text-decoration: none;
            display: block; text-align: center; transition: all .2s;
        }
        .continue-btn:hover { background: var(--light-bg); border-color: var(--accent); color: var(--accent); }
        .secure-note { text-align: center; font-size: 11px; color: var(--muted); margin-top: 16px; }

        @media (max-width: 768px) {
            .navbar { padding: 16px 20px; }
            .hero { padding: 32px 20px; }
            .cart-layout { grid-template-columns: 1fr; }
            .summary-box { position: static; }
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
                <a href="user_home.php"><div class="menu-icon">🛍️</div><div class="menu-label"><span class="title">Home</span></div></a>
                <a href="invoice.php"><div class="menu-icon">🧾</div><div class="menu-label"><span class="title">Invoice</span><span class="sub">View your billing</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="my_orders.php"><div class="menu-icon">📦</div><div class="menu-label"><span class="title">My Orders</span><span class="sub">Track your purchases</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="product_description.php"><div class="menu-icon">🔍</div><div class="menu-label"><span class="title">Product Description</span><span class="sub">Detailed product info</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="cart.php"><div class="menu-icon">🛒</div><div class="menu-label"><span class="title">Add to Cart</span><span class="sub">View your cart</span></div></a>
            </div>
        </div>
        <div class="user-pill">👤 <strong><?php echo htmlspecialchars($name); ?></strong> &nbsp;·&nbsp; <?php echo $userType === 'admin' ? '🔐 Admin' : 'Customer'; ?></div>
        <a href="user_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="hero">
    <h2>🛒 Your <em>Cart</em></h2>
    <p><?php echo $item_count; ?> item<?php echo $item_count !== 1 ? 's' : ''; ?> · Ready to checkout</p>
</div>

<div class="container">
    <?php if (empty($cart)): ?>
        <div class="cart-items-box">
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <p>Your cart is empty. Start adding some products!</p>
                <a href="user_home.php">Browse Products</a>
            </div>
        </div>
    <?php else: ?>
    <form method="POST" action="cart.php">
    <div class="cart-layout">
        <!-- Items -->
        <div class="cart-items-box">
            <div class="cart-header">
                <h3>Cart Items <span style="color:var(--muted);font-size:14px;font-weight:400">(<?php echo $item_count; ?>)</span></h3>
                <a href="cart.php?clear=1" class="clear-btn">🗑 Clear All</a>
            </div>
            <?php foreach ($cart as $pid => $item): ?>
            <div class="cart-item">
                <div class="item-img">
                    <?php if ($item['image']): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    <?php else: ?>
                        <span class="no-img">🖼️</span>
                    <?php endif; ?>
                </div>
                <div class="item-info">
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <div class="item-price">Unit price: <strong>₹<?php echo number_format($item['price'], 2); ?></strong></div>
                </div>
                <div class="qty-control">
                    <button type="button" class="qty-btn" onclick="changeQty(<?php echo $pid; ?>, -1)">−</button>
                    <input type="number" name="qty[<?php echo $pid; ?>]" id="qty_<?php echo $pid; ?>"
                           class="qty-input" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                    <button type="button" class="qty-btn" onclick="changeQty(<?php echo $pid; ?>, 1)">+</button>
                </div>
                <div class="item-subtotal">₹<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                <a href="cart.php?remove=<?php echo $pid; ?>" class="remove-btn" title="Remove">×</a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div class="summary-box">
            <h3>Order Summary</h3>
            <?php foreach ($cart as $item): ?>
            <div class="summary-line">
                <span><?php echo htmlspecialchars(substr($item['name'], 0, 20)) . (strlen($item['name']) > 20 ? '…' : ''); ?> ×<?php echo $item['qty']; ?></span>
                <span>₹<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="summary-line"><span>Shipping</span><span style="color:#34c46a">Free</span></div>
            <div class="summary-line total"><span>Total</span><span>₹<?php echo number_format($total, 2); ?></span></div>
            <button type="submit" name="update_qty" class="checkout-btn" style="margin-bottom:4px">↻ Update Cart</button>
            <button type="submit" name="checkout" class="checkout-btn" style="margin-top:8px">Proceed to Checkout →</button>
            <a href="user_home.php" class="continue-btn">← Continue Shopping</a>
            <div class="secure-note">🔒 Secure checkout · All prices in INR</div>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>

<script>
function changeQty(pid, delta) {
    const input = document.getElementById('qty_' + pid);
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > parseInt(input.max)) val = parseInt(input.max);
    input.value = val;
}
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