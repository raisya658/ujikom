# Persiapan Wawancara Asesor UKK — Junior Web Programmer
### Dipetakan langsung dari "Soal Latihan UKK 2026" ke Project Aplikasi Slip Gaji Karyawan

---

## Cara Pakai Dokumen Ini

Lembar soal UKK kamu punya **2 kelompok pekerjaan** dengan total **8 unit kompetensi**. Asesor biasanya bertanya berdasarkan **indikator penilaian** di halaman terakhir soal, bentuknya pertanyaan "kenapa kamu bikin ini begini", "coba jelasin ini jalan gimana", atau "coba tunjukkin di kode yang mana". Dokumen ini menjawab tiap indikator itu **pakai contoh nyata dari project kamu sendiri**, supaya jawabanmu konkret, bukan teori kosong.

---

## RINGKASAN PEMETAAN

| Kelompok Pekerjaan | Unit Kompetensi | Sudah tercermin di project kamu sebagai... |
|---|---|---|
| 1. Merancang UI | Mengimplementasikan UI | Wireframe → Mockup → jadi halaman nyata (login, data gaji, tambah/edit gaji, slip gaji) |
| 1. Merancang UI | Instalasi Software Tools | XAMPP/Laragon (Apache+PHP+MySQL) + browser + text editor |
| 1. Merancang UI | Guidelines & Best Practices | Penamaan file konsisten, komentar kode, struktur folder rapi |
| 2. Menulis Kode | Struktur Data | Array asosiatif (`$form`, `$nilai`), hasil query (`mysqli_fetch_assoc`), tabel database |
| 2. Menulis Kode | Pemrograman Terstruktur | Pemisahan file per fungsi (`koneksi.php`, `format.php`, per halaman), fungsi reusable |
| 2. Menulis Kode | Library Pihak Ketiga | Bootstrap 5, Font Awesome, mysqli extension, WhatsApp/Gmail deep link |
| 2. Menulis Kode | Dokumentasi Kode | Komentar `//` dan `/* */` di hampir semua file menjelaskan alasan kode dibuat |
| 2. Menulis Kode | Debugging | Validasi input, `try/catch` + transaction, cerita nyata bug "karyawan duplikat" yang sudah pernah diperbaiki |

---

# KELOMPOK PEKERJAAN 1 — MERANCANG USER INTERFACE

> Soal aslinya minta kamu membuat **wireframe** dan **mockup** dari nol dalam 90 menit, dikirim ke Google Drive, pakai software UI/UX (Figma, dsb). Ini **tahap terpisah sebelum** coding. Tapi karena project kamu sudah jadi aplikasi utuh, asesor kemungkinan besar akan tanya **bagaimana proses berpikirmu dari wireframe sampai jadi kode**, bukan cuma lihat hasil akhir.

## Unit 1: J.620100.005.02 — Mengimplementasikan User Interface

**Indikator penilaian terkait:**
- Mampu membuat wireframe/mockup tampilan aplikasi
- Mampu menerjemahkan kebutuhan pengguna (user requirement) menjadi desain

### Pertanyaan yang mungkin muncul + cara jawab

**Q: "Coba jelaskan, kenapa desain halaman Login-mu seperti ini? Kenapa nggak langsung ke halaman data gaji?"**
> A: Karena data gaji itu data sensitif, jadi harus ada lapisan otentikasi dulu (login) sebelum admin bisa mengakses data karyawan. Di wireframe, alur pertama memang selalu mulai dari Login → baru masuk ke halaman utama (Data Gaji). Ini menerjemahkan kebutuhan bisnis: "hanya admin yang boleh lihat/ubah data gaji".

**Q: "Kenapa ada popup 'Pilih Periode' sebelum form Tambah Data Gaji, bukan langsung satu form saja?"**
> A: Ini hasil analisis user requirement: periode gajian itu wajib ada di setiap data gaji, tapi kalau digabung dalam satu form panjang, admin bisa lupa mengisi atau salah pilih di tengah-tengah proses input. Jadi saya pisah jadi 2 langkah: pertama tentukan **konteks** (periode), baru isi **detail** (nama, nominal). Ini juga mencegah data tersimpan tanpa periode yang valid — kalau admin coba akses form tanpa lewat popup, sistem otomatis melempar balik ke popup (lihat `tambah_gaji.php`, dicek dengan `preg_match` format `YYYY-MM`).

