<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}
if (isset($_GET['reset'])) {
    $editing_product = false;
    header("Location: add_products.php");
    exit();
}

include("config.php");

$admin_name = $_SESSION['admin'];
$message = "";
$error_msg = "";
$editing_product = null;

// ✅ Handle success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        $message = "✅ Product updated successfully!";
    } elseif ($_GET['success'] === 'added') {
        $message = "✅ Product added successfully!";
    } elseif ($_GET['success'] === 'deleted') {
        $message = "✅ Product deleted successfully!";
    }
}

// ════════════ CLOUDINARY UPLOAD HELPER ════════════
function upload_to_cloudinary($file_tmp) {
    $Cloud_Name    = getenv('CLOUDINARY_CLOUD_NAME');
    $Upload_Preset = getenv('CLOUDINARY_UPLOAD_PRESET');
    $Url           = 'https://api.cloudinary.com/v1_1/' . $Cloud_Name . '/image/upload';

    $Post_Data = [
        'file'          => new CURLFile($file_tmp),
        'upload_preset' => $Upload_Preset,
    ];

    $ch = curl_init($Url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $Post_Data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $Response = curl_exec($ch);
    curl_close($ch);

    $Result = json_decode($Response, true);
    return isset($Result['secure_url']) ? $Result['secure_url'] : '';
}

if (isset($_POST['add_product'])) {
    $product_name        = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price       = mysqli_real_escape_string($conn, $_POST['product_price']);
    $product_category    = mysqli_real_escape_string($conn, $_POST['product_category']);
    $product_description = mysqli_real_escape_string($conn, $_POST['product_description']);
    $product_stock       = mysqli_real_escape_string($conn, $_POST['product_stock']);
    $image_path          = "";

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type     = mime_content_type($_FILES['product_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $cloudinary_url = upload_to_cloudinary($_FILES['product_image']['tmp_name']);
            if ($cloudinary_url) {
                $image_path = mysqli_real_escape_string($conn, $cloudinary_url);
            } else {
                $error_msg = "❌ Failed to upload image to Cloudinary.";
            }
        } else {
            $error_msg = "❌ Invalid image type. Only JPG, PNG, WEBP, GIF are allowed.";
        }
    }

    if (!$error_msg && $product_name && $product_price && $product_category && $product_description && $product_stock) {
        $sql = "INSERT INTO products (product_name, price, category, description, stock, image)
                VALUES ('$product_name', '$product_price', '$product_category', '$product_description', '$product_stock', '$image_path')";

        if (mysqli_query($conn, $sql)) {
            header("Location: add_products.php?success=added");
            exit();
        } else {
            $error_msg = "❌ Error adding product: " . mysqli_error($conn);
        }
    } elseif (!$error_msg) {
        $error_msg = "❌ Please fill all fields!";
    }
}

// ════════════ UPDATE PRODUCT ════════════
if (isset($_POST['update_product'])) {
    $product_id          = intval($_POST['product_id']);
    $product_name        = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price       = mysqli_real_escape_string($conn, $_POST['product_price']);
    $product_category    = mysqli_real_escape_string($conn, $_POST['product_category']);
    $product_description = mysqli_real_escape_string($conn, $_POST['product_description']);
    $product_stock       = mysqli_real_escape_string($conn, $_POST['product_stock']);

    // Get current product data
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id='$product_id'"));
    $image_path = $current['image'];

    // Handle new image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type     = mime_content_type($_FILES['product_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $cloudinary_url = upload_to_cloudinary($_FILES['product_image']['tmp_name']);
            if ($cloudinary_url) {
                $image_path = mysqli_real_escape_string($conn, $cloudinary_url);
            } else {
                $error_msg = "❌ Failed to upload image to Cloudinary.";
            }
        } else {
            $error_msg = "❌ Invalid image type. Only JPG, PNG, WEBP, GIF are allowed.";
        }
    }

    if (!$error_msg && $product_name && $product_price && $product_category && $product_description && $product_stock) {
        $sql = "UPDATE products SET 
                product_name='$product_name', 
                price='$product_price', 
                category='$product_category', 
                description='$product_description', 
                stock='$product_stock', 
                image='$image_path' 
                WHERE id='$product_id'";

        if (mysqli_query($conn, $sql)) {
            // ✅ Redirect to reset form to "Add" mode (removes ?edit=ID parameter)
            header("Location: add_products.php?success=updated");
            exit();
        } else {
            $error_msg = "❌ Error updating product: " . mysqli_error($conn);
        }
    } elseif (!$error_msg) {
        $error_msg = "❌ Please fill all fields!";
    }
}

