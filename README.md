# Secure Single Vendor E-Commerce System

<p align="center">
  <img src="./public/assets/images/logo.png" alt="Easy Shopping Logo" height="120">
</p>

## 📌 Project Overview


This project is a secure, single-vendor e-commerce web application developed as part of a Work-Based Learning module. The system enables a small retail business to move from manual sales processes to a fully digital platform.

The application allows customers to browse products, select variants, place orders, and make secure online payments using Stripe. It also provides seller and admin functionality for managing inventory, orders, users, and business operations.

---

## 🚀 Features

### Customer Features

- User registration and login
- Browse products by category
- Product variant selection, including size and colour
- Shopping cart
- Checkout
- Secure Stripe payment
- Order history
- Account profile management
- Change password
- Contact form
- Review system

### Seller Features

- Seller dashboard
- Product management
- Product variant management
- Additional product and variant image uploads
- Inventory tracking
- Expiry date tracking for food products
- Order management
- CSV order export
- Business insights dashboard

### Admin Features

- Admin dashboard
- User management
- Role control
- Account activation and deactivation
- Platform settings
- Reports
- System logs

---

## 🔐 Security Features

- Password hashing using PHP `password_hash`
- Password verification using `password_verify`
- Prepared statements to help prevent SQL injection
- Role-based access control for customer, seller, and admin users
- Stripe payment tokenisation
- No card details stored locally
- Webhook handling for Stripe payment confirmation
- Sensitive files excluded from GitHub using `.gitignore`

---

## 🧱 System Architecture

The system uses a simple MVC-style structure:

- `app/Controllers` handles application logic
- `app/Views` contains page templates
- `app/Core` contains routing, database, authentication, middleware, and mailer logic
- `public` contains the front controller, CSS, JavaScript, and images
- `config` contains local configuration files

Main technologies used:

- PHP
- MySQL
- Composer
- PHPMailer
- Stripe API
- CloudPanel hosting environment

---

## 💳 Payment Integration

Stripe is used for secure online payment processing. The system uses:

- Stripe Hosted Checkout
- Stripe test mode
- Webhook confirmation
- No local card storage

To run the payment features, users must create their own Stripe account and add their own Stripe test keys inside `config/config.php`.

Required Stripe values include:

- Stripe publishable key
- Stripe secret key
- Stripe webhook secret

---

## 🗃️ Database

The system uses a MySQL database with key tables including:

- users
- categories
- products
- product_images
- product_variants
- variant_images
- carts
- cart_items
- orders
- order_items
- payments
- stock_movements
- audit_logs
- reviews
- contact_messages
- platform_settings

⚠️ Important:

The database is not automatically created when the project is cloned.

The empty database structure is included in:

database/schema.sql

Import this file through phpMyAdmin or MySQL before running the project.

After importing, register at least three users through the website, then manually update user roles in phpMyAdmin:

- change one user to `seller`
- change one user to `admin`

New registered users are created as `customer` by default.

---

## 👤 User Role Setup

After importing the database structure, users can create accounts through the registration page.

For testing the system, create at least three accounts:

1. Customer account
2. Seller account
3. Admin account

By default, newly registered users are created as customers.

To create seller and admin access, update the `role` field manually in the `users` table using phpMyAdmin.

Example:

```sql
UPDATE users
SET role = 'seller'
WHERE email = 'seller@example.com';

UPDATE users
SET role = 'admin'
WHERE email = 'admin@example.com';
```




---

## 👤 User Roles

Valid roles are:

customer
seller
admin

⚠️ Admin and seller login details are not included in this repository for security and confidentiality reasons.

---

## 📂 Example Database Setup

Create a database, for example:

    CREATE DATABASE workrelateddb;

Then import the schema file located at:

database/schema.sql

The project will not work correctly until the database tables have been imported.

Some parts of the system also require category records before products can be created. Categories can be added manually in phpMyAdmin if needed.

Example category inserts:

    INSERT INTO categories (name, slug) VALUES
    ('Clothing', 'clothing'),
    ('Hair', 'hair'),
    ('Food', 'food'),
    ('Accessories', 'accessories');

---

## ⚙️ Setup Instructions

1. Clone the repository

    git clone https://github.com/WinterFlames007/WRL200-Ecommerce-Project.git

2. Go into the project folder

    cd WRL200-Ecommerce-Project

3. Install dependencies

    composer install

This recreates the vendor folder using the dependencies listed in composer.json.

---

## 🔧 Configuration

Create this file:

config/config.php

Use the provided example file:

config/config.example.php

Add your own local values, including:

Database host
Database name
Database username
Database password
Stripe publishable key
Stripe secret key
Stripe webhook secret
SMTP email settings

⚠️ Important:

Real API keys, passwords, and private credentials are not included in this GitHub repository for security reasons.

---

## 📧 Email Setup

The project uses PHPMailer for email features such as password reset.

To use email features, add your own SMTP settings in config/config.php.

If using Gmail SMTP, create a Gmail App Password instead of using a normal Gmail password.

---

## 🌐 Running the Project

The application should run from the public folder.

For local development, configure Apache, XAMPP, or similar so the document root points to:

/public

For CloudPanel deployment, the domain document root should be changed from:

domain_name.xyz

to:

domain_name.xyz/workrelated/public

Replace domain_name.xyz with the actual domain used in the hosting environment.

---

## 🧪 Testing

The system was tested using:

Functional testing
User acceptance testing
Security testing
Stripe test mode
Role-based access testing
Checkout and order testing

---

## 📊 Development Approach

This project was developed using Agile methodology, with sprint-based iterations and weekly development logs.

---

## 📎 Evidence

Supporting project evidence includes:

Figma designs
System diagrams
ERD
DFD
Architecture diagram
Blogger development logs
Stripe integration
GitHub repository
Deployment evidence
Testing evidence

---

## 🔐 Confidential Information

For security and confidentiality reasons:

Admin credentials are not included
Seller credentials are not included
Stripe keys are not included
Gmail SMTP password is not included
config/config.php is excluded from GitHub
The database file is provided separately with the assignment submission

---

## 👨‍💻 Author

OGBEIDE PRINCE

Work-Based Learning Project