**Q: "Kenapa slip gaji dipisah dari halaman edit/tambah data gaji?"**
> A: Karena fungsinya beda: form tambah/edit itu untuk **input data** (bisa diubah, punya validasi), sedangkan slip gaji itu untuk **menampilkan hasil akhir** yang siap dibagikan/dicetak ke karyawan (read-only, ada tombol kirim WA/Email/PDF). Memisahkan keduanya membuat setiap halaman fokus ke satu tanggung jawab (mirip prinsip *single responsibility*).

**Q: "Kalau saya minta kamu tambah 1 halaman baru sekarang, misalnya laporan gaji bulanan, langkahnya apa aja?"**
> A: Saya akan mulai dari wireframe dulu (kotak-kotak kasar: filter bulan, tabel ringkasan per karyawan, total keseluruhan) → mockup dengan warna/style yang konsisten dengan halaman lain (pakai warna tema `#2b6b85` dan Bootstrap yang sudah dipakai) → baru menulis kode PHP-nya (query SUM/GROUP BY ke tabel `gaji`) supaya sesuai desain.

## Unit 2: J.620100.011.01 — Instalasi Software Tools Pemrograman

**Pertanyaan yang mungkin muncul:**

**Q: "Tools apa saja yang kamu install dan pakai untuk mengerjakan project ini?"**
> A:
> - **XAMPP/Laragon** — paket Apache (web server), PHP, dan MySQL/MariaDB dalam satu instalasi, supaya bisa menjalankan aplikasi PHP secara lokal.
> - **phpMyAdmin** — untuk membuat database `db_slipgaji` dan mengimpor file `.sql`.
> - **Text editor / code editor** (VS Code, Sublime, dll) — untuk menulis kode.
> - **Browser (Chrome)** — untuk testing tampilan, termasuk fitur DevTools untuk cek tampilan responsive di berbagai ukuran layar.

**Q: "Kenapa pakai XAMPP, bukan install Apache/PHP/MySQL satu-satu?"**
> A: Supaya lebih praktis — semua komponen yang dibutuhkan PHP (web server + PHP interpreter + database) sudah jadi satu paket, tidak perlu konfigurasi manual dari nol, cocok untuk kebutuhan development lokal seperti Ujikom ini.

## Unit 3: J.620100.016.01 — Menulis Kode Sesuai Guidelines & Best Practices

*(Catatan: di tabel soal, unit ini masuk Kelompok Pekerjaan 1, tapi secara isi sebenarnya nyambung ke penulisan kode. Siapkan jawaban dari dua sisi: standar desain DAN standar kode.)*

**Q: "Standar/guideline apa yang kamu terapkan di project ini?"**
> A:
> - **Penamaan file & folder konsisten** — misalnya semua file terkait gaji diawali/berkaitan jelas: `tambah_gaji.php`, `edit_gaji.php`, `hapus_gaji.php`, `data_gaji.php`.
> - **Pemisahan tanggung jawab** — koneksi database di satu file (`koneksi.php`), fungsi format & perhitungan di satu file (`format.php`), supaya tidak ditulis ulang di banyak tempat.
> - **Konsistensi visual** — warna tema (`#2b6b85`, `#195b8c`), struktur form yang sama antar halaman.
> - **Guard clause / pengecekan sesi login** — di setiap halaman yang butuh login, baris pertama selalu cek `if (!isset($_SESSION['user_email']))` sebelum eksekusi kode lain.

---

# KELOMPOK PEKERJAAN 2 — MENULIS KODE PEMROGRAMAN

