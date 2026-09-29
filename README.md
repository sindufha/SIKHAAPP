# SIKHA — Sistem Kehadiran Siswa

SIKHA adalah aplikasi presensi siswa berbasis PHP dan MySQL untuk SDI Khadijah Sukorejo. Presensi dapat dicatat melalui pemindaian QR atau secara manual oleh wali kelas.

## Kebutuhan

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 5.7+/8.x atau MariaDB yang mendukung tipe JSON
- Web server Apache/Nginx, atau server bawaan PHP untuk pengembangan
- HTTPS pada lingkungan produksi agar akses kamera QR bekerja dengan aman

## Instalasi

1. Buat dan pilih database `sikha_db`, lalu impor `database.sql` ke database tersebut.
2. Arahkan document root web server ke folder proyek atau letakkan folder ini di bawah document root.
3. Atur variabel lingkungan database bila tidak menggunakan nilai bawaan:

   - `DB_HOST` (bawaan `127.0.0.1`)
   - `DB_PORT` (bawaan `3306`)
   - `DB_NAME` (bawaan `sikha_db`)
   - `DB_USER` (bawaan `root`)
   - `DB_PASSWORD` (bawaan kosong)
   - `APP_BASE_PATH` (opsional, misalnya `/sikha-final`; biasanya terdeteksi otomatis)

4. Buka aplikasi dari browser dan masuk dengan akun awal:

   - Username: `admin`
   - Password: `password`

5. Segera ganti password akun admin melalui halaman Profil.

Untuk pengembangan lokal dari folder proyek:

```shell
php -S 127.0.0.1:8000
```

Kemudian buka `http://127.0.0.1:8000`.

## Peran pengguna

- **Admin:** mengelola pengguna, siswa, kelas, tahun ajaran, jam presensi, QR, laporan, dan audit log.
- **Guru:** memindai QR, mengisi presensi manual, dan melihat laporan hanya untuk kelas yang menjadi tanggung jawabnya.

## Catatan produksi

- Gunakan HTTPS dan kredensial database khusus aplikasi.
- Jangan gunakan akun database `root` di server produksi.
- Cadangkan database secara berkala; riwayat presensi berada di tabel `presensi`.
- QR siswa adalah kredensial presensi. Jangan menyebarkannya di luar kebutuhan sekolah.
