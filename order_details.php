<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit(); }
include("config.php");
$admin_name = $_SESSION['admin'];

// Auto-create orders table if it doesn't exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Auto-create transactions table if it doesn't exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Update order status
if (isset($_POST['update_status'])) {
    $oid    = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id='$oid'");
    if ($status === 'delivered') {
        mysqli_query($conn, "UPDATE transactions SET status='completed' WHERE order_id='$oid'");
    } elseif ($status === 'cancelled') {
        mysqli_query($conn, "UPDATE transactions SET status='failed' WHERE order_id='$oid'");
    }
    header("Refresh:0");
}

$sql_orders = "
    SELECT o.*, u.Name as user_name, u.Mobile_number, p.product_name, p.image
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.Name
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC
";
$orders = mysqli_query($conn, $sql_orders);

if (!$orders) {
    die("SQL Error: " . mysqli_error($conn));
}

$total = mysqli_num_rows($orders);

$counts = [];
foreach(['pending','confirmed','shipped','delivered','cancelled'] as $s) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='$s'"));
    $counts[$s] = $r['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Details</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👑</text></svg>">
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
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:15px; margin-bottom:25px; }
.stat { background:white; padding:18px; border-radius:10px; text-align:center; box-shadow:0 3px 10px rgba(0,0,0,0.07); }
.stat .num { font-size:28px; font-weight:bold; margin-bottom:5px; }
.stat .lbl { font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px; }
.pending-c { color:#f5a623; } .confirmed-c { color:#667eea; } .shipped-c { color:#17a2b8; } .delivered-c { color:#28a745; } .cancelled-c { color:#ff6b6b; }

table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08); }
thead { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }
th, td { padding:13px 15px; text-align:left; font-size:13px; }
tbody tr { border-bottom:1px solid #f0f0f0; }
tbody tr:hover { background:#fff8f8; }
.product-img { width:45px; height:45px; object-fit:contain; border-radius:5px; border:1px solid #eee; }
.status-badge { padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; text-transform:uppercase; }
.s-pending { background:#fff3cd; color:#856404; }
.s-confirmed { background:#cce5ff; color:#004085; }
.s-shipped { background:#d1ecf1; color:#0c5460; }
.s-delivered { background:#d4edda; color:#155724; }
.s-cancelled { background:#f8d7da; color:#721c24; }
select { padding:5px 8px; border:1px solid #ddd; border-radius:5px; font-size:12px; cursor:pointer; }
.update-btn { background:#ff6b6b; color:white; border:none; padding:5px 12px; border-radius:5px; cursor:pointer; font-size:12px; font-weight:bold; }
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
        <a href="order_details.php" class="active-link" onclick="closeMenu()"><span>🛒</span><span>Order Details</span></a>
        <a href="customer_approval.php" onclick="closeMenu()"><span>👥</span><span>Customer Approval</span></a>
        <a href="customer_list.php" onclick="closeMenu()"><span>⚙️</span><span>Customer List</span></a>
        <a href="transaction_details.php" onclick="closeMenu()"><span>📊</span><span>Transaction Details</span></a>
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
  <h2 style="margin-bottom:20px;color:#333;">🛒 Order Details</h2>

  <div class="stats">
    <div class="stat"><div class="num" style="color:#333"><?php echo $total; ?></div><div class="lbl">Total Orders</div></div>
    <div class="stat"><div class="num pending-c"><?php echo $counts['pending']; ?></div><div class="lbl">Pending</div></div>
    <div class="stat"><div class="num confirmed-c"><?php echo $counts['confirmed']; ?></div><div class="lbl">Confirmed</div></div>
    <div class="stat"><div class="num shipped-c"><?php echo $counts['shipped']; ?></div><div class="lbl">Shipped</div></div>
    <div class="stat"><div class="num delivered-c"><?php echo $counts['delivered']; ?></div><div class="lbl">Delivered</div></div>
    <div class="stat"><div class="num cancelled-c"><?php echo $counts['cancelled']; ?></div><div class="lbl">Cancelled</div></div>
  </div>

  <?php if ($total === 0): ?>
    <div class="empty">📭 No orders placed yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>#</th><th>Product</th><th>Customer</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Update</th></tr></thead>
    <tbody>
    <?php while($o = mysqli_fetch_assoc($orders)): ?>
      <tr>
        <td><strong>#<?php echo $o['id']; ?></strong></td>
        <td style="display:flex;align-items:center;gap:10px;padding:13px 15px;">
          <?php if(!empty($o['image']) && file_exists(__DIR__.'/'.$o['image'])): ?>
            <img src="<?php echo htmlspecialchars($o['image']); ?>" class="product-img">
          <?php else: ?><div style="width:45px;height:45px;background:#f0f0f0;border-radius:5px;display:flex;align-items:center;justify-content:center;">🖼️</div><?php endif; ?>
          <?php echo htmlspecialchars($o['product_name'] ?? 'Deleted product'); ?>
        </td>
        <td><?php echo htmlspecialchars($o['user_name'] ?? 'Unknown'); ?><br><small style="color:#999"><?php echo htmlspecialchars($o['Mobile_number'] ?? ''); ?></small></td>
        <td><?php echo $o['quantity']; ?></td>
        <td style="color:#ff6b6b;font-weight:bold;">₹<?php echo number_format($o['total_price'],2); ?></td>
        <td><span class="status-badge s-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
        <td style="color:#999;font-size:12px"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
        <td>
          <form method="POST" style="display:flex;gap:5px;align-items:center;">
            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
            <select name="status">
              <?php foreach(['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $o['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="update-btn">✓</button>
          </form>
        </td>
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