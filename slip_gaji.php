<?php
session_start();
require 'koneksi.php';
require 'format.php';

// gerbang penjaga: kalau belum login, tendang ke login.php
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

$gaji_id = (int) ($_GET['id'] ?? 0);

if ($gaji_id <= 0) {
    header('Location: data_gaji.php');
    exit;
}

// Fungsi bantu format tanggal Indonesia yang sudah diperbaiki
function formatIndoTanggal($tanggal) {
    if (!$tanggal) return '';
    $bulanIndo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    if (count($pecah) === 3) {
        return $pecah[2] . ' ' . ($bulanIndo[$pecah[1]] ?? $pecah) . ' ' . $pecah[0];
    }
    return $tanggal;
}

// ==============================
// AMBIL PERIODE AKTIF YANG SEDANG BERLAKU
// ==============================
$q_aktif = mysqli_query($koneksi, "SELECT tanggal_mulai, tanggal_selesai FROM periode_gaji WHERE is_active = 1 LIMIT 1");
$periode_aktif = mysqli_fetch_assoc($q_aktif);

// ==============================
// AMBIL DATA GAJI + KARYAWAN BERDASARKAN ID GAJI YANG DIPILIH DARI TABEL DATA GAJI
// ==============================
$stmt = mysqli_prepare($koneksi, "
    SELECT gaji.*, karyawan.nama, karyawan.nik, karyawan.jabatan, karyawan.no_wa, karyawan.email
    FROM gaji
    JOIN karyawan ON gaji.karyawan_id = karyawan.id
    WHERE gaji.id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $gaji_id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    header('Location: data_gaji.php');
    exit;
}

if ($periode_aktif) {
    $teks_periode = formatIndoTanggal($periode_aktif['tanggal_mulai']) . ' - ' . formatIndoTanggal($periode_aktif['tanggal_selesai']);
} else {
    $teks_periode = $data['periode'] ?? '';
}

// ==============================
// PERHITUNGAN GAJI
// ==============================
$gaji_pokok        = $data['gaji_pokok'];
$lembur            = $data['lembur'];
$pinjaman_karyawan = $data['pinjaman_karyawan'];

$total_penghasilan = $gaji_pokok + $lembur;
$total_potongan    = $pinjaman_karyawan;
$gaji_bersih       = $total_penghasilan - $total_potongan;

// ==============================
// SIAPKAN PESAN WHATSAPP
// ==============================
$pesan_wa = "SLIP GAJI KARYAWAN\n\n"
          . "Nama: " . $data['nama'] . "\n"
          . "Periode: " . $teks_periode . "\n"
          . "Jabatan: " . $data['jabatan'] . "\n\n"
          . "Gaji Pokok: " . rupiah($gaji_pokok) . "\n"
          . "Lembur: " . rupiah($lembur) . "\n"
          . "Total Penghasilan: " . rupiah($total_penghasilan) . "\n\n"
          . "Pinjaman: " . rupiah($pinjaman_karyawan) . "\n"
          . "Total Potongan: " . rupiah_potongan($total_potongan) . "\n\n"
          . "Gaji Bersih: " . rupiah($gaji_bersih) . "\n\n"
          . "Terima kasih.";

$no_wa_default = htmlspecialchars($data['no_wa'] ?? '');

// ==============================
// SIAPKAN ISI EMAIL (Simpel, Rapi, & Profesional)
// ==============================
$subjek_email = 'Slip Gaji Periode ' . $teks_periode . ' - ' . $data['nama'];
$isi_email    = "Halo " . $data['nama'] . ",\n\n"
              . "Berikut adalah rincian slip gaji Anda untuk periode " . $teks_periode . ":\n\n"
              . "• NIK / Jabatan : " . $data['nik'] . " / " . $data['jabatan'] . "\n"
              . "• Gaji Pokok    : " . rupiah($gaji_pokok) . "\n"
              . "• Lembur        : " . rupiah($lembur) . "\n"
              . "• Potongan      : " . rupiah_potongan($total_potongan) . "\n\n"
              . "Total Gaji Bersih : " . rupiah($gaji_bersih) . "\n\n"
              . "Terima kasih atas kerja keras Anda.\n\n"
              . "Salam,\n"
              . "Manajemen";

