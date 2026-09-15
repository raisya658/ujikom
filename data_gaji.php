<?php
session_start();
require 'koneksi.php';
require 'format.php';

// gerbang penjaga: kalau belum login, tendang ke login.php
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fungsi bantu format tanggal Indonesia (contoh: 2026-09-01 -> 01 September 2026)
function formatIndoTanggal($tanggal) {
    if (!$tanggal) return '';
    $bulanIndo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    if (count($pecah) === 3) {
        return $pecah[2] . ' ' . ($bulanIndo[$pecah[1]] ?? $pecah[1]) . ' ' . $pecah[0];
    }
    return $tanggal;
}

// ==============================
// PROSES SIMPAN PERIODE BARU (ATUR PERIODE)
// ==============================
$pesan_error = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_periode') {
    $tanggal_mulai   = trim($_POST['tanggal_mulai'] ?? '');
    $tanggal_selesai = trim($_POST['tanggal_selesai'] ?? '');

    if (empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $pesan_error = 'Tanggal mulai dan tanggal selesai wajib diisi!';
    } elseif ($tanggal_selesai < $tanggal_mulai) {
        $pesan_error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai!';
    } else {
        // Nonaktifkan is_active sebelumnya
        mysqli_query($koneksi, "UPDATE periode_gaji SET is_active = 0");

        // Cek apakah tanggal ini sudah pernah ada di database
        $cek_ada = mysqli_prepare($koneksi, "SELECT id FROM periode_gaji WHERE tanggal_mulai = ? AND tanggal_selesai = ? LIMIT 1");
        mysqli_stmt_bind_param($cek_ada, 'ss', $tanggal_mulai, $tanggal_selesai);
        mysqli_stmt_execute($cek_ada);
        $res_ada = mysqli_stmt_get_result($cek_ada);

        if ($row_ada = mysqli_fetch_assoc($res_ada)) {
            // Jika sudah ada di riwayat, tinggal aktifkan kembali (is_active = 1)
            $id_exist = $row_ada['id'];
            mysqli_query($koneksi, "UPDATE periode_gaji SET is_active = 1 WHERE id = $id_exist");
            $pesan_sukses = 'Periode penggajian berhasil diaktifkan kembali!';
        } else {
            // Jika belum ada, insert baru
            $insert = "INSERT INTO periode_gaji (tanggal_mulai, tanggal_selesai, status, is_active) VALUES (?, ?, 'Belum Digunakan', 1)";
            $stmt_ins = mysqli_prepare($koneksi, $insert);
            mysqli_stmt_bind_param($stmt_ins, 'ss', $tanggal_mulai, $tanggal_selesai);
            if (mysqli_stmt_execute($stmt_ins)) {
                $pesan_sukses = 'Periode penggajian baru berhasil disimpan!';
            } else {
                $pesan_error = 'Gagal menyimpan periode penggajian ke database.';
            }
        }
    }
}

// ==============================
// AMBIL PERIODE AKTIF SAAT INI (UNTUK VALUE & PETUNJUK DI MODAL)
// ==============================
$q_aktif = mysqli_query($koneksi, "SELECT * FROM periode_gaji WHERE is_active = 1 LIMIT 1");
$info_aktif = mysqli_fetch_assoc($q_aktif);

// ==============================
// AMBIL RIWAYAT SEMUA PERIODE
// ==============================
$q_histori = mysqli_query($koneksi, "SELECT id, tanggal_mulai, tanggal_selesai, is_active FROM periode_gaji ORDER BY tanggal_mulai DESC");
$arr_histori = mysqli_fetch_all($q_histori, MYSQLI_ASSOC);

// ==============================
// SEARCH & FILTER
// ==============================
$cari       = trim($_GET['cari'] ?? '');       // pencarian nama karyawan
$filter_pid = trim($_GET['filter_periode'] ?? ''); // filter periode_id

// query dasar: gabung tabel gaji + karyawan + periode_gaji (status dihapus)
$sql = "SELECT gaji.id, karyawan.nama, karyawan.jabatan, 
               periode_gaji.id AS periode_id, periode_gaji.tanggal_mulai, periode_gaji.tanggal_selesai,
               gaji.gaji_pokok, gaji.lembur, gaji.pinjaman_karyawan
        FROM gaji
        JOIN karyawan ON gaji.karyawan_id = karyawan.id
        LEFT JOIN periode_gaji ON gaji.periode_id = periode_gaji.id
        WHERE 1=1";
