# IPL RT 14

Aplikasi pengelolaan iuran lingkungan untuk Perumahan Villa Rizky Ilhami. Tahap pertama: database, resource Filament, dashboard rekap, akun pengurus/warga, dan unggah/verifikasi resi.

## Stack

- PHP **8.5.x** (`php:8.5-fpm-alpine`).
- Laravel **13.37.0**, Filament **5.10.2**; versi paket dikunci dalam `composer.lock`.
- MySQL **8.4**, Nginx **1.28**, Composer **2**.
- Spatie Permission untuk role dan izin.

Laravel dan Filament adalah paket Composer yang dipasang saat build image aplikasi. Image aplikasi: `ipl-erte14-app:dev`; image web: `ipl-erte14-web:dev`. Tidak memerlukan PHP, Composer, atau Node di komputer host. Docker Desktop harus aktif menggunakan Linux containers.

## 1. Jalankan Docker di Windows

```powershell
git clone https://github.com/coker91/IPL_erte14.git
cd IPL_erte14
.\scripts\setup-docker.ps1
```

Jika sudah clone sebelumnya, jalankan `git pull` dari folder repository, kemudian skrip setup di atas.

Skrip menyalin `.env.example` jika belum ada, membuat APP_KEY dan password database acak jika nilainya kosong, menarik image database, membangun image aplikasi/web dengan base image terbaru, menjalankan container, lalu migrasi dan seeder master. Database menggunakan named volume; menjalankan ulang skrip tidak menghapus data.

Linux/macOS:

```sh
sh scripts/setup-docker.sh
```

Buat akun admin pertama secara interaktif:

```sh
docker compose exec app php artisan ipl:create-admin
```

Isi username, nama, email, dan password minimal 12 karakter. Tidak ada password admin produksi yang ditanam di kode.

| Halaman | Alamat |
| --- | --- |
| Login pengurus | http://localhost:8080/admin/login |
| Dashboard rekap | http://localhost:8080/ |
| Login warga | http://localhost:8080/warga/login |
| Tagihan dan unggah resi warga | http://localhost:8080/warga/tagihan-saya |

Dashboard memerlukan login pada implementasi ini. Port web hanya terikat ke `127.0.0.1`; MySQL dan PHP-FPM tidak dibuka ke host. Ini konfigurasi pengembangan lokal.

## 2. Seeder master dan data sumber

`db:seed` memasukkan kategori IPL/THR/HUT RI, jabatan RT, pengaturan dasar, dan role/permission. Seeder ini tidak membuat pembayaran fiktif atau akun untuk seluruh warga.

**Pilihan A — impor snapshot Google Script:**

1. Unduh `ipl-source-snapshot.json` yang disediakan terpisah dalam percakapan.
2. Simpan menjadi `.local-data/ipl-source-snapshot.json` dalam folder proyek.
3. Periksa field `year` sebelum impor. Angka 2026 mengikuti tanggal halaman saat diamati; tahun tidak tertulis pada judul tabel sumber.
4. Jalankan:

```sh
docker compose exec app php artisan ipl:import-source
```

Alternatif perintah seeder Laravel standar: `docker compose exec app php artisan db:seed --class=GoogleScriptSnapshotSeeder`. Keduanya memakai importer dan aturan yang sama.

Snapshot diamati pada 19 Oktober 2026, dengan keterangan sumber terakhir diperbarui **15 October 2026, 12:37 WIB**. Isinya 87 unit dan 1.044 status bulanan. Pada Oktober, 18 unit memiliki setidaknya tiga bulan belum bayar.

Saldo awal sumber:

| Kategori | Saldo |
| --- | ---: |
| IPL | Rp18.389.017 |
| THR | Rp451.409 |
| HUT RI | Rp81 |

Importer menyimpan status lama di `legacy_recap_entries`, terpisah dari tagihan/transaksi baru. Tidak merekayasa tarif, nominal pembayaran per warga, tanggal transfer, atau resi yang tidak tersedia di halaman sumber. Saldo awal dicatat satu kali, bukan menjumlah ulang seluruh status lunas. Impor snapshot yang sama diulang tidak menggandakan data. Impor berbeda atau impor ke database yang sudah berisi rumah/keuangan ditolak agar tidak menimpa data operasional.

Nama/status rumah kosong dipertahankan sebagai label sumber, bukan dibuat sebagai profil orang. Relasi penghuni impor belum diberi akses pembayaran otomatis: pengurus harus memeriksa hubungan akun–warga–rumah dahulu.

File snapshot privat, `.env`, resi, database runtime, dan `.local-data` dikecualikan dari Git dan Docker image. Repository publik hanya memuat kode importer/seeder. Jangan memasukkan data sumber pribadi ke commit berikutnya.

**Pilihan B — data simulasi:**

Gunakan database pengembangan yang belum diimpor snapshot. Isi `IPL_DEMO_PASSWORD` di `.env` dengan password pilihan minimal 12 karakter, lalu:

```sh
docker compose up -d --force-recreate app
docker compose exec app php artisan db:seed --class=DemoSeeder
```

Akun simulasi: `super_admin`, `ketua_rt`, `wakil_rt`, `sekretaris`, `bendahara`, `pengurus`, `warga_demo`. Semuanya memakai password dari `.env`. Hanya satu rumah `DEMO/1`, rekening simulasi, dan tiga tagihan @Rp100.000 untuk mencoba alur. Angka tersebut **bukan tarif asli RT**. Seeder demo dibatasi lingkungan local/testing dan tidak mengganti password akun yang sudah ada.

