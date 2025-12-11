# 📰 Struktur Project Web-Berita

## 📋 Ringkasan Project

**Web-Berita** adalah aplikasi web pencarian berita berbasis TF-IDF yang mengintegrasikan **Laravel** (backend PHP) dengan **Python** (search engine) untuk memberikan pengalaman pencarian berita yang cepat dan akurat.

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                      FRONT END (Blade)                       │
│  - Homepage dengan statistik sistem                          │
│  - Search Interface                                          │
│  - News Detail View                                          │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│              BACKEND API (Laravel 11)                        │
│  - SearchController (routes & logic)                         │
│  - SystemController (stats & import)                         │
│  - PythonSearchService (komunikasi dengan Python)           │
│  - Models (News, User)                                       │
└────────────────┬────────────────────────────────────────────┘
                 │ HTTP Request
                 │ (Port 5000)
┌────────────────▼────────────────────────────────────────────┐
│            PYTHON SEARCH ENGINE (Flask)                      │
│  - TF-IDF Vectorizer                                         │
│  - Cosine Similarity Search                                  │
│  - CSV Data Processing                                       │
│  - REST API Endpoints                                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Struktur Direktori Lengkap

### **Root Level Files**
```
artisan                      # Laravel CLI tool
composer.json               # PHP dependencies manager
package.json                # Node.js dependencies
phpunit.xml                 # Testing configuration
tailwind.config.js          # Tailwind CSS config
vite.config.js              # Vite bundler config
postcss.config.js           # PostCSS configuration
preprocessed_news.csv       # Backup CSV (original)
README.md                   # Default Laravel README
```

### **1️⃣ `/app` - Application Core (Backend Logic)**

#### **Controllers** (`app/Http/Controllers/`)
- **`SearchController.php`** (256 lines)
  - `index()` - Menampilkan homepage dengan statistik sistem
  - `search()` - Memproses query pencarian
  - `show()` - Menampilkan detail berita
  - `debug()` - Debug mode untuk testing
  - `getSystemStats()` - Mengambil stats sistem
  - Komunikasi dengan Python API via HTTP

- **`SystemController.php`**
  - System information endpoints
  - CSV import functionality
  - Database initialization

- **`Controller.php`**
  - Base controller class

#### **Models** (`app/Models/`)
- **`News.php`** - News model dengan fields:
  - `title` - Judul berita
  - `original_text` - Isi teks berita
  - `category` - Kategori berita
  - `source` - Sumber berita

- **`User.php`** - User authentication model

#### **Services** (`app/Services/`)
- **`PythonSearchService.php`** (88 lines)
  - `__construct()` - Inisialisasi dengan base URL Python API
  - `healthCheck()` - Verifikasi koneksi ke Python server (timeout 1.5s)
  - `search()` - Mengirim query ke Python engine dengan timeout 30s
  - Error handling & logging
  - Variabel env: `PYTHON_API_URL` (default: `http://127.0.0.1:5000`)

#### **Providers** (`app/Providers/`)
- **`AppServiceProvider.php`** - Service provider untuk aplikasi

---

### **2️⃣ `/python_app` - Python Search Engine**

#### **Core File**
- **`tfidf_server.py`** (425 lines) - Single-file Python Flask server
  ```
  ├── Configuration
  │   ├── CSV_PATH: python_app/preprocessed_news.csv
  │   ├── HOST: 127.0.0.1
  │   └── PORT: 5000
  │
  ├── TFIDFSearchEngine Class
  │   ├── __init__() - Inisialisasi dengan CSV path
  │   ├── initialize() - Load data & fit TF-IDF vectorizer
  │   └── search() - Cosine similarity search
  │
  ├── Flask Routes
  │   ├── GET /health - Health check endpoint
  │   ├── GET /stats - Statistics endpoint
  │   ├── GET /search - Search endpoint dengan parameter 'query' & 'top_k'
  │   └── GET /rebuild - Rebuild search engine
  │
  └── Logging & Error Handling
  ```

#### **Data Files**
- **`preprocessed_news.csv`** - Dataset berita terpreproses
  - Format: CSV dengan kolom (title, original_text, category, source)
  - Loading di startup untuk fast search

- **`requirements.txt`** - Python dependencies:
  ```
  pandas==2.0.3
  scikit-learn==1.3.0
  numpy==1.24.3
  flask==2.3.3
  waitress==2.1.2
  ```

---

### **3️⃣ `/routes` - URL Routing**

**`routes/web.php`** - Main routing configuration:
```php
GET  /                    → SearchController@index (Homepage)
GET  /search              → SearchController@search (Search results)
GET  /news/{id}           → SearchController@show (News detail)
GET  /debug               → SearchController@debug (Debug page)
GET  /system-info         → SystemController@systemInfo
GET  /import-csv          → SystemController@importCSV
GET  /test-python         → Test Python connection
GET  /test-search-all     → Test search dengan semua results
```

