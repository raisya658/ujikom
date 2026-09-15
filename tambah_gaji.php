<?php
session_start();
require 'koneksi.php';
require 'format.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// ==============================
// TENTUKAN PERIODE GAJI (dari popup "Pilih Periode" di halaman Data Gaji)
// Admin hanya memilih bulan & tahun; tanggalnya otomatis 25 -> 25,
// contoh: 25 November - 25 Desember 2026.
// ==============================
$bulan_input = $_POST['bulan'] ?? $_GET['bulan'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $bulan_input)) {
    // Formulir TIDAK boleh dibuka sebelum periode diisi.
    // Lempar balik ke Data Gaji, popup "Pilih Periode" langsung terbuka di sana.
    header('Location: data_gaji.php?pilih=1');
    exit;
}
$periode             = periodeGajianDariBulan($bulan_input);
$tanggal_gajian      = $periode['mulai'];   // tersimpan di DB sebagai awal periode (tanggal 25)
$teks_periode_aktif  = teksPeriodeGajian($tanggal_gajian);

// CAPTCHA
if (!isset($_SESSION['captcha_num1'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

$error = '';

$form = [
    'nama'              => '',
    'nik'               => '',
    'jabatan'           => '',
    'gaji_pokok'        => 0,
    'lembur'            => 0,
    'pinjaman_karyawan' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama']              = trim($_POST['nama'] ?? '');
    $form['nik']               = trim($_POST['nik'] ?? '');
    $form['jabatan']           = trim($_POST['jabatan'] ?? '');
    // input dari user sudah pakai titik ribuan (mis. "2.000.000"), jadi
    // dibersihkan dulu semua karakter selain angka sebelum diubah ke angka.
    $form['gaji_pokok']        = (float) preg_replace('/\D/', '', $_POST['gaji_pokok'] ?? '0');
    $form['lembur']            = (float) preg_replace('/\D/', '', $_POST['lembur'] ?? '0');
    $form['pinjaman_karyawan'] = (float) preg_replace('/\D/', '', $_POST['pinjaman_karyawan'] ?? '0');

    $jawaban_benar = $_SESSION['captcha_num1'] * $_SESSION['captcha_num2'];
    $jawaban_user  = isset($_POST['captcha_jawaban']) ? (int) $_POST['captcha_jawaban'] : null;

    if ($form['nama'] === '') {
        $error = 'Nama karyawan wajib diisi.';
    } elseif ($jawaban_user !== $jawaban_benar) {
        $error = 'Jawaban captcha salah. Silakan coba lagi.';
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            // ---------------------------------------------------------
            // CARI DULU APAKAH KARYAWAN INI SUDAH ADA (berdasarkan NIK,
            // atau kalau NIK kosong berdasarkan nama persis sama).
            // Ini memperbaiki bug lama: dulu SELALU insert karyawan baru
            // setiap kali "Tambah Data Gaji" -> karena kolom email di
            // tabel karyawan bersifat UNIK, baris ke-2 dst dengan email
            // kosong gagal disimpan (bentrok unik) dan data gaji jadi
            // ikut tidak pernah tersimpan / tidak muncul di Data Gaji.
            // ---------------------------------------------------------
            $karyawan_id = null;

            if ($form['nik'] !== '') {
                $cek = mysqli_prepare($koneksi, "SELECT id FROM karyawan WHERE nik = ? LIMIT 1");
                mysqli_stmt_bind_param($cek, 's', $form['nik']);
                mysqli_stmt_execute($cek);
                $row_cek = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
                if ($row_cek) {
                    $karyawan_id = (int) $row_cek['id'];
                }
            }

            if ($karyawan_id === null) {
                $cek = mysqli_prepare($koneksi, "SELECT id FROM karyawan WHERE nama = ? LIMIT 1");
                mysqli_stmt_bind_param($cek, 's', $form['nama']);
                mysqli_stmt_execute($cek);
                $row_cek = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
                if ($row_cek) {
                    $karyawan_id = (int) $row_cek['id'];
                }
            }

            if ($karyawan_id !== null) {
                // Karyawan sudah ada -> update data identitasnya saja (jangan duplikat)
                $stmt_up = mysqli_prepare($koneksi,
                    "UPDATE karyawan SET nama = ?, nik = ?, jabatan = ? WHERE id = ?"
                );
                mysqli_stmt_bind_param($stmt_up, 'sssi', $form['nama'], $form['nik'], $form['jabatan'], $karyawan_id);
                if (!mysqli_stmt_execute($stmt_up)) {
                    throw new Exception(mysqli_stmt_error($stmt_up));
                }
            } else {
                // Karyawan baru -> insert. Email diisi placeholder unik (bukan string
                // kosong) supaya tidak melanggar UNIQUE KEY email di tabel karyawan.
                $email_placeholder = 'karyawan_' . uniqid() . '@slipgaji.local';
                $stmt_kar = mysqli_prepare($koneksi,
                    "INSERT INTO karyawan (nama, nik, email, password, jabatan) VALUES (?, ?, ?, '', ?)"
                );
                mysqli_stmt_bind_param($stmt_kar, 'ssss', $form['nama'], $form['nik'], $email_placeholder, $form['jabatan']);
                if (!mysqli_stmt_execute($stmt_kar)) {
                    throw new Exception(mysqli_stmt_error($stmt_kar));
                }
                $karyawan_id = mysqli_insert_id($koneksi);
            }

            // 2. Masukkan data gaji dengan menghubungkan `karyawan_id` + tanggal gajian (tetap tanggal 25)
            $stmt_gaji = mysqli_prepare($koneksi,
                "INSERT INTO gaji (karyawan_id, tanggal_gajian, gaji_pokok, lembur, pinjaman_karyawan)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_gaji, 'isddd',
                $karyawan_id,
                $tanggal_gajian,
                $form['gaji_pokok'],
                $form['lembur'],
                $form['pinjaman_karyawan']
            );
            if (!mysqli_stmt_execute($stmt_gaji)) {
                throw new Exception(mysqli_stmt_error($stmt_gaji));
            }

            mysqli_commit($koneksi);

            // Simpan data langsung tersimpan & langsung muncul (real-time) di Data Gaji
            header('Location: data_gaji.php');
            exit;
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
        }
    }

    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Gaji</title>
    <style>
        body {
            background-color: #1e1e1e;
            font-family: Arial, sans-serif;
            padding: 30px 15px;
            margin: 0;
        }
        .slip-container {
            background: #ffffff;
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 40px 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            box-sizing: border-box;
        }
        .slip-title {
            text-align: center;
            font-weight: bold;
            font-size: 24px;
            margin-bottom: 5px;
            color: #000;
        }
        .periode-teks {
            text-align: center;
            font-size: 13px;
            color: #444;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .alert-form {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
            font-size: 14px;
        }
        .field-baca {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        .field-baca label {
            width: 130px;
            font-size: 14px;
            color: #000;
            flex-shrink: 0;
        }
        .kotak {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #333;
            border-radius: 0px;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            background-color: #fff;
            width: 100%;
        }
        .kotak-readonly {
            background-color: #f5f5f5;
            color: #555;
        }
        .rupiah-wrap {
            flex: 1;
            display: flex;
        }
        .rupiah-prefix {
            display: flex;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #333;
            border-right: none;
            background-color: #eee;
            font-size: 14px;
            color: #333;
        }
        .kotak-rupiah {
            border-left: none !important;
        }
        .dua-kolom {
            display: flex;
            gap: 30px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .kolom {
            flex: 1;
        }
        .kolom-judul {
            background: #195b8c;
            color: white;
            text-align: center;
            font-weight: bold;
            padding: 6px;
            font-size: 13px;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }
        .kolom .field-baca label {
            width: 125px;
            font-size: 13px;
        }
        .potongan-merah {
            color: #c0392b;
        }
        .gaji-bersih {
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .captcha-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .captcha-box {
            background: #dcdcdc;
            border: 1px solid #333;
            padding: 5px 15px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }
        .refresh-btn {
            background: #f0f0f0;
            border: 1px solid #ccc;
            font-size: 16px;
            cursor: pointer;
            padding: 3px 8px;
        }
        .captcha-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #333;
            border-radius: 0px;
            margin-bottom: 25px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: #195b8c;
            color: white;
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: lowercase;
        }
        .btn-submit:hover {
            background: #124368;
        }
        .btn-batal {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #555;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-batal:hover {
            text-decoration: underline;
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            .slip-container {
                padding: 20px 15px;
            }
            .field-baca {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 15px;
            }
            .field-baca label {
                width: 100%;
                margin-bottom: 5px;
            }
            .dua-kolom {
                flex-direction: column;
                gap: 15px;
            }
            .penyangga-hp {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="slip-container">
        <h2 class="slip-title">SLIP GAJI KARYAWAN</h2>
        <!-- periode yang tadi diisi di popup, tampil persis di bawah judul -->
        <div class="periode-teks">Periode: <?= htmlspecialchars($teks_periode_aktif) ?></div>

        <?php if ($error): ?>
            <div class="alert-form"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="tambah_gaji.php" autocomplete="off">
            <input type="hidden" name="bulan" value="<?= htmlspecialchars($bulan_input) ?>">

            <div class="field-baca">
                <label>Nama:</label>
                <input type="text" name="nama" class="kotak" value="<?= htmlspecialchars($form['nama']) ?>" required autocomplete="off" placeholder="Ketik nama karyawan...">
            </div>

            <div class="field-baca">
                <label>NIK:</label>
                <input type="text" name="nik" class="kotak" value="<?= htmlspecialchars($form['nik']) ?>" autocomplete="off" placeholder="Ketik NIK...">
            </div>

            <div class="field-baca">
                <label>Jabatan:</label>
                <input type="text" name="jabatan" class="kotak" value="<?= htmlspecialchars($form['jabatan']) ?>" autocomplete="off" placeholder="Ketik jabatan...">
            </div>

            <div class="dua-kolom">
                <div class="kolom">
                    <div class="kolom-judul">PENGHASILAN</div>

                    <div class="field-baca">
                        <label>Gaji pokok:</label>
                        <div class="rupiah-wrap">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="gaji_pokok" id="gaji_pokok" class="kotak kotak-rupiah" value="<?= angkaRibuan($form['gaji_pokok']) ?>" required oninput="formatRibuan(this)">
                        </div>
                    </div>
                    <div class="field-baca">
                        <label>Lembur:</label>
                        <div class="rupiah-wrap">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="lembur" id="lembur" class="kotak kotak-rupiah" value="<?= angkaRibuan($form['lembur']) ?>" required oninput="formatRibuan(this)">
                        </div>
                    </div>
                    <div class="field-baca">
                        <label>Total penghasilan:</label>
                        <input type="text" id="preview_penghasilan" class="kotak kotak-readonly" value="Rp 0" readonly>
                    </div>
                </div>

                <div class="kolom">
                    <div class="kolom-judul">POTONGAN</div>

                    <div class="field-baca">
                        <label>Pinjaman karyawan:</label>
                        <div class="rupiah-wrap">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="pinjaman_karyawan" id="pinjaman_karyawan" class="kotak kotak-rupiah" value="<?= angkaRibuan($form['pinjaman_karyawan']) ?>" required oninput="formatRibuan(this)">
                        </div>
                    </div>
                    <div class="field-baca penyangga-hp" style="opacity: 0; pointer-events: none;">
                        <label>-</label>
                        <input type="text" class="kotak">
                    </div>
                    <div class="field-baca">
                        <label>Total potongan:</label>
                        <input type="text" id="preview_potongan" class="kotak kotak-readonly potongan-merah" value="Rp 0" readonly>
                    </div>
                </div>
            </div>

            <div class="field-baca gaji-bersih">
                <label>Gaji bersih:</label>
                <input type="text" id="preview_bersih" class="kotak kotak-readonly" style="font-weight: bold;" value="Rp 0" readonly>
            </div>

            <div class="captcha-wrapper">
                <div class="captcha-box">
                    <span id="captcha-soal">
                        Captcha: <?= $_SESSION['captcha_num1'] ?> &times; <?= $_SESSION['captcha_num2'] ?>
                    </span>
                </div>
                <button type="button" class="refresh-btn" title="Refresh captcha" onclick="refreshCaptcha()">&#8635;</button>
            </div>
            <input type="text" name="captcha_jawaban" class="captcha-input" placeholder="Jawaban captcha..." required autocomplete="off">

            <button type="submit" class="btn-submit">submit</button>
        </form>

        <a href="data_gaji.php" class="btn-batal">← Kembali ke Data Gaji</a>
    </div>

    <script>
        function formatRupiah(angka) {
            return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
        }

        function ambilAngka(id) {
            return parseFloat(document.getElementById(id).value.replace(/\./g, '')) || 0;
        }

        function hitungPreview() {
            const gajiPokok = ambilAngka('gaji_pokok');
            const lembur    = ambilAngka('lembur');
            const pinjaman  = ambilAngka('pinjaman_karyawan');

            const totalPenghasilan = gajiPokok + lembur;
            const totalPotongan    = pinjaman;
            const gajiBersih       = totalPenghasilan - totalPotongan;

            document.getElementById('preview_penghasilan').value = formatRupiah(totalPenghasilan);
            document.getElementById('preview_potongan').value    = totalPotongan > 0 ? ('- ' + formatRupiah(totalPotongan)) : formatRupiah(totalPotongan);
            document.getElementById('preview_bersih').value      = formatRupiah(gajiBersih);
        }

        // Kasih titik ribuan otomatis sambil ngetik, mis. "2000000" -> "2.000.000"
        function formatRibuan(el) {
            const raw = el.value.replace(/\D/g, '');
            el.value = raw === '' ? '' : raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            hitungPreview();
        }

        hitungPreview();

        function refreshCaptcha() {
            fetch('captcha_refresh.php')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.num1 !== undefined) {
                        document.getElementById('captcha-soal').textContent =
                            'Captcha: ' + data.num1 + ' \u00d7 ' + data.num2;
                        document.querySelector('.captcha-input').value = '';
                    }
                })
                .catch(function () {
                    alert('Gagal memuat ulang captcha, coba lagi.');
                });
        }
    </script>
</body>
</html>