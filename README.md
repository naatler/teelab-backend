# TeeLab Backend

API Backend untuk aplikasi e-commerce golf TeeLab, dibangun dengan Laravel 11.

## 📋 Teknologi
- **Framework**: Laravel 11
- **Database**: MySQL / MariaDB
- **Autentikasi**: Laravel Sanctum
- **Payment Gateway**: Xendit
- **File Storage**: Local / S3

## 🚀 Instalasi

### 1. Prasyarat
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+

### 2. Setup Project
```bash
# Install dependensi
composer install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Konfigurasi database pada file .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teelab
DB_USERNAME=root
DB_PASSWORD=

# Jalankan migrasi database
php artisan migrate

# Jalankan seeder (opsional)
php artisan db:seed

# Link storage
php artisan storage:link
```

### 3. Konfigurasi Tambahan
```env
# Payment Gateway
XENDIT_SECRET_KEY=your_xendit_key
XENDIT_CALLBACK_TOKEN=your_callback_token

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
FRONTEND_URL=http://localhost:3000
```

### 4. Jalankan Server
```bash
php artisan serve
```
Server akan berjalan di `http://localhost:8000`

## Struktur Proyek
```
app/
├── Http/
│   ├── Controllers/API/   # Semua controller API
│   └── Middleware/        # Middleware kustom
├── Models/                # Eloquent Models
└── Policies/              # Authorization Policies

routes/
└── api.php                # Routing API

database/
├── migrations/            # File migrasi database
└── seeders/               # Data seeder
```

## 🔌 API Endpoints

### Auth
| Method | Endpoint          | Deskripsi              |
|--------|-------------------|------------------------|
| POST   | `/api/auth/register` | Registrasi user baru |
| POST   | `/api/auth/login`    | Login user           |
| POST   | `/api/auth/logout`   | Logout user          |
| GET    | `/api/auth/me`       | Data user saat ini   |

### Produk
| Method | Endpoint          | Deskripsi              |
|--------|-------------------|------------------------|
| GET    | `/api/products`      | Daftar semua produk |
| GET    | `/api/products/{id}` | Detail produk       |
| GET    | `/api/categories`    | Daftar kategori     |

### Orders & Cart
| Method | Endpoint          | Deskripsi              |
|--------|-------------------|------------------------|
| GET    | `/api/cart`          | Cart user           |
| POST   | `/api/cart/items`    | Tambah item ke cart |
| GET    | `/api/orders`        | Daftar order user   |
| POST   | `/api/orders`        | Buat order baru     |

### Reviews
| Method | Endpoint          | Deskripsi              |
|--------|-------------------|------------------------|
| GET    | `/api/reviews`         | Daftar review        |
| GET    | `/api/reviews/featured`| Review terpopuler     |
| POST   | `/api/reviews`         | Tambah review (auth) |

## 🛠️ Development Commands
```bash
# Jalankan migrasi
php artisan migrate

# Rollback migrasi
php artisan migrate:rollback

# Refresh database + seed
php artisan migrate:fresh --seed

# Clear cache
php artisan optimize:clear

# Cek routing
php artisan route:list
```

## Webhook Payment
Endpoint untuk callback payment gateway:
```
POST /api/xendit/webhook
```

## Autentikasi
Semua endpoint protected memerlukan header:
```
Authorization: Bearer {token}
```

Token didapatkan setelah login berhasil.