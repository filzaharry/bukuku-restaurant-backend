# Bukuku Restaurant Management System - Backend API

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
    <strong>Restaurant Management System Backend API</strong><br>
    Built with Laravel 11, JWT Authentication, Redis Caching, and Local Storage
</p>

---

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Setup](#environment-setup)
- [Database Setup](#database-setup)
- [Storage Setup](#storage-setup)
- [Redis & Horizon Setup](#redis--horizon-setup)
- [API Rate Limiting](#api-rate-limiting)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Security Features](#security-features)

---

## 🚀 Features

### Core Functionality

- **JWT Authentication**: Secure token-based auth with OTP verification.
- **User Management**: Role-based access control (Admin, Staff, etc.).
- **F&B Menu & Categories**: Full CRUD operations for menu organization.
- **Kitchen Order Management**: Real-time status tracking (Pending → Preparing → Ready → Completed).
- **Table Management**: Unique QR-based table identification and status tracking.
- **File Storage**: Local storage with symbolic link for public access.

### Performance & Security

- **Redis Caching**: High-performance caching for frequently accessed POS data.
- **Laravel Horizon**: Advanced monitoring for background jobs and queues.
- **API Rate Limiting**: Protection against brute-force and DDoS on critical endpoints.
- **Input Validation**: Strict validation using Laravel Form Requests.

---

## 📋 Requirements

- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0 or MariaDB >= 10.3
- **Redis** 7.0+ (Required for Caching, Rate Limiting, and Horizon)

---

## 🛠️ Installation

1. **Clone & Enter Folder**

    ```bash
    git clone https://github.com/filzaharry/bukuku-restaurant-backend.git
    cd bukuku-restaurant-backend
    ```

2. **Install Dependencies**

    ```bash
    composer install
    ```

3. **Initialize Environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret
    ```

4. **Run Migrations & Seeders**

    ```bash
    php artisan migrate --seed
    ```

5. **Finalize Setup**

    ```bash
    php artisan storage:link
    ```

6. **Start Services**

    ```bash
    php artisan serve --port=8001
    php artisan horizon
    ```

---

## ⚙️ Environment Setup

| Variable            | Description    | Recommended Value |
| ------------------- | -------------- | ----------------- |
| `DB_DATABASE`       | Database Name  | `bukuku`          |
| `FILESYSTEM_DISK`   | Storage Driver | `public`          |
| `QUEUE_CONNECTION`  | Queue Driver   | `redis`           |
| `JWT_TTL`           | Token Lifetime | `60` (minutes)    |

---

## 🗄️ Database Setup (MySQL/MariaDB)

1. **Create Database & User**

    ```sql
    CREATE DATABASE bukuku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'bukuku_user'@'localhost' IDENTIFIED BY 'your_password';
    GRANT ALL PRIVILEGES ON bukuku.* TO 'bukuku_user'@'localhost';
    FLUSH PRIVILEGES;
    ```

2. **Update .env**

    ```bash
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=bukuku
    DB_USERNAME=bukuku_user
    DB_PASSWORD=your_password
    ```

---

## 📁 Storage Setup

By default, the application uses Laravel's local filesystem to store menu images and other media.

1. **Configure .env**
    ```bash
    FILESYSTEM_DISK=public
    ```

2. **Link Storage Folder**
    ```bash
    php artisan storage:link
    ```
    This creates a symbolic link from `public/storage` to `storage/app/public`.

---

## 📊 Redis & Horizon Setup

### 1. Installation

```bash
composer require laravel/horizon
php artisan horizon:install
```

### 2. Running Horizon

```bash
# Start the dashboard and workers
php artisan horizon
```

Access Dashboard: `http://127.0.0.1:8001/horizon`

---

## 🛑 API Rate Limiting

We protect critical endpoints to prevent abuse:

| Endpoint                     | Max Attempts | Time Window |
| ---------------------------- | ------------ | ----------- |
| `POST /auth/login`           | 5            | 1 Minute    |
| `POST /auth/register`        | 3            | 1 Minute    |
| `POST /auth/forgot-password` | 3            | 5 Minutes   |
| `POST /auth/verify-otp`      | 10           | 1 Minute    |

---

## 🧪 Testing

We use PHPUnit for robust automated testing.

```bash
# Run all tests
php artisan test

# Run Specific Critical Tests
php artisan test --filter AuthenticationTest
php artisan test --filter FnbMenuTest
```

---

## 🛡️ Security Features

- **Password Hashing**: Bcrypt encryption for all user passwords.
- **OTP Verification**: Multi-step verification for login and sensitive actions.
- **SQL Injection Protection**: Strictly using Eloquent ORM and Query Builder.
- **CORS Protection**: Configurable allowed origins in `config/cors.php`.

---

**Built with ❤️ by the Bukuku Team**