Jangan mencampur pilihan A/B pada database operasional yang sama. Tidak ada instruksi `migrate:fresh` pada setup normal.

## 3. Urutan penggunaan

1. Login admin. Masukkan atau impor warga/rumah.
2. Periksa penghuni pada menu **Penghuni Rumah**. Atur tanggal hubungan dan izin mengelola pembayaran.
3. Buat akun di **Akun Pengguna**, hubungkan ke profil warga, lalu berikan role yang sesuai. Akun baru dengan profil warga mendapat role warga. Hanya super_admin/izin `roles.manage` dapat mengubah role.
4. Isi susunan pengurus dan kontak jika ingin ditampilkan. Masa tugas menentukan tampilan kontak; penetapan/pencabutan role tetap dikelola terpisah melalui akun pengguna pada tahap ini.
5. Tentukan **Tarif Iuran** dengan periode berlaku yang tidak tumpang tindih. Tarif asli tidak diisi otomatis karena tidak terlihat pada sumber.
6. Di **Tagihan**, gunakan **Terbitkan IPL Bulanan**, pilih tanggal 1 sebagai periode dan tanggal jatuh tempo yang disepakati. Periode yang sudah ada atau sudah lunas/dikecualikan pada snapshot dilewati. Tagihan manual per rumah juga tersedia.
7. Warga login, memilih rumah, rekening tujuan, beberapa tagihan satu kategori, tanggal/nominal transfer, dan 1–3 resi. Total harus sesuai jumlah sisa tagihan terpilih.
8. Bendahara memeriksa resi dan penerimaan dana, kemudian **Setujui** atau **Tolak** dengan alasan. Pengajuan menunggu/ditolak tidak melunasi tagihan dan tidak menambah kas.
9. Pembayaran disetujui mencatat pemasukan satu kali. Koreksi memakai **Balikkan** dengan alasan dan kas pembalik; transaksi lama tetap tersimpan. Petugas tidak boleh memverifikasi pengajuannya sendiri.

Unggahan menerima JPG/PNG/PDF, maksimal 5 MB per berkas. File disimpan privat dan diunduh melalui route yang memeriksa izin. Pengaju hanya melihat resinya sendiri; pengurus perlu izin `payments.view`.

Pembayaran sebagian belum diaktifkan pada formulir. Penerbitan tagihan rutin difokuskan pada IPL. THR/HUT RI sudah memiliki kategori, rekening, dan kas, tetapi aturan tagihannya harus ditentukan pengurus. Pembayaran bertanggal sebelum/sama dengan tanggal saldo awal harus direkonsiliasi, agar tidak menghitung penerimaan yang sudah masuk saldo awal dua kali.

## 4. Resource Filament

| Kelompok | Resource |
| --- | --- |
| Warga | Warga, Rumah, Penghuni Rumah |
| Pengurus | Jabatan RT, Susunan Pengurus, Akun Pengguna |
| Iuran | Kategori Dana, Rekening Pembayaran, Tarif Iuran, Tagihan |
| Keuangan | Verifikasi Pembayaran, Buku Kas |
| Informasi | Rekap Sumber Lama, Pengaturan, Riwayat Aktivitas |

Master dapat ditambah/diperbarui menurut izin. Tagihan yang terbit, buku kas, dan riwayat pembayaran tidak diedit bebas atau dihapus melalui resource. Pengeluaran/pemasukan lain dicatat sebagai entri baru. Nominal/kategori/awal tarif lama bersifat tetap. Perubahan tarif dilakukan dengan menutup tanggal akhir tarif lama lalu menambah tarif baru; akhir tarif tidak boleh mendahului tagihan yang sudah terbit.

## 5. Perintah Docker

```sh
# Lihat layanan dan log
docker compose ps
docker compose logs --tail=100 app web db

# Tarik MySQL dan perbarui base image saat build aplikasi
docker compose pull db
docker compose build --pull
docker compose up -d

# Jalankan pengujian aplikasi di image pengujian
docker compose --profile test run --build --rm test

# Hentikan layanan, tetap pertahankan volume database dan resi
docker compose down
```

Untuk menyimpan image aplikasi ke arsip setelah build pada komputer Anda:

```sh
docker save -o ipl-erte14-images.tar ipl-erte14-app:dev ipl-erte14-web:dev mysql:8.4
```

Perubahan source memerlukan build ulang karena image berisi salinan kode. Jangan menjalankan `docker compose down -v` kecuali memang ingin membuang volume database dan resi.

## Validasi dan batas saat ini

- Migrasi, seeder, impor snapshot, resource admin, formulir warga, login, otorisasi resi, verifikasi/pembalikan, dan penerbitan tagihan diuji dengan PHP 8.5 serta SQLite untuk test suite.
- Compose divalidasi melalui CLI Compose. Docker daemon pada lingkungan pengerjaan menolak socket (`operation not permitted`), sehingga build image, pull image, MySQL container, dan startup end-to-end Docker belum bisa diuji di sini. Skrip setup menjalankan langkah tersebut di Docker Desktop Anda.
- Tarif, jatuh tempo, kewajiban rumah kosong, hak akses final, dan tahun snapshot tetap perlu dikonfirmasi pengurus sebelum memakai data sebagai pencatatan operasional.
- Belum ada OCR resi, integrasi bank, notifikasi WhatsApp otomatis, atau deployment internet. BRD dan rancangan database tetap tersedia pada `brd.md` dan `database-design.md`.
