# 🌹 Sistem Informasi & Absensi Legio Maria (Legio Mariae)

Aplikasi web modern, responsif, dan elegan untuk pengelolaan presensi rapat mingguan, keanggotaan aktif & auksiler, jadwal pertemuan, dan rekapitulasi kehadiran Legio Maria. Dirancang khusus untuk bekerja sempurna di **Laragon (MySQL & PHP)** dengan tampilan yang indah dan ramah perangkat (Laptop, Komputer, Tablet, iPhone, dan Android).

---

## ✨ Fitur Unggulan

1. **Halaman Login Admin Sakral & Eksklusif**
   - Menampilkan visual lukisan kudus **Hati Tersuci Santa Perawan Maria** (sesuai foto yang diunggah).
   - Tampilan Glassmorphism bernuansa *Marian Blue* dan aksen *Golden Glory*.
   - Dilengkapi kutipan sakral doa **Catena Legionis**: *"Siapakah Dia yang muncul sebagai fajar merekah..."*
   - Akun Admin default:
     - **Username**: `admin`
     - **Password**: `admin123`

2. **Dukungan Anggota Dinamis & Tak Terbatas (Scalable)**
   - Mendukung anggota dalam jumlah berapapun (puluhan, ratusan, hingga ribuan legioner tanpa batas).
   - Penomoran otomatis Nomor Buku Induk Anggota (`LM-001`, `LM-002`, dst).
   - Klasifikasi lengkap: **Legioner Aktif**, **Legioner Auksiler (Pendoa)**, dan **Perwira** (Pemimpin Spritual, Pemimpin, Wakil, Sekretaris, Bendahara).
   - Filter cepat, pencarian nama langsung, status probation/telah berjanji Legio.

3. **Absensi Rapat Mingguan Interaktif (Mobile & Desktop Friendly)**
   - Pilih tanggal rapat / pertemuan presidium.
   - Status presensi lengkap:
     - 🟢 **H** - Hadir
     - 🔵 **I** - Izin
     - 🟡 **S** - Sakit
     - 🔴 **A** - Alpa (Tanpa Keterangan)
     - 🟣 **T** - Tugas Kerasulan
   - Tombol sekali klik: **"Set Semua Hadir"** untuk mempercepat proses presensi.
   - Kolom catatan tugas kerasulan mingguan untuk setiap legioner.
   - Desain tombol sentuh besar yang sangat nyaman di layar HP dan iPhone.

4. **Jadwal & Buku Pertemuan Presidium**
   - Nomor rapat berturut-turut, tanggal, jam, dan lokasi.
   - Pencatatan pembacaan Buku Pegangan Legio Maria (Bab & Halaman).
   - Tema Alokusi (Allocutio) dan pemberi alokusi (Spiritual Director / Perwira).

5. **Rekapitulasi & Laporan Siap Cetak (Official Report)**
   - Matriks presensi lengkap per bulan & per tahun.
   - Perhitungan persentase kehadiran otomatis.
   - Format cetak rapi (*Print Ready*) lengkap dengan kolom tanda tangan Pemimpin Presidium dan Sekretaris untuk arsip kuria / komisium.

---

## 🚀 Cara Menjalankan di Laragon

### Langkah 1: Pindahkan / Copy Folder ke Laragon `www`
Salin folder `absensi-legio-maria` ke direktori web Laragon Anda, yaitu:
```text
C:\laragon\www\absensi-legio-maria
```

*(Tips: Buka Laragon -> Klik tombol **Root** di Laragon untuk langsung membuka folder `C:\laragon\www`, lalu salin folder `absensi-legio-maria` ke sana)*

### Langkah 2: Nyalakan Laragon
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All** (memastikan Apache dan MySQL berjalan).

### Langkah 3: Buka di Browser
Buka browser favorit Anda (Chrome / Edge / Firefox / Safari di HP) dan kunjungi:
```text
http://localhost/absensi-legio-maria
```
atau jika fitur Pretty URL Laragon aktif:
```text
http://absensi-legio-maria.test
```

> **Catatan Database Otomatis**: 
> Saat pertama kali Anda membuka halaman web, sistem akan **secara otomatis membuat database `absensi_legio_maria` beserta seluruh tabel dan data awalnya**. Anda tidak perlu repot melakukan import database manual!
> (File `database.sql` juga tetap kami sertakan jika Anda ingin mengecek atau mengimpornya via phpMyAdmin/HeidiSQL).

---

## 📱 Akses dari HP / iPhone dalam Satu Jaringan Wi-Fi

Untuk membuka absensi ini dari HP atau iPhone Anda:
1. Pastikan laptop dan HP terhubung ke Wi-Fi yang sama.
2. Cek IP Address laptop Anda (misalnya: `192.168.1.15`).
3. Buka browser Safari / Chrome di HP Anda, lalu ketik:
   ```text
   http://192.168.1.15/absensi-legio-maria
   ```
4. Absensi siap digunakan langsung dari genggaman Anda saat rapat presidium berlangsung!

---

*Ad Jesum Per Mariam — Melalui Maria Menuju Yesus.*

