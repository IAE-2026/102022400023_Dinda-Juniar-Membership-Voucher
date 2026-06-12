# 🅿️ Smart Parking System — Service C

**Keanggotaan & Voucher**

Backend service untuk mengelola data **membership** dan **voucher** parkir pada Smart Parking System. Dibangun menggunakan **Laravel** dengan database **MySQL**, dikemas dalam **Docker** container.

---

## 📋 Informasi

| | |
|---|---|
| **Service** | Service C — Keanggotaan & Voucher |
| **Pembuat** | Dinda Juniar |
| **NIM** | 102022400023 |
| **Kelompok** | 6 |
| **Mata Kuliah** | BBK2HAB3 — Integrasi Aplikasi Enterprise |
| **Teknologi** | Laravel 12 · PHP 8.2 · MySQL 8.0 |
| **Port** | 8000 |

---

## 🚀 Menjalankan dengan Docker

```bash
docker-compose up --build -d
```

Setelah berjalan, akses:

| Halaman | URL |
|---------|-----|
| 🏠 Landing Page | http://localhost:8000 |
| 📄 Swagger UI | http://localhost:8000/api/docs |
| 🔮 GraphQL Playground | http://localhost:8000/graphiql |
| 💚 Health Check | http://localhost:8000/health |

### Container

| Nama | Fungsi | Port |
|------|--------|------|
| `service-c-app` | Laravel application | 8000 |
| `service-c-mysql` | MySQL 8.0 database | 3307 |

### Menghentikan

```bash
docker-compose down
```

### Reset Database

```bash
docker-compose down -v
docker-compose up --build -d
```

---

## 🛠️ Menjalankan Lokal (Tanpa Docker)

Pastikan PHP 8.2+ dan MySQL sudah terinstal.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Akses di http://localhost:8000

---

## 🔌 REST API Endpoints

Semua endpoint memerlukan header autentikasi:

```
X-IAE-KEY: 102022400023
```

### Keanggotaan (Membership)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/api/v1/memberships` | Melihat daftar seluruh member |
| `GET` | `/api/v1/memberships/{id}` | Mengecek detail dan status aktif seorang member |
| `POST` | `/api/v1/memberships` | Mendaftarkan member baru |

### Contoh Request

**GET daftar member:**

```bash
curl -H "X-IAE-KEY: 102022400023" http://localhost:8000/api/v1/memberships
```

**POST member baru:**

```bash
curl -X POST http://localhost:8000/api/v1/memberships \
  -H "X-IAE-KEY: 102022400023" \
  -H "Content-Type: application/json" \
  -d '{"name":"Rani Putri","email":"rani@mail.com","phone":"081234567899","membership_type":"perak"}'
```

---

## 📦 Struktur Respon

### Berhasil (2xx)

```json
{
  "status": "success",
  "message": "Data berhasil diambil",
  "data": { "..." },
  "meta": {
    "service_name": "Keanggotaan-Voucher-Service",
    "api_version": "v1"
  }
}
```

### Gagal (4xx/5xx)

```json
{
  "status": "error",
  "message": "Anggota 'MEM999' tidak ditemukan",
  "errors": null
}
```

---

## 🔐 Autentikasi

Service ini menggunakan **API Key** melalui header `X-IAE-KEY`.

| Skenario | Response |
|----------|----------|
| Header tidak dikirim | `401 Unauthorized` |
| Key tidak valid | `403 Forbidden` |
| Key valid | Request diproses |

API Key yang diizinkan (NIM anggota kelompok):
- `102022400023` (Dinda Juniar)
- `102022400039` (Farid Maulana)
- `102022400126` (Hadid Hamar)

---

## 🔮 GraphQL

Service ini menyediakan akses **GraphQL** melalui Lighthouse. Playground tersedia di `/graphiql`.

### Contoh Query

```graphql
{
  memberships {
    id
    member_code
    name
    membership_type
    status
    discount_percent
    registered_at
    expired_at
  }
}
```

```graphql
{
  membership(member_code: "MEM001") {
    name
    email
    membership_type
    discount_percent
  }
}
```

---

## 🗄️ Struktur Database

### Tabel `memberships`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| member_code | string | Kode unik (MEM001, MEM002, ...) |
| name | string | Nama anggota |
| email | string | Email |
| phone | string | Nomor telepon |
| membership_type | enum | perunggu / perak / emas / platina |
| status | enum | aktif / kedaluwarsa |
| discount_percent | integer | Persentase diskon |
| registered_at | datetime | Tanggal daftar |
| expired_at | datetime | Tanggal kedaluwarsa |

### Tabel `vouchers`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| code | string | Kode voucher (WELCOME50, FLAT5K, ...) |
| description | string | Deskripsi voucher |
| discount_type | enum | persen / nominal |
| discount_value | decimal | Nilai diskon |
| max_discount | decimal | Batas maksimal diskon |
| is_used | boolean | Status penggunaan |
| valid_until | datetime | Masa berlaku |

### Tipe Keanggotaan

| Tipe | Diskon |
|------|--------|
| Perunggu | 10% |
| Perak | 15% |
| Emas | 20% |
| Platina | 50% |

---

## 📁 Struktur Project

```
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── MembershipController.php
│   │   └── Middleware/
│   │       └── VerifyApiKey.php

## 🐇 RabbitMQ (Event Publishing)

This service can publish events to RabbitMQ exchange `iae.central.exchange` whenever membership data is retrieved.

1. Install AMQP client:

```bash
composer require php-amqplib/php-amqplib
```

2. Configure connection in `.env` (see `.env.example` for keys):

- `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASS`, `RABBITMQ_EXCHANGE`

3. Events published use routing key `order_created` (exchange `iae.central.exchange`).

If the RabbitMQ library is not installed or the broker is unreachable, publishing is logged and does not affect API responses.
│   └── Models/
│       ├── Membership.php
│       ├── MembershipUsage.php
│       └── Voucher.php
├── database/
│   ├── migrations/
│   └── seeders/
├── graphql/
│   └── schema.graphql
├── resources/views/
│   └── welcome.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── Dockerfile
├── docker-compose.yml
├── docker-entrypoint.sh
└── AI_PROMPT_LOG.md
```

---

## 🔗 Integrasi Antar Service

Service C berperan dalam alur integrasi Smart Parking sebagai penyedia data keanggotaan:

1. **Saat kendaraan masuk** — Service B memanggil `GET /api/v1/memberships/{id}` untuk mengenali profil pengguna
2. **Saat checkout** — Service B memvalidasi status keanggotaan untuk menentukan apakah pengguna berhak mendapat diskon
3. **Setelah pembayaran** — Service B mencatat penggunaan benefit di Service C
