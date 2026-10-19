# Rancangan Database — Aplikasi IPL

**Versi:** 0.1 — Draft untuk review  
**Tanggal:** 19 Oktober 2026  
**Acuan:** BRD v0.2 dan tambahan kebutuhan login pengurus RT serta unggah bukti pembayaran oleh warga.

## 1. Keputusan rancangan

- **Database usulan: MySQL**, menggunakan transaksi database dan foreign key. Versi server dipilih saat implementasi.
- Satu lingkungan/RT untuk tahap awal; kategori dana tetap **IPL, THR, HUT RI** sesuai dashboard referensi.
- Satu tabel akun untuk semua pengguna. Pengurus memakai panel admin Filament; warga memakai portal warga setelah login.
- Nama warga, rumah, dan akun dipisahkan: warga bisa belum memiliki akun; seorang pengurus juga bisa menjadi warga.
- Satu pembayaran dapat melunasi beberapa bulan pada satu rumah dan satu kategori dana.
- Unggahan resi bukan pelunasan otomatis. Hanya pembayaran **approved** yang dihitung pada pelunasan dan kas.
- Ini rancangan untuk aplikasi baru; struktur internal Google Sheets tidak diketahui.

## 2. Login dan hak akses — usulan awal

Setiap orang mempunyai akun sendiri, bukan akun bersama berdasarkan jabatan. Login memakai username dan password; email opsional untuk pemulihan akun. Akun dibuat/diaktifkan admin dan dikaitkan dengan warga yang benar. Pengguna tidak boleh memilih sendiri role atau mengklaim rumah hanya dengan mengetik nomor blok.

| Role | Akses panel admin | Wewenang usulan |
| --- | --- | --- |
| super_admin | Ya | Mengelola akun, role, izin, dan konfigurasi; role teknis, bukan jabatan RT. |
| ketua_rt | Ya | Melihat seluruh rekap keuangan, mengelola susunan pengurus dan akun melalui izin khusus. |
| wakil_rt | Ya | Melihat seluruh rekap dan data pengurus; tugas operasional tambahan diberikan per izin. |
| sekretaris | Ya | Mengelola warga, rumah, hubungan penghuni, dan informasi pengurus. Keuangan baca saja. |
| bendahara | Ya | Mengelola tarif/tagihan, memverifikasi pembayaran, mencatat kas, dan melakukan pembatalan terkontrol. |
| pengurus | Ya | Melihat dashboard admin; akses lain diberikan sesuai tugas. |
| warga | Tidak | Masuk portal warga; melihat tagihan rumah yang diizinkan, mengunggah resi, dan melihat hasil verifikasi. |

Satu akun boleh memiliki beberapa role. Jabatan disimpan beserta masa tugas; pergantian pengurus memperbarui hak akses dalam proses yang sama. Login admin tidak berarti semua menu boleh diubah. Verifikator dilarang menyetujui pembayaran yang ia ajukan sendiri; pembayaran tersebut dialihkan ke pengguna lain yang diberi izin verifikasi.

## 3. Kamus tabel

**Konvensi:** kecuali tabel bawaan yang memiliki struktur sendiri, gunakan `id BIGINT UNSIGNED` sebagai PK dan `created_at/updated_at`. FK bertipe sama dengan PK. Semua nominal memakai `DECIMAL(18,2)`, bukan float. `?` berarti nullable. Kolom penting berikut belum merupakan migration final.

### A. Akun dan susunan pengurus

| Tabel | Kolom penting | Fungsi/aturan |
| --- | --- | --- |
| users | resident_id?, name, username, email?, password, is_active, last_login_at?, remember_token? | Username unik; resident_id unik jika terisi; email unik jika dipakai. Password berupa hash. |
| rt_positions | code, name, is_active | Code unik. Ketua, Wakil, Sekretaris, Bendahara, dan jabatan lain dapat ditambahkan. |
| committee_members | user_id, rt_position_id, start_date, end_date?, public_phone?, show_in_contact | Riwayat masa tugas dan sumber kontak pengurus pada dashboard. Tidak boleh ada penugasan identik yang bertumpang tindih. |

Gunakan **Spatie Laravel Permission** dengan lima tabel bawaannya:

