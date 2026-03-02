<?php
session_start();
require_once 'config.php';

$products = $conn->query("SELECT id, name, image FROM products ORDER BY main_category, sub_category");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Check</title>
    <style>
        body { background: #000; color: white; font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #333; }
        .exists { color: #4CAF50; }
        .missing { color: #f44336; }
    </style>
</head>
<body>
    <h1>Product Image Status</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Image File</th>
            <th>Status</th>
        </tr>
        <?php while($product = $products->fetch_assoc()): 
            $image_path = "images/" . $product['image'];
            $exists = file_exists($image_path);
        ?>
        <tr>
            <td><?php echo $product['id']; ?></td>
            <td><?php echo $product['name']; ?></td>
            <td><?php echo $product['image']; ?></td>
            <td class="<?php echo $exists ? 'exists' : 'missing'; ?>">
                <?php echo $exists ? '✓ Image Found' : '✗ Missing'; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>