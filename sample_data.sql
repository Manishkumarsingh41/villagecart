-- Insert sample users (password is 'password123')
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('seller', 'seller@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'shop_owner'),
('customer', 'customer@villagecart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Handicrafts', 'Traditional handmade crafts and artifacts'),
('Pottery', 'Handmade clay pots and items'),
('Vegetables', 'Fresh farm vegetables'),
('Fruits', 'Fresh seasonal fruits');

-- Insert sample shops
INSERT INTO shops (user_id, shop_name, description, address, phone, status) VALUES
(2, 'Village Handicrafts', 'Traditional handmade crafts from local artisans', 'Main Street, Village Center', '9876543210', 'active'),
(2, 'Farm Fresh Produce', 'Fresh vegetables and fruits directly from farmers', 'Market Road, Village Square', '9876543211', 'active');

-- Insert sample products
INSERT INTO products (shop_id, category_id, name, description, price, discount_price, stock, image_url, status) VALUES
(1, 1, 'Handwoven Basket', 'Traditional bamboo basket, handwoven by local artisans', 499.00, 399.00, 50, '../assets/images/products/basket.jpg', 'active'),
(1, 2, 'Clay Water Pot', 'Traditional clay water pot for natural cooling', 299.00, NULL, 30, '../assets/images/products/waterpot.jpg', 'active'),
(2, 3, 'Organic Tomatoes', 'Fresh organic tomatoes from local farms', 40.00, 35.00, 100, '../assets/images/products/tomatoes.jpg', 'active'),
(2, 4, 'Sweet Mangoes', 'Fresh Alfonso mangoes from local orchards', 199.00, NULL, 75, '../assets/images/products/mangoes.jpg', 'active');
