# SPK TK Pertiwi — Sistem Pendukung Keputusan Penilaian Perkembangan Anak

Sistem berbasis web untuk membantu penilaian perkembangan anak Taman Kanak-Kanak (TK) Pertiwi menggunakan metode **Simple Additive Weighting (SAW)**.

## 🌟 Fitur Utama

- **Dashboard** — Statistik ringkasan data anak, kelas, kriteria, dan penilaian
- **Manajemen Data Anak** — Input, edit, dan hapus data siswa
- **Manajemen Kelas** — Kelola data kelas
- **Manajemen Kriteria** — Kelola kriteria penilaian (C1–C6) beserta bobot
- **Input Penilaian** — Input nilai BSB/BSH/MB/BB per anak per bulan
- **Proses SAW** — Hitung normalisasi dan nilai terbobot secara otomatis
- **Hasil & Ranking** — Lihat hasil perankingan perkembangan anak
- **Rekap Nilai** — Rekap nilai bulanan semua anak
- **Laporan** — Laporan perkembangan anak

## 🔐 Sistem Login

| Role | Username | Password | Akses |
|---|---|---|---|
| Admin | `admin` | `admin` | Full akses |
| Guru | `guru1` | `guru123` | Input penilaian, lihat hasil |
| Kepala Sekolah | `kepala1` | *(lihat SQL)* | Lihat hasil & laporan |

## 🛠️ Tech Stack

- **Backend**: PHP 8+ (native, tanpa framework)
- **Database**: MySQL (via PDO)
- **Frontend**: Bootstrap 5.3 + FontAwesome 6.5 + Custom CSS
- **Metode SPK**: Simple Additive Weighting (SAW)
- **Keamanan**: CSRF token, bcrypt password, prepared statements

## 📋 Aspek Penilaian (SAW)

| Kode | Aspek | Bobot |
|---|---|---|
| C1 | Nilai Agama dan Moral | 15% |
| C2 | Fisik Motorik | 15% |
| C3 | Kognitif | 20% |
| C4 | Bahasa | 15% |
| C5 | Sosial Emosional | 20% |
| C6 | Seni | 15% |

## ⚙️ Skala Nilai

| Nilai | Keterangan |
|---|---|
| 4 (BSB) | Berkembang Sangat Baik |
| 3 (BSH) | Berkembang Sesuai Harapan |
| 2 (MB) | Mulai Berkembang |
| 1 (BB) | Belum Berkembang |

## 🚀 Instalasi Lokal (XAMPP)

1. Clone repo ke `htdocs/tk_pertiwi_spk`
2. Buka phpMyAdmin, import `database.sql`
3. Akses: `http://localhost/tk_pertiwi_spk`

## 🚂 Deploy ke Railway

### Environment Variables yang Diperlukan

| Variable | Keterangan |
|---|---|
| `DB_HOST` | Host MySQL Railway |
| `DB_PORT` | Port MySQL Railway |
| `DB_NAME` | Nama database |
| `DB_USER` | Username MySQL |
| `DB_PASS` | Password MySQL |
| `BASE_URL` | `""` (kosong, karena berjalan di root) |
| `APP_ENV` | `production` |

### Langkah Deploy

1. Fork/push repo ini ke GitHub
2. Buka [railway.app](https://railway.app) → New Project → Deploy from GitHub
3. Tambah plugin **MySQL** di Railway
4. Set environment variables di atas
5. Import `database.sql` ke MySQL Railway

## 📁 Struktur Project

```
tk_pertiwi_spk/
├── config/
│   ├── env.php          # Konfigurasi environment (BASE_URL, APP_ENV)
│   ├── koneksi.php      # Koneksi database (baca dari env vars)
│   ├── auth.php         # Autentikasi & CSRF
│   └── functions.php    # Helper functions
├── layout/
│   ├── header.php       # Layout header + sidebar
│   └── footer.php       # Layout footer
├── assets/              # CSS themes
├── anak/                # Modul data anak
├── kelas/               # Modul data kelas
├── kriteria/            # Modul kriteria
├── penilaian/           # Modul input penilaian
├── saw/                 # Modul proses & hasil SAW
├── rekap/               # Modul rekap nilai
├── laporan/             # Modul laporan
├── index.php            # Dashboard
├── login.php            # Halaman login
├── logout.php           # Logout handler
├── database.sql         # Skema database + data awal
├── nixpacks.toml        # Konfigurasi build Railway
└── Procfile             # Start command Railway
```
