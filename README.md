PPIC Produksi System

Sistem ini adalah aplikasi internal berbasis Laravel 10 dan Vue 3 (dengan Vite) untuk mengelola aktivitas antara Departemen PPIC dan Departemen Produksi. Aplikasi ini mendukung autentikasi multi-departemen menggunakan Laravel Sanctum dengan pembagian peran pada masing-masing departemen. Akses pengguna dibatasi berdasarkan departemen dan peran. Aplikasi ini menerapkan Single Page Application menggunakan Vue Router dan Tailwind CSS, dengan dashboard terpisah untuk PPIC dan Produksi.

Prasyarat
- PHP >= 8.1
- Composer
- Node.js >= 16
- NPM
- MySQL

Instalasi

1. Clone repository:
   git clone https://github.com/bhatito/test-intern.git

2. Masuk ke direktori proyek:
   cd test-intern

3. Install dependency Laravel:
   composer install

4. Install dependency frontend:
   npm install

5. Konfigurasi file .env:
   cp .env.example .env

6. Edit file .env sesuai dengan konfigurasi database Anda. Contoh:
   APP_NAME="PPIC PRODUKSI SYSTEM"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ppic_produksi
   DB_USERNAME=root
   DB_PASSWORD=

   SANCTUM_STATEFUL_DOMAINS=localhost:8000,localhost:5173
   SESSION_DOMAIN=localhost

7. Generate application key dan migrasi database:
   php artisan key:generate
   php artisan migrate --seed atau import file database di mysql dengan nama file database.sql

Data Pengguna
Berikut adalah data pengguna awal yang dibuat melalui seeder:

Departemen  Role    Email                          Password
PPIC        Manager manager.ppic@example.com      password123
PPIC        Staff   staff.ppic@example.com        password123
Produksi    Manager manager.produksi@example.com  password123
Produksi    Staff   staff.produksi@example.com    password123

Menjalankan Aplikasi

1. Jalankan server Laravel di terminal pertama:
   php artisan serve

2. Jalankan server frontend (Vite) di terminal kedua:
   npm run dev

3. Akses aplikasi melalui browser di http://localhost:8000.

Catatan
- Pastikan Nama Database sesuai MySQL sudah berjalan sebelum melakukan migrasi database.
- Sesuaikan konfigurasi .env dengan environment Anda.
- Aplikasi ini menggunakan Laravel Sanctum untuk autentikasi, pastikan konfigurasi SANCTUM_STATEFUL_DOMAINS sesuai dengan domain yang digunakan.


NOTED :

- UNTUK BISA MELIHAT SCREANSHOT ADA PADA FOLDER PUBLIK  DAN DI SCREENSHOT PADA PUBLIK ADALAH UNTUK ROLE MANAGER SETIAP DEPARTEMENT 