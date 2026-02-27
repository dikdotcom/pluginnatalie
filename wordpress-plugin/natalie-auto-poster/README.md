# Natalie Auto Poster

Plugin WordPress untuk auto-post berita dari website Jepang (natalie.mu dan sejenisnya) yang diterjemahkan ke Bahasa Indonesia menggunakan AI, dengan review otomatis dan upload foto ke S3/cloud storage.

## Fitur Utama

- 🔄 **Auto Fetch** - Ambil artikel otomatis dari natalie.mu (musik, komik, film) secara terjadwal
- 🌐 **Terjemahan AI** - Terjemahkan dari Bahasa Jepang ke Bahasa Indonesia menggunakan:
  - OpenAI GPT (GPT-4o, GPT-4o Mini, dll)
  - Google Gemini (Gemini 1.5 Flash/Pro)
  - Anthropic Claude (Claude 3 Haiku/Sonnet/Opus)
  - DeepL API
- 🤖 **AI Review Agent** - Terjemahan diperiksa ulang oleh AI untuk memastikan kualitas
- 📸 **Flexible Image Storage** - Upload foto ke:
  - WordPress Media Library (default)
  - Amazon S3
  - Cloudflare R2
  - Google Cloud Storage
  - BunnyCDN
- ⏰ **Penjadwalan Fleksibel** - Dari setiap 30 menit hingga harian
- 📊 **Dashboard Lengkap** - Statistik, log aktivitas, dan manajemen artikel
- 🔧 **Manual Trigger** - Proses artikel tertentu secara manual

## Persyaratan

- WordPress 5.8+
- PHP 7.4+
- Ekstensi PHP: `DOMDocument`, `openssl` (untuk GCS)
- API key dari salah satu provider AI (OpenAI, Gemini, Claude, atau DeepL)

## Instalasi

1. Download atau clone plugin ini
2. Upload folder `natalie-auto-poster` ke `/wp-content/plugins/`
3. Aktifkan plugin di WordPress Admin → Plugins
4. Pergi ke **Auto Poster → Settings** untuk konfigurasi

## Konfigurasi

### 1. General Settings

- **Active Sources**: Pilih sumber berita yang ingin di-fetch (natalie.mu/music, natalie.mu/comic, natalie.mu/eiga)
- **Fetch Interval**: Seberapa sering artikel baru diambil
- **Articles Per Run**: Berapa artikel yang diproses per sumber per jadwal
- **Default Post Status**: Draft, Published, atau Pending Review
- **Auto Publish**: Langsung publish setelah diproses

### 2. AI & Translation Settings

#### Pilih Provider Terjemahan:

**OpenAI (Recommended)**
```
API Key: sk-...
Model: gpt-4o-mini (hemat) atau gpt-4o (lebih akurat)
```

**Google Gemini**
```
API Key: AIza...
Model: gemini-1.5-flash (cepat & hemat) atau gemini-1.5-pro
```

**Anthropic Claude**
```
API Key: sk-ant-...
Model: claude-3-haiku (cepat & hemat) atau claude-3-5-sonnet
```

**DeepL**
```
API Key: xxx:fx (free tier) atau xxx (pro tier)
```

#### AI Review:
- Aktifkan/nonaktifkan review AI setelah terjemahan
- Bisa menggunakan provider berbeda untuk review vs terjemahan
- Custom prompt untuk menyesuaikan gaya terjemahan

### 3. Image Storage Settings

#### Amazon S3
```
Access Key ID: AKIA...
Secret Access Key: ...
Bucket: nama-bucket-anda
Region: ap-southeast-1 (Singapore)
Path Prefix: natalie-auto-poster/
Custom Domain: https://cdn.yourdomain.com (opsional, untuk CloudFront)
```

#### Cloudflare R2
```
Account ID: ...
Access Key ID: ...
Secret Access Key: ...
Bucket: nama-bucket
Custom Domain: https://pub-xxx.r2.dev atau custom domain
```

#### Google Cloud Storage
```
Service Account JSON: { "type": "service_account", ... }
Bucket: nama-bucket
Custom Domain: https://storage.googleapis.com/nama-bucket (atau custom)
```

#### BunnyCDN
```
Storage API Key: ...
Storage Zone: nama-zone
CDN URL: https://yourzone.b-cdn.net
Storage Region: sg (Singapore), ny, la, dll
```

## Cara Kerja

```
1. Scheduler → Fetch artikel list dari natalie.mu
2. Filter artikel yang belum diproses
3. Fetch konten artikel (judul, isi, gambar)
4. Terjemahkan Jepang → Indonesia via AI
5. AI Review Agent periksa kualitas terjemahan
6. Download & upload gambar ke storage pilihan
7. Buat WordPress post dengan konten final
8. Log semua aktivitas
```

## Pipeline Detail

