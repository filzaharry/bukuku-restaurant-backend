# Bukuku Restaurant Management System - Backend

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
    <strong>Restaurant Management System Backend API</strong><br>
    Built with Laravel 11, JWT Authentication, and MinIO for file storage
</p>

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Setup](#environment-setup)
- [Database Setup](#database-setup)
- [MinIO Setup](#minio-setup)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Security Features](#security-features)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

## 🚀 Features

### Core Functionality
- **JWT Authentication** with OTP verification
- **User Management** with role-based access control
- **F&B Menu Management** (CRUD operations)
- **Kitchen Order Management** with real-time status tracking
- **Order Processing** with table management
- **File Upload** via MinIO integration

### Security Features
- **API Rate Limiting** on critical endpoints
- **Input Validation** with Form Requests
- **Password Hashing** with bcrypt
- **CORS Protection** for cross-origin requests
- **SQL Injection Prevention** with Eloquent ORM

### API Features
- **RESTful API** design
- **JSON Response** format
- **Error Handling** with proper HTTP status codes
- **API Documentation** with structured responses
- **Automated Testing** with PHPUnit

## 📋 Requirements

### System Requirements
- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0 or MariaDB >= 10.3
- **Redis** (for caching and rate limiting)
- **MinIO** (for file storage)
- **Node.js** >= 18 (for frontend assets)

### PHP Extensions
```bash
php-bcmath
php-curl
php-dom
php-fileinfo
php-fpm
php-gd
php-iconv
php-intl
php-json
php-mbstring
php-mysql
php-openssl
php-pcre
php-simplexml
php-tokenizer
php-xml
php-xmlwriter
php-zip
```

## 🛠️ Installation

### 1. Clone Repository
```bash
git clone https://github.com/your-username/bukuku.git
cd bukuku/backend
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment Variables
See [Environment Setup](#environment-setup) section.

### 5. Database Setup
See [Database Setup](#database-setup) section.

### 6. MinIO Setup
See [MinIO Setup](#minio-setup) section.

### 7. Final Setup
```bash
php artisan storage:link
php artisan migrate --seed
php artisan optimize:clear
```

### 8. Start Development Server
```bash
php artisan serve --host=127.0.0.1 --port=8001
```

## ⚙️ Environment Setup

### Environment Variables (.env)

```bash
# Application
APP_NAME="Bukuku Restaurant"
APP_ENV=local
APP_KEY=base64:your-app-key-here
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bukuku_restaurant
DB_USERNAME=root
DB_PASSWORD=your-database-password

# Redis (for caching and rate limiting)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# MinIO Configuration
FILESYSTEM_DISK=minio
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_ACCESS_KEY=minioadmin
MINIO_SECRET_KEY=minioadmin
MINIO_BUCKET_NAME=bukuku-uploads
MINIO_USE_SSL=false
MINIO_REGION=us-east-1

# JWT Authentication
JWT_SECRET=your-jwt-secret-key-here
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Mail Configuration (for OTP)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@bukuku.com"
MAIL_FROM_NAME="${APP_NAME}"

# Rate Limiting
RATE_LIMIT_CACHE_DRIVER=redis
RATE_LIMIT_DRIVER=redis

# CORS
CORS_ALLOWED_ORIGINS="http://localhost:3000,http://127.0.0.1:3000"
```

### Environment File Setup Steps

1. **Copy Environment File**
   ```bash
   cp .env.example .env
   ```

2. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

3. **Generate JWT Secret**
   ```bash
   php artisan jwt:secret
   ```

4. **Update Database Credentials**
   ```bash
   # Edit .env file with your database credentials
   DB_DATABASE=bukuku_restaurant
   DB_USERNAME=your_mysql_user
   DB_PASSWORD=your_mysql_password
   ```

5. **Update MinIO Configuration**
   ```bash
   # Edit .env file with your MinIO settings
   MINIO_ACCESS_KEY=your_minio_access_key
   MINIO_SECRET_KEY=your_minio_secret_key
   MINIO_BUCKET_NAME=bukuku-uploads
   ```

## 🗄️ Database Setup

### MySQL Setup

#### 1. Create Database
```sql
CREATE DATABASE bukuku_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bukuku_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON bukuku_restaurant.* TO 'bukuku_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. Update .env
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bukuku_restaurant
DB_USERNAME=bukuku_user
DB_PASSWORD=strong_password
```

#### 3. Run Migrations
```bash
php artisan migrate
```

#### 4. Run Seeders
```bash
php artisan db:seed
```

### MariaDB Setup

#### 1. Create Database
```sql
CREATE DATABASE bukuku_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bukuku_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON bukuku_restaurant.* TO 'bukuku_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. Update .env
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307  # MariaDB default port
DB_DATABASE=bukuku_restaurant
DB_USERNAME=bukuku_user
DB_PASSWORD=strong_password
```

### Database Migrations

#### Key Migration Files
- `2025_01_22_173058_create_user_levels_table.php` - User role management
- `2025_01_22_173059_create_users_table.php` - User accounts
- `2025_04_30_222224_create_password_resets_table.php` - OTP verification
- `2025_01_22_173112_create_menus_table.php` - Menu categories
- `2025_01_22_173113_create_user_menus_table.php` - User menu access

#### Migration Commands
```bash
# Run all migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=database/migrations/2025_01_22_173058_create_user_levels_table.php

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh --seed

# Rollback migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### Database Seeding

#### Available Seeders
- `UserLevelSeeder` - Creates default user levels (Admin, User Regular, etc.)
- `DatabaseSeeder` - Main seeder that calls all other seeders

#### Seeding Commands
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserLevelSeeder

# Fresh migrate and seed
php artisan migrate:fresh --seed
```

## 🗃️ MinIO Setup

### MinIO Installation

#### Option 1: Docker (Recommended)
```bash
# Create MinIO container
docker run -d \
  --name minio \
  -p 9000:9000 \
  -p 9001:9001 \
  -v minio_data:/data \
  -e "MINIO_ROOT_USER=minioadmin" \
  -e "MINIO_ROOT_PASSWORD=minioadmin" \
  minio/minio server /data --console-address ":9001"
```

#### Option 2: Local Installation
```bash
# Download MinIO
wget https://dl.min.io/server/minio/release/linux-amd64/minio

# Make executable
chmod +x minio

# Create data directory
mkdir -p ~/minio-data

# Run MinIO
./minio server ~/minio-data --console-address ":9001"
```

### MinIO Configuration

#### 1. Access MinIO Console
- **URL**: http://127.0.0.1:9001
- **Username**: minioadmin
- **Password**: minioadmin

#### 2. Create Bucket
1. Login to MinIO console
2. Click "Create Bucket"
3. Enter bucket name: `bukuku-uploads`
4. Set access policy: "Public" or "Private"
5. Click "Create Bucket"

#### 3. Update .env Configuration
```bash
# MinIO Settings
FILESYSTEM_DISK=minio
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_ACCESS_KEY=minioadmin
MINIO_SECRET_KEY=minioadmin
MINIO_BUCKET_NAME=bukuku-uploads
MINIO_USE_SSL=false
MINIO_REGION=us-east-1
```

#### 4. Configure Laravel Filesystem
Update `config/filesystems.php`:
```php
'disks' => [
    'minio' => [
        'driver' => 's3',
        'endpoint' => env('MINIO_ENDPOINT'),
        'use_path_style_endpoint' => true,
        'key' => env('MINIO_ACCESS_KEY'),
        'secret' => env('MINIO_SECRET_KEY'),
        'region' => env('MINIO_REGION'),
        'bucket' => env('MINIO_BUCKET_NAME'),
        'use_ssl' => env('MINIO_USE_SSL', false),
    ],
],
```

### Testing MinIO Integration

#### 1. Test Upload
```bash
php artisan tinker
>>> Storage::disk('minio')->put('test.txt', 'Hello MinIO!');
>>> Storage::disk('minio')->exists('test.txt');
>>> Storage::disk('minio')->get('test.txt');
```

#### 2. Test File URL
```bash
php artisan tinker
>>> $url = Storage::disk('minio')->url('test.txt');
>>> echo $url;
```

## 📚 API Documentation

### Base URL
```
Development: http://127.0.0.1:8001/api/v1
Production: https://your-domain.com/api/v1
```

### Authentication Endpoints

#### Login with OTP Flow
```bash
# Step 1: Login (sends OTP)
POST /auth/login
{
    "email": "user@example.com",
    "password": "password"
}

# Step 2: Verify OTP (returns JWT token)
POST /auth/verify-otp
{
    "email": "user@example.com",
    "otp": "123456",
    "purpose": "login"
}
```

#### Registration
```bash
POST /auth/register
{
    "username": "newuser",
    "fullname": "New User",
    "email": "newuser@example.com",
    "phone": "123456789",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Password Reset
```bash
# Step 1: Forgot password (sends OTP)
POST /auth/forgot-password
{
    "email": "user@example.com"
}

# Step 2: Verify OTP
POST /auth/verify-otp
{
    "email": "user@example.com",
    "otp": "123456",
    "purpose": "forgot_password"
}

# Step 3: Reset password
POST /auth/reset-password
{
    "email": "user@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### F&B Management Endpoints

#### Menu Categories
```bash
GET /admin/fnb/category          # List categories
POST /admin/fnb/category         # Create category
POST /admin/fnb/category/{id}    # Update category
DELETE /admin/fnb/category/{id}  # Delete category
```

#### Menu Items
```bash
GET /admin/fnb/menu              # List menu items
POST /admin/fnb/menu             # Create menu item
POST /admin/fnb/menu/{id}        # Update menu item
DELETE /admin/fnb/menu/{id}      # Delete menu item
```

#### Order Management
```bash
GET /admin/fnb/order             # List orders
POST /admin/fnb/order            # Create order
POST /admin/fnb/order/{id}/pending    # Set status to pending
POST /admin/fnb/order/{id}/preparing  # Set status to preparing
POST /admin/fnb/order/{id}/ready      # Set status to ready
POST /admin/fnb/order/{id}/completed  # Set status to completed
DELETE /admin/fnb/order/{id}          # Delete order
```

### Rate Limiting

All authentication endpoints are rate limited:

| Endpoint | Max Attempts | Time Window |
|----------|--------------|-------------|
| Login | 5 attempts | 1 minute |
| Register | 3 attempts | 1 minute |
| Forgot Password | 3 attempts | 5 minutes |
| Verify OTP | 10 attempts | 1 minute |
| Reset Password | 3 attempts | 5 minutes |

### Response Format

#### Success Response
```json
{
    "statusCode": 200,
    "message": "Success message",
    "data": {
        // Response data here
    }
}
```

#### Error Response
```json
{
    "statusCode": 422,
    "message": "Validation error",
    "data": {
        "field": "Error message"
    }
}
```

#### Rate Limit Response
```json
{
    "statusCode": 429,
    "message": "Too many attempts. Please try again later.",
    "data": {
        "retry_after": 45,
        "max_attempts": 5
    }
}
```

## 🧪 Testing

### Running Tests

#### All Tests
```bash
php artisan test
```

#### Specific Test Class
```bash
php artisan test --filter AuthenticationTest
php artisan test --filter RateLimitTest
php artisan test --filter ApiTest
```

#### Specific Test Method
```bash
php artisan test --filter AuthenticationTest::test_user_registration_with_valid_data
```

#### Test Coverage
```bash
php artisan test --coverage
```

### Available Test Classes

#### AuthenticationTest
- User registration validation
- Login OTP flow
- JWT token generation
- Invalid credentials handling

#### RateLimitTest
- Login endpoint rate limiting
- Register endpoint rate limiting
- Forgot password rate limiting
- OTP verification rate limiting

#### ApiTest
- Basic API functionality
- Authentication flow
- JWT authorization
- Protected endpoint access

### Test Database

Tests use SQLite in-memory database for isolation:

```php
// tests/CreatesApplication.php
protected function setUp(): void
{
    parent::setUp();
    $this->artisan('db:seed', ['--class' => 'UserLevelSeeder']);
}
```

## 🔒 Security Features

### Authentication Security
- **JWT Tokens** with configurable TTL
- **OTP Verification** for login and password reset
- **Password Hashing** with bcrypt
- **Rate Limiting** on authentication endpoints

### API Security
- **Input Validation** with Form Requests
- **SQL Injection Prevention** with Eloquent ORM
- **CORS Protection** for cross-origin requests
- **Request Rate Limiting** for abuse prevention

### File Upload Security
- **MinIO Integration** for secure file storage
- **File Type Validation** for uploads
- **Size Limits** for uploaded files
- **Secure URLs** for file access

### Environment Security
- **Environment Variables** for sensitive data
- **App Key** encryption
- **JWT Secret** for token signing
- **Database Credentials** protection

## 🚀 Deployment

### Production Setup

#### 1. Server Requirements
- **PHP** >= 8.2 with required extensions
- **MySQL** >= 8.0 or MariaDB >= 10.3
- **Redis** for caching
- **MinIO** for file storage
- **Nginx** or **Apache** for web server

#### 2. Environment Configuration
```bash
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Production database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=bukuku_production
DB_USERNAME=bukuku_user
DB_PASSWORD=strong_production_password

# Production MinIO
MINIO_ENDPOINT=https://your-minio-domain.com
MINIO_USE_SSL=true
MINIO_ACCESS_KEY=production_access_key
MINIO_SECRET_KEY=production_secret_key
```

#### 3. Deployment Commands
```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Link storage
php artisan storage:link
```

#### 4. Web Server Configuration

##### Nginx Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/bukuku/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

##### Apache Example
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/bukuku/backend/public

    <Directory /path/to/bukuku/backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Docker Deployment

#### Dockerfile
```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

#### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "9000:9000"
    volumes:
      - .:/var/www/html
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
      - minio

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: bukuku_restaurant
      MYSQL_USER: bukuku_user
      MYSQL_PASSWORD: password
      MYSQL_ROOT_PASSWORD: root_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  minio:
    image: minio/minio
    ports:
      - "9000:9000"
      - "9001:9001"
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin
    command: server /data --console-address ":9001"
    volumes:
      - minio_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "8001:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - .:/var/www/html
    depends_on:
      - app

volumes:
  mysql_data:
  minio_data:
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Migration Errors
```bash
# Error: "No such table: users"
# Solution: Check database connection and run migrations
php artisan migrate:status
php artisan migrate

# Error: "Foreign key constraint fails"
# Solution: Check table order in migrations
php artisan migrate:fresh --seed
```

#### 2. JWT Errors
```bash
# Error: "JWT_SECRET not found"
# Solution: Generate JWT secret
php artisan jwt:secret

# Error: "Token not provided"
# Solution: Check Authorization header format
Authorization: Bearer your-jwt-token-here
```

#### 3. MinIO Connection Issues
```bash
# Error: "Connection refused"
# Solution: Check MinIO is running and endpoint is correct
curl http://127.0.0.1:9000

# Error: "Access denied"
# Solution: Check access keys and bucket permissions
php artisan tinker
>>> Storage::disk('minio')->files()
```

#### 4. Rate Limiting Issues
```bash
# Error: "Rate limit exceeded"
# Solution: Wait for timeout or clear Redis cache
redis-cli FLUSHALL

# Check rate limit status
php artisan tinker
>>> use Illuminate\Support\Facades\RateLimiter;
>>> RateLimiter::attempts('your-key');
```

#### 5. File Upload Issues
```bash
# Error: "File not found"
# Solution: Check storage link and permissions
php artisan storage:link
chmod -R 755 storage

# Error: "File too large"
# Solution: Check php.ini upload limits
upload_max_filesize = 10M
post_max_size = 10M
```

### Debug Mode

#### Enable Debugging
```bash
# Enable debug mode
APP_DEBUG=true

# Clear cache
php artisan optimize:clear

# Check logs
tail -f storage/logs/laravel.log
```

#### Database Debugging
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check tables
>>> DB::select('SHOW TABLES');

# Check migrations
>>> \Schema::hasTable('users');
```

### Performance Optimization

#### Cache Configuration
```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check cache status
php artisan cache:status
```

#### Database Optimization
```bash
# Check slow queries
php artisan db:show --counts

# Optimize tables
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE users');
```

## 📞 Support

### Getting Help
- **Documentation**: Check this README first
- **Issues**: Create GitHub issue for bugs
- **Discussions**: Use GitHub Discussions for questions
- **Email**: Contact development team for support

### Contributing
1. Fork the repository
2. Create feature branch
3. Make your changes
4. Add tests
5. Submit pull request

### License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Built with ❤️ using Laravel 11**