// ════════════ DELETE PRODUCT ════════════
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    $img_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id='$product_id'"));
    if ($img_row && $img_row['image']) {
        // Cloudinary images are URLs — no local file to delete
        // Cloudinary image — nothing to unlink locally
    }

    $sql = "DELETE FROM products WHERE id='$product_id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: add_products.php?success=deleted");
        exit();
    } else {
        $error_msg = "❌ Error deleting product!";
    }
}

if (isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $editing_product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id='$product_id'"));
}

$products_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$total_products = mysqli_num_rows($products_query);

$electronics = mysqli_query($conn, "SELECT * FROM products WHERE category='Electronics' ORDER BY id DESC");
$clothing    = mysqli_query($conn, "SELECT * FROM products WHERE category='Clothing' ORDER BY id DESC");
$books       = mysqli_query($conn, "SELECT * FROM products WHERE category='Books' ORDER BY id DESC");
$furniture   = mysqli_query($conn, "SELECT * FROM products WHERE category='Furniture' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Product Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        /* ════════════ NAVBAR ════════════ */
        .navbar {
            background: linear-gradient(135deg, #ff6b6b 0%, #f2630a 100%);
            padding: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 70px;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 0;
            flex: 1;
        }

        .navbar h1 {
            color: white;
            font-size: 26px;
            padding: 20px 40px;
            margin: 0;
        }

        /* ════════════ DROPDOWN MENU ════════════ */
        .menu-container {
            position: relative;
        }

        .menu-toggle {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 20px 30px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 70px;
        }

        .menu-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .menu-toggle::after {
            content: '▼';
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .menu-toggle.active::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 70px;
            left: 0;
            background: white;
            min-width: 250px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-radius: 0 0 10px 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .dropdown-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .dropdown-menu a:hover {
            background: #f5f5f5;
            border-left-color: #ff6b6b;
            padding-left: 24px;
            color: #ff6b6b;
        }

        .dropdown-menu a .icon {
            font-size: 18px;
        }

        /* ════════════ NAVBAR RIGHT ════════════ */
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-right: 40px;
        }

        .admin-info {
            display: flex;
            gap: 12px;
            align-items: center;
            color: white;
            font-size: 14px;
        }

        .admin-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .logout-btn {
            background: white;
            color: #ff6b6b;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* ════════════ MAIN CONTAINER ════════════ */
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 30px 20px; 
        }

        .header { 
            text-align: center; 
            margin-bottom: 40px; 
        }

        .header h2 { 
            color: #333; 
            font-size: 32px; 
            margin-bottom: 10px; 
        }

        /* ════════════ MESSAGES ════════════ */
        .message { 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-weight: bold; 
        }

        .message.success { 
            background: #d4edda; 
            color: #155724; 
            border-left: 4px solid #28a745; 
        }

        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
            border-left: 4px solid #f5c6cb; 
        }

        /* ════════════ FORM ════════════ */
        .add-product-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .add-product-form h3 { 
            color: #333; 
            margin-bottom: 20px; 
            font-size: 20px; 
        }

        .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        .form-group textarea {
            grid-column: 1 / -1;
            resize: vertical;
            min-height: 100px;
        }

        .form-group input[type="file"] {
            grid-column: 1 / -1;
            padding: 10px;
            background: #fafafa;
            border: 2px dashed #ddd;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 5px rgba(255,107,107,0.3);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .submit-btn, .cancel-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .submit-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #f2630a 100%);
            color: white;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,107,0.3);
        }

        .cancel-btn {
            background: #ddd;
            color: #333;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .cancel-btn:hover {
            background: #ccc;
        }

        .current-image {
            margin-top: 10px;
            max-height: 150px;
        }

        .current-image img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            border: 2px solid #ddd;
        }

        /* ════════════ STATS ════════════ */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            text-align: center; 
        }

        .stat-card .number { 
            font-size: 32px; 
            font-weight: bold; 
            color: #ff6b6b; 
            margin-bottom: 10px; 
        }

        .stat-card .label { 
            color: #666; 
            font-size: 14px; 
        }

        /* ════════════ PRODUCTS SECTION ════════════ */
        .products-section { 
            margin-bottom: 40px; 
        }

        .section-title {
            background: linear-gradient(135deg, #ff6b6b 0%, #f2630a 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
        }

        .product-img-wrap {
            width: 100%;
            height: 200px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            transition: transform 0.4s;
            display: block;
        }

        .product-card:hover .product-img-wrap img { 
            transform: scale(1.06); 
        }

        .product-img-wrap .no-img {
            font-size: 48px;
            color: #ccc;
        }

        .product-body { 
            padding: 16px 20px 20px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
        }

        .product-card h4 { 
            color: #333; 
            margin-bottom: 8px; 
            font-size: 16px; 
        }

        .product-price { 
            color: #ff6b6b; 
            font-size: 20px; 
            font-weight: bold; 
            margin: 8px 0; 
        }

        .product-category {
            background: #ffffff;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            display: inline-block;
        }

        .product-stock { 
            color: #666; 
            font-size: 13px; 
            margin: 6px 0; 
        }

        .product-description { 
            color: #888; 
            font-size: 13px; 
            line-height: 1.4; 
            flex: 1; 
        }

        .product-actions { 
            display: flex; 
            gap: 10px; 
            margin-top: 15px; 
        }

        .edit-btn, .delete-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: transform 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn { 
            background: #667eea; 
            color: white; 
        }

        .delete-btn { 
            background: #ff6b6b; 
            color: white; 
        }

        .edit-btn:hover, .delete-btn:hover { 
            transform: translateY(-2px); 
        }

        .empty-message { 
            text-align: center; 
            padding: 30px; 
            color: #999; 
            background: white; 
            border-radius: 10px; 
        }

        @media (max-width: 768px) {
            .form-group { grid-template-columns: 1fr; }
            .navbar { flex-direction: column; height: auto; }
            .navbar h1 { padding: 15px 20px; font-size: 20px; }
            .navbar-left { width: 100%; }
            .navbar-right { width: 100%; padding: 10px 20px; flex-direction: column; }
            .products-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-left">
        <h1>🛍️ Admin Dashboard</h1>
        
        <div class="menu-container">
            <button class="menu-toggle" onclick="toggleMenu()">
                📋 Menu
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="add_products.php" onclick="closeMenu()">
                    <span class="icon">📦</span>
                    <span>Products details</span>
                </a>
                <a href="admin_home.php" onclick="closeMenu()">
                    <span class="icon">📋</span>
                    <span>Products add</span>
                </a>
                <a href="order_details.php" onclick="closeMenu()">
                    <span class="icon">🛒</span>
                    <span>Order details</span>
                </a>
                <a href="customer_approval.php" onclick="closeMenu()">
                    <span class="icon">👥</span>
                    <span>Customer Approval</span>
                </a>
                <a href="customer_list.php" onclick="closeMenu()">
                    <span class="icon">⚙️</span>
                    <span>Customer List</span>
                </a>
                <a href="transaction_details.php" onclick="closeMenu()">
                    <span class="icon">📊</span>
                    <span>Transaction details</span>
                </a>
                
            </div>
        </div>
    </div>
    <div class="navbar-right">
        <div class="admin-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            <span class="admin-badge">🔐 ADMIN</span>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <h2>Product Management</h2>
    </div>

    <?php if ($message):  ?><div class="message success"><?php echo $message;   ?></div><?php endif; ?>
    <?php if ($error_msg):?><div class="message error">  <?php echo $error_msg; ?></div><?php endif; ?>

    <div class="add-product-form">
        <h3><?php echo $editing_product ? "✏️ Edit Product" : "➕ Add New Product"; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            
            <?php if ($editing_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $editing_product['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <input type="text" name="product_name" placeholder="Product Name" 
                       value="<?php echo $editing_product ? htmlspecialchars($editing_product['product_name']) : ''; ?>" required>
                <input type="number" step="1" name="product_price" placeholder="Price" 
                       value="<?php echo $editing_product ? htmlspecialchars($editing_product['price']) : ''; ?>" required>
                <input type="number" name="product_stock" placeholder="Stock Quantity" 
                       value="<?php echo $editing_product ? htmlspecialchars($editing_product['stock']) : ''; ?>" required>
                <select name="product_category" required>
                    <option value="">Select Category</option>
                    <option value="Electronics" <?php echo ($editing_product && $editing_product['category'] === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                    <option value="Clothing" <?php echo ($editing_product && $editing_product['category'] === 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                    <option value="Books" <?php echo ($editing_product && $editing_product['category'] === 'Books') ? 'selected' : ''; ?>>Books</option>
                    <option value="Furniture" <?php echo ($editing_product && $editing_product['category'] === 'Furniture') ? 'selected' : ''; ?>>Furniture</option>
                </select>
            </div>

            <div class="form-group">
                <textarea name="product_description" placeholder="Product Description" required><?php echo $editing_product ? htmlspecialchars($editing_product['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <input type="file" name="product_image" accept="image/*">
                <?php if ($editing_product && $editing_product['image']): ?>
                    <div class="current-image">
                        <p><strong>Current Image:</strong></p>
                        <img src="<?php echo htmlspecialchars($editing_product['image']); ?>" alt="<?php echo htmlspecialchars($editing_product['product_name']); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" 
                        name="<?php echo $editing_product ? 'update_product' : 'add_product'; ?>" 
                        class="submit-btn">
                    <?php echo $editing_product ? '💾 Update Product' : '➕ Add Product'; ?>
                </button>
                <?php if ($editing_product): ?>
                    <a href="?reset=1" class="cancel-btn">❌ Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="number"><?php echo $total_products; ?></div>
            <div class="label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo mysqli_num_rows($electronics); ?></div>
            <div class="label">Electronics</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo mysqli_num_rows($clothing); ?></div>
            <div class="label">Clothing</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo mysqli_num_rows($books); ?></div>
            <div class="label">Books</div>
        </div>
    </div>

    <?php
    function admin_products($result, $show_category = true) {
        if (mysqli_num_rows($result) === 0) {
            echo "<div class='empty-message'>No products found.</div>";
            return;
        }
        echo "<div class='products-grid'>";
        while ($p = mysqli_fetch_assoc($result)) {
            $img_html = ($p['image'])
                ? "<img src='" . htmlspecialchars($p['image']) . "' alt='" . htmlspecialchars($p['product_name']) . "'>"
                : "<span class='no-img'>🖼️</span>";

            $cat = $show_category
                ? "<div class='product-category'>" . htmlspecialchars($p['category']) . "</div>"
                : "";

            echo "
            <div class='product-card'>
                <div class='product-img-wrap'>{$img_html}</div>
                <div class='product-body'>
                    <h4>" . htmlspecialchars($p['product_name']) . "</h4>
                    {$cat}
                    <div class='product-price'>₹" . number_format((float)$p['price'], 2) . "</div>
                    <div class='product-stock'>Stock: " . htmlspecialchars($p['stock']) . " units</div>
                    <div class='product-description'>" . htmlspecialchars(substr($p['description'], 0, 80)) . "...</div>
                    <div class='product-actions'>
                        <a href='?edit=" . $p['id'] . "' class='edit-btn'>✏️ Edit</a>
                        <a href='?delete=" . $p['id'] . "' class='delete-btn' onclick=\"return confirm('Delete this product?')\">🗑️ Delete</a>
                    </div>
                </div>
            </div>";
        }
        echo "</div>";
    }
    ?>

    <div class="products-section" id="products-section">
        <div class="section-title">📦 All Products</div>
        <?php admin_products($products_query); ?>
    </div>

    <div class="products-section">
        <div class="section-title">⚡ Electronics</div>
        <?php admin_products($electronics, false); ?>
    </div>

    <div class="products-section">
        <div class="section-title">👕 Clothing</div>
        <?php admin_products($clothing, false); ?>
    </div>

    <div class="products-section">
        <div class="section-title">📚 Books</div>
        <?php admin_products($books, false); ?>
    </div>

    <div class="products-section">
        <div class="section-title">🪑 Furniture</div>
        <?php admin_products($furniture, false); ?>
    </div>

</div>

<script>
    function toggleMenu() {
        const dropdownMenu = document.getElementById('dropdownMenu');
        const menuToggle = document.querySelector('.menu-toggle');
        
        dropdownMenu.classList.toggle('active');
        menuToggle.classList.toggle('active');
    }

    function closeMenu() {
        document.getElementById('dropdownMenu').classList.remove('active');
        document.querySelector('.menu-toggle').classList.remove('active');
    }

    document.addEventListener('click', function(event) {
        const menuContainer = document.querySelector('.menu-container');
        if (!menuContainer.contains(event.target)) {
            closeMenu();
        }
    });

    window.addEventListener('load', function() {
        <?php if ($editing_product): ?>
            document.querySelector('.add-product-form').scrollIntoView({ behavior: 'smooth' });
        <?php endif; ?>
    });
</script>

</body>
</html>