> Soal aslinya minta: (1) convert mockup jadi web, (2) tulis kode tiap halaman, (3) tambah library pihak ketiga (email, WhatsApp, cetak PDF), (4) hitung gaji bersih dengan rumus tertentu. **Semua ini sudah ada di project kamu** — bagian ini yang paling penting dikuasai karena paling teknis.

## Unit 4: J.620100.004.02 — Menggunakan Struktur Data

**Indikator: kode ditulis dengan struktur yang rapi dan mudah dibaca.**

**Q: "Struktur data apa saja yang kamu pakai di project ini? Kasih contoh konkret."**
> A: Beberapa contoh:
> 1. **Array asosiatif** untuk menampung data form sebelum divalidasi, contoh di `tambah_gaji.php`:
>    ```php
>    $form = [
>        'nama' => '', 'nik' => '', 'jabatan' => '',
>        'gaji_pokok' => 0, 'lembur' => 0, 'pinjaman_karyawan' => 0,
>    ];
>    ```
>    Ini memudahkan karena satu variabel menampung banyak data terkait, dan gampang di-reset/diisi ulang.
> 2. **Array untuk parameter query dinamis** — di `data_gaji.php`, saya pakai `$params = []` dan `$types = ''` yang diisi bertahap tergantung filter mana saja yang aktif (cari nama, filter periode), lalu di-*unpack* pakai `...$params` ke `mysqli_stmt_bind_param()`.
> 3. **Struktur tabel database itu sendiri** (relasi antar tabel `admin`, `karyawan`, `gaji`) juga bentuk struktur data — dirancang supaya tidak ada duplikasi data (misal data karyawan tidak perlu ditulis ulang di setiap baris gaji, cukup direlasikan lewat `karyawan_id`).

**Q: "Kenapa hasil query disimpan sebagai associative array (`mysqli_fetch_assoc`), bukan angka index biasa?"**
> A: Supaya kode lebih mudah dibaca — `$row['nama']` jauh lebih jelas maksudnya dibanding `$row[1]`. Ini juga mengurangi bug kalau urutan kolom di query berubah.

## Unit 5: J.620100.017.02 — Mengimplementasikan Pemrograman Terstruktur

**Q: "Apa itu pemrograman terstruktur dan di mana penerapannya di project-mu?"**
> A: Pemrograman terstruktur artinya kode disusun dalam alur yang jelas (urutan, percabangan, pengulangan) dan dipecah jadi bagian-bagian kecil yang punya tugas masing-masing, bukan semua logika ditulis panjang dalam satu blok. Contoh penerapan:
> - **Pemisahan file per fungsi**: `koneksi.php` (khusus koneksi DB), `format.php` (khusus fungsi format & perhitungan), tiap halaman fokus pada satu tugas.
> - **Fungsi reusable di `format.php`**: `rupiah()`, `rupiah_potongan()`, `teksPeriodeGajian()`, `periodeGajianDariBulan()` — dipakai berkali-kali di banyak file lewat `require`, tidak ditulis ulang.
> - **Percabangan (if/else) yang jelas** — misalnya validasi form di `tambah_gaji.php`: cek nama kosong → cek captcha salah → baru proses simpan, masing-masing tahap punya pesan error sendiri.
> - **Perulangan (`while`)** — untuk menampilkan setiap baris data gaji di tabel (`while ($row = mysqli_fetch_assoc($result))`).

**Q: "Coba jelaskan alur logic dari awal buka halaman Tambah Data Gaji sampai data tersimpan."**
> A: *(jelaskan alur ini dengan kata-katamu sendiri, poin-poinnya:)*
> 1. Cek dulu apakah user sudah login (`session`), kalau belum → redirect ke login.
> 2. Cek parameter `bulan` valid formatnya (`YYYY-MM`), kalau tidak → redirect balik ke popup pilih periode.
> 3. Kalau request `GET` (baru buka halaman) → tampilkan form kosong.
> 4. Kalau request `POST` (submit form) → validasi nama tidak kosong & captcha benar.
> 5. Cek apakah karyawan sudah ada (by NIK / nama) → kalau ada, `UPDATE`; kalau belum, `INSERT` karyawan baru.
> 6. `INSERT` data gaji baru, dihubungkan ke `karyawan_id`.
> 7. Semua langkah 5–6 dibungkus **transaction**, supaya konsisten (jelaskan di Unit Debugging di bawah).
> 8. Kalau berhasil → redirect ke `data_gaji.php`.

