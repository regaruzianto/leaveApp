# LeaveHub — Leave Request Management System

Aplikasi manajemen cuti karyawan. Karyawan (user) dapat mengajukan permohonan cuti, dan admin dapat menyetujui atau menolak permohonan tersebut. Sistem mengelola sisa kuota cuti secara otomatis.

---

## Tech Stack

| Layer      | Stack                          |
|------------|-------------------------------|
| Backend    | Laravel 12, PHP ≥ 8.2         |
| Database   | PostgreSQL                    |
| Auth       | Laravel Sanctum (PAT)         |
| Frontend   | Vue.js 3 + TypeScript (Vite)  |
| HTTP       | Axios                         |
| State      | Pinia                         |
| Test BE    | PHPUnit 11                    |
| Test FE    | Vitest + Vue Test Utils       |

---

## Struktur Monorepo

```
leave-hub/
├── backend/          # Laravel 12 REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   └── Models/
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── docs/
│   │   └── openapi.yaml    # API docs (Swagger/OpenAPI 3.0)
│   ├── routes/
│   │   └── api.php
│   └── tests/Feature/
└── frontend/         # Vue 3 + TypeScript (Vite)
    └── src/
        ├── components/   # StatusBadge, AlertError, Modal, Loader
        ├── views/
        │   ├── admin/    # AdminLayout, KelolUserView, LeaveRequestsView
        │   └── user/     # UserLayout, SisaKuotaView, AjukanCutiView, RiwayatCutiView
        ├── routes/       # Vue Router + navigation guards
        ├── stores/       # Pinia auth store
        ├── services/     # Axios API services
        ├── types/        # TypeScript interfaces
        ├── plugins/      # helpers.ts, layout.css
        └── tests/        # Vitest tests
```

---

## Cara Instalasi

### Prasyarat

- PHP >= 8.2
- Composer
- PostgreSQL
- Node.js >= 18
- npm

---

### 1. Clone & Setup Backend

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi database PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=leavehub
DB_USERNAME=postgres
DB_PASSWORD=your_password

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

```bash
# Buat database PostgreSQL
# (bisa melalui psql atau pgAdmin)
createdb leavehub

# Jalankan migration
php artisan migrate

# Jalankan seeder (buat admin + leave types)
php artisan db:seed
```

---


### 2. Setup Frontend

```bash
cd frontend

# Install dependencies
npm install
```

---

## Cara Menjalankan Aplikasi

### Jalankan Backend (Laravel)

```bash
cd backend
php artisan serve
# API tersedia di: http://localhost:8000/api
```

### Jalankan Frontend (Vue + Vite)

```bash
cd frontend
npm run dev
# App tersedia di: http://localhost:5173
```

> Frontend sudah dikonfigurasi proxy `/api` → `http://localhost:8000`, sehingga tidak perlu CORS manual saat development.

---

## Akun Default (Seeder)

| Role  | Email                 | Password     |
|-------|-----------------------|--------------|
| Admin | admin@energeek.id     | password123  |

> User dibuat oleh admin melalui dashboard Kelola User (maksimal 2 user).

---

## Cara Menjalankan Testing

### Backend — PHPUnit

```bash
cd backend
php artisan test
# atau
vendor/bin/phpunit
```


## API Documentation

Dokumentasi API tersedia dalam format **OpenAPI 3.0** (kompatibel dengan Swagger UI, Bruno, Insomnia, dan Postman).

File: `backend/docs/openapi.yaml`

### Import ke Postman / Insomnia / Bruno

1. Buka Postman → Import → pilih file `backend/docs/openapi.yaml`
2. Atau di Bruno: New Collection → Import OpenAPI

### Buka via Swagger UI (online)

1. Buka [https://editor.swagger.io](https://editor.swagger.io)
2. File → Import File → pilih `backend/docs/openapi.yaml`

---

## API Endpoints Ringkasan

### Authentication
| Method | Endpoint         | Deskripsi                |
|--------|------------------|--------------------------|
| POST   | /api/auth/login  | Login, dapat token       |
| POST   | /api/auth/logout | Logout, revoke token     |
| GET    | /api/auth/me     | Profil user aktif        |

### Leave Types
| Method | Endpoint          | Deskripsi                |
|--------|------------------|--------------------------|
| GET    | /api/leave-types  | List jenis cuti (dropdown) |

### Leave Balances
| Method | Endpoint             | Deskripsi                     |
|--------|---------------------|-------------------------------|
| GET    | /api/leave-balances  | Sisa kuota cuti user aktif    |

### Leave Requests
| Method | Endpoint                              | Deskripsi                           |
|--------|---------------------------------------|-------------------------------------|
| GET    | /api/leave-requests                   | List request (admin: all, user: own)|
| POST   | /api/leave-requests                   | Submit cuti baru (user)             |
| PATCH  | /api/leave-requests/{id}/cancel       | Cancel request pending (user)       |
| DELETE | /api/leave-requests/{id}              | Soft delete request final           |

### Admin Only
| Method | Endpoint                                    | Deskripsi                       |
|--------|---------------------------------------------|---------------------------------|
| GET    | /api/admin/users                            | List semua user                 |
| POST   | /api/admin/users                            | Buat user baru (maks 2)         |
| PATCH  | /api/admin/users/{id}/password              | Update password user            |
| POST   | /api/admin/leave-requests/{id}/respond      | Approve / Reject request        |

---