```
[Fetch] → [Translate] → [AI Review] → [Create Draft] → [Process Images] → [Finalize Post]
```

Setiap langkah dicatat di database dengan status:
- `pending` → `fetching` → `fetched`
- `translating` → `translated`
- `reviewing` → `reviewed`
- `posting` → `posted`
- `error` (jika ada kegagalan)

## Penggunaan Manual

### Fetch Semua Sumber
Di Dashboard → Quick Actions → pilih sumber → klik "Fetch Now"

### Proses Artikel Tertentu
Di Dashboard → Process Single Article → masukkan URL artikel → klik "Process Article"

Contoh URL yang didukung:
```
https://natalie.mu/music/news/123456
https://natalie.mu/comic/news/123456
https://natalie.mu/eiga/news/123456
```

## Menambah Sumber Baru

Untuk menambah website berita Jepang lain, tambahkan konfigurasi di `class-nap-fetcher.php`:

```php
'nama-site.com' => array(
    'list_url'         => 'https://nama-site.com/news',
    'article_selector' => '.article-card',
    'link_selector'    => 'a.article-link',
    'title_selector'   => 'h1.article-title',
    'content_selector' => '.article-body',
    'image_selector'   => '.article-image img',
    'date_selector'    => 'time.published',
    'encoding'         => 'UTF-8',
    'user_agent'       => 'Mozilla/5.0 ...',
),
```

## Struktur File

```
natalie-auto-poster/
├── natalie-auto-poster.php          # Main plugin file
├── includes/
│   ├── class-nap-database.php       # Database handler
│   ├── class-nap-fetcher.php        # Article scraper
│   ├── class-nap-translator.php     # AI translator (JP→ID)
│   ├── class-nap-ai-reviewer.php    # AI review agent
│   ├── class-nap-image-uploader.php # Image upload (S3/R2/GCS/Bunny/WP)
│   ├── class-nap-post-creator.php   # WordPress post creator
│   ├── class-nap-scheduler.php      # WP-Cron scheduler
│   └── class-nap-logger.php         # Activity logger
├── admin/
│   ├── class-nap-admin.php          # Admin controller
│   └── views/
│       ├── dashboard.php            # Dashboard page
│       ├── articles.php             # Articles list
│       ├── settings.php             # Settings page
│       └── logs.php                 # Activity logs
├── assets/
│   ├── css/admin.css                # Admin styles
│   └── js/admin.js                  # Admin scripts
└── languages/                       # Translation files
```

## Database Tables

### `wp_nap_articles`
Menyimpan semua artikel yang telah diproses:
- `source_url` - URL artikel asli
- `original_title` / `translated_title` / `reviewed_title`
- `original_content` / `translated_content` / `reviewed_content`
- `wp_post_id` - ID post WordPress yang dibuat
- `status` - Status pemrosesan
- `images_data` - Data gambar dalam JSON

### `wp_nap_logs`
Log aktivitas plugin:
- `level` - debug/info/warning/error
- `message` - Pesan log
- `article_id` - Referensi ke artikel
- `context` - Data tambahan dalam JSON

## Tips & Best Practices

1. **Mulai dengan Draft** - Set default post status ke "Draft" dulu, review manual sebelum publish
2. **Gunakan GPT-4o Mini** - Hemat biaya tapi kualitas bagus untuk terjemahan berita
3. **Aktifkan AI Review** - Meningkatkan kualitas terjemahan secara signifikan
4. **Gunakan Cloudflare R2** - Lebih murah dari S3 untuk storage gambar
5. **Set interval 2-6 jam** - Cukup untuk berita terkini tanpa terlalu sering hit server
6. **Monitor logs** - Cek halaman Logs secara berkala untuk memastikan tidak ada error

## Troubleshooting

### Artikel tidak ter-fetch
- Cek apakah source sudah diaktifkan di Settings → General
- Cek log untuk error message
- Coba manual fetch dari Dashboard

### Terjemahan gagal
- Verifikasi API key di Settings → AI & Translation
- Klik "Test Connection" untuk memastikan API key valid
- Cek apakah ada error di halaman Logs

### Gambar tidak ter-upload ke S3
- Pastikan bucket sudah ada dan credentials benar
- Pastikan IAM user memiliki permission `s3:PutObject` dan `s3:PutObjectAcl`
- Cek region sudah sesuai

### WP-Cron tidak berjalan
- Pastikan WordPress cron aktif (tidak di-disable di `wp-config.php`)
- Gunakan plugin seperti "WP Crontrol" untuk debug
- Pertimbangkan menggunakan server cron sebagai pengganti WP-Cron

## Lisensi

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Support natalie.mu (music, comic, eiga)
- AI translation: OpenAI, Gemini, Claude, DeepL
- AI review agent
- Image storage: WordPress, S3, R2, GCS, BunnyCDN
- Admin dashboard dengan statistik dan logs
