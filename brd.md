# BRD — Aplikasi Iuran Pengelolaan Lingkungan (IPL)

**Versi:** 0.2 — Draft untuk review  
**Tanggal:** 19 Oktober 2026  
**Referensi:** [Dashboard Keuangan RT 14 — Villa Rizky Ilhami](https://script.google.com/macros/s/AKfycbwzxrZfp_5nUI-7NYH7fn7htxRqu_fWIYG2OTL5DXvPndEbuRKFRZDO2x7i5bbJ9hGVdw/exec)

## 1. Tujuan bisnis

Menyediakan aplikasi web untuk mendata pembayaran IPL dan menampilkan informasi keuangan lingkungan secara transparan: saldo kas IPL, THR, HUT RI, status pembayaran warga, serta tunggakan. Tampilan mengikuti dashboard referensi, dengan tambahan login pengurus RT dan portal warga untuk unggah bukti pembayaran sesuai permintaan lanjutan.

**Dasar penyusunan:** halaman dan rincian warga diakses pada tanggal di atas. Referensi memperlihatkan dashboard baca saja; formulir pengelolaan dan struktur data sumber tidak terlihat. Login pengurus dan unggah resi oleh warga adalah tambahan kebutuhan pengguna; keduanya tidak berasal dari fitur yang terlihat pada referensi. Rincian hak akses dan mekanisme kas merupakan usulan untuk review.

## 2. Fitur dashboard sesuai referensi

| ID | Kebutuhan bisnis |
| --- | --- |
| BR-01 | Menampilkan identitas perumahan/RT, dua logo, slogan, teks pembuka, jam, tanggal Masehi, dan tanggal Hijriah. |
| BR-02 | Menampilkan tiga kartu saldo: **Total Kas IPL (RT)**, **Total Kas THR**, dan **Total Kas HUT RI**, dalam Rupiah. |
| BR-03 | Menampilkan rekening pembayaran terpisah untuk IPL, THR, dan HUT RI: bank, nomor rekening, dan nama pemilik. |
| BR-04 | Menampilkan rekap IPL dengan kolom **No, Nama Warga, Blok, Januari–Desember**, menggunakan indikator lunas, belum bayar, dan tanda “-”. |
| BR-05 | Menyediakan pencarian berdasarkan nama warga/blok, tombol **Segarkan Data**, dan informasi waktu pembaruan data. |
| BR-06 | Saat warga dipilih, menampilkan modal nama, blok, dan status pembayaran setiap bulan, beserta arahan menghubungi bendahara jika ada ketidaksesuaian. |
| BR-07 | Menampilkan **Peringatan IPL: Tunggakan ≥3 Bulan**, jumlah warga, serta daftar nama, blok, dan jumlah bulan tertunggak. Memilih warga membuka rincian bulanan. |
| BR-08 | Menyediakan **Kontak RT** berisi tautan WhatsApp Ketua RT, Wakil RT, Sekretaris, dan Bendahara. Pengiriman pesan dilakukan pengguna. |
| BR-09 | Menampilkan status pemuatan hingga data siap dan tombol **Buka Laporan** seperti referensi. |
| BR-10 | Menyediakan login pengurus: Ketua RT, Wakil RT, Sekretaris, Bendahara, dan pengurus lainnya dengan hak akses sesuai tugas. |
| BR-11 | Menyediakan login warga untuk melihat tagihan rumah yang diizinkan, mengunggah resi pembayaran, dan memantau hasil verifikasi. |
| BR-12 | Pengurus berizin menyetujui atau menolak bukti pembayaran dengan alasan; pelunasan dan pemasukan kas hanya berlaku setelah persetujuan. |

Rekap bulanan yang terlihat hanya untuk IPL. THR dan HUT RI ditampilkan sebagai saldo serta rekening pembayaran.

## 3. Panel admin dan portal warga

**Pengguna:** pengurus memiliki akun masing-masing dan masuk panel admin Filament. Warga login ke portal warga untuk pengajuan pembayaran. Satu orang dapat menjadi pengurus sekaligus warga. Matriks wewenang per jabatan diusulkan dalam rancangan database dan perlu direview.

| Menu | Data/fungsi minimum |
| --- | --- |
| Warga dan blok | Mendata dan memperbarui nama warga, blok/nomor rumah, serta penanda unit yang tidak dikenai IPL jika disetujui. |
| Tagihan dan pembayaran IPL | Menerbitkan tagihan per rumah/bulan/tahun dan mencatat alokasi pembayaran. Ketentuan lunas mengikuti tarif yang disepakati. |
| Portal warga | Memilih tagihan rumah yang diizinkan, memasukkan tanggal/nominal transfer, mengunggah resi, mengirim pengajuan, dan melihat statusnya. |
| Verifikasi pembayaran | Bendahara/pengurus berizin memeriksa pengajuan dan menyetujui/menolak dengan riwayat keputusan. |
| Akun dan pengurus | Mengelola akun, jabatan, masa tugas, dan izin; akun warga dikaitkan ke data penghuni yang sudah diperiksa admin. |
| Saldo kas | Usulan: saldo awal + pemasukan − pengeluaran per kategori. Pembayaran yang disetujui mencatat pemasukan sekali; perubahan saldo dilakukan melalui catatan kas. Mekanisme ini perlu direview. |
| Informasi dashboard | Memperbarui identitas, logo, rekening per kategori, serta nama/jabatan dan kontak pengurus. |

**Alur utama:** admin mendata warga/rumah dan menerbitkan tagihan → warga login, memilih tagihan, dan mengunggah resi → pengurus memverifikasi → pembayaran disetujui memperbarui pelunasan dan kas → dashboard serta tunggakan diperbarui. Penolakan menampilkan alasan dan warga dapat mengajukan ulang.

## 4. Aturan bisnis

1. Rekap pembayaran terikat pada unit/warga, bulan, dan tahun; pencatatan tidak boleh menggandakan pelunasan periode yang sama.
2. Status lunas, sebagian (jika disepakati), dan belum bayar harus konsisten antara tabel, modal, dan peringatan. Status menunggu verifikasi tidak dianggap lunas.
3. Dari contoh rincian yang diamati, bulan mendatang yang belum dibayar ditampilkan “-” pada modal dan tidak masuk hitungan tunggakan. Tabel utama masih memperlihatkan silang pada bulan tersebut; aplikasi baru diusulkan memakai makna status yang konsisten.
4. Peringatan memuat unit dengan sedikitnya tiga bulan belum dibayar sampai bulan berjalan. Apakah harus berturut-turut masih perlu dikonfirmasi.
5. Referensi menampilkan unit bertanda rumah kosong dalam daftar tunggakan dan tanda “-” pada unit fasilitas. Pengecualian kewajiban IPL perlu ditentukan sebelum implementasi.
6. Saldo setiap kategori dipisahkan. Nominal dan alokasi yang diakui berasal dari pengajuan approved, bukan dari unggahan resi saja.
7. Satu pengajuan dapat membayar beberapa bulan pada satu rumah dan kategori. Pengajuan ditolak tidak memengaruhi kas/tagihan; koreksi pembayaran disetujui memakai pembalikan beralasan dengan riwayat.
8. Warga hanya boleh mengelola rumah yang diizinkan dan melihat bukti pengajuannya sendiri. Bukti transfer disimpan privat; hanya pengaju dan petugas berizin yang dapat membukanya.
9. Verifikator tidak boleh menyetujui pengajuan miliknya sendiri. Pengurus lain harus diberi izin untuk memprosesnya.
10. Usulan batas unggahan: JPG/PNG/PDF, maksimal 5 MB per file dan 3 file per pengajuan. Verifikasi manual; tidak ada integrasi bank.

## 5. Tampilan dan batasan teknologi

- Mengikuti warna hijau tua/emerald, aksen emas, kartu putih bersudut membulat, indikator pembayaran hijau/merah, serta panel tunggakan merah.
- Desktop: tiga kartu saldo, rekening pembayaran, kemudian tabel rekap di kiri dan peringatan di kanan. Pada ponsel, komponen ditumpuk dan tabel dapat digeser.
- Panel pengelolaan memakai **Filament 5.x**, dengan target **PHP 8.5.x** dan **Laravel 13.x**. Gunakan patch stabil terbaru yang kompatibel saat implementasi; versi final dikunci setelah pengecekan dependensi.
- Dashboard menggunakan halaman khusus agar susunannya mengikuti referensi.
- Bahasa Indonesia, format Rupiah, dan zona waktu Asia/Jakarta.
- Perubahan data hanya melalui pengguna berwenang; kegagalan pemuatan harus ditampilkan secara jelas.

## 6. Kriteria penerimaan

1. Ketiga saldo, rekening, rekap 12 bulan, kontak, dan peringatan tersedia sesuai cakupan referensi.
2. Pencarian nama/blok menyaring rekap; memilih warga dari tabel maupun peringatan membuka rincian yang sama.
3. Pengajuan warga muncul sebagai menunggu verifikasi. Persetujuan mencatat pelunasan serta kas satu kali pada periode yang tepat; penolakan tidak mengubah keduanya dan alasannya dapat dilihat warga.
4. Unit dengan dua bulan tertunggak tidak masuk peringatan; unit dengan tiga bulan masuk sesuai aturan yang disetujui.
5. Warga tidak dapat masuk panel admin atau melihat bukti warga lain. Izin pengurus membatasi akses sesuai tugas; tampilan dapat digunakan melalui desktop dan ponsel.
6. Satu resi dapat diajukan untuk beberapa bulan dengan total alokasi sesuai nominal pembayaran; pengajuan tidak boleh melunasi tagihan melebihi sisa.
7. Pergantian penghuni/pengurus menjaga riwayat dan memperbarui akses; koreksi pembayaran berjejak.

## 7. Poin review sebelum pengembangan

- Besaran tarif IPL, ketentuan lunas, pembayaran sebagian, dan pembayaran beberapa bulan sekaligus.
- Tahun data awal, pergantian tahun, serta definisi tunggakan berturut-turut atau akumulasi.
- Kewajiban rumah kosong/fasilitas dan dampak pergantian penghuni.
- Persetujuan usulan buku kas otomatis, saldo awal, sumber data awal, dan kebutuhan pemindahan data dari Google Sheets.
- Dashboard ringkasan terbuka seperti referensi atau dibatasi login; portal unggah pembayaran selalu memerlukan login.
- Matriks izin pengurus, petugas pengganti verifikasi pembayaran bendahara, serta proses aktivasi akun warga.

**Batas lingkup draft:** pendataan, dashboard, login pengurus/warga, unggah resi, dan verifikasi manual sebagaimana di atas. Integrasi pembayaran otomatis, pengingat WhatsApp otomatis, ekspor laporan, dan modul tambahan belum termasuk karena tidak terlihat pada referensi.

**Lampiran rancangan:** database-design.md — tabel, relasi, hak akses, dan alur pembayaran.

**Rujukan teknologi:** [Filament 5](https://filamentphp.com/docs/5.x/introduction/installation), [PHP 8.5](https://www.php.net/releases/8.5/en.php), [Laravel 13](https://laravel.com/framework/docs/13.x/releases).
