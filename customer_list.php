<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit(); }
include("config.php");
$admin_name = $_SESSION['admin'];

// Ensure status column exists (same as customer_approval.php)
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field='status'");
if (mysqli_num_rows($check_status) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
}

// Check if created_at column exists
$check_created = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field='created_at'");
$has_created_at = mysqli_num_rows($check_created) > 0;

// Delete customer
if (isset($_GET['delete'])) {
    $name = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM users WHERE Name='$name'");
    header("Refresh:0");
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where  = $search ? "WHERE Name LIKE '%$search%' OR Mobile_number LIKE '%$search%' OR Email_id LIKE '%$search%'" : '';
$order  = $has_created_at ? "ORDER BY created_at DESC" : "ORDER BY Name ASC";
$users  = mysqli_query($conn, "SELECT * FROM users $where $order");
$total  = mysqli_num_rows($users);

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) as total,
    SUM(status='approved') as approved,
    SUM(status='pending')  as pending,
    SUM(status='rejected') as rejected
FROM users"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer List</title>
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

.container { max-width:1200px; margin:0 auto; padding:30px 20px; }
.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px; }
.top-bar h2 { font-size:24px; color:#333; }
.search-form { display:flex; gap:10px; }
.search-form input { padding:9px 15px; border:1px solid #ddd; border-radius:6px; font-size:14px; width:250px; }
.search-form button { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; border:none; padding:9px 20px; border-radius:6px; cursor:pointer; font-weight:bold; }

.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:25px; }
.stat { background:white; padding:18px; border-radius:10px; text-align:center; box-shadow:0 3px 10px rgba(0,0,0,0.07); }
.stat .num { font-size:28px; font-weight:bold; margin-bottom:4px; }
.stat .lbl { font-size:12px; color:#888; text-transform:uppercase; }

table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08); }
thead { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }
th, td { padding:13px 16px; text-align:left; font-size:13px; }
tbody tr { border-bottom:1px solid #f0f0f0; }
tbody tr:hover { background:#fff8f8; }
.avatar { width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:16px; }
.s-approved { background:#d4edda; color:#155724; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.s-pending  { background:#fff3cd; color:#856404; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.s-rejected { background:#f8d7da; color:#721c24; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.del-btn { background:#ff6b6b; color:white; padding:6px 14px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; }
.approve-link { background:#28a745; color:white; padding:6px 14px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-right:5px; }
.empty { text-align:center; padding:50px; color:#999; }
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
        <a href="customer_list.php" class="active-link" onclick="closeMenu()"><span>⚙️</span><span>Customer List</span></a>
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
  <div class="top-bar">
    <h2>⚙️ Customer List</h2>
    <form class="search-form" method="GET">
      <input type="text" name="search" placeholder="Search by name, mobile, email..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit">🔍 Search</button>
      <?php if($search): ?><a href="customer_list.php" style="padding:9px 15px;background:#ddd;border-radius:6px;text-decoration:none;color:#333;font-weight:bold;">✕ Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="stats">
    <div class="stat"><div class="num" style="color:#333"><?php echo $stats['total']; ?></div><div class="lbl">Total</div></div>
    <div class="stat"><div class="num" style="color:#28a745"><?php echo $stats['approved']; ?></div><div class="lbl">Approved</div></div>
    <div class="stat"><div class="num" style="color:#f5a623"><?php echo $stats['pending']; ?></div><div class="lbl">Pending</div></div>
    <div class="stat"><div class="num" style="color:#ff6b6b"><?php echo $stats['rejected']; ?></div><div class="lbl">Rejected</div></div>
  </div>

  <?php if($total===0): ?>
    <div class="empty"><?php echo $search ? "No customers found for \"$search\"." : "No customers registered yet."; ?></div>
  <?php else: ?>
  <table>
    <thead><tr><th>#</th><th>Customer</th><th>Mobile</th><th>Email</th><th>Status</th><?php if($has_created_at): ?><th>Joined</th><?php endif; ?><th>Actions</th></tr></thead>
    <tbody>
    <?php $i=1; while($u=mysqli_fetch_assoc($users)): ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td style="display:flex;align-items:center;gap:12px;padding:13px 16px;">
          <div class="avatar"><?php echo strtoupper(substr($u['Name'],0,1)); ?></div>
          <strong><?php echo htmlspecialchars($u['Name']); ?></strong>
        </td>
        <td><?php echo htmlspecialchars($u['Mobile_number']); ?></td>
        <td><?php echo htmlspecialchars($u['Email_id']); ?></td>
        <td><span class="s-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
        <?php if($has_created_at): ?>
        <td style="color:#999;font-size:12px"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
        <?php endif; ?>
        <td>
          <a href="customer_approval.php" class="approve-link">Manage</a>
          <a href="?delete=<?php echo urlencode($u['Name']); ?>" class="del-btn" onclick="return confirm('Delete this customer?')">🗑️ Delete</a>
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