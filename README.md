Sistem ini merupakan aplikasi internal berbasis Laravel 10 dan Vue 3 (dengan Vite) yang digunakan untuk mengelola aktivitas antara Departemen PPIC dan Departemen Produksi. Aplikasi ini mendukung autentikasi multi-departemen menggunakan Laravel Sanctum dengan pembagian peran pada masing-masing departemen. Akses pengguna dibatasi sesuai departemen dan perannya. Aplikasi ini menerapkan Single Page Application menggunakan Vue Router dan Tailwind CSS, serta menyediakan dashboard terpisah untuk PPIC dan Produksi. Sistem ini dibuat sebagai bagian dari test intern yang saya kerjakan.

Instalasi
- git clone https://github.com/bhatito/test-intern.git

Install dependency Laravel
- composer install

Install dependency frontend
- npm install

Konfigurasi .env
- cp .env.example .env


Contoh isi .env
APP_NAME="PPIC PRODUKSI SYSTEM"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ppic_produksi
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (opsional)
SANCTUM_STATEFUL_DOMAINS=localhost:8000,localhost:5173
SESSION_DOMAIN=localhost

Generate key & migrate database
- php artisan key:generate
- php artisan migrate --seed

data user

Data User 
Departemen	Role	Email	Password
PPIC	Manager	manager.ppic@example.com	password123
PPIC	Staff	staff.ppic@example.com	password123
Produksi	Manager	manager.produksi@example.com	password123
Produksi	Staff	staff.produksi@example.com	password123

Menjalankan Aplikasi

jalankan di terminal yang berbeda 
- terminal 1
php artisan serve
- terminal 2
npm run dev