---

### **4️⃣ `/resources` - Frontend Assets**

#### **Views** (`resources/views/`)
```
resources/views/
├── welcome.blade.php      # Default Laravel welcome page
├── search/                # Search related views
│   ├── index.blade.php    # Search homepage
│   └── results.blade.php  # Search results page
├── layouts/               # Layout templates
│   ├── app.blade.php      # Main app layout
│   └── header.blade.php   # Header component
└── debug/                 # Debug pages
    ├── stats.blade.php    # System statistics
    └── connection.blade.php # Connection status
```

#### **Styles** (`resources/css/`)
- **`app.css`** - Application stylesheet
  - Tailwind CSS directives
  - Custom styles

#### **Scripts** (`resources/js/`)
- **`app.js`** - Main JavaScript file
- **`bootstrap.js`** - Bootstrap script
  - Axios HTTP client setup
  - Global event listeners

---

### **5️⃣ `/database` - Database Configuration**

#### **Migrations** (`database/migrations/`)
- **`0001_01_01_000000_create_users_table.php`** - Users table
- **`0001_01_01_000001_create_cache_table.php`** - Cache table
- **`0001_01_01_000002_create_jobs_table.php`** - Jobs table
- **`2025_11_23_092723_create_news_table.php`** - News table
  ```php
  // Columns:
  - id (primary key)
  - title (string)
  - original_text (text)
  - category (string)
  - source (string)
  - created_at, updated_at
  ```

#### **Seeders** (`database/seeders/`)
- **`DatabaseSeeder.php`** - Seed database dengan data awal

#### **Factories** (`database/factories/`)
- **`UserFactory.php`** - Factory untuk generate test users

---

### **6️⃣ `/config` - Configuration Files**

```
config/
├── app.php           # Konfigurasi aplikasi (app name, timezone, etc)
├── auth.php          # Authentication configuration
├── cache.php         # Cache backend configuration
├── database.php      # Database connection configuration
├── filesystems.php   # File storage configuration
├── logging.php       # Logging configuration
├── mail.php          # Mail configuration
├── queue.php         # Queue job configuration
├── services.php      # External services configuration
└── session.php       # Session configuration
```

---

### **7️⃣ `/storage` - Application Storage**

```
storage/
├── app/
│   ├── public/       # Publicly accessible files
│   ├── private/      # Private files
│   └── python_data/  # Python engine temporary data
├── framework/
│   ├── cache/        # Application cache
│   ├── sessions/     # Session files
│   ├── testing/      # Testing temporary files
│   └── views/        # Compiled Blade templates
└── logs/             # Application logs
```

---

### **8️⃣ `/public` - Public Web Root**

```
public/
├── index.php         # Entry point aplikasi (index/request handler)
├── robots.txt        # SEO robots configuration
├── storage/          # Symbolic link ke storage/app/public
└── build/
    ├── manifest.json # Vite manifest
    └── assets/       # Compiled assets (CSS, JS)
```

---

### **9️⃣ `/tests` - Unit & Feature Tests**

```
tests/
├── TestCase.php          # Base test case class
├── Unit/
│   └── ExampleTest.php   # Unit test example
└── Feature/
    └── ExampleTest.php   # Feature test example
```

Jalankan tests dengan:
```bash
php artisan test
# atau
./vendor/bin/phpunit
```

---

### **🔟 `/bootstrap` - Framework Bootstrap**

```
bootstrap/
├── app.php          # Application container setup
└── providers.php    # Service providers loading
```

---

## 🔄 Data Flow Diagram

### **Pencarian Berita**
```
1. User mengetik query di homepage
   ↓
2. Frontend mengirim GET /search?q=gempa ke SearchController
   ↓
3. SearchController menerima request
   ↓
4. SearchController call PythonSearchService::search('gempa')
   ↓
5. PythonSearchService send HTTP GET ke Python server:
   GET http://127.0.0.1:5000/search?query=gempa&top_k=10
   ↓
6. Python Flask server:
   - Load CSV ke memory
   - Vectorize dengan TF-IDF
   - Compute cosine similarity
   - Return TOP 10 results
   ↓
7. Laravel format hasil sebagai JSON
   ↓
8. Frontend render results ke blade template
   ↓
9. User melihat hasil pencarian
```

---

## 🛠️ Technology Stack

### **Backend**
| Technology | Version | Purpose |
|-----------|---------|---------|
| Laravel | 11.31 | Web framework |
| PHP | ^8.2 | Backend language |
| SQLite/MySQL | - | Database |
| Tailwind CSS | 3.4.18 | Frontend styling |
| Vite | 6.0.11 | Asset bundler |

