<?php
session_start();
require 'koneksi.php';
require 'format.php';

// gerbang penjaga: kalau belum login, tendang ke login.php
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// ==============================
// SEARCH & FILTER
// ==============================
$cari         = trim($_GET['cari'] ?? '');            // pencarian nama karyawan
$filter_bulan = trim($_GET['filter_periode'] ?? '');  // filter bulan gajian, format "YYYY-MM"

// query dasar: gabung tabel gaji + karyawan (periode_gaji sudah tidak dipakai lagi)
$sql = "SELECT gaji.id, karyawan.nama, karyawan.jabatan,
               gaji.tanggal_gajian,
               gaji.gaji_pokok, gaji.lembur, gaji.pinjaman_karyawan
        FROM gaji
        JOIN karyawan ON gaji.karyawan_id = karyawan.id
        WHERE 1=1";
$params = [];
$types  = '';

if ($cari !== '') {
    $sql .= " AND karyawan.nama LIKE ?";
    $params[] = '%' . $cari . '%';
    $types   .= 's';
}
if ($filter_bulan !== '' && preg_match('/^\d{4}-\d{2}$/', $filter_bulan)) {
    $sql .= " AND DATE_FORMAT(gaji.tanggal_gajian, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types   .= 's';
}
$sql .= " ORDER BY gaji.id DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ambil daftar bulan gajian yang sudah pernah dipakai, untuk dropdown filter
$daftar_bulan = mysqli_query($koneksi, "
    SELECT DISTINCT DATE_FORMAT(tanggal_gajian, '%Y-%m') AS ym
    FROM gaji
    WHERE tanggal_gajian IS NOT NULL
    ORDER BY ym DESC
");

// bulan berjalan, dipakai sebagai default di popup "Pilih Periode"
$bulan_sekarang = date('Y-m');

// kalau user nyasar ke tambah_gaji.php tanpa memilih periode, dia dilempar
// balik ke sini dengan ?pilih=1 -> popup langsung dibuka otomatis.
$paksa_buka_popup = isset($_GET['pilih']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Gaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css?v=3">
    <style>
        .table-container-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.5rem;
            overflow: visible !important;
        }
        .table-responsive {
            overflow-x: auto;
            overflow-y: visible !important;
        }
        table.table, table.table tbody tr {
            overflow: visible !important;
        }
        .periode-preview {
            font-size: 14px;
            color: #2b6b85;
            font-weight: bold;
            margin-top: 6px;
        }
    </style>
</head>
<body class="admin-page">

    <!-- NAVBAR RESPONSIF -->
    <nav class="navbar navbar-app navbar-expand mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Aplikasi Penggajian</span>

            <div class="d-flex align-items-center ms-auto user-section">
                <span class="text-white me-2 d-none d-sm-inline">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-light" onclick="return confirm('Yakin ingin logout?')">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">

        <?php if ($paksa_buka_popup): ?>
            <div class="alert alert-warning py-2">
                Silakan pilih <b>periode</b> dulu sebelum mengisi formulir data gaji.
            </div>
        <?php endif; ?>

        <!-- HEADER + TOMBOL TAMBAH DATA GAJI -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h4 class="mb-0">Data Gaji</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPilihPeriode">
                    + Tambah Data Gaji
                </button>
            </div>
        </div>

        <!-- SEARCH & FILTER PERIODE (TERPISAH) -->
        <form method="GET" action="data_gaji.php" class="row g-2 mb-3">
            <div class="col-md-5 col-12">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama karyawan..." value="<?= htmlspecialchars($cari) ?>">
            </div>
            <div class="col-md-4 col-12">
                <select name="filter_periode" class="form-select">
                    <option value="">Filter Periode</option>
                    <?php while ($b = mysqli_fetch_assoc($daftar_bulan)):
                        $label_b = teksPeriodeGajian($b['ym'] . '-25');
                    ?>
                        <option value="<?= htmlspecialchars($b['ym']) ?>" <?= $filter_bulan === $b['ym'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label_b) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 col-12 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-50">Filter</button>
                <a href="data_gaji.php" class="btn btn-outline-secondary w-50 text-center">Reset</a>
            </div>
        </form>

        <!-- TABEL DATA GAJI -->
        <div class="table-container-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan</th>
                            <th>Periode Gaji</th>
                            <th>Gaji Bersih</th>
                            <th class="text-center" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $ada_data = false;
                        while ($row = mysqli_fetch_assoc($result)):
                            $ada_data = true;
                            $total_penghasilan = $row['gaji_pokok'] + $row['lembur'];
                            $total_potongan    = $row['pinjaman_karyawan'];
                            $gaji_bersih       = $total_penghasilan - $total_potongan;

                            $periode_teks = teksPeriodeGajian($row['tanggal_gajian']);
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['jabatan']) ?></td>
                            <td><?= htmlspecialchars($periode_teks) ?></td>
                            <td><strong><?= rupiah($gaji_bersih) ?></strong></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-aksi btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        &#8942;
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item" href="slip_gaji.php?id=<?= $row['id'] ?>">Lihat Detail</a></li>
                                        <li><a class="dropdown-item" href="edit_gaji.php?id=<?= $row['id'] ?>">Edit</a></li>
                                        <li>
                                            <a class="dropdown-item text-danger"
                                               href="hapus_gaji.php?id=<?= $row['id'] ?>"
                                               onclick="return confirm('Yakin ingin menghapus data gaji ini? Data yang dihapus tidak bisa dikembalikan.')">
                                               Hapus
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php if (!$ada_data): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Belum ada data gaji.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ================================================================= -->
    <!-- POPUP "PILIH PERIODE"                                             -->
    <!-- Muncul duluan saat klik "+ Tambah Data Gaji". Halaman formulir    -->
    <!-- baru bisa dibuka setelah periodenya diisi di sini.                -->
    <!-- Admin cuma pilih BULAN & TAHUN, tanggalnya otomatis 25 -> 25      -->
    <!-- (contoh: 25 November - 25 Desember), sesuai mockup.               -->
    <!-- ================================================================= -->
    <div class="modal fade" id="modalPilihPeriode" tabindex="-1" aria-labelledby="modalPilihPeriodeLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="tambah_gaji.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPilihPeriodeLabel">Pilih Periode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="bulan_periode" class="form-label">Bulan &amp; Tahun</label>
                        <input type="month" class="form-control" id="bulan_periode" name="bulan"
                               value="<?= htmlspecialchars($bulan_sekarang) ?>" required>

                        <div class="periode-preview" id="periodePreview"></div>
                        <small class="text-muted d-block mt-1">Tanggal gajian selalu tanggal 25.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Lanjut Isi Data Gaji</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const namaBulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tampilkan bocoran periode secara langsung: "25 November - 25 Desember 2026"
        function tampilkanPreviewPeriode() {
            const val = document.getElementById('bulan_periode').value; // format YYYY-MM
            const preview = document.getElementById('periodePreview');
            if (!val) { preview.textContent = ''; return; }

            const tahunMulai = parseInt(val.split('-')[0], 10);
            const bulanMulai = parseInt(val.split('-')[1], 10);

            // tanggal 25 bulan berikutnya
            let bulanSelesai = bulanMulai + 1;
            let tahunSelesai = tahunMulai;
            if (bulanSelesai > 12) { bulanSelesai = 1; tahunSelesai = tahunMulai + 1; }

            let teks;
            if (tahunMulai === tahunSelesai) {
                teks = '25 ' + namaBulanIndo[bulanMulai] + ' - 25 ' + namaBulanIndo[bulanSelesai] + ' ' + tahunMulai;
            } else {
                teks = '25 ' + namaBulanIndo[bulanMulai] + ' ' + tahunMulai +
                       ' - 25 ' + namaBulanIndo[bulanSelesai] + ' ' + tahunSelesai;
            }
            preview.textContent = 'Periode: ' + teks;
        }

        document.getElementById('bulan_periode').addEventListener('input', tampilkanPreviewPeriode);
        document.getElementById('modalPilihPeriode').addEventListener('shown.bs.modal', tampilkanPreviewPeriode);

<?php if ($paksa_buka_popup): ?>
        // dibuka otomatis karena formulir diakses sebelum periode diisi
        new bootstrap.Modal(document.getElementById('modalPilihPeriode')).show();
<?php endif; ?>
    </script>
</body>
</html>