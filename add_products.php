<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit(); }
include("config.php");
$admin_name = $_SESSION['admin'];

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$total    = mysqli_num_rows($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Details</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👑</text></svg>">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; }

.navbar {
    background: linear-gradient(135deg,#ff6b6b,#f2630a);
    display:flex; justify-content:space-between; align-items:center;
    height:70px; padding:0; position:sticky; top:0; z-index:1000;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
}
.navbar-left { display:flex; align-items:center; }
.navbar h1 { color:white; font-size:22px; padding:0 30px; }
.menu-container { position:relative; }
.menu-toggle {
    background:rgba(255,255,255,0.2); color:white; border:none;
    padding:0 25px; height:70px; font-size:15px; font-weight:bold;
    cursor:pointer; display:flex; align-items:center; gap:8px;
}
.menu-toggle::after { content:'▼'; font-size:11px; transition:transform 0.3s; }
.menu-toggle.active::after { transform:rotate(180deg); }
.dropdown-menu {
    position:absolute; top:70px; left:0; background:white;
    min-width:240px; box-shadow:0 10px 30px rgba(0,0,0,0.15);
    border-radius:0 0 10px 10px; opacity:0; visibility:hidden;
    transform:translateY(-10px); transition:all 0.3s; z-index:999;
}
.dropdown-menu.active { opacity:1; visibility:visible; transform:translateY(0); }
.dropdown-menu a {
    display:flex; align-items:center; gap:12px; padding:14px 20px;
    color:#333; text-decoration:none; border-left:4px solid transparent;
    transition:all 0.2s;
}
.dropdown-menu a:hover { background:#f5f5f5; border-left-color:#ff6b6b; color:#ff6b6b; padding-left:24px; }
.dropdown-menu a.active-link { background:#fff5f5; border-left-color:#ff6b6b; color:#ff6b6b; }
.navbar-right { display:flex; align-items:center; gap:15px; padding-right:30px; }
.admin-badge { background:rgba(255,255,255,0.2); color:white; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:bold; }
.logout-btn { background:white; color:#ff6b6b; padding:9px 20px; border-radius:5px; font-weight:bold; text-decoration:none; }

.container { max-width:1400px; margin:0 auto; padding:30px 20px; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.page-header h2 { font-size:26px; color:#333; }
.badge { background:#ff6b6b; color:white; padding:5px 14px; border-radius:20px; font-size:13px; }
.add-btn { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; padding:10px 22px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:14px; }

table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08); }
thead { background:linear-gradient(135deg,#ff6b6b,#f2630a); color:white; }
th, td { padding:14px 16px; text-align:left; font-size:14px; }
tbody tr { border-bottom:1px solid #f0f0f0; transition:background 0.2s; }
tbody tr:hover { background:#fff8f8; }
.product-img { width:55px; height:55px; object-fit:contain; border-radius:6px; border:1px solid #eee; background:#fafafa; }
.no-img { width:55px; height:55px; background:#f0f0f0; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:22px; }
.cat-badge { background:#f0f0f0; color:#666; padding:3px 10px; border-radius:12px; font-size:12px; }
.price { color:#ff6b6b; font-weight:bold; }
.stock-ok { color:#28a745; font-weight:600; }
.stock-low { color:#f5a623; font-weight:600; }
.stock-out { color:#ff6b6b; font-weight:600; }
.action-btns { display:flex; gap:8px; }
.edit-btn { background:#667eea; color:white; padding:6px 14px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; }
.delete-btn { background:#ff6b6b; color:white; padding:6px 14px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; }
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
        <a href="add_products.php" class="active-link" onclick="closeMenu()"><span>📦</span><span>Product Details</span></a>
        <a href="admin_home.php" onclick="closeMenu()"><span>📋</span><span>Add Product</span></a>
        <a href="order_details.php" onclick="closeMenu()"><span>🛒</span><span>Order Details</span></a>
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
  <div class="page-header">
    <h2>📦 Product Details <span class="badge"><?php echo $total; ?> products</span></h2>
    <a href="admin_home.php" class="add-btn">➕ Add New Product</a>
  </div>

  <?php if ($total === 0): ?>
    <div class="empty">No products yet. <a href="admin_home.php">Add one!</a></div>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>#</th><th>Image</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Added</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php $i=1; while($p = mysqli_fetch_assoc($products)): 
      $stock = (int)$p['stock'];
      $sc = $stock > 10 ? 'stock-ok' : ($stock > 0 ? 'stock-low' : 'stock-out');
    ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td>
          <?php if($p['image']): ?>
            <img src="<?php echo htmlspecialchars($p['image']); ?>" class="product-img">
          <?php else: ?><div class="no-img">🖼️</div><?php endif; ?>
        </td>
        <td><strong><?php echo htmlspecialchars($p['product_name']); ?></strong><br><small style="color:#999"><?php echo htmlspecialchars(substr($p['description'],0,50)); ?>...</small></td>
        <td><span class="cat-badge"><?php echo htmlspecialchars($p['category']); ?></span></td>
        <td class="price">₹<?php echo number_format($p['price'],2); ?></td>
        <td class="<?php echo $sc; ?>"><?php echo $stock; ?> units</td>
        <td style="color:#999;font-size:13px"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
        <td>
          <div class="action-btns">
            <a href="admin_home.php?edit=<?php echo $p['id']; ?>" class="edit-btn">✏️ Edit</a>
            <a href="admin_home.php?delete=<?php echo $p['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?')">🗑️ Del</a>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function toggleMenu() {
    document.getElementById('dropdownMenu').classList.toggle('active');
    document.querySelector('.menu-toggle').classList.toggle('active');
}
function closeMenu() {
    document.getElementById('dropdownMenu').classList.remove('active');
    document.querySelector('.menu-toggle').classList.remove('active');
}
document.addEventListener('click', e => {
    if (!document.querySelector('.menu-container').contains(e.target)) closeMenu();
});
</script>
</body>
</html>