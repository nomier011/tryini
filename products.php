<?php
session_start();
require_once 'config.php';

requireLogin();

// Get products by main category
$drinks = $conn->query("SELECT * FROM products WHERE main_category = 'drinks' AND is_available = 1 ORDER BY sub_category, name");
$pastry = $conn->query("SELECT * FROM products WHERE main_category = 'pastry' AND is_available = 1 ORDER BY sub_category, name");
$foods = $conn->query("SELECT * FROM products WHERE main_category = 'foods' AND is_available = 1 ORDER BY sub_category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            padding: 20px;
            background: #000;
            position: relative;
            overflow-x: hidden;
        }

        #video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .brand a:hover {
            background: rgba(255,255,255,0.2);
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .brand-text h1 {
            color: white;
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .category-tab {
            padding: 15px 35px;
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .category-tab:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-3px);
        }

        .category-tab.active {
            background: rgba(255,215,0,0.3);
            border-color: #ffd700;
            color: #ffd700;
        }

        .category-tab i {
            font-size: 1.2rem;
        }

        /* Category Sections */
        .category-section {
            display: none;
            margin-bottom: 40px;
        }

        .category-section.active {
            display: block;
        }

        .category-title {
            color: white;
            font-size: clamp(1.3rem, 3vw, 1.8rem);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,215,0,0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-title i {
            color: #ffd700;
        }

        .subcategory-title {
            color: rgba(255,255,255,0.9);
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            margin: 30px 0 20px;
            padding-left: 10px;
            border-left: 3px solid #ffd700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .product-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            overflow: hidden;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: rgba(255,255,255,0.05);
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-image .placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 3rem;
        }

        .product-info {
            padding: 20px;
            flex: 1;
        }

        .product-name {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .product-description {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .product-price {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .product-price::before {
            content: '₱';
            margin-right: 2px;
        }

        .product-stock {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-high {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
        }

        .stock-medium {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
        }

        .stock-low {
            background: rgba(244,67,54,0.2);
            color: #f44336;
        }

        .no-products {
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 60px;
            font-size: 1.2rem;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .category-tabs {
                gap: 10px;
            }
            
            .category-tab {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .category-tabs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .category-tab {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg2.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="brand-text">
                    <h1>Our Menu</h1>
                </div>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <div class="category-tab active" onclick="showCategory('drinks')">
                <i class="fas fa-coffee"></i> Drinks
            </div>
            <div class="category-tab" onclick="showCategory('pastry')">
                <i class="fas fa-bread-slice"></i> Pastry
            </div>
            <div class="category-tab" onclick="showCategory('foods')">
                <i class="fas fa-utensils"></i> Foods
            </div>
        </div>

        <!-- Drinks Section -->
        <div id="drinks-section" class="category-section active">
            <h2 class="category-title"><i class="fas fa-coffee"></i> Drinks</h2>
            
            <?php 
            $current_sub = '';
            if ($drinks && $drinks->num_rows > 0):
                while($product = $drinks->fetch_assoc()): 
                    if ($current_sub != $product['sub_category']):
                        if ($current_sub != '') echo '</div>'; // Close previous grid
                        $current_sub = $product['sub_category'];
            ?>
                <h3 class="subcategory-title"><?php echo htmlspecialchars($current_sub); ?></h3>
                <div class="products-grid">
            <?php endif; ?>
            
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $image_path = "images/" . $product['image'];
                        if (file_exists($image_path) && !empty($product['image'])): 
                        ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-coffee"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'Delicious beverage'); ?></div>
                        <div class="product-details">
                            <span class="product-price"><?php echo number_format($product['price'], 2); ?></span>
                            <?php 
                            $stock_class = 'stock-high';
                            if ($product['stock'] < 10) $stock_class = 'stock-low';
                            elseif ($product['stock'] < 25) $stock_class = 'stock-medium';
                            ?>
                            <span class="product-stock <?php echo $stock_class; ?>">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            
            <?php 
                endwhile;
                echo '</div>'; // Close last grid
            else: 
            ?>
            <div class="no-products">
                <i class="fas fa-coffee" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <p>No drinks available at the moment</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pastry Section -->
        <div id="pastry-section" class="category-section">
            <h2 class="category-title"><i class="fas fa-bread-slice"></i> Pastry</h2>
            
            <?php 
            $current_sub = '';
            if ($pastry && $pastry->num_rows > 0):
                while($product = $pastry->fetch_assoc()): 
                    if ($current_sub != $product['sub_category']):
                        if ($current_sub != '') echo '</div>';
                        $current_sub = $product['sub_category'];
            ?>
                <h3 class="subcategory-title"><?php echo htmlspecialchars($current_sub); ?></h3>
                <div class="products-grid">
            <?php endif; ?>
            
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $image_path = "images/" . $product['image'];
                        if (file_exists($image_path) && !empty($product['image'])): 
                        ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-bread-slice"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'Freshly baked pastry'); ?></div>
                        <div class="product-details">
                            <span class="product-price"><?php echo number_format($product['price'], 2); ?></span>
                            <?php 
                            $stock_class = 'stock-high';
                            if ($product['stock'] < 10) $stock_class = 'stock-low';
                            elseif ($product['stock'] < 25) $stock_class = 'stock-medium';
                            ?>
                            <span class="product-stock <?php echo $stock_class; ?>">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            
            <?php 
                endwhile;
                echo '</div>';
            else: 
            ?>
            <div class="no-products">
                <i class="fas fa-bread-slice" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <p>No pastries available at the moment</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Foods Section -->
        <div id="foods-section" class="category-section">
            <h2 class="category-title"><i class="fas fa-utensils"></i> Foods</h2>
            
            <?php 
            $current_sub = '';
            if ($foods && $foods->num_rows > 0):
                while($product = $foods->fetch_assoc()): 
                    if ($current_sub != $product['sub_category']):
                        if ($current_sub != '') echo '</div>';
                        $current_sub = $product['sub_category'];
            ?>
                <h3 class="subcategory-title"><?php echo htmlspecialchars($current_sub); ?></h3>
                <div class="products-grid">
            <?php endif; ?>
            
                <div class="product-card">
                    <div class="product-image">
                        <?php 
                        $image_path = "images/" . $product['image'];
                        if (file_exists($image_path) && !empty($product['image'])): 
                        ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-utensils"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'Delicious food item'); ?></div>
                        <div class="product-details">
                            <span class="product-price"><?php echo number_format($product['price'], 2); ?></span>
                            <?php 
                            $stock_class = 'stock-high';
                            if ($product['stock'] < 10) $stock_class = 'stock-low';
                            elseif ($product['stock'] < 25) $stock_class = 'stock-medium';
                            ?>
                            <span class="product-stock <?php echo $stock_class; ?>">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            
            <?php 
                endwhile;
                echo '</div>';
            else: 
            ?>
            <div class="no-products">
                <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <p>No food items available at the moment</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showCategory(category) {
            // Hide all sections
            document.getElementById('drinks-section').classList.remove('active');
            document.getElementById('pastry-section').classList.remove('active');
            document.getElementById('foods-section').classList.remove('active');
            
            // Show selected section
            document.getElementById(category + '-section').classList.add('active');
            
            // Update tab active states
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>