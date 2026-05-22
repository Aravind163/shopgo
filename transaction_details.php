<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit(); }
include("config.php");
$admin_name = $_SESSION['admin'];

// ── Step 1: ensure orders table exists ──────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     VARCHAR(100) NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT DEFAULT 1,
    total_price DECIMAL(10,2) DEFAULT 0.00,
    status      VARCHAR(20) DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Step 2: ensure transactions table exists with FULL schema ────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS transactions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL,
    user_id        VARCHAR(100),
    amount         DECIMAL(10,2) DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'N/A',
    status         VARCHAR(20) DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Step 3: patch columns that may be missing on already-created table ───────
$cols = [];
$col_res = mysqli_query($conn, "SHOW COLUMNS FROM transactions");
while ($c = mysqli_fetch_assoc($col_res)) $cols[] = $c['Field'];

if (!in_array('user_id', $cols))
    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN user_id VARCHAR(100) AFTER order_id");

if (!in_array('payment_method', $cols))
    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN payment_method VARCHAR(50) DEFAULT 'N/A' AFTER amount");

if (!in_array('amount', $cols))
    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN amount DECIMAL(10,2) DEFAULT 0.00 AFTER user_id");

if (!in_array('status', $cols))
    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");

if (!in_array('created_at', $cols))
    mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// ── Step 4: run the main query ───────────────────────────────────────────────
$transactions = mysqli_query($conn, "
    SELECT t.*, u.Name as user_name, u.Mobile_number,
           p.product_name, o.quantity, o.status as order_status
    FROM transactions t
    LEFT JOIN users u    ON t.user_id    = u.Name
    LEFT JOIN orders o   ON t.order_id   = o.id
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY t.created_at DESC
");

if (!$transactions) die("SQL Error: " . mysqli_error($conn));
$total = mysqli_num_rows($transactions);

$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN status='completed' THEN amount ELSE 0 END),0) as revenue,
        SUM(status='completed') as completed,
        SUM(status='pending')   as pending,
        SUM(status='failed')    as failed,
        SUM(status='refunded')  as refunded
    FROM transactions
