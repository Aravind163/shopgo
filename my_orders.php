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

$uname_escaped = mysqli_real_escape_string($conn, $name);

// Get user_id from users table using Name
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE Name='$uname_escaped' LIMIT 1"));
$user_id  = $user_row ? (int)$user_row['Mobile_number'] : 0; // fallback; we'll use Name-based subquery below

// Build query: JOIN orders with products
if ($userType === 'admin') {
    $query = "SELECT o.id, o.user_id, o.quantity, o.total_price, o.status, o.created_at,
                     p.product_name, p.price, p.category, p.image,
                     u.Name AS customer_name
              FROM orders o
              JOIN products p ON o.product_id = p.id
              JOIN users u ON o.user_id = u.Mobile_number
              ORDER BY o.id DESC";
} else {
    $query = "SELECT o.id, o.user_id, o.quantity, o.total_price, o.status, o.created_at,
                     p.product_name, p.price, p.category, p.image
              FROM orders o
              JOIN products p ON o.product_id = p.id
              JOIN users u ON o.user_id = u.Mobile_number
              WHERE u.Name = '$uname_escaped'
              ORDER BY o.id DESC";
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("<p style='padding:30px;font-family:sans-serif;color:red'>Query error: " . mysqli_error($conn) . "</p>");
}

$orders = [];
while ($o = mysqli_fetch_assoc($result)) $orders[] = $o;

