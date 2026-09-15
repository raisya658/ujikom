<?php
// Fungsi bantu format angka jadi Rupiah.
// Dipakai di SEMUA halaman (Data Gaji, Tambah, Edit, Slip, Cetak/PDF, WhatsApp, Email)
// supaya formatnya selalu konsisten: "Rp 3.445.000"
// Database TETAP menyimpan angka biasa (3445000), ini hanya untuk tampilan.

// Format angka jadi "2.000.000" (pakai titik ribuan, tanpa "Rp") -> dipakai
// buat isi awal input nominal supaya dari pertama buka juga sudah rapi.
if (!function_exists('angkaRibuan')) {
    function angkaRibuan($angka) {
        return number_format((float) $angka, 0, ',', '.');
    }
}

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

// Khusus nilai POTONGAN: otomatis kasih tanda "-" kalau nilainya lebih dari 0.
// Kalau 0, tetap tampil "Rp 0" tanpa minus (biar tidak aneh secara visual).
function rupiah_potongan($angka) {
    $angka = (float) $angka;
    if ($angka > 0) {
        return '- ' . rupiah($angka);
    }
    return rupiah($angka);
}

// Fungsi bantu format tanggal Indonesia (contoh: 2026-09-25 -> 25 September 2026)
// Dipusatkan di sini supaya tidak ditulis ulang di setiap file (data_gaji, tambah_gaji,
// edit_gaji, slip_gaji dulu masing-masing punya salinan sendiri -> rawan beda hasil).
if (!function_exists('formatIndoTanggal')) {
    function formatIndoTanggal($tanggal) {
        if (!$tanggal) return '';
        $bulanIndo = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $pecah = explode('-', $tanggal);
        if (count($pecah) === 3) {
            return (int) $pecah[2] . ' ' . ($bulanIndo[$pecah[1]] ?? $pecah[1]) . ' ' . $pecah[0];
        }
        return $tanggal;
    }
}

// Nama bulan Indonesia dari angka bulan (1-12) atau string '01'-'12'.
if (!function_exists('namaBulanIndo')) {
    function namaBulanIndo($bulan) {
        $bulanIndo = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $key = str_pad((string)(int)$bulan, 2, '0', STR_PAD_LEFT);
        return $bulanIndo[$key] ?? $key;
    }
}

// =====================================================================
// PERIODE GAJIAN (sesuai mockup: "25 November - 25 Desember")
// Admin cuma memilih BULAN & TAHUN, tanggalnya SELALU dipaksa tanggal 25.
// Periode dihitung: tanggal 25 bulan yang dipilih  s/d  tanggal 25 bulan
// berikutnya. Di database yang disimpan cukup tanggal MULAI periode
// (kolom `gaji`.`tanggal_gajian`), tanggal selesai dihitung otomatis.
// =====================================================================

// Ambil tanggal mulai & selesai periode dari input bulan format "YYYY-MM".
if (!function_exists('periodeGajianDariBulan')) {
    function periodeGajianDariBulan($bulanInput) {
        if (is_string($bulanInput) && preg_match('/^(\d{4})-(\d{2})$/', $bulanInput, $m)) {
            $tahun = (int) $m[1];
            $bulan = (int) $m[2];
        } else {
            $tahun = (int) date('Y');
            $bulan = (int) date('m');
        }
        $mulai   = sprintf('%04d-%02d-25', $tahun, $bulan);
        $selesai = date('Y-m-d', strtotime($mulai . ' +1 month')); // otomatis tanggal 25 bulan depan
        return ['mulai' => $mulai, 'selesai' => $selesai];
    }
}

// Ubah tanggal mulai periode jadi teks siap tampil.
// Contoh hasil: "25 November - 25 Desember 2026"
//               "25 Desember 2026 - 25 Januari 2027" (kalau ganti tahun)
if (!function_exists('teksPeriodeGajian')) {
    function teksPeriodeGajian($tanggalMulai) {
        if (!$tanggalMulai) return 'Belum diatur';

        $periode = periodeGajianDariBulan(substr($tanggalMulai, 0, 7));
        $mulai   = $periode['mulai'];
        $selesai = $periode['selesai'];

        $thn1 = substr($mulai, 0, 4);
        $thn2 = substr($selesai, 0, 4);
        $bln1 = namaBulanIndo(substr($mulai, 5, 2));
        $bln2 = namaBulanIndo(substr($selesai, 5, 2));

        if ($thn1 === $thn2) {
            return '25 ' . $bln1 . ' - 25 ' . $bln2 . ' ' . $thn1;
        }
        return '25 ' . $bln1 . ' ' . $thn1 . ' - 25 ' . $bln2 . ' ' . $thn2;
    }
}

// Bangun tanggal gajian tetap tanggal 25 dari input bulan format "YYYY-MM".
// Kalau formatnya tidak valid, otomatis jatuh ke bulan berjalan (bulan sekarang).
if (!function_exists('tanggalGajianDariBulan')) {
    function tanggalGajianDariBulan($bulanInput) {
        if (is_string($bulanInput) && preg_match('/^(\d{4})-(\d{2})$/', $bulanInput, $m)) {
            $tahun = $m[1];
            $bulan = $m[2];
        } else {
            $tahun = date('Y');
            $bulan = date('m');
        }
        return $tahun . '-' . $bulan . '-25';
    }
}