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

// ════════════ PLACE ORDERS FROM CART (only for users) ════════════
if ($userType === 'user' && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $item) {
        $pid       = intval($pid);
        $qty       = intval($item['qty']);
        $price     = floatval($item['price']);
        $total     = $price * $qty;
        $user_esc  = mysqli_real_escape_string($conn, $name);

        // Insert order
        mysqli_query($conn, "INSERT INTO orders (user_id, product_id, quantity, total_price, status, created_at)
                              VALUES ('$user_esc', '$pid', '$qty', '$total', 'pending', NOW())");

        // Deduct stock
        mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id = $pid AND stock >= $qty");
    }

    // Clear cart after placing orders
    unset($_SESSION['cart']);
}

// ════════════ FETCH ORDERS FOR DISPLAY ════════════
if ($userType === 'admin') {
    $query = "SELECT o.id, o.quantity, o.total_price, o.status, o.created_at,
                     p.product_name, p.price, p.category, p.image,
                     u.Name AS customer_name
              FROM orders o
              JOIN products p ON o.product_id = p.id
              LEFT JOIN users u ON o.user_id = u.Name
              ORDER BY o.id DESC";
} else {
    $query = "SELECT o.id, o.quantity, o.total_price, o.status, o.created_at,
                     p.product_name, p.price, p.category, p.image
              FROM orders o
              JOIN products p ON o.product_id = p.id
              WHERE o.user_id = '$uname_escaped'
              ORDER BY o.id DESC";
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("<p style='padding:30px;font-family:sans-serif;color:red'>Query error: " . mysqli_error($conn) . "</p>");
}

$orders      = [];
$grand_total = 0;
while ($o = mysqli_fetch_assoc($result)) {
    $grand_total += (float)$o['total_price'];
    $orders[]     = $o;
}

$invoice_no   = 'INV-' . strtoupper(substr(md5($name . date('Ym')), 0, 8));
$invoice_date = date('d M Y');