$status_colors = [
    'pending'   => ['bg' => '#fff8e1', 'color' => '#f59e0b', 'dot' => '#f59e0b'],
    'confirmed' => ['bg' => '#e8f5ff', 'color' => '#3b82f6', 'dot' => '#3b82f6'],
    'shipped'   => ['bg' => '#ede8ff', 'color' => '#8b5cf6', 'dot' => '#8b5cf6'],
    'delivered' => ['bg' => '#edf7ee', 'color' => '#22c55e', 'dot' => '#22c55e'],
    'cancelled' => ['bg' => '#fff0f0', 'color' => '#ef4444', 'dot' => '#ef4444'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders – ShopGo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent:#f2630a;--accent2:#ff6b6b;--dark:#1a1a2e;--mid:#2d2d44;
            --light-bg:#f7f5f2;--card-bg:#ffffff;--text:#2c2c2c;--muted:#888;
            --border:#e8e4df;--radius:14px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'DM Sans',sans-serif;background:var(--light-bg);color:var(--text);min-height:100vh;}
        .navbar{background:var(--dark);padding:18px 48px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:999;box-shadow:0 4px 24px rgba(0,0,0,.35);}
        .navbar .brand span{font-family:'Playfair Display',serif;font-size:22px;color:#fff;}
        .navbar .brand .dot{color:var(--accent);}
        .nav-right{display:flex;align-items:center;gap:20px;}
        .menu-container{position:relative;}
        .menu-btn{background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.15);padding:9px 18px;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:8px;transition:background .2s;font-family:'DM Sans',sans-serif;}
        .menu-btn:hover{background:rgba(255,255,255,0.18);}
        .menu-btn .arrow{font-size:10px;transition:transform .3s;}
        .menu-btn.open .arrow{transform:rotate(180deg);}
        .dropdown{position:absolute;top:calc(100% + 10px);left:0;background:#fff;border-radius:12px;box-shadow:0 16px 48px rgba(0,0,0,0.18);min-width:220px;opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .25s ease;overflow:hidden;z-index:1000;}
        .dropdown.open{opacity:1;visibility:visible;transform:translateY(0);}
        .dropdown a{display:flex;align-items:center;gap:12px;padding:14px 18px;color:#333;text-decoration:none;font-size:14px;font-weight:500;border-left:3px solid transparent;transition:all .2s;}
        .dropdown a:hover{background:#fff8f5;border-left-color:var(--accent);color:var(--accent);padding-left:22px;}
        .dropdown a .menu-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
        .dropdown a:nth-child(1) .menu-icon{background:#fff0e8;}
        .dropdown a:nth-child(2) .menu-icon{background:#e8f5ff;}
        .dropdown a:nth-child(3) .menu-icon{background:#edf7ee;}
        .dropdown a:nth-child(4) .menu-icon{background:#fff3e0;}
        .dropdown a .menu-label{display:flex;flex-direction:column;}
        .dropdown a .menu-label .title{font-weight:500;font-size:14px;}
        .dropdown a .menu-label .sub{font-size:11px;color:#aaa;margin-top:1px;}
        .dropdown-divider{height:1px;background:#f0ede8;margin:0 14px;}
        .user-pill{background:var(--mid);color:#ccc;padding:7px 16px;border-radius:30px;font-size:13px;}
        .user-pill strong{color:#fff;}
        .logout-btn{background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff;padding:9px 22px;border-radius:8px;text-decoration:none;font-weight:500;font-size:14px;transition:opacity .2s,transform .2s;}
        .logout-btn:hover{opacity:.88;transform:translateY(-1px);}

        .hero{background:linear-gradient(135deg,var(--dark) 0%,var(--mid) 100%);color:#fff;padding:44px 48px 36px;position:relative;overflow:hidden;}
        .hero::after{content:'';position:absolute;right:-80px;top:-80px;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(242,99,10,.25) 0%,transparent 70%);}
        .hero h2{font-family:'Playfair Display',serif;font-size:32px;margin-bottom:6px;}
        .hero h2 em{color:var(--accent);font-style:normal;}
        .hero p{color:#aaa;font-size:14px;font-weight:300;}

        .container{max-width:1100px;margin:0 auto;padding:40px 24px;}
        .section-header{display:flex;align-items:center;gap:14px;margin-bottom:24px;}
        .section-header .icon-box{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--accent2),var(--accent));display:flex;align-items:center;justify-content:center;font-size:20px;}
        .section-header h3{font-family:'Playfair Display',serif;font-size:22px;color:var(--dark);}
        .count-badge{background:var(--light-bg);border:1px solid var(--border);color:var(--muted);font-size:12px;padding:3px 10px;border-radius:20px;}

        .orders-box{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
        .orders-box table{width:100%;border-collapse:collapse;}
        thead tr{background:#faf9f7;border-bottom:2px solid var(--border);}
        th{padding:14px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:500;}
        tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#fdfcfb;}
        td{padding:15px 20px;font-size:14px;vertical-align:middle;}

        .prod-thumb{width:46px;height:46px;border-radius:8px;object-fit:contain;background:#f0ede8;border:1px solid var(--border);}
        .prod-no-img{width:46px;height:46px;border-radius:8px;background:#f0ede8;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;}
        .prod-info{display:flex;align-items:center;gap:12px;}
        .prod-info .prod-name{font-weight:500;color:var(--dark);font-size:14px;}
        .prod-info .prod-cat{font-size:11px;color:var(--muted);margin-top:2px;}

        .order-id{font-family:'Playfair Display',serif;color:var(--dark);}
        .price-cell{font-weight:600;color:var(--accent);}
        .date-cell{font-size:12px;color:var(--muted);}

        .status-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:500;text-transform:capitalize;}
        .status-dot{width:7px;height:7px;border-radius:50%;}

        .empty-state{padding:60px 24px;text-align:center;color:var(--muted);}
        .empty-state .empty-icon{font-size:60px;margin-bottom:16px;opacity:.4;}
        .empty-state p{font-size:15px;margin-bottom:20px;}
        .empty-state a{background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff;padding:12px 28px;border-radius:9px;text-decoration:none;font-weight:500;font-size:14px;}

        @media(max-width:768px){
            .navbar{padding:16px 20px;}
            .hero{padding:32px 20px;}
            .container{padding:24px 16px;}
            table{font-size:13px;}
            th,td{padding:11px 12px;}
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="brand"><span>🛍️ Shop<span class="dot">.</span>Go</span></div>
    <div class="nav-right">
        <div class="menu-container">
            <button class="menu-btn" id="menuBtn" onclick="toggleMenu()">☰ Menu <span class="arrow">▼</span></button>
            <div class="dropdown" id="dropdownMenu">
                <a href="user_home.php"><div class="menu-icon">🛍️</div><div class="menu-label"><span class="title">Home</span></div></a>
                <a href="invoice.php"><div class="menu-icon">🧾</div><div class="menu-label"><span class="title">Invoice</span><span class="sub">View your billing</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="my_orders.php"><div class="menu-icon">📦</div><div class="menu-label"><span class="title">My Orders</span><span class="sub">Track your purchases</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="product_description.php"><div class="menu-icon">🔍</div><div class="menu-label"><span class="title">Product Description</span><span class="sub">Detailed product info</span></div></a>
                <div class="dropdown-divider"></div>
                <a href="cart.php"><div class="menu-icon">🛒</div><div class="menu-label"><span class="title">Cart</span><span class="sub">View your cart</span></div></a>
            </div>
        </div>
        <div class="user-pill">👤 <strong><?php echo htmlspecialchars($name); ?></strong> &nbsp;·&nbsp; <?php echo $userType==='admin'?'🔐 Admin':'Customer'; ?></div>
        <a href="user_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="hero">
    <h2>📦 My <em>Orders</em></h2>
    <p><?php echo $userType==='admin'?'All customer orders — manage and track everything':'Track and review your purchases'; ?></p>
</div>

<?php if (isset($_GET['ordered'])): ?>
<div style="background:#edfbf0;border:1px solid #a7f0bc;color:#166534;padding:14px 48px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;">
    ✅ Order placed successfully! Your items are being processed.
</div>
<?php endif; ?>

<div class="container">
    <div class="section-header">
        <div class="icon-box">📦</div>
        <h3><?php echo $userType==='admin'?'All Orders':'Your Orders'; ?></h3>
        <span class="count-badge"><?php echo count($orders); ?> order<?php echo count($orders)!==1?'s':''; ?></span>
    </div>

    <div class="orders-box">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <p>No orders yet. Start shopping!</p>
            <a href="user_home.php">Browse Products</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <?php if($userType==='admin'): ?><th>Customer</th><?php endif; ?>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o):
                $sc = $status_colors[strtolower($o['status'])] ?? $status_colors['pending'];
            ?>
            <tr>
                <td><span class="order-id">#<?php echo $o['id']; ?></span></td>
                <td>
                    <div class="prod-info">
                        <?php if (!empty($o['image'])): ?>
                            <img src="<?php echo htmlspecialchars($o['image']); ?>" class="prod-thumb" alt="">
                        <?php else: ?>
                            <div class="prod-no-img">🖼️</div>
                        <?php endif; ?>
                        <div>
                            <div class="prod-name"><?php echo htmlspecialchars($o['product_name']); ?></div>
                            <div class="prod-cat"><?php echo htmlspecialchars($o['category']); ?></div>
                        </div>
                    </div>
                </td>
                <?php if($userType==='admin'): ?>
                <td><?php echo htmlspecialchars($o['customer_name'] ?? '—'); ?></td>
                <?php endif; ?>
                <td><?php echo (int)$o['quantity']; ?></td>
                <td>₹<?php echo number_format((float)$o['price'], 2); ?></td>
                <td class="price-cell">₹<?php echo number_format((float)$o['total_price'], 2); ?></td>
                <td>
                    <span class="status-badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>">
                        <span class="status-dot" style="background:<?php echo $sc['dot']; ?>"></span>
                        <?php echo htmlspecialchars($o['status']); ?>
                    </span>
                </td>
                <td class="date-cell"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMenu(){document.getElementById('menuBtn').classList.toggle('open');document.getElementById('dropdownMenu').classList.toggle('open');}
document.addEventListener('click',function(e){if(!document.querySelector('.menu-container').contains(e.target)){document.getElementById('menuBtn').classList.remove('open');document.getElementById('dropdownMenu').classList.remove('open');}});
</script>
</body>
</html>