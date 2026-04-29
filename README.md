# Secure Single Vendor E-Commerce System

## 📌 Project Overview
This project is a secure, single-vendor e-commerce web application developed as part of a Work-Based Learning module. The system enables a small retail business to transition from manual sales processes to a fully digital platform.

The application allows customers to browse products, select variants, place orders, and make secure online payments using Stripe. It also provides administrative and seller functionalities for managing inventory, orders, and users.

---

## 🚀 Features

### Customer Features
- User registration and login
- Browse products by category
- Variant selection (size, colour)
- Shopping cart and checkout
- Secure Stripe payment
- Order history
- Contact form
- Review system

### Seller Features
- Product and variant management
- Inventory tracking (including expiry dates)
- Order management
- Dashboard with business insights

### Admin Features
- User management
- Role control
- Account activation/deactivation

---

## 🔐 Security Features
- Password hashing using PHP `password_hash`
- Prepared statements to prevent SQL injection
- Role-based access control
- Stripe payment tokenisation (no card storage)
- Webhook verification for payment confirmation

---

## 🧱 System Architecture
- PHP (MVC structure)
- MySQL database
- CloudPanel hosting environment
- Stripe API integration
- PHPMailer (SMTP email system)

---

## 💳 Payment Integration
Stripe is used for secure payment processing. The system uses:
- Hosted Checkout
- Webhooks for payment confirmation
- No storage of card details

---

## 🗃️ Database
The system uses MySQL with key tables including:
- users
- products
- product_variants
- orders
- order_items
- payments
- stock_movements
- reviews
- contact_messages

---

## 🧪 Testing
The system was tested using:
- Functional testing
- User acceptance testing
- Security testing
- Stripe test mode

---

## ⚙️ Setup Instructions

### 1. Clone the repository
```bash
git clone https://github.com/WinterFlames007/WRL200-Ecommerce-Project.git