$email_default = htmlspecialchars($data['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="assets/style.css?v=2"/>
</head>
<body>

    <!-- ===================================================== -->
    <!-- BAGIAN AREA CETAK (Tercetak / PDF) -->
    <!-- ===================================================== -->
    <div class="slip-container" id="area-cetak">
       
        <h2 class="slip-title">SLIP GAJI KARYAWAN</h2>
        <p class="slip-periode">Periode <?= htmlspecialchars($teks_periode) ?></p>

        <div class="field-baca">
            <label>Nama:</label>
            <div class="kotak"><?= htmlspecialchars($data['nama']) ?></div>
        </div>
        <div class="field-baca">
            <label>NIK:</label>
            <div class="kotak"><?= htmlspecialchars($data['nik']) ?></div>
        </div>
        <div class="field-baca">
            <label>Jabatan:</label>
            <div class="kotak"><?= htmlspecialchars($data['jabatan']) ?></div>
        </div>

        <div class="dua-kolom">
            <div class="kolom">
                <div class="kolom-judul">PENGHASILAN</div>

                <div class="field-baca">
                    <label>Gaji pokok:</label>
                    <div class="kotak"><?= rupiah($gaji_pokok) ?></div>
                </div>
                <div class="field-baca">
                    <label>Lembur:</label>
                    <div class="kotak"><?= rupiah($lembur) ?></div>
                </div>
                <div class="field-baca">
                    <label>Total penghasilan:</label>
                    <div class="kotak"><?= rupiah($total_penghasilan) ?></div>
                </div>
            </div>

            <div class="kolom">
                <div class="kolom-judul">POTONGAN</div>

                <div class="field-baca">
                    <label>Pinjaman karyawan:</label>
                    <div class="kotak potongan-merah"><?= rupiah_potongan($pinjaman_karyawan) ?></div>
                </div>
                <div class="field-baca">
                    <label>Total potongan:</label>
                    <div class="kotak potongan-merah"><?= rupiah_potongan($total_potongan) ?></div>
                </div>
            </div>
        </div>

        <div class="field-baca gaji-bersih">
            <label>Gaji bersih:</label>
            <div class="kotak"><strong><?= rupiah($gaji_bersih) ?></strong></div>
        </div>
    </div>
    <!-- ===================== akhir area-cetak ===================== -->

    <!-- BAGIAN AKSI ADMIN (TIDAK IKUT TERCETAK) -->
    <div class="slip-container slip-aksi no-print">

        <div class="bagikan-wrap">
            <button type="button" class="btn-bagikan" onclick="toggleBagikan()">Bagikan &#9662;</button>
            <div class="bagikan-menu" id="bagikan-menu">
                <a href="#" onclick="bukaFormWhatsapp(); return false;">WhatsApp</a>
                <a href="#" onclick="window.print(); return false;">Cetak</a>
                <a href="#" onclick="window.print(); return false;">Download PDF</a>
                <a href="#" onclick="bukaFormEmail(); return false;">Email</a>
            </div>
        </div>

        <!-- Form Input Nomor WhatsApp Tujuan -->
        <div id="form-whatsapp-container" class="field-baca" style="margin-top:15px; display:none;">
            <label>Nomor WhatsApp tujuan (Bisa diedit):</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="no_wa_input" class="kotak" value="<?= $no_wa_default ?>" placeholder="Contoh: 081234567890" onkeypress="cekEnterWhatsapp(event)">
                <button type="button" onclick="kirimWhatsapp()" style="padding: 0 15px; cursor: pointer;">Kirim</button>
            </div>
            <small style="color: #666; display: block; margin-top: 4px;">Tekan <b>Enter</b> pada keyboard untuk langsung mengirim.</small>
        </div>

        <!-- Form Input Email Tujuan -->
        <div id="form-email-container" class="field-baca" style="margin-top:15px; display:none;">
            <label>Email tujuan (Bisa diedit):</label>
            <div style="display: flex; gap: 8px;">
                <input type="email" id="email_input" class="kotak" value="<?= $email_default ?>" placeholder="Contoh: karyawan@email.com" onkeypress="cekEnterEmail(event)">
                <button type="button" onclick="kirimEmail()" style="padding: 0 15px; cursor: pointer;">Kirim</button>
            </div>
            <small style="color: #666; display: block; margin-top: 4px;">Tekan <b>Enter</b> pada keyboard untuk langsung memproses.</small>
        </div>

        <a href="data_gaji.php" class="btn-kembali" style="display: block; margin-top: 15px;">&larr; Kembali ke Data Gaji</a>
    </div>

    <script>
        const pesanWA = <?= json_encode($pesan_wa) ?>;
        const subjekEmail = <?= json_encode($subjek_email) ?>;
        const isiEmail = <?= json_encode($isi_email) ?>;

        function toggleBagikan() {
            document.getElementById('bagikan-menu').classList.toggle('tampil');
        }

        document.addEventListener('click', function (e) {
            const wrap = document.querySelector('.bagikan-wrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('bagikan-menu').classList.remove('tampil');
            }
        });

        function bukaFormWhatsapp() {
            document.getElementById('bagikan-menu').classList.remove('tampil');
            document.getElementById('form-email-container').style.display = 'none';
            document.getElementById('form-whatsapp-container').style.display = 'block';
            document.getElementById('no_wa_input').focus();
        }

        function bukaFormEmail() {
            document.getElementById('bagikan-menu').classList.remove('tampil');
            document.getElementById('form-whatsapp-container').style.display = 'none';
            document.getElementById('form-email-container').style.display = 'block';
            document.getElementById('email_input').focus();
        }

        function cekEnterWhatsapp(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                kirimWhatsapp();
            }
        }

        function cekEnterEmail(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                kirimEmail();
            }
        }

        function kirimWhatsapp() {
            let nomor = document.getElementById('no_wa_input').value;
            nomor = nomor.replace(/\D/g, '');

            if (nomor === '') {
                alert('Nomor WhatsApp belum diisi.');
                return;
            }
            if (nomor.startsWith('0')) {
                nomor = '62' + nomor.substring(1);
            }
            const link = 'https://wa.me/' + nomor + '?text=' + encodeURIComponent(pesanWA);
            window.open(link, '_blank');
        }

        function kirimEmail() {
            let emailTujuan = document.getElementById('email_input').value.trim();

            if (emailTujuan === '') {
                alert('Email karyawan belum diisi.');
                return;
            }

            const gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=' 
                        + encodeURIComponent(emailTujuan) 
                        + '&su=' + encodeURIComponent(subjekEmail) 
                        + '&body=' + encodeURIComponent(isiEmail);

            window.open(gmailUrl, '_blank');
        }
    </script>

</body>
</html>