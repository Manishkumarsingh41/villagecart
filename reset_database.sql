DROP DATABASE IF EXISTS villagecart;
CREATE DATABASE villagecart;
USE villagecart;

-- Copy contents of database.sql here
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'shop_owner', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    shop_name VARCHAR(100) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(20),
    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock INT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    contact_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_product ON cart(product_id);
CREATE INDEX idx_products_shop ON products(shop_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);

-- Insert sample data
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('seller', 'seller@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'shop_owner'),
('customer', 'customer@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

INSERT INTO categories (name, description) VALUES
('Handicrafts', 'Traditional handmade crafts and artifacts'),
('Pottery', 'Handmade clay pots and items'),
('Vegetables', 'Fresh farm vegetables'),
('Fruits', 'Fresh seasonal fruits');

INSERT INTO shops (user_id, shop_name, description, address, phone, status) VALUES
(2, 'Village Handicrafts', 'Traditional handmade crafts from local artisans', 'Main Street, Village Center', '9876543210', 'active'),
(2, 'Farm Fresh Produce', 'Fresh vegetables and fruits directly from farmers', 'Market Road, Village Square', '9876543211', 'active');

INSERT INTO products (shop_id, category_id, name, description, price, discount_price, stock, image_url, status) VALUES
(1, 1, 'Handwoven Basket', 'Traditional bamboo basket, handwoven by local artisans', 499.00, 399.00, 50, '../assets/images/products/basket.jpg', 'active'),
(1, 2, 'Clay Water Pot', 'Traditional clay water pot for natural cooling', 299.00, NULL, 30, '../assets/images/products/waterpot.jpg', 'active'),
(2, 3, 'Organic Tomatoes', 'Fresh organic tomatoes from local farms', 40.00, 35.00, 100, '../assets/images/products/tomatoes.jpg', 'active'),
(2, 4, 'Sweet Mangoes', 'Fresh Alfonso mangoes from local orchards', 199.00, NULL, 75, '../assets/images/products/mangoes.jpg', 'active');