"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction Details</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; }
.navbar { background:linear-gradient(135deg,#ff6b6b,#f2630a); display:flex; justify-content:space-between; align-items:center; height:70px; padding:0; position:sticky; top:0; z-index:1000; box-shadow:0 4px 15px rgba(0,0,0,0.2); }
.navbar-left { display:flex; align-items:center; }
.navbar h1 { color:white; font-size:22px; padding:0 30px; }
.menu-container { position:relative; }
.menu-toggle { background:rgba(255,255,255,0.2); color:white; border:none; padding:0 25px; height:70px; font-size:15px; font-weight:bold; cursor:pointer; display:flex; align-items:center; gap:8px; }
.menu-toggle::after { content:'▼'; font-size:11px; transition:transform 0.3s; }
.menu-toggle.active::after { transform:rotate(180deg); }
.dropdown-menu { position:absolute; top:70px; left:0; background:white; min-width:240px; box-shadow:0 10px 30px rgba(0,0,0,0.15); border-radius:0 0 10px 10px; opacity:0; visibility:hidden; transform:translateY(-10px); transition:all 0.3s; z-index:999; }
.dropdown-menu.active { opacity:1; visibility:visible; transform:translateY(0); }
.dropdown-menu a { display:flex; align-items:center; gap:12px; padding:14px 20px; color:#333; text-decoration:none; border-left:4px solid transparent; transition:all 0.2s; }
.dropdown-menu a:hover, .dropdown-menu a.active-link { background:#fff5f5; border-left-color:#ff6b6b; color:#ff6b6b; padding-left:24px; }
.navbar-right { display:flex; align-items:center; gap:15px; padding-right:30px; }
.admin-badge { background:rgba(255,255,255,0.2); color:white; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:bold; }
.logout-btn { background:white; color:#ff6b6b; padding:9px 20px; border-radius:5px; font-weight:bold; text-decoration:none; }
.container { max-width:1400px; margin:0 auto; padding:30px 20px; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:15px; margin-bottom:25px; }
.stat { background:white; padding:18px; border-radius:10px; text-align:center; box-shadow:0 3px 10px rgba(0,0,0,0.07); }
.stat .num { font-size:24px; font-weight:bold; margin-bottom:4px; }
.stat .lbl { font-size:12px; color:#888; text-transform:uppercase; }
.revenue-card { background:linear-gradient(135deg,#ff6b6b,#f2630a); }
.revenue-card .num, .revenue-card .lbl { color:white !important; }
table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08); }
thead { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }
th, td { padding:13px 16px; text-align:left; font-size:13px; }
tbody tr { border-bottom:1px solid #f0f0f0; }
tbody tr:hover { background:#fff8f8; }
.amount { color:#ff6b6b; font-weight:bold; font-size:15px; }
.s-completed { background:#d4edda; color:#155724; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.s-pending   { background:#fff3cd; color:#856404; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.s-failed    { background:#f8d7da; color:#721c24; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.s-refunded  { background:#d1ecf1; color:#0c5460; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.os-delivered { background:#d4edda; color:#155724; padding:3px 8px; border-radius:8px; font-size:10px; }
.os-pending   { background:#fff3cd; color:#856404; padding:3px 8px; border-radius:8px; font-size:10px; }
.os-cancelled { background:#f8d7da; color:#721c24; padding:3px 8px; border-radius:8px; font-size:10px; }
.os-shipped   { background:#d1ecf1; color:#0c5460; padding:3px 8px; border-radius:8px; font-size:10px; }
.os-confirmed { background:#cce5ff; color:#004085; padding:3px 8px; border-radius:8px; font-size:10px; }
.empty { text-align:center; padding:60px; color:#999; background:white; border-radius:10px; }
</style>
</head>
<body>
<div class="navbar">
  <div class="navbar-left">
    <h1>🛍️ Admin Dashboard</h1>
    <div class="menu-container">
      <button class="menu-toggle" onclick="toggleMenu()">📋 Menu</button>
      <div class="dropdown-menu" id="dropdownMenu">
        <a href="add_products.php" onclick="closeMenu()"><span>📦</span><span>Product Details</span></a>
        <a href="admin_home.php" onclick="closeMenu()"><span>📋</span><span>Add Product</span></a>
        <a href="order_details.php" onclick="closeMenu()"><span>🛒</span><span>Order Details</span></a>
        <a href="customer_approval.php" onclick="closeMenu()"><span>👥</span><span>Customer Approval</span></a>
        <a href="customer_list.php" onclick="closeMenu()"><span>⚙️</span><span>Customer List</span></a>
        <a href="transaction_details.php" class="active-link" onclick="closeMenu()"><span>📊</span><span>Transaction Details</span></a>
      </div>
    </div>
  </div>
  <div class="navbar-right">
    <span style="color:white;font-size:14px;">Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
    <span class="admin-badge">🔐 ADMIN</span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<div class="container">
  <h2 style="margin-bottom:20px;color:#333;">📊 Transaction Details</h2>
  <div class="stats">
    <div class="stat revenue-card">
      <div class="num">₹<?php echo number_format($summary['revenue'],2); ?></div>
      <div class="lbl">Total Revenue</div>
    </div>
    <div class="stat"><div class="num" style="color:#333"><?php echo $summary['total']; ?></div><div class="lbl">Transactions</div></div>
    <div class="stat"><div class="num" style="color:#28a745"><?php echo $summary['completed']; ?></div><div class="lbl">Completed</div></div>
    <div class="stat"><div class="num" style="color:#f5a623"><?php echo $summary['pending']; ?></div><div class="lbl">Pending</div></div>
    <div class="stat"><div class="num" style="color:#ff6b6b"><?php echo $summary['failed']; ?></div><div class="lbl">Failed</div></div>
    <div class="stat"><div class="num" style="color:#17a2b8"><?php echo $summary['refunded']; ?></div><div class="lbl">Refunded</div></div>
  </div>

  <?php if($total===0): ?>
    <div class="empty">📭 No transactions yet. They appear here when customers place orders.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Txn #</th><th>Order #</th><th>Customer</th><th>Product</th><th>Qty</th><th>Amount</th><th>Payment</th><th>Txn Status</th><th>Order Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php while($t = mysqli_fetch_assoc($transactions)): ?>
      <tr>
        <td><strong>#<?php echo $t['id']; ?></strong></td>
        <td><a href="order_details.php" style="color:#ff6b6b;text-decoration:none;font-weight:bold;">#<?php echo $t['order_id']; ?></a></td>
        <td><?php echo htmlspecialchars($t['user_name'] ?? 'Unknown'); ?><br><small style="color:#999"><?php echo htmlspecialchars($t['Mobile_number'] ?? ''); ?></small></td>
        <td><?php echo htmlspecialchars($t['product_name'] ?? 'N/A'); ?></td>
        <td><?php echo $t['quantity'] ?? 1; ?></td>
        <td class="amount">₹<?php echo number_format($t['amount'],2); ?></td>
        <td style="color:#666"><?php echo htmlspecialchars($t['payment_method'] ?? 'N/A'); ?></td>
        <td><span class="s-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
        <td><span class="os-<?php echo $t['order_status'] ?? 'pending'; ?>"><?php echo ucfirst($t['order_status'] ?? 'N/A'); ?></span></td>
        <td style="color:#999;font-size:12px"><?php echo date('d M Y H:i', strtotime($t['created_at'])); ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function toggleMenu() { document.getElementById('dropdownMenu').classList.toggle('active'); document.querySelector('.menu-toggle').classList.toggle('active'); }
function closeMenu() { document.getElementById('dropdownMenu').classList.remove('active'); document.querySelector('.menu-toggle').classList.remove('active'); }
document.addEventListener('click', e => { if (!document.querySelector('.menu-container').contains(e.target)) closeMenu(); });
</script>
</body>
</html>