| Tabel | Relasi/kolom utama |
| --- | --- |
| roles | id, name, guard_name; unik(name, guard_name). |
| permissions | id, name, guard_name; unik(name, guard_name). |
| model_has_roles | role_id, model_type, model_id; penghubung User–Role. |
| role_has_permissions | role_id, permission_id; penghubung Role–Permission. |
| model_has_permissions | permission_id, model_type, model_id; izin langsung jika diperlukan. |

Ikuti migration resmi paket, termasuk indeks gabungannya; jangan menambahkan kolom `roles` pada `users`. Contoh izin: `residents.manage`, `houses.manage`, `bills.manage`, `payments.review`, `payments.reverse`, `cash.manage`, `committee.manage`, `users.manage`, `roles.manage`. Jabatan tidak memberikan akses secara otomatis tanpa penetapan izin.

### B. Warga dan rumah

| Tabel | Kolom penting | Fungsi/aturan |
| --- | --- | --- |
| residents | name, phone?, is_active | Profil warga. Tidak memerlukan NIK/KK untuk kebutuhan awal. |
| houses | code, occupancy_status, is_ipl_subject, billing_start_period?, billing_end_period? | Code unik, misalnya E12A/2. occupancy_status: occupied/vacant/facility. Status kosong tidak otomatis membebaskan IPL. |
| house_residents | house_id, resident_id, relation_type, start_date, end_date?, is_primary_contact, can_manage_payments | Riwayat pemilik/penghuni dan rumah yang boleh dikelola akun warga. Akhiri relasi lama saat pindah; jangan menimpa sejarah. |

`house_residents` mendukung beberapa warga per rumah dan seorang warga terkait beberapa rumah. Validasi satu kontak utama aktif per rumah dan cegah relasi aktif identik yang bertumpang tindih. Akses rumah hanya untuk relasi aktif yang diizinkan; setelah pindah, warga tetap dapat melihat pengajuan miliknya sendiri, bukan data penghuni berikutnya.

### C. Kategori dana dan tagihan

| Tabel | Kolom penting | Fungsi/aturan |
| --- | --- | --- |
| fund_categories | code, name, is_active | Seed IPL, THR, HUT_RI; code unik. |
| bank_accounts | fund_category_id, bank_name, account_number, account_holder, is_active, display_order | Sumber informasi rekening per kategori; nomor rekening berupa teks. Unik(bank_name, account_number). |
| dues_rates | fund_category_id, amount, effective_from, effective_to?, created_by | Tarif per bulan/kategori; hindari periode berlaku tumpang tindih untuk kategori yang sama. |
| bills | house_id, fund_category_id, dues_rate_id?, responsible_residency_id?, period, due_date, amount, state, void_reason?, created_by | period berupa DATE hari pertama bulan. Unik(house_id, fund_category_id, period). state: issued/void. |

`responsible_residency_id` menunjuk `house_residents` saat tagihan diterbitkan dan harus berasal dari rumah yang sama. Nominal tagihan disalin saat terbit, sehingga perubahan tarif tidak mengubah tagihan lama. Tagihan berbayar tidak boleh diubah nominalnya atau dibatalkan sebelum pembayaran dibalik.

Untuk tahap awal, penerbitan tagihan rutin difokuskan pada **IPL**. Tarif/tagihan THR dan HUT RI disiapkan oleh skema tetapi diaktifkan setelah aturan kewajibannya disetujui; tidak diasumsikan bulanan seperti IPL.

### D. Pengajuan pembayaran dan resi

| Tabel | Kolom penting | Fungsi/aturan |
| --- | --- | --- |
| payments | reference_no, house_id, fund_category_id, bank_account_id?, submitted_by, paid_at, amount, method, transfer_reference?, payer_name?, notes?, status, submitted_at?, resubmission_of_id? | reference_no unik; method: transfer/cash. status: draft/submitted/approved/rejected/reversed. Satu pengajuan untuk satu rumah/kategori. |
| payment_allocations | payment_id, bill_id, amount | Pembagian pembayaran ke tagihan, termasuk beberapa bulan. Unik(payment_id, bill_id). |
| payment_proofs | payment_id, uploaded_by, disk, path, original_name, mime_type, size_bytes, sha256 | Satu pengajuan dapat memiliki beberapa resi. Simpan file privat; DB menyimpan metadata dan lokasi berkas. |
| payment_reviews | payment_id, reviewer_id, action, reason?, created_at | Riwayat keputusan append-only: approve/reject/reverse. Alasan wajib untuk penolakan/pembalikan. |

