# B. Perancangan Sistem (BISA BERUBAH)

### 1. Pengguna Sistem (User & Hak Akses)

#### 1. Admin

Admin memiliki hak akses penuh terhadap sistem, dengan kemampuan:

* Login ke sistem
* Mengelola data piring (RFID, jenis piring, harga)
* Mengelola data karyawan
* Melihat seluruh transaksi pembayaran
* Melihat laporan penjualan
* Mengelola konfigurasi payment gateway Midtrans

#### 2. Karyawan (Kasir)

Karyawan bertugas dalam operasional harian, dengan akses:

* Login ke sistem
* Melakukan proses scan piring menggunakan RFID
* Melihat total harga otomatis
* Memproses pembayaran pelanggan
* Melihat riwayat transaksi miliknya

---

### 2. Fitur Sistem

Fitur utama yang dirancang dalam sistem ini meliputi:

1. Autentikasi pengguna (Admin & Karyawan)
2. Manajemen data piring berbasis RFID
3. Pembacaan RFID untuk menghitung jumlah piring
4. Perhitungan total harga otomatis
5. Manajemen transaksi
6. Pembayaran digital menggunakan Midtrans
7. Riwayat dan laporan transaksi
8. Logout sistem

---

### 3. Alur Sistem Tiap Fitur

#### a. Alur Login

1. User membuka halaman login
2. User memasukkan email dan password
3. Sistem memverifikasi kredensial
4. User diarahkan ke dashboard sesuai role

---

#### b. Alur Scan Piring Menggunakan RFID

1. Karyawan mengaktifkan mode scan
2. Piring ditempelkan ke RFID reader
3. Reader mengirim UID RFID ke backend Laravel
4. Sistem mencocokkan UID dengan data piring
5. Harga piring ditambahkan ke transaksi aktif
6. Sistem menampilkan daftar piring dan total harga

---

#### c. Alur Perhitungan Total Harga

1. Setiap UID RFID yang valid masuk ke keranjang transaksi
2. Sistem menjumlahkan harga seluruh piring
3. Total harga ditampilkan secara real-time

---

#### d. Alur Pembayaran (Midtrans)

1. Karyawan memilih metode pembayaran
2. Sistem mengirim data transaksi ke Midtrans API
3. Midtrans mengembalikan token pembayaran
4. Pelanggan melakukan pembayaran
5. Midtrans mengirim notifikasi status pembayaran
6. Sistem memperbarui status transaksi menjadi **paid**

---

#### e. Alur Laporan Transaksi (Admin)

1. Admin membuka menu laporan
2. Sistem menampilkan daftar transaksi
3. Admin dapat memfilter berdasarkan tanggal atau karyawan

---

### 4. Perancangan Database (Rencana Tabel)

#### 1. users

| Field      | Tipe                  |
| ---------- | --------------------- |
| id         | bigint                |
| name       | varchar               |
| email      | varchar               |
| password   | varchar               |
| role       | enum(admin, karyawan) |
| timestamps |                       |

---

#### 2. plates (piring)

| Field       | Tipe                  |
| ----------- | --------------------- |
| id          | bigint                |
| item_id     | bigint                |
| rfid_uid    | varchar               |
| nama_piring | varchar               |
| harga       | integer               |
| status      | enum(aktif, nonaktif) |
| timestamps  |                       |

---

#### 3. transactions

| Field        | Tipe                        |
| ------------ | --------------------------- |
| id           | bigint                      |
| user_id      | bigint                      |
| total_harga  | integer                     |
| status       | enum(pending, paid, failed) |
| payment_type | varchar                     |
| timestamps   |                             |

---

#### 4. transaction_details

| Field          | Tipe    |
| -------------- | ------- |
| id             | bigint  |
| transaction_id | bigint  |
| plate_id       | bigint  |
| harga          | integer |
| timestamps     |         |

---

#### 5. payments

| Field             | Tipe    |
| ----------------- | ------- |
| id                | bigint  |
| transaction_id    | bigint  |
| midtrans_order_id | varchar |
| snap_token        | varchar |
| payment_status    | varchar |
| timestamps        |         |

---

### 6. items (makanan & minuman)
| Field      | Tipe                   |
| ---------- | ---------------------- |
| id         | bigint                 |
| nama_item  | varchar                |
| kategori   | enum(makanan, minuman) |
| harga      | integer                |
| status     | enum(aktif, nonaktif)  |
| timestamps |                        |

---

### 5. Perancangan Sistem Lainnya

#### a. Arsitektur Sistem

* **Frontend**: Blade Template (Laravel 12)
* **Backend**: Laravel 12 (REST API)
* **Database**: MySQL
* **Perangkat**: RFID Reader + RFID Tag
* **Payment Gateway**: Midtrans Snap

---

#### b. Keamanan Sistem

* Hash password menggunakan bcrypt
* Middleware role-based access
* Validasi webhook Midtrans
* CSRF protection Laravel

---

#### c. Diagram yang Bisa Dicantumkan (Opsional untuk Laporan)

* Use Case Diagram
* Activity Diagram proses scan RFID
* ERD database
* Sequence Diagram pembayaran Midtrans

---
