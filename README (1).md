# Aplikasi Slip Gaji Karyawan

Aplikasi web sederhana berbasis PHP native + MySQL untuk mengelola data gaji karyawan: input gaji, hitung otomatis gaji bersih, cetak/lihat slip gaji, serta mengirim slip gaji ke karyawan lewat WhatsApp atau Email.

Proyek ini dibuat sebagai tugas **UKK (Ujian Kompetensi Keahlian)**.

## Fitur

- **Login admin** dengan proteksi session (`login.php`, `proses.php`, `logout.php`).
- **Lupa password** — reset password lewat email terdaftar, berlaku untuk tabel `admin` maupun `karyawan` (`lupa-password.php`, `proses_lupa.php`).
- **Data Gaji** (`data_gaji.php`) — daftar seluruh data gaji, lengkap dengan:
  - Pencarian berdasarkan nama karyawan.
  - Filter berdasarkan periode gajian.
  - Aksi Lihat Detail / Edit / Hapus per baris.
- **Tambah Data Gaji** (`tambah_gaji.php`):
  - Wajib memilih periode (bulan & tahun) lebih dulu lewat popup di halaman Data Gaji.
  - Periode otomatis dihitung tanggal 25 → tanggal 25 bulan berikutnya.
  - Input nominal otomatis diberi format ribuan saat mengetik.
  - Preview otomatis Total Penghasilan, Total Potongan, dan Gaji Bersih.
  - Dilindungi CAPTCHA sederhana (perkalian dua angka acak) yang bisa di-refresh via AJAX (`captcha_refresh.php`) tanpa menghapus isi form.
  - Otomatis mendeteksi karyawan yang sudah ada (berdasarkan NIK atau nama) agar tidak membuat data karyawan duplikat.
- **Edit Data Gaji** (`edit_gaji.php`) — mengubah data karyawan sekaligus nominal gaji & periode.
- **Hapus Data Gaji** (`hapus_gaji.php`) — hapus baris data gaji (dengan konfirmasi di sisi tampilan).
- **Slip Gaji** (`slip_gaji.php`):
  - Tampilan slip siap cetak (mode print/PDF lewat `window.print()`).
  - Kirim slip via **WhatsApp** (`wa.me`) dengan pesan otomatis.
  - Kirim slip via **Email** (Gmail compose link) dengan subjek & isi otomatis.
- Semua nominal ditampilkan dalam format Rupiah (`Rp 3.445.000`) lewat helper terpusat di `format.php`, sementara di database tetap disimpan sebagai angka biasa.

## Teknologi

- **Backend:** PHP native (mysqli, prepared statements)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS (custom + Bootstrap 5.3.3 via CDN), JavaScript vanilla
- **Ikon:** Font Awesome 6 (via CDN)

## Struktur Proyek

```
slipgaji_ukk/
├── assets/
│   ├── style.css          # Style halaman login, lupa password, slip gaji
│   └── admin.css          # Style halaman Data Gaji (dashboard admin)
├── captcha_refresh.php    # Endpoint AJAX untuk refresh soal captcha
├── data_gaji.php          # Halaman utama: daftar, cari, filter data gaji
├── edit_gaji.php          # Form edit data gaji + data karyawan
├── format.php             # Kumpulan fungsi format (rupiah, tanggal, periode)
├── hapus_gaji.php         # Proses hapus data gaji
├── koneksi.php            # Koneksi ke database MySQL
├── login.php              # Halaman form login admin
├── logout.php             # Proses logout & hancurkan session
├── lupa-password.php      # Halaman form reset password
├── migration_periode_25.sql  # Skrip migrasi dari sistem periode lama ke periode tetap tgl 25
├── proses.php             # Proses autentikasi login
├── proses_lupa.php        # Proses reset password
├── slip_gaji.php          # Tampilan detail/cetak slip gaji + kirim WA/Email
└── tambah_gaji.php        # Form tambah data gaji baru (dengan captcha)
```

## Struktur Database

Database: `db_slipgaji`

| Tabel      | Keterangan                                                                 |
|------------|------------------------------------------------------------------------------|
| `admin`    | Akun admin (`id`, `email`, `password`)                                       |
| `karyawan` | Data identitas karyawan (`id`, `nama`, `nik`, `email`, `password`, `jabatan`, `no_wa`) |
| `gaji`     | Data gaji per periode, terhubung ke `karyawan` via `karyawan_id` (`gaji_pokok`, `lembur`, `pinjaman_karyawan`, `tanggal_gajian`) |

Relasi: `gaji.karyawan_id` → `karyawan.id` (`ON DELETE CASCADE`).

> Catatan: `tanggal_gajian` menyimpan **tanggal mulai periode** (selalu tanggal 25). Tanggal selesai periode (tanggal 25 bulan berikutnya) dihitung otomatis oleh aplikasi lewat fungsi di `format.php`, tidak disimpan terpisah di database.

## Instalasi & Menjalankan Secara Lokal

1. **Siapkan server lokal** — gunakan XAMPP/Laragon (Apache + MySQL/MariaDB + PHP).
2. **Salin proyek** ke folder web server, misalnya `htdocs/slipgaji_ukk`.
3. **Buat database:**
   - Buka phpMyAdmin, buat database baru bernama `db_slipgaji`.
   - Import file `db_slipgaji (2).sql` yang disertakan (berisi struktur tabel + data contoh).
4. **Sesuaikan koneksi database** bila perlu, di `koneksi.php`:
   ```php
   $host     = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'db_slipgaji';
   ```
5. **Jalankan** dengan mengakses `http://localhost/slipgaji_ukk/login.php` di browser.
6. **Login** menggunakan akun admin bawaan:
   - Email: `admin@gmail.com`
   - Password: `admin123`

## Alur Penggunaan

1. Login sebagai admin.
2. Di halaman **Data Gaji**, klik **+ Tambah Data Gaji** → pilih bulan & tahun periode pada popup.
3. Isi formulir data karyawan dan nominal gaji, jawab captcha, lalu submit.
4. Data langsung muncul di daftar Data Gaji, lengkap dengan gaji bersih yang sudah terhitung.
5. Gunakan menu **⋮** pada setiap baris untuk **Lihat Detail** (buka slip gaji), **Edit**, atau **Hapus**.
6. Di halaman slip gaji, gunakan tombol WhatsApp/Email untuk mengirim slip, atau tombol PDF untuk mencetak/menyimpan sebagai PDF.

## Catatan Keamanan

Proyek ini dibuat untuk keperluan pembelajaran/UKK, sehingga beberapa hal berikut **belum production-ready** dan sebaiknya diperbaiki sebelum dipakai di lingkungan nyata:

- Password admin & karyawan disimpan dalam bentuk **plain text** (belum di-hash, misalnya dengan `password_hash()`).
- Proses login (`proses.php`) tidak membatasi jumlah percobaan (belum ada rate limiting/lockout).
- Query yang melibatkan input pengguna umumnya sudah memakai **prepared statement**, namun tetap disarankan melakukan audit ulang sebelum deployment.

## Migrasi Database

Jika Anda mempunyai database versi lama (masih memakai tabel `periode_gaji`), jalankan `migration_periode_25.sql` melalui tab SQL di phpMyAdmin untuk memindahkan struktur ke sistem periode tetap tanggal 25 → 25. Skrip ini aman dijalankan berulang kali.