Resi transfer **wajib sebelum submit**. Usulan format JPG/PNG/PDF, maksimal **5 MB per file**, maksimum **3 file per pengajuan**; dapat diubah setelah review. Validasi tipe konten dan ukuran di server. Nilai `sha256` diindeks sebagai petunjuk resi duplikat, bukan jaminan transfer sah atau larangan mutlak menggunakan ulang file.

Riwayat tidak ditimpa: pengajuan ditolak dapat dibuatkan pengajuan baru melalui `resubmission_of_id`. Pengajuan submitted tidak boleh diedit; draft boleh diubah pemiliknya. Bukti baru diverifikasi manual melalui rekening tujuan dan detail transfer; tidak ada OCR atau integrasi bank pada tahap ini.

### E. Kas, pengaturan, dan jejak perubahan

| Tabel | Kolom penting | Fungsi/aturan |
| --- | --- | --- |
| cash_entries | fund_category_id, payment_id?, reversal_of_id?, entry_type, amount, entry_date, description, created_by | entry_type: opening/income/expense. Nominal positif. payment_id unik bila terisi; reversal_of_id unik bila terisi dan FK ke cash_entries. |
| app_settings | key, value, updated_by | Key unik. Identitas, logo, teks pembuka, ambang tunggakan default 3, dan pengaturan tampilan. |
| activity_logs | actor_id?, action, entity_type, entity_id, changes?, created_at | Jejak perubahan master, pengajuan, dan konfigurasi. Tidak menyimpan password atau isi resi. |

**Usulan penghitungan saldo:** saldo awal + pemasukan − pengeluaran per kategori. Persetujuan pembayaran membuat tepat satu kas income dengan `payment_id`; pembalikan membuat satu kas expense dengan `reversal_of_id` yang menunjuk pemasukan semula. Jangan mengubah/menghapus kas lama atau sekaligus menginputnya sebagai pemasukan manual.

Kolom saldo terpisah pada kategori tidak diperlukan karena dapat dihitung dari buku kas. Ini usulan mekanisme saldo yang perlu Anda review, bukan perilaku sumber Google Sheets yang sudah dipastikan.

Tambahkan tabel infrastruktur sesuai konfigurasi Laravel: `password_reset_tokens`, `sessions`, `jobs`, `failed_jobs`, `job_batches`, `cache`, dan `cache_locks`. Tabel tersebut bukan modul bisnis baru.

## 4. Relasi inti

| Induk | Hubungan |
| --- | --- |
| residents | 1–0..1 users; 1–banyak house_residents. |
| houses | 1–banyak house_residents, bills, payments. |
| users | banyak–banyak roles; 1–banyak committee_members dan pengajuan/review pembayaran. |
| fund_categories | 1–banyak bank_accounts, dues_rates, bills, payments, cash_entries. |
| payments | 1–banyak payment_proofs, payment_reviews, payment_allocations; 1–0..1 pemasukan cash_entries. |
| bills | 1–banyak payment_allocations; satu pembayaran bisa tersebar ke beberapa bills. |
| cash_entries | 1–0..1 entri pembalikan melalui reversal_of_id. |

## 5. Alur warga dan verifikasi

1. Warga login, memilih rumah yang diizinkan, lalu memilih tagihan.
2. Warga memasukkan tanggal dan nominal transfer, memilih rekening tujuan, mengunggah resi, lalu mengirim pengajuan.
3. Sistem memvalidasi file dan nominal/alokasi; status menjadi **submitted / Menunggu Verifikasi**.
4. Bendahara/petugas berizin memeriksa resi dan penerimaan dana.
5. **Setujui:** simpan review, ubah status, dan catat pemasukan kas dalam satu transaksi database. **Tolak:** simpan alasan; tagihan dan kas tidak berubah.
6. Warga melihat hasilnya. Resi hanya dapat dilihat pengaju serta petugas yang berizin.
7. Kesalahan pada pembayaran approved ditangani dengan **reverse**, alasan, review, dan kas pembalik; tagihan dihitung ulang.

