<?php
// Run this file once to set up the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('cashier', 'manager') DEFAULT 'cashier',
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    last_login DATETIME NULL,
    last_logout DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    main_category ENUM('drinks', 'pastry', 'foods') NOT NULL,
    sub_category VARCHAR(50),
    stock INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default.jpg',
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Products table created successfully<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create orders table (WITHOUT PayMongo columns)
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    cashier_id INT NOT NULL,
    customer_name VARCHAR(100) DEFAULT 'Walk-in',
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    order_status ENUM('pending', 'preparing', 'served', 'cancelled') DEFAULT 'pending',
    cancellation_requested BOOLEAN DEFAULT FALSE,
    cancellation_reason TEXT,
    cancellation_requested_by INT NULL,
    cancellation_requested_at DATETIME NULL,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    FOREIGN KEY (cancellation_requested_by) REFERENCES users(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Orders table created successfully<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Order items table created successfully<br>";
} else {
    echo "Error creating order_items table: " . $conn->error . "<br>";
}

// Create cashier_sessions table
$sql = "CREATE TABLE IF NOT EXISTS cashier_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Cashier sessions table created successfully<br>";
} else {
    echo "Error creating cashier_sessions table: " . $conn->error . "<br>";
}

// Create cancelled_orders table
$sql = "CREATE TABLE IF NOT EXISTS cancelled_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    cashier_id INT NOT NULL,
    cashier_name VARCHAR(50) NOT NULL,
    customer_name VARCHAR(100),
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    cancellation_reason TEXT,
    cancelled_by INT NOT NULL,
    cancelled_by_name VARCHAR(50) NOT NULL,
    original_created_at DATETIME NOT NULL,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Cancelled orders table created successfully<br>";
} else {
    echo "Error creating cancelled_orders table: " . $conn->error . "<br>";
}

// Insert default admin user (password: admin123)
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, role, is_approved) VALUES 
        ('admin', 'admin@coffeeshop.com', '$hashed_password', 'manager', TRUE)";
if ($conn->query($sql) === TRUE) {
    echo "Admin user created (username: admin, password: admin123)<br>";
}

// Insert sample cashier
$hashed_password2 = password_hash('password', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, role, is_approved) VALUES 
        ('cashier1', 'cashier1@coffeeshop.com', '$hashed_password2', 'cashier', TRUE)";
if ($conn->query($sql) === TRUE) {
    echo "Cashier user created (username: cashier1, password: password)<br>";
}

// Insert sample products
$sql = "INSERT IGNORE INTO products (name, description, price, main_category, sub_category, stock, is_available) VALUES
        ('Espresso', 'Strong and rich espresso shot', 120.00, 'drinks', 'Hot Coffee', 100, 1),
        ('Cappuccino', 'Espresso with steamed milk foam', 150.00, 'drinks', 'Hot Coffee', 100, 1),
        ('Latte', 'Smooth espresso with steamed milk', 140.00, 'drinks', 'Hot Coffee', 100, 1),
        ('Iced Americano', 'Chilled espresso with water', 130.00, 'drinks', 'Cold Drinks', 100, 1),
        ('Iced Caramel Macchiato', 'Sweet caramel with espresso', 170.00, 'drinks', 'Cold Drinks', 100, 1),
        ('Chocolate Croissant', 'Flaky croissant with chocolate filling', 90.00, 'pastry', 'Croissants', 50, 1),
        ('Butter Croissant', 'Classic French butter croissant', 80.00, 'pastry', 'Croissants', 50, 1),
        ('Blueberry Muffin', 'Moist muffin with fresh blueberries', 70.00, 'pastry', 'Muffins', 50, 1),
        ('Cheese Danish', 'Cream cheese filled pastry', 85.00, 'pastry', 'Danish', 50, 1),
        ('Club Sandwich', 'Triple-decker with chicken and bacon', 180.00, 'foods', 'Sandwiches', 30, 1),
        ('Caesar Salad', 'Fresh romaine with Caesar dressing', 160.00, 'foods', 'Salads', 30, 1),
        ('Pasta Carbonara', 'Creamy pasta with bacon and egg', 220.00, 'foods', 'Pasta', 30, 1)";

if ($conn->query($sql) === TRUE) {
    echo "Sample products added successfully<br>";
} else {
    echo "Error adding products: " . $conn->error . "<br>";
}

echo "<br><strong>Setup complete! You can now login with:</strong><br>";
echo "Manager: admin / admin123<br>";
echo "Cashier: cashier1 / password<br>";
echo "<a href='login.php'>Go to Login</a>";

$conn->close();
?>