### **Frontend**
| Technology | Version | Purpose |
|-----------|---------|---------|
| Blade Templates | - | Server-side templates |
| Tailwind CSS | 3.4.18 | Styling |
| Axios | 1.7.4 | HTTP client |
| PostCSS | 8.4.47 | CSS processing |

### **Python Search Engine**
| Library | Version | Purpose |
|---------|---------|---------|
| Flask | 2.3.3 | Web framework |
| Pandas | 2.0.3 | Data processing |
| Scikit-learn | 1.3.0 | TF-IDF & similarity |
| NumPy | 1.24.3 | Numerical computing |
| Waitress | 2.1.2 | WSGI server |

---

## ⚙️ Konfigurasi Penting

### **Environment Variables (.env)**
```bash
# Database
DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

# Python API
PYTHON_API_URL=http://127.0.0.1:5000

# Application
APP_NAME=Web-Berita
APP_URL=http://localhost
APP_DEBUG=true
```

---

## 🚀 Cara Menjalankan Project

### **1. Setup Backend (Laravel)**
```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate

# Build assets
npm install
npm run build

# Run Laravel
php artisan serve
# Akses: http://localhost:8000
```

### **2. Setup & Run Python Search Engine**
```bash
# Go to python app
cd python_app

# Install dependencies
pip install -r requirements.txt

# Run server
python tfidf_server.py
# Server berjalan di: http://127.0.0.1:5000
```

### **3. Verify Connection**
```bash
# Test endpoint
curl http://localhost:8000/test-python

# Expected response:
{
  "python_connected": true,
  "status": "connected",
  "response": { "status": "ok" }
}
```

---

## 📊 Key Features

✅ **TF-IDF Search** - Pencarian berbasis text similarity  
✅ **Real-time Health Check** - Monitor Python server status  
✅ **System Statistics** - Tampil total dokumen, vocabulary size  
✅ **CSV Import** - Import data berita dari CSV  
✅ **Beautiful UI** - Styled dengan Tailwind CSS  
✅ **Debug Mode** - Endpoint untuk testing  
✅ **Responsive Design** - Mobile-friendly interface  

---

## 📝 API Endpoints

### **Laravel Routes**
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Homepage dengan stats |
| GET | `/search?q=...` | Search results |
| GET | `/news/{id}` | News detail |
| GET | `/debug` | Debug page |
| GET | `/system-info` | System information |
| GET | `/test-python` | Test Python connection |

### **Python API Routes**
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/health` | Health check |
| GET | `/stats` | Engine statistics |
| GET | `/search?query=...&top_k=10` | TF-IDF search |
| GET | `/rebuild` | Rebuild search engine |

---

## 📝 File Dependencies

```
SearchController.php
├── PythonSearchService.php    (service untuk Python API)
├── News.php                   (model)
└── routes/web.php            (routing)

tfidf_server.py
├── preprocessed_news.csv      (data source)
├── requirements.txt           (dependencies)
└── Flask                      (framework)

resources/views/
├── search/index.blade.php    (main search UI)
├── search/results.blade.php  (results display)
└── layouts/app.blade.php     (master layout)
```

---

## 🔧 Development Notes

1. **CSV Path**: Python mencari CSV di `python_app/preprocessed_news.csv`
2. **Timeout Settings**:
   - Health check: 1.5 detik
   - Search: 30 detik
3. **Port Configuration**: 
   - Laravel: `http://localhost:8000`
   - Python: `http://127.0.0.1:5000`
4. **Logging**: Check `storage/logs/laravel.log` untuk debug

---

## 📌 Checklist Setup

- [ ] Clone repository
- [ ] Run `composer install`
- [ ] Run `npm install`
- [ ] Copy `.env.example` ke `.env`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Setup database: `php artisan migrate`
- [ ] Build frontend: `npm run build`
- [ ] Install Python dependencies: `pip install -r python_app/requirements.txt`
- [ ] Verifikasi CSV exists di `python_app/preprocessed_news.csv`
- [ ] Run Python server: `python python_app/tfidf_server.py`
- [ ] Run Laravel server: `php artisan serve`
- [ ] Test connection: `curl http://localhost:8000/test-python`

---

## 📚 Useful Commands

```bash
# Laravel
php artisan migrate              # Run migrations
php artisan tinker              # Interactive shell
php artisan cache:clear         # Clear cache
php artisan route:list          # List all routes
php artisan test                # Run tests

# NPM/Frontend
npm run dev                      # Development build
npm run build                    # Production build

# Python
python python_app/tfidf_server.py  # Run search engine
```

---

**Last Updated**: December 10, 2025  
**Project Type**: Laravel + Python Integration  
**Status**: Active Development