## 6. Aturan integritas dan perhitungan

- Total alokasi harus sama dengan nominal pembayaran. Semua tagihan alokasi harus berada pada rumah dan kategori yang sama dengan pengajuan; rekening tujuan harus cocok dengan kategori.
- Usulan tahap awal: tidak ada saldo titipan/kelebihan bayar. Alokasi tidak boleh melampaui sisa tagihan. Pembayaran sebagian didukung struktur, tetapi penggunaannya menunggu keputusan Anda.
- Saat approve/reverse, kunci baris pembayaran dan tagihan terkait, periksa ulang status dan sisa tagihan, lalu simpan semuanya secara atomik. Dua petugas tidak boleh mengesahkan pengajuan yang sama dua kali.
- **Terbayar tagihan** = jumlah alokasi dari payments berstatus approved. Submitted/rejected/reversed tidak dihitung.
- **Sisa tagihan** = amount − terbayar. Tampilan lunas jika sisa nol; sebagian jika terbayar >0 dan sisa >0; belum bayar jika belum ada pembayaran disetujui.
- **Usulan tunggakan:** jumlah periode IPL dengan sisa >0 yang telah jatuh tempo. Peringatan muncul jika jumlahnya ≥3; bulan mendatang belum jatuh tempo tidak dihitung. Tanggal jatuh tempo dan akumulasi/berturut-turut perlu disetujui.
- Dashboard tahunan membaca bills dan agregasi alokasi, bukan kolom fisik jan/feb/.../des pada rumah.
- Saat reverse, pemasukan asli dan entri pembalik tetap masuk perhitungan saldo dan saling meniadakan; jangan hanya menyaring kas berdasarkan status pembayaran saat ini.
- Riwayat keuangan tidak dihapus permanen; akun/master yang pernah dipakai dinonaktifkan. FK keuangan memakai RESTRICT.
- Indeks penting: bills(period, state), payments(status, submitted_at), payments(house_id, created_at), payment_allocations(bill_id), cash_entries(fund_category_id, entry_date), house_residents(resident_id, end_date).
- Setiap akses tagihan, unduh resi, dan pengajuan divalidasi di server berdasarkan kepemilikan/izin, bukan hanya menu yang disembunyikan. Role `warga` tidak membuka panel admin.
- Resi dan metadata transfer tidak ditampilkan pada dashboard umum. Akses baca dashboard umum tetap mengikuti keputusan review BRD.

## 7. Contoh singkat

**Ilustrasi, bukan tarif final:** tarif IPL Rp100.000/bulan. Warga memilih Januari dan Februari, memasukkan transfer Rp200.000, dan mengunggah satu resi.

Sistem menyimpan 1 payments, 2 payment_allocations @Rp100.000, dan 1 payment_proofs. Sebelum disetujui, kedua tagihan masih belum lunas. Setelah disetujui, terbentuk 1 payment_reviews dan 1 kas income Rp200.000; kedua tagihan menjadi lunas. Jika dibalik, terbentuk review reverse dan kas expense Rp200.000; tagihan kembali belum lunas.

## 8. Keputusan yang masih perlu review

1. Tarif IPL, jatuh tempo, pembayaran sebagian, dan aturan rumah kosong/fasilitas.
2. Matriks izin pengurus, terutama petugas pengganti untuk pembayaran milik bendahara.
3. Penerapan buku kas yang menghitung saldo otomatis dan sumber saldo awal.
4. Proses aktivasi akun warga dan rumah yang ditanggung saat penghuni berganti.
5. Rekap umum tanpa login atau dibatasi warga yang login.

## Rujukan teknis

- [Filament — Users](https://filamentphp.com/docs/5.x/users/overview)
- [Spatie — Roles dan Permissions](https://spatie.be/docs/laravel-permission/v8/basic-usage/role-permissions)
- [Laravel — Penyimpanan file](https://laravel.com/framework/docs/filesystem)
- [Laravel — Validasi unggahan](https://laravel.com/framework/docs/validation)

Dokumen ini adalah rancangan untuk review; belum membuat database atau aplikasi.