## Unit 6: J.620100.019.02 — Menggunakan Library atau Komponen Pre-Existing

**Indikator: memanfaatkan library/framework yang sesuai.**

**Q: "Library pihak ketiga apa saja yang kamu pakai? Jelaskan fungsinya masing-masing."**
> A:
> | Library | Fungsi |
> |---|---|
> | **Bootstrap 5.3.3** (CDN) | Grid system untuk layout responsive, komponen siap pakai (modal popup "Pilih Periode", dropdown menu aksi, tombol, form styling) |
> | **Font Awesome 6** (CDN) | Ikon (panah kembali, WhatsApp, amplop email, file PDF) di halaman Slip Gaji |
> | **mysqli extension (PHP)** | Library bawaan PHP untuk komunikasi dengan database MySQL, termasuk fitur prepared statement |
> | **WhatsApp Click-to-Chat (`wa.me`)** | Layanan pihak ketiga dari WhatsApp untuk membuka chat baru dengan pesan yang sudah terisi otomatis, tanpa perlu API resmi berbayar |
> | **Gmail Compose Link** | Layanan pihak ketiga dari Google untuk membuka jendela compose email dengan subjek & isi sudah terisi otomatis |
> | **Browser Print API (`window.print()`)** | Fitur bawaan browser untuk mencetak/menyimpan halaman sebagai PDF, dipakai di tombol "Cetak/Download PDF" |

**Q: "Kenapa nggak pakai library resmi WhatsApp Business API atau PHPMailer buat kirim email beneran dari server?"**
> A: Karena untuk kebutuhan aplikasi internal skala kecil seperti ini, pakai API resmi WhatsApp Business butuh approval bisnis & biaya, dan PHPMailer butuh konfigurasi SMTP server. Solusi yang saya pakai (`wa.me` link dan Gmail compose link) jauh lebih sederhana dan tidak butuh biaya/konfigurasi tambahan, cukup membuka aplikasi WhatsApp/Gmail yang sudah terpasang di device admin dengan pesan yang sudah disiapkan otomatis. Trade-off-nya: admin tetap harus klik "Kirim" secara manual, tidak terkirim otomatis dari server.

## Unit 7: J.620100.023.02 — Membuat Dokumen Kode Program

**Indikator: kode ditulis dengan struktur yang rapi dan mudah dibaca (dokumentasi = bagian dari ini).**

**Q: "Bagaimana kamu mendokumentasikan kode program ini?"**
> A: Saya kasih komentar di bagian-bagian penting, terutama yang **menjelaskan alasan** (kenapa), bukan cuma mengulang apa yang kode lakukan. Contoh nyata dari project:
> ```php
> // ---------------------------------------------------------
> // CARI DULU APAKAH KARYAWAN INI SUDAH ADA (berdasarkan NIK,
> // atau kalau NIK kosong berdasarkan nama persis sama).
> // Ini memperbaiki bug lama: dulu SELALU insert karyawan baru
> // setiap kali "Tambah Data Gaji" -> karena kolom email di
> // tabel karyawan bersifat UNIK, baris ke-2 dst dengan email
> // kosong gagal disimpan (bentrok unik) dan data gaji jadi
> // ikut tidak pernah tersimpan / tidak muncul di Data Gaji.
> // ---------------------------------------------------------
> ```
> Komentar seperti ini penting supaya kalau ada developer lain (atau diri saya sendiri di masa depan) baca ulang kode, langsung paham **kenapa** logikanya seperti itu, bukan cuma **apa** yang terjadi.
>
> Selain komentar inline, saya juga punya `README.md` yang menjelaskan struktur project, cara instalasi, dan struktur database secara keseluruhan — ini dokumentasi di level project, bukan cuma di level baris kode.