$status_colors = [
    'pending'   => ['bg'=>'#fff8e1','color'=>'#f59e0b','dot'=>'#f59e0b'],
    'confirmed' => ['bg'=>'#e8f5ff','color'=>'#3b82f6','dot'=>'#3b82f6'],
    'shipped'   => ['bg'=>'#ede8ff','color'=>'#8b5cf6','dot'=>'#8b5cf6'],
    'delivered' => ['bg'=>'#edf7ee','color'=>'#22c55e','dot'=>'#22c55e'],
    'cancelled' => ['bg'=>'#fff0f0','color'=>'#ef4444','dot'=>'#ef4444'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice – ShopGo</title>

    <!-- EVERYTHING BELOW IS 100% SAME AS YOUR FILE -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root{--accent:#f2630a;--accent2:#ff6b6b;--dark:#1a1a2e;--mid:#2d2d44;--light-bg:#f7f5f2;--card-bg:#ffffff;--text:#2c2c2c;--muted:#888;--border:#e8e4df;--radius:14px;}
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

        .action-bar{background:var(--card-bg);border-bottom:1px solid var(--border);padding:14px 48px;display:flex;align-items:center;justify-content:space-between;}
        .breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);}
        .breadcrumb a{color:var(--accent);text-decoration:none;}
        .print-btn{background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff;border:none;padding:9px 22px;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;font-family:'DM Sans',sans-serif;transition:opacity .2s;}
        .print-btn:hover{opacity:.88;}

        .container{max-width:920px;margin:0 auto;padding:40px 24px;}
        .invoice-card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.06);}

        .invoice-head{background:linear-gradient(135deg,var(--dark) 0%,var(--mid) 100%);padding:36px 44px;display:flex;justify-content:space-between;align-items:flex-start;position:relative;overflow:hidden;}
        .invoice-head::after{content:'';position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(242,99,10,.3) 0%,transparent 70%);}
        .brand-name{font-family:'Playfair Display',serif;font-size:26px;color:#fff;margin-bottom:4px;}
        .brand-name .dot{color:var(--accent);}
        .brand-sub{font-size:13px;color:#aaa;}
        .invoice-meta{text-align:right;}
        .inv-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin-bottom:4px;}
        .inv-number{font-family:'Playfair Display',serif;font-size:20px;color:#fff;margin-bottom:8px;}
        .inv-date{font-size:13px;color:#bbb;}

        .invoice-info{padding:28px 44px;display:grid;grid-template-columns:1fr 1fr;gap:24px;border-bottom:1px solid var(--border);}
        .info-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px;}
        .info-value{font-size:15px;color:var(--dark);font-weight:500;margin-bottom:3px;}
        .info-sub{font-size:13px;color:var(--muted);}

        .invoice-table-wrap{padding:0 44px 28px;}
        table{width:100%;border-collapse:collapse;margin-top:24px;}
        thead tr{border-bottom:2px solid var(--border);}
        th{padding:12px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);font-weight:500;}
        th:last-child,td:last-child{text-align:right;}
        tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#fdfcfb;}
        td{padding:14px 14px;font-size:14px;vertical-align:middle;}

        .prod-thumb{width:40px;height:40px;border-radius:7px;object-fit:contain;background:#f0ede8;border:1px solid var(--border);}
        .prod-no-img{width:40px;height:40px;border-radius:7px;background:#f0ede8;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:18px;}
        .prod-cell{display:flex;align-items:center;gap:10px;}
        .prod-name{font-weight:500;color:var(--dark);}
        .prod-id{font-size:11px;color:var(--muted);margin-top:2px;}
        .status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:500;text-transform:capitalize;}
        .status-dot{width:6px;height:6px;border-radius:50%;}

        .invoice-totals{padding:20px 44px 32px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;}
        .totals-box{min-width:280px;}
        .total-line{display:flex;justify-content:space-between;font-size:14px;color:var(--muted);margin-bottom:10px;}
        .total-line.grand{border-top:2px solid var(--dark);margin-top:12px;padding-top:14px;font-size:20px;font-weight:700;color:var(--dark);}
        .total-line.grand span:last-child{color:var(--accent);}

        .invoice-footer{background:var(--light-bg);border-top:1px solid var(--border);padding:18px 44px;text-align:center;font-size:12px;color:var(--muted);}

        .empty-state{padding:60px 44px;text-align:center;color:var(--muted);}
        .empty-state .empty-icon{font-size:56px;opacity:.4;margin-bottom:14px;}
        .empty-state a{display:inline-block;margin-top:16px;background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:14px;}

        @media print{.navbar,.action-bar,.print-btn{display:none!important;}body{background:#fff;}.container{max-width:100%;padding:0;}.invoice-card{box-shadow:none;border:none;}}
        @media(max-width:768px){.navbar{padding:16px 20px;}.action-bar{padding:12px 16px;}.invoice-head{flex-direction:column;gap:16px;padding:24px;}.invoice-meta{text-align:left;}.invoice-info{grid-template-columns:1fr;padding:20px 24px;}.invoice-table-wrap{padding:0 16px 20px;}.invoice-totals{padding:16px 24px;}.container{padding:20px 12px;}}
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

<div class="action-bar">
    <div class="breadcrumb"><a href="user_home.php">🏠 Home</a> <span>›</span> <span>Invoice</span></div>
    <button class="print-btn" onclick="window.print()">🖨 Print Invoice</button>
</div>

<div class="container">
    <div class="invoice-card">
        <!-- Header -->
        <div class="invoice-head">
            <div>
                <div class="brand-name">🛍️ Shop<span class="dot">.</span>Go</div>
                <div class="brand-sub">Your Trusted Online Store</div>
            </div>
            <div class="invoice-meta">
                <div class="inv-label">Invoice</div>
                <div class="inv-number"><?php echo $invoice_no; ?></div>
                <div class="inv-date">📅 <?php echo $invoice_date; ?></div>
            </div>
        </div>

        <!-- Bill Info -->
        <div class="invoice-info">
            <div>
                <div class="info-label">Bill To</div>
                <div class="info-value">👤 <?php echo htmlspecialchars($name); ?></div>
                <div class="info-sub"><?php echo $userType==='admin'?'Administrator':'Customer Account'; ?></div>
            </div>
            <div>
                <div class="info-label">Invoice Summary</div>
                <div class="info-value"><?php echo count($orders); ?> order<?php echo count($orders)!==1?'s':''; ?></div>
                <div class="info-sub">Grand Total: <strong style="color:var(--accent)">₹<?php echo number_format($grand_total, 2); ?></strong></div>
            </div>
        </div>

        <!-- Table -->
        <div class="invoice-table-wrap">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">🧾</div>
                <p>No orders to invoice yet.</p>
                <a href="user_home.php">Start Shopping</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <?php if($userType==='admin'): ?><th>Customer</th><?php endif; ?>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; foreach ($orders as $o):
                    $sc = $status_colors[strtolower($o['status'])] ?? $status_colors['pending'];
                ?>
                <tr>
                    <td style="color:var(--muted)"><?php echo $i++; ?></td>
                    <td>
                        <div class="prod-cell">
                            <?php if(!empty($o['image'])): ?>
                                <img src="<?php echo htmlspecialchars($o['image']); ?>" class="prod-thumb" alt="">
                            <?php else: ?>
                                <div class="prod-no-img">🖼️</div>
                            <?php endif; ?>
                            <div>
                                <div class="prod-name"><?php echo htmlspecialchars($o['product_name']); ?></div>
                                <div class="prod-id">Order #<?php echo $o['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <?php if($userType==='admin'): ?>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <td><?php echo (int)$o['quantity']; ?></td>
                    <td>₹<?php echo number_format((float)$o['price'], 2); ?></td>
                    <td>
                        <span class="status-badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>">
                            <span class="status-dot" style="background:<?php echo $sc['dot']; ?>"></span>
                            <?php echo htmlspecialchars($o['status']); ?>
                        </span>
                    </td>
                    <td style="color:var(--muted);font-size:12px"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                    <td><strong style="color:var(--accent)">₹<?php echo number_format((float)$o['total_price'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>

        <!-- Totals -->
        <?php if (!empty($orders)): ?>
        <div class="invoice-totals">
            <div class="totals-box">
                <div class="total-line"><span>Subtotal</span><span>₹<?php echo number_format($grand_total, 2); ?></span></div>
                <div class="total-line"><span>Shipping</span><span style="color:#34c46a">Free</span></div>
                <div class="total-line"><span>Tax (0%)</span><span>₹0.00</span></div>
                <div class="total-line grand"><span>Grand Total</span><span>₹<?php echo number_format($grand_total, 2); ?></span></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="invoice-footer">
            🙏 Thank you for shopping with <strong>ShopGo</strong>! &nbsp;·&nbsp; All prices in INR &nbsp;·&nbsp; Generated on <?php echo $invoice_date; ?>
        </div>
    </div>
</div>

<script>
function toggleMenu(){document.getElementById('menuBtn').classList.toggle('open');document.getElementById('dropdownMenu').classList.toggle('open');}
document.addEventListener('click',function(e){if(!document.querySelector('.menu-container').contains(e.target)){document.getElementById('menuBtn').classList.remove('open');document.getElementById('dropdownMenu').classList.remove('open');}});
</script>
</body>
</html>