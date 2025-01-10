# VillageCart

VillageCart is an e-commerce platform designed to provide a simple and efficient online shopping experience for users in rural and village areas. The platform allows users to browse, add products to their cart, and make purchases seamlessly.

## Features

- **Product Catalog**: Browse through a variety of products.
- **Shopping Cart**: Add products to the cart and manage quantities.
- **Order Checkout**: Complete your purchase and proceed with payment.
- **User Authentication**: User sign-up and login features to manage profiles.
- **Mobile-Friendly**: Optimized for use on mobile devices.

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript (optional frameworks like React or Vue if used)
- **Backend**: PHP (using XAMPP's Apache server)
- **Database**: MySQL (managed by XAMPP)
- **Authentication**: Session-based authentication or JWT
- **Deployment**: Local environment using XAMPP

## Installation

To run this project locally using **XAMPP**, follow these steps:

### 1. Clone the repository:

```bash
git clone https://github.com/Manishkumarsingh41/villagecart.git

```
## 2. Move to the XAMPP "htdocs" directory:

By default, XAMPP’s web root directory is located at:
Windows: C:\xampp\htdocs\
macOS: /Applications/XAMPP/htdocs/
Move the cloned repository to this directory:

```bash
mv villagecart /path/to/xampp/htdocs/
```
## 3. Set up the database:
Open phpMyAdmin by navigating to http://localhost/phpmyadmin in your browser.
Create a new database (e.g., villagecart_db).
Import the database schema (if provided) or manually create tables for your project.

## 4. Update database connection settings:
In your project, locate the database connection file (usually something like db.php or config.php in PHP projects) and update the database details like the database name, username, and password.

## php

$servername = "localhost";
$username = "root";  // default XAMPP username is 'root'
$password = "";  // default password is empty
$dbname = "villagecart_db";  // the database you created


## 5. Start XAMPP:
Launch XAMPP Control Panel.
Start Apache and MySQL services.

## 6. Access the project:
Open your browser and navigate to:

## arduino

http://localhost/villagecart/
You should now be able to see and interact with your project.

## Usage
Browse Products: Navigate through the product catalog to view available items.
Add to Cart: Select products and add them to your cart.
Checkout: Proceed to checkout, enter your shipping details, and complete the purchase.
Contributing
We welcome contributions to improve VillageCart! To contribute:

## Fork the repository.
Create a new branch (git checkout -b feature-name).
Make your changes.
Commit your changes (git commit -am 'Add new feature').
Push to your fork (git push origin feature-name).
Submit a pull request.

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Contact
For any inquiries, please contact:

Author: Manish Kumar Singh
GitHub: Manishkumarsingh41
