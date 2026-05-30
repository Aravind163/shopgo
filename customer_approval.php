<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit(); }
include("config.php");
$admin_name = $_SESSION['admin'];

$message = "";

// Handle approve/reject
if (isset($_POST['action']) && isset($_POST['user_name'])) {
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    // Check if status column exists, if not add it
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field='status'");
    if (mysqli_num_rows($check_col) === 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
    }
    
    mysqli_query($conn, "UPDATE users SET status='$action' WHERE Name='$user_name'");
    $message = $action === 'approved' ? "✅ Customer approved!" : "❌ Customer rejected.";
}

// Ensure status column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field='status'");
if (mysqli_num_rows($check_col) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
}

$pending  = mysqli_query($conn, "SELECT * FROM users WHERE status='pending' ORDER BY Name ASC");
$approved = mysqli_query($conn, "SELECT * FROM users WHERE status='approved' ORDER BY Name ASC");
$rejected = mysqli_query($conn, "SELECT * FROM users WHERE status='rejected' ORDER BY Name ASC");
$p_count  = mysqli_num_rows($pending);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Approval</title>
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
.msg { padding:12px 20px; border-radius:6px; margin-bottom:20px; font-weight:bold; background:#d4edda; color:#155724; border-left:4px solid #28a745; }

.tabs { display:flex; gap:5px; margin-bottom:25px; }
.tab { padding:10px 24px; border-radius:6px 6px 0 0; cursor:pointer; font-weight:bold; font-size:14px; border:none; background:#ddd; color:#666; }
.tab.active { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }

.tab-content { display:none; }
.tab-content.active { display:block; }

table { width:100%; border-collapse:collapse; background:white; border-radius:0 10px 10px 10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08); }
thead { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }
th, td { padding:13px 16px; text-align:left; font-size:13px; }
tbody tr { border-bottom:1px solid #f0f0f0; }
tbody tr:hover { background:#fff8f8; }
.approve-btn { background:#28a745; color:white; border:none; padding:7px 16px; border-radius:5px; cursor:pointer; font-weight:bold; font-size:12px; margin-right:5px; }
.reject-btn { background:#ff6b6b; color:white; border:none; padding:7px 16px; border-radius:5px; cursor:pointer; font-weight:bold; font-size:12px; }
.status-approved { background:#d4edda; color:#155724; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.status-rejected { background:#f8d7da; color:#721c24; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.status-pending  { background:#fff3cd; color:#856404; padding:4px 12px; border-radius:12px; font-size:11px; font-weight:bold; }
.empty { text-align:center; padding:40px; color:#999; background:white; border-radius:0 10px 10px 10px; }
.pending-badge { background:#ff6b6b; color:white; border-radius:50%; padding:2px 7px; font-size:11px; margin-left:5px; }
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
        <a href="customer_approval.php" class="active-link" onclick="closeMenu()"><span>👥</span><span>Customer Approval</span></a>
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
  <h2 style="margin-bottom:20px;color:#333;">👥 Customer Approval</h2>
  <?php if($message): ?><div class="msg"><?php echo $message; ?></div><?php endif; ?>

  <div class="tabs">
    <button class="tab active" onclick="showTab('pending')">⏳ Pending <?php if($p_count>0): ?><span class="pending-badge"><?php echo $p_count; ?></span><?php endif; ?></button>
    <button class="tab" onclick="showTab('approved')">✅ Approved</button>
    <button class="tab" onclick="showTab('rejected')">❌ Rejected</button>
  </div>

  <!-- PENDING -->
  <div class="tab-content active" id="tab-pending">
    <?php if(mysqli_num_rows($pending)===0): ?>
      <div class="empty">No pending approvals.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while($u=mysqli_fetch_assoc($pending)): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><strong><?php echo htmlspecialchars($u['Name']); ?></strong></td>
          <td><?php echo htmlspecialchars($u['Mobile_number']); ?></td>
          <td><?php echo htmlspecialchars($u['Email_id']); ?></td>
          <td style="color:#999;font-size:12px"><?php echo isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : 'N/A'; ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($u['Name']); ?>">
              <button name="action" value="approve" class="approve-btn">✅ Approve</button>
              <button name="action" value="reject"  class="reject-btn">❌ Reject</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- APPROVED -->
  <div class="tab-content" id="tab-approved">
    <?php if(mysqli_num_rows($approved)===0): ?>
      <div class="empty">No approved customers yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Email</th><th>Registered</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php $i=1; while($u=mysqli_fetch_assoc($approved)): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><strong><?php echo htmlspecialchars($u['Name']); ?></strong></td>
          <td><?php echo htmlspecialchars($u['Mobile_number']); ?></td>
          <td><?php echo htmlspecialchars($u['Email_id']); ?></td>
          <td style="color:#999;font-size:12px"><?php echo isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : 'N/A'; ?></td>
          <td><span class="status-approved">✅ Approved</span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($u['Name']); ?>">
              <button name="action" value="reject" class="reject-btn">❌ Revoke</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- REJECTED -->
  <div class="tab-content" id="tab-rejected">
    <?php if(mysqli_num_rows($rejected)===0): ?>
      <div class="empty">No rejected customers.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Email</th><th>Registered</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php $i=1; while($u=mysqli_fetch_assoc($rejected)): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><strong><?php echo htmlspecialchars($u['Name']); ?></strong></td>
          <td><?php echo htmlspecialchars($u['Mobile_number']); ?></td>
          <td><?php echo htmlspecialchars($u['Email_id']); ?></td>
          <td style="color:#999;font-size:12px"><?php echo isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : 'N/A'; ?></td>
          <td><span class="status-rejected">❌ Rejected</span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($u['Name']); ?>">
              <button name="action" value="approve" class="approve-btn">✅ Approve</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    event.target.classList.add('active');
}
function toggleMenu() { document.getElementById('dropdownMenu').classList.toggle('active'); document.querySelector('.menu-toggle').classList.toggle('active'); }
function closeMenu() { document.getElementById('dropdownMenu').classList.remove('active'); document.querySelector('.menu-toggle').classList.remove('active'); }
document.addEventListener('click', e => { if (!document.querySelector('.menu-container').contains(e.target)) closeMenu(); });
</script>
</body>
</html>