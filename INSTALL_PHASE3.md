# Panduan Instalasi Phase 3 - Fitur Lanjutan

## Fitur yang Ditambahkan

### 1. QR Code pada Kartu Token
- QR Code berisi JSON: `{student_id, token, election_id}`
- Ditampilkan pada halaman cetak kartu (single & bulk)
- Digunakan untuk verifikasi oleh operator via scanner

### 2. QR Scanner untuk Operator
- Halaman `/admin/scan` dengan akses kamera
- Menggunakan library `html5-qrcode`
- Memindai QR Code dan memverifikasi token
- Audit log otomatis untuk setiap scan

### 3. Realtime Monitoring (Laravel Reverb)
- Event `VoteCasted` di-broadcast saat ada voting
- Dashboard admin menampilkan notifikasi real-time
- Livewire component auto-refresh saat ada suara baru
- Channel: `election.{id}` per pemilihan

### 4. Backup Database (Spatie Laravel Backup)
- Halaman `/admin/backups` (hanya SuperAdmin)
- Buat backup manual (hanya database)
- Download dan hapus backup
- File disimpan di `storage/app/backups/`

---

## Instalasi

### Step 1: Install Dependencies PHP

```bash
composer update
```

> Packages yang ditambahkan:
> - `simplesoftwareio/simple-qrcode` ^4.2
> - `spatie/laravel-backup` ^9.0

### Step 2: Install Dependencies JS (jika belum)

```bash
npm install
npm run build
```

> Packages yang sudah ada:
> - `laravel-echo` ^1.18.0
> - `pusher-js` ^8.4.0
> - `html5-qrcode` (di-load via CDN di blade)

### Step 3: Migrate & Seed

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### Step 4: Jalankan Service Reverb (untuk Realtime)

```bash
docker compose exec app php artisan reverb:start
```

> Reverb berjalan di port 8080 (sudah dikonfigurasi di docker-compose.yml)

### Step 5: Jalankan Queue Worker (untuk Broadcast)

```bash
docker compose exec app php artisan queue:work
```

> Queue worker sudah berjalan di container `queue-worker`

### Step 6: Build Vite (untuk Echo client)

```bash
npm run build
```

---

## Akses

| Fitur | URL | Akses |
|-------|-----|-------|
| QR Scanner | `/admin/scan` | Semua role |
| Backup | `/admin/backups` | SuperAdmin saja |
| Dashboard Realtime | `/admin` | Semua role |

---

## Konfigurasi

### .env (sudah dikonfigurasi)

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=evoting-local
REVERB_APP_KEY=evoting-local-key
REVERB_APP_SECRET=evoting-local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Backup Config

File `config/backup.php` sudah dikonfigurasi untuk:
- Backup ke disk `local` (storage/app/backups/)
- Hanya database (bukan file)
- Compressed dengan gzip

---

## Cara Kerja Realtime

1. Pemilih melakukan voting di `/vote`
2. `VotingController::vote()` memanggil `broadcast(new VoteCasted(...))`
3. Reverb mengirim event ke channel `election.{id}`
4. Dashboard admin menerima event via Laravel Echo
5. Livewire component `DashboardStats` refresh otomatis
6. Notifikasi muncul di pojok kanan bawah

---

## Troubleshooting

### QR Scanner tidak bisa akses kamera
- Pastikan menggunakan HTTPS atau localhost
- Izinkan akses kamera di browser

### Reverb tidak connect
- Cek apakah container reverb berjalan: `docker compose ps`
- Cek log: `docker compose logs reverb`

### Backup gagal
- Pastikan directory `storage/app/backups/` writable
- Cek log: `docker compose logs app`