$params = [];
$types   = '';

if ($cari !== '') {
    $sql .= " AND karyawan.nama LIKE ?";
    $params[] = '%' . $cari . '%';
    $types   .= 's';
}
if ($filter_pid !== '') {
    $sql .= " AND gaji.periode_id = ?";
    $params[] = $filter_pid;
    $types   .= 'i';
}
$sql .= " ORDER BY gaji.id DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ambil semua daftar periode dari tabel periode_gaji (untuk dropdown filter)
$daftar_periode = mysqli_query($koneksi, "SELECT id, tanggal_mulai, tanggal_selesai, status FROM periode_gaji ORDER BY tanggal_mulai DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Gaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css?v=2">
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

        <!-- NOTIFIKASI ERROR / SUKSES -->
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($pesan_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($pesan_sukses) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- HEADER + TOMBOL ATUR PERIODE & TAMBAH DATA GAJI -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h4 class="mb-0">Data Gaji</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalAturPeriode">
                    📅 Atur Periode
                </button>
                <a href="tambah_gaji.php" class="btn btn-primary">+ Tambah Data Gaji</a>
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
                    <?php while ($p = mysqli_fetch_assoc($daftar_periode)): 
                        $label_p = formatIndoTanggal($p['tanggal_mulai']) . ' - ' . formatIndoTanggal($p['tanggal_selesai']);
                    ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_pid == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label_p) ?>
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
                            <th>Periode</th>
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
                            
                            $periode_teks = '';
                            if (!empty($row['tanggal_mulai']) && !empty($row['tanggal_selesai'])) {
                                $periode_teks = formatIndoTanggal($row['tanggal_mulai']) . ' - ' . formatIndoTanggal($row['tanggal_selesai']);
                            } else {
                                $periode_teks = 'Belum diatur';
                            }
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

    <!-- MODAL ATUR PERIODE -->
    <div class="modal fade" id="modalAturPeriode" tabindex="-1" aria-labelledby="modalAturPeriodeLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="data_gaji.php">
                    <input type="hidden" name="action" value="simpan_periode">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAturPeriodeLabel">Atur Periode Penggajian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        
                        <!-- KOTAK PETUNJUK PERIODE AKTIF -->
                        <?php if ($info_aktif): ?>
                            <div class="alert alert-info py-2 mb-3">
                                <small>
                                    <b>Petunjuk:</b> Periode aktif saat ini adalah tanggal 
                                    <b><?= formatIndoTanggal($info_aktif['tanggal_mulai']) ?> s.d <?= formatIndoTanggal($info_aktif['tanggal_selesai']) ?></b>. 
                                    Jika ingin mengganti, silakan pilih dari riwayat atau ubah tanggal di bawah ini.
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 mb-3">
                                <small><b>Petunjuk:</b> Belum ada periode yang diset. Silakan masukkan tanggal periode di bawah ini.</small>
                            </div>
                        <?php endif; ?>

                        <!-- DROPDOWN PILIH DARI RIWAYAT PERIODE -->
                        <div class="mb-3">
                            <label for="pilih_riwayat" class="form-label">Pilih dari Riwayat Periode</label>
                            <select class="form-select" id="pilih_riwayat" onchange="pilihPeriodeRiwayat(this)">
                                <option value="">-- Pilih periode yang pernah diinput --</option>
                                <?php foreach ($arr_histori as $h): 
                                    $label_h = formatIndoTanggal($h['tanggal_mulai']) . ' s.d ' . formatIndoTanggal($h['tanggal_selesai']);
                                ?>
                                    <option value="<?= $h['tanggal_mulai'] . '|' . $h['tanggal_selesai'] ?>">
                                        <?= htmlspecialchars($label_h) ?> <?= $h['is_active'] == 1 ? '(Sedang Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" 
                                   value="<?= $info_aktif['tanggal_mulai'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" 
                                   value="<?= $info_aktif['tanggal_selesai'] ?? '' ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Periode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function pilihPeriodeRiwayat(selectEl) {
            const val = selectEl.value;
            if (val) {
                const parts = val.split('|');
                document.getElementById('tanggal_mulai').value = parts[0];
                document.getElementById('tanggal_selesai').value = parts[1]; // Diperbaiki jadi parts[1]
            }
        }
    </script>
</body>
</html>