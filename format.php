<?php
// Fungsi bantu format angka jadi Rupiah.
// Dipakai di SEMUA halaman (Data Gaji, Tambah, Edit, Slip, Cetak/PDF, WhatsApp, Email)
// supaya formatnya selalu konsisten: "Rp 3.445.000"
// Database TETAP menyimpan angka biasa (3445000), ini hanya untuk tampilan.

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
