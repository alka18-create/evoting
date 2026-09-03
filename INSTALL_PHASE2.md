# Instalasi Phase 2 — Administration

## Langkah Instalasi

Semua file Phase 2 sudah dibuat. Sekarang Anda perlu menginstall dependencies yang diperlukan.

### 1. Install Composer Dependencies

Jalankan command berikut di terminal:

```bash
composer install
```

Atau jika Anda menggunakan Docker/Sail:

```bash
./vendor/bin/sail composer install
```

### 2. Publish Config (Opsional)

Jika ingin customize config DomPDF:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### 3. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 4. Test Fitur Export

Setelah install selesai, test fitur-fitur berikut:

#### A. Export Excel - Hasil Pemilihan
1. Buka halaman **Admin → Hasil**
2. Pilih pemilihan yang sudah CLOSED/ARCHIVED
3. Klik tombol **"Export Excel"** (hijau)
4. File `.xlsx` akan terdownload

#### B. Export PDF - Hasil Pemilihan
1. Di halaman yang sama
2. Klik tombol **"Export PDF"** (merah)
3. File `.pdf` akan terdownload

#### C. Export Excel - Daftar Pemilih
1. Buka halaman **Admin → Pemilih**
2. Klik tombol **"Export Excel"** (hijau)
3. File daftar pemilih akan terdownload

#### D. Dashboard dengan Charts
1. Buka **Admin → Dashboard**
2. Anda akan melihat:
   - 4 stat cards (Total Pemilihan, Aktif, Pemilih, Suara)
   - 3 participation cards (Sudah/Belum Memilih, Partisipasi %)
   - 2 charts: Status Pemilihan (donut) dan Partisipasi per Kelas (bar)
   - Tabel pemilihan terbaru

---

## File yang Dibuat/Diubah

### Baru
- `app/Exports/ElectionResultExport.php`
- `app/Exports/VoterListExport.php`
- `resources/views/admin/results/pdf/show.blade.php`

### Diubah
- `composer.json` (tambah maatwebsite/excel + barryvdh/laravel-dompdf)
- `app/Http/Controllers/Admin/ResultController.php`
- `app/Http/Controllers/Admin/VoterController.php`
- `app/Livewire/Admin/DashboardStats.php`
- `resources/views/livewire/admin/dashboard-stats.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/results/show.blade.php`
- `resources/views/admin/voters/index.blade.php`
- `routes/web.php`

---

## Troubleshooting

### Error: Class 'Maatwebsite\Excel\Facades\Excel' not found
**Solusi**: Jalankan `composer install` atau `composer update`

### Error: Class 'Barryvdh\DomPDF\Facade\Pdf' not found
**Solusi**: Sama dengan di atas

### PDF tidak muncul grafik
**Catatan**: DomPDF tidak support JavaScript (Chart.js). Grafik hanya tersedia di halaman web. PDF berisi data dalam bentuk tabel.

### Chart tidak muncul di dashboard
**Solusi**: Pastikan ada data pemilihan dan pemilih. Chart hanya muncul jika ada data.

---

## Next Steps (Phase 3 - Advanced)

Setelah Phase 2 selesai, fitur yang bisa ditambahkan:
- QR Code generation untuk token
- Mode kelas/TPS (grouping)
- Realtime monitoring dengan Laravel Reverb
- Backup otomatis database
- Email notification

---

**Phase 2 — Administration**: ✅ SELESAI
