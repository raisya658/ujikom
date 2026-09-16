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

// ==============================
// AMBIL DATA GAJI + KARYAWAN
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

$teks_periode = teksPeriodeGajian($data['tanggal_gajian']);

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
// SIAPKAN PESAN WHATSAPP & EMAIL
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

$email_default   = htmlspecialchars($data['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="assets/style.css?v=3"/>
    <!-- FontAwesome untuk ikon tombol -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Panel aksi dibuat nempel tepat di atas kotak slip */
        .panel-aksi-atas {
            max-width: 750px;
            margin: 10px auto 2px auto; /* Jarak atas kecil, jarak bawah sangat dekat dengan slip */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5px;
            box-sizing: border-box;
        }
        .kelompok-ikon {
            display: flex;
            gap: 10px;
        }
        .btn-ikon {
            background: #ffffff;
            border: 1px solid #ced4da;
            color: #333;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .btn-ikon:hover {
            background: #195b8c;
            color: #fff;
            border-color: #195b8c;
        }
        .btn-ikon.wa:hover { background: #25d366; border-color: #25d366; }
        .btn-ikon.email:hover { background: #ea4335; border-color: #ea4335; }
        .btn-ikon.pdf:hover { background: #dc3545; border-color: #dc3545; }

        .form-drawer {
            max-width: 750px;
            margin: 0 auto 5px auto;
            background: #fff;
            padding: 12px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            box-sizing: border-box;
            display: none;
            border-radius: 4px;
        }
        .btn-kembali-teks {
            color: #333; 
            text-decoration: none; 
            font-size: 14px; 
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-kembali-teks:hover {
            color: #195b8c;
        }
        /* Mengurangi margin atas slip-container agar langsung merapat ke atas */
        .slip-container {
            margin-top: 5px !important;
        }
    </style>
</head>
<body>

    <!-- ===================================================== -->
    <!-- BAGIAN AKSI ADMIN DI ATAS (MENEMPEL KE FORMULIR) -->
    <!-- ===================================================== -->
    <div class="panel-aksi-atas no-print">
        <a href="data_gaji.php" class="btn-kembali-teks">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>

        <div class="kelompok-ikon">
            <!-- Tombol WhatsApp -->
            <button type="button" class="btn-ikon wa" title="Kirim / Bagikan via WhatsApp" onclick="bukaFormWhatsapp()">
                <i class="fab fa-whatsapp"></i>
            </button>

            <!-- Tombol Email -->
            <button type="button" class="btn-ikon email" title="Kirim via Email" onclick="bukaFormEmail()">
                <i class="fa fa-envelope"></i>
            </button>

            <!-- Tombol PDF / Cetak -->
            <button type="button" class="btn-ikon pdf" title="Cetak / Download PDF" onclick="window.print()">
                <i class="fa fa-file-pdf"></i>
            </button>
        </div>
    </div>

    <!-- Form Input Nomor WhatsApp Tersembunyi -->
    <div id="form-whatsapp-container" class="form-drawer no-print">
        <label style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 5px;">Nomor WhatsApp Tujuan:</label>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="no_wa_input" class="kotak" value="<?= $no_wa_default ?>" placeholder="Contoh: 081234567890" onkeypress="cekEnterWhatsapp(event)" style="padding: 6px 10px; border: 1px solid #ccc; flex: 1;">
            <button type="button" onclick="kirimWhatsapp()" style="padding: 0 15px; cursor: pointer; background: #25d366; color: white; border: none; border-radius: 3px; font-weight: bold;">Kirim</button>
        </div>
        <small style="color: #666; display: block; margin-top: 5px;">Tekan <b>Enter</b> untuk langsung mengirim pesan WhatsApp.</small>
    </div>

    <!-- Form Input Email Tersembunyi -->
    <div id="form-email-container" class="form-drawer no-print">
        <label style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 5px;">Email Tujuan:</label>
        <div style="display: flex; gap: 8px;">
            <input type="email" id="email_input" class="kotak" value="<?= $email_default ?>" placeholder="Contoh: karyawan@email.com" onkeypress="cekEnterEmail(event)" style="padding: 6px 10px; border: 1px solid #ccc; flex: 1;">
            <button type="button" onclick="kirimEmail()" style="padding: 0 15px; cursor: pointer; background: #ea4335; color: white; border: none; border-radius: 3px; font-weight: bold;">Kirim</button>
        </div>
        <small style="color: #666; display: block; margin-top: 5px;">Tekan <b>Enter</b> untuk memproses email.</small>
    </div>

    <!-- ===================================================== -->
    <!-- BAGIAN AREA CETAK / SLIP GAJI -->
    <!-- ===================================================== -->
    <div class="slip-container" id="area-cetak">

        <h2 class="slip-title">SLIP GAJI KARYAWAN</h2>
        <p class="slip-periode">Periode: <?= htmlspecialchars($teks_periode) ?></p>

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

    <script>
        const pesanWA = <?= json_encode($pesan_wa) ?>;
        const subjekEmail = <?= json_encode($subjek_email) ?>;
        const isiEmail = <?= json_encode($isi_email) ?>;

        function bukaFormWhatsapp() {
            const formWa = document.getElementById('form-whatsapp-container');
            const formEmail = document.getElementById('form-email-container');
            
            formEmail.style.display = 'none';
            formWa.style.display = formWa.style.display === 'block' ? 'none' : 'block';
            if(formWa.style.display === 'block') {
                document.getElementById('no_wa_input').focus();
            }
        }

        function bukaFormEmail() {
            const formWa = document.getElementById('form-whatsapp-container');
            const formEmail = document.getElementById('form-email-container');
            
            formWa.style.display = 'none';
            formEmail.style.display = formEmail.style.display === 'block' ? 'none' : 'block';
            if(formEmail.style.display === 'block') {
                document.getElementById('email_input').focus();
            }
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