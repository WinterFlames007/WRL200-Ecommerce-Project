# Secure Single Vendor E-Commerce System

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

---

## 🗃️ Database

The system uses a MySQL database with key tables including:

- users
- products
- product_images
- product_variants
- variant_images
- orders
- order_items
- payments
- stock_movements
- reviews
- contact_messages
- platform_settings

⚠️ Important:

The database is not automatically created when the project is cloned.  
A database SQL file will be provided separately with the assignment submission so the required tables can be imported before running the system.

The database should be imported through phpMyAdmin or another MySQL management tool.

---

## ⚙️ Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/WinterFlames007/WRL200-Ecommerce-Project.git