**Q: "Kalau asesor minta kamu tunjukkan 1 contoh komentar kode yang paling penting, mana yang kamu pilih?"**
> A: Siapkan 1–2 contoh hafal luar kepala dari project kamu (misalnya yang di atas soal duplikat karyawan), supaya bisa langsung tunjuk ke layar tanpa bingung cari-cari.

## Unit 8: J.620100.025.02 — Melakukan Debugging

**Indikator: mampu melakukan debugging dan troubleshooting.**

**Q: "Ceritakan pengalaman kamu menemukan dan memperbaiki bug di project ini."**
> A: *(Ini pertanyaan favorit asesor — dan kamu punya cerita nyata! Jawab seperti ini:)*
> Dulu, setiap kali admin klik "Tambah Data Gaji" untuk karyawan yang **sama** di bulan berbeda, sistem selalu membuat baris karyawan **baru** di tabel `karyawan`. Masalahnya, kolom `email` di tabel itu punya batasan `UNIQUE`, dan karena email sering dikosongkan, baris kedua dan seterusnya gagal disimpan (bentrok unique constraint) — akibatnya data gaji juga ikut gagal tersimpan / tidak muncul.
>
> **Cara saya debug:**
> 1. Cek pesan error dari `mysqli_stmt_error()` — ternyata errornya soal *duplicate entry* di kolom email.
> 2. Telusuri kode `tambah_gaji.php`, ternyata memang **selalu** ada `INSERT` karyawan baru tanpa pengecekan dulu.
> 3. **Solusi**: sebelum insert karyawan baru, cek dulu apakah NIK atau nama-nya sudah ada di database. Kalau sudah ada, `UPDATE` datanya saja, bukan insert baru. Kalau memang karyawan baru, kasih **email placeholder unik** (`karyawan_<uniqid>@slipgaji.local`) supaya tidak bentrok dengan constraint UNIQUE meskipun email aslinya kosong.
> 4. Saya tes ulang: tambah data gaji untuk karyawan yang sama 2x dengan periode berbeda → berhasil tersimpan, dan tabel `karyawan` tidak lagi duplikat.

**Q: "Bagaimana caramu mencegah error/bug sejak awal, bukan cuma memperbaiki setelah muncul?"**
> A:
> - **Validasi input** sebelum diproses (nama tidak boleh kosong, captcha harus benar, format bulan harus `YYYY-MM` lewat `preg_match`).
> - **Transaction** (`mysqli_begin_transaction` / `commit` / `rollback`) di proses yang melibatkan lebih dari satu tabel (insert karyawan + insert gaji), supaya kalau salah satu gagal, tidak ada data "setengah tersimpan" yang bikin data tidak konsisten.
> - **try/catch** untuk menangkap `Exception` saat query gagal, supaya errornya ditangani rapi dan ditampilkan sebagai pesan yang jelas ke admin, bukan halaman putih error PHP.
> - **Prepared statement** untuk semua input dari user, supaya tidak bisa disusupi SQL Injection.

**Q: "Kalau saya kasih data aneh, misalnya gaji pokok diisi huruf, apa yang terjadi?"**
> A: Input nominal dibersihkan dulu pakai `preg_replace('/\D/', '', ...)` yang **menghapus semua karakter selain angka** sebelum dikonversi ke `(float)`. Jadi kalaupun admin iseng ketik huruf, hasilnya otomatis jadi `0`, tidak menyebabkan error di database (karena kolomnya bertipe `decimal`).

---

# PERTANYAAN UMUM "KENAPA ADA INI" (Lintas Kelompok Pekerjaan)

Asesor sering menunjuk **satu elemen visual/kode spesifik** di layar dan tanya "ini kenapa dibikin gini?". Ini daftar elemen yang paling mungkin ditanya:

| Elemen | Jawaban singkat "kenapa" |
|---|---|
| Captcha di form Tambah Data Gaji | Mencegah pengisian otomatis/berulang oleh script/bot, soalnya sederhana (perkalian) supaya tidak mengganggu admin manusia |
| Tombol refresh captcha pakai AJAX (`fetch`), bukan reload halaman | Supaya data form yang sudah diisi admin **tidak hilang** saat captcha di-refresh |
| Popup "Pilih Periode" sebelum form | Memastikan periode gajian selalu diisi valid sebelum data lain diinput, mencegah data tanpa periode |
| Tanggal gajian dipaksa selalu tanggal 25 | Sesuai kebijakan perusahaan (siklus gajian tetap 25→25), disederhanakan di sistem supaya admin tidak perlu pilih tanggal manual, cukup bulan & tahun |
| Tabel Data Gaji berubah jadi kartu di layar HP | Supaya data tetap mudah dibaca di layar kecil, tidak perlu scroll horizontal yang mengganggu pengalaman pengguna (UX) |
| Dropdown menu titik tiga untuk Aksi | Meringkas 3 aksi (Detail/Edit/Hapus) dalam satu tombol supaya tabel tidak terlalu ramai, terutama penting di layar kecil |
| Prepared statement di semua query | Mencegah SQL Injection — input user tidak pernah digabung langsung ke string SQL |
| Password disimpan plain text (**kalau ditanya kekurangan**) | *(Jawab jujur)* Ini kekurangan yang saya sadari; idealnya pakai `password_hash()`/`password_verify()`. Untuk scope latihan ini saya fokus dulu ke alur CRUD dan logika bisnis, tapi saya paham risikonya dan solusinya |
| Email karyawan dibuat placeholder unik saat kosong | Supaya tidak melanggar `UNIQUE KEY` di kolom email, tapi tetap bisa menyimpan karyawan yang belum punya email asli |

---

# CHECKLIST DEMO LIVE (Kalau Diminta Praktik Langsung)

Urutan demo yang natural dan mengcover hampir semua unit kompetensi:

1. **Buka `login.php`** → jelaskan kenapa harus login dulu (autentikasi).
2. **Login berhasil** → masuk `data_gaji.php`, jelaskan query JOIN antara `gaji` dan `karyawan`.
3. **Klik "+ Tambah Data Gaji"** → jelaskan kenapa popup periode muncul duluan.
4. **Isi form, tunjukkan captcha & preview otomatis hitungan gaji bersih** → jelaskan JavaScript `oninput` yang menghitung real-time.
5. **Submit** → data muncul di tabel → jelaskan proses INSERT + cek duplikat karyawan.
6. **Klik menu titik tiga → Lihat Detail** → buka `slip_gaji.php`, jelaskan rumus gaji bersih.
7. **Klik tombol WhatsApp/Email** → jelaskan cara kerja deep link.
8. **Klik Edit → ubah nominal → Update** → jelaskan query UPDATE.
9. **Resize browser ke ukuran HP (DevTools)** → tunjukkan tabel berubah jadi kartu, dan halaman login center di tengah layar → jelaskan `@media` query.
10. **Klik Hapus** → jelaskan konfirmasi (`confirm()` di JavaScript) + `DELETE` di database.
11. **Logout** → jelaskan `session_destroy()`.

---

# TIPS TERAKHIR

1. **Jangan jawab dengan istilah teknis doang** — selalu kaitkan ke *kenapa* itu dibutuhkan dari sisi pengguna (admin/karyawan), bukan cuma dari sisi teknis.
2. Kalau asesor tanya sesuatu yang **belum kamu pikirkan sebelumnya**, jangan panik — jawab dengan cara berpikir logis: *"Kalau kasusnya begitu, kemungkinan saya akan..."* — asesor menilai cara berpikir, bukan cuma hafalan.
3. **Latih cerita bug "karyawan duplikat"** sampai lancar — ini bukti nyata kemampuan debugging kamu, dan hampir pasti jadi salah satu poin favorit asesor karena ceritanya konkret (bukan teori).
4. Kalau ditanya kekurangan (misalnya password plain text), **jangan defensif** — akui dengan tenang dan sebutkan solusinya. Ini justru menunjukkan level pemahaman yang lebih matang dibanding pura-pura tidak tahu.
