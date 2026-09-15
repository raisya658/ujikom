<?php
session_start();
require 'koneksi.php';
require 'format.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: data_gaji.php');
    exit;
}

// Ambil data gaji digabung dengan data karyawan
$stmt_get = mysqli_prepare($koneksi,
    "SELECT gaji.*, karyawan.nama, karyawan.nik, karyawan.jabatan, karyawan.id AS karyawan_id
     FROM gaji
     JOIN karyawan ON gaji.karyawan_id = karyawan.id
     WHERE gaji.id = ?"
);
mysqli_stmt_bind_param($stmt_get, 'i', $id);
mysqli_stmt_execute($stmt_get);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));

if (!$data) {
    header('Location: data_gaji.php');
    exit;
}

$bulan_saat_ini = $data['tanggal_gajian'] ? substr($data['tanggal_gajian'], 0, 7) : date('Y-m');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama              = trim($_POST['nama'] ?? '');
    $nik               = trim($_POST['nik'] ?? '');
    $jabatan           = trim($_POST['jabatan'] ?? '');
    $gaji_pokok        = (float) preg_replace('/\D/', '', $_POST['gaji_pokok'] ?? '0');
    $lembur            = (float) preg_replace('/\D/', '', $_POST['lembur'] ?? '0');
    $pinjaman_karyawan = (float) preg_replace('/\D/', '', $_POST['pinjaman_karyawan'] ?? '0');
    $bulan_input       = $_POST['bulan'] ?? '';
    $periode_baru      = periodeGajianDariBulan($bulan_input);
    $tanggal_gajian    = $periode_baru['mulai']; // awal periode, selalu tanggal 25

    if (empty($nama)) {
        $error = 'Nama karyawan wajib diisi.';
    } else {
        // 1. Update data identitas ke tabel KARYAWAN berdasarkan karyawan_id
        $stmt_up_karyawan = mysqli_prepare($koneksi,
            "UPDATE karyawan SET nama = ?, nik = ?, jabatan = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt_up_karyawan, 'sssi', $nama, $nik, $jabatan, $data['karyawan_id']);
        mysqli_stmt_execute($stmt_up_karyawan);

        // 2. Update data nominal gaji + tanggal gajian ke tabel GAJI
        $stmt_update = mysqli_prepare($koneksi,
            "UPDATE gaji SET tanggal_gajian = ?, gaji_pokok = ?, lembur = ?, pinjaman_karyawan = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt_update, 'sdddi', $tanggal_gajian, $gaji_pokok, $lembur, $pinjaman_karyawan, $id);

        if (mysqli_stmt_execute($stmt_update)) {
            header('Location: data_gaji.php');
            exit;
        } else {
            $error = 'Gagal update ke database: ' . mysqli_stmt_error($stmt_update);
        }
    }

    $bulan_saat_ini = $bulan_input ?: $bulan_saat_ini;
}

$angka_pokok    = $_SERVER['REQUEST_METHOD'] === 'POST' ? preg_replace('/\D/', '', $_POST['gaji_pokok'] ?? '0') : ($data['gaji_pokok'] ?? 0);
$angka_lembur   = $_SERVER['REQUEST_METHOD'] === 'POST' ? preg_replace('/\D/', '', $_POST['lembur'] ?? '0') : ($data['lembur'] ?? 0);
$angka_pinjaman = $_SERVER['REQUEST_METHOD'] === 'POST' ? preg_replace('/\D/', '', $_POST['pinjaman_karyawan'] ?? '0') : ($data['pinjaman_karyawan'] ?? 0);

// Menyiapkan nilai untuk form
$nilai = [
    'nama'              => $_POST['nama']    ?? ($data['nama'] ?? ''),
    'nik'               => $_POST['nik']     ?? ($data['nik'] ?? ''),
    'jabatan'           => $_POST['jabatan'] ?? ($data['jabatan'] ?? ''),
    'gaji_pokok'        => angkaRibuan($angka_pokok),
    'lembur'            => angkaRibuan($angka_lembur),
    'pinjaman_karyawan' => angkaRibuan($angka_pinjaman),
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Gaji</title>
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
            margin-bottom: 25px;
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
            width: 140px;
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
            margin-bottom: 15px;
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
            margin-top: 10px;
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
            .kolom .field-baca label {
                width: 100%;
            }
            .penyangga-hp {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="slip-container">
        <h2 class="slip-title">EDIT DATA GAJI</h2>
        <div class="periode-teks">
            Periode saat ini: <?= htmlspecialchars(teksPeriodeGajian($data['tanggal_gajian'])) ?>
        </div>

        <?php if ($error): ?>
            <div class="alert-form"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_gaji.php?id=<?= $id ?>">

            <div class="field-baca">
                <label>Periode Gaji:</label>
                <input type="month" name="bulan" id="bulan" class="kotak" value="<?= htmlspecialchars($bulan_saat_ini) ?>" required>
            </div>
            <div class="field-baca">
                <label></label>
                <small style="color:#666;">
                    Cukup pilih bulan &amp; tahun. Periode otomatis dihitung tanggal 25 sampai tanggal 25 bulan berikutnya.
                    <span id="periodePreview" style="display:block; color:#2b6b85; font-weight:bold; margin-top:4px;"></span>
                </small>
            </div>

            <div class="field-baca">
                <label>Nama Karyawan:</label>
                <input type="text" name="nama" id="nama" class="kotak" value="<?= htmlspecialchars($nilai['nama']) ?>" required>
            </div>

            <div class="field-baca">
                <label>NIK:</label>
                <input type="text" name="nik" id="nik" class="kotak" value="<?= htmlspecialchars($nilai['nik']) ?>">
            </div>

            <div class="field-baca">
                <label>Jabatan:</label>
                <input type="text" name="jabatan" id="jabatan" class="kotak" value="<?= htmlspecialchars($nilai['jabatan']) ?>">
            </div>

            <div class="dua-kolom">
                <div class="kolom">
                    <div class="kolom-judul">PENGHASILAN</div>

                    <div class="field-baca">
                        <label>Gaji pokok:</label>
                        <div class="rupiah-wrap">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="gaji_pokok" id="gaji_pokok" class="kotak kotak-rupiah" value="<?= htmlspecialchars($nilai['gaji_pokok']) ?>" required oninput="formatRibuan(this)">
                        </div>
                    </div>
                    <div class="field-baca">
                        <label>Lembur:</label>
                        <div class="rupiah-wrap">
                            <span class="rupiah-prefix">Rp</span>
                            <input type="text" inputmode="numeric" name="lembur" id="lembur" class="kotak kotak-rupiah" value="<?= htmlspecialchars($nilai['lembur']) ?>" required oninput="formatRibuan(this)">
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
                            <input type="text" inputmode="numeric" name="pinjaman_karyawan" id="pinjaman_karyawan" class="kotak kotak-rupiah" value="<?= htmlspecialchars($nilai['pinjaman_karyawan']) ?>" required oninput="formatRibuan(this)">
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

            <button type="submit" class="btn-submit">update</button>
        </form>

        <a href="data_gaji.php" class="btn-batal">← Batal</a>
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

        // Preview periode "25 ... - 25 ..." saat bulan diganti
        const namaBulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function tampilkanPreviewPeriode() {
            const val = document.getElementById('bulan').value;
            const preview = document.getElementById('periodePreview');
            if (!val) { preview.textContent = ''; return; }

            const tahunMulai = parseInt(val.split('-')[0], 10);
            const bulanMulai = parseInt(val.split('-')[1], 10);

            let bulanSelesai = bulanMulai + 1;
            let tahunSelesai = tahunMulai;
            if (bulanSelesai > 12) { bulanSelesai = 1; tahunSelesai = tahunMulai + 1; }

            preview.textContent = 'Periode: ' + (tahunMulai === tahunSelesai
                ? '25 ' + namaBulanIndo[bulanMulai] + ' - 25 ' + namaBulanIndo[bulanSelesai] + ' ' + tahunMulai
                : '25 ' + namaBulanIndo[bulanMulai] + ' ' + tahunMulai + ' - 25 ' + namaBulanIndo[bulanSelesai] + ' ' + tahunSelesai);
        }

        document.getElementById('bulan').addEventListener('input', tampilkanPreviewPeriode);
        tampilkanPreviewPeriode();
    </script>

</body>
</html>