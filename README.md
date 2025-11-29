WEB-BERITA/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php              # Base controller Laravel
│   │       ├── SearchController.php        # Controller utama fitur pencarian
│   │       └── SystemController.php        # Controller untuk system & debug
│   └── Models/
│       └── News.php                        # Model untuk data berita
│
├── python_app/
│   ├── tfidf_server.py                     # Server Python (TF-IDF + Cosine Similarity)
│   └── preprocessed_news.csv               # Dataset berita yang sudah dipreprocessing
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php               # Layout utama
│       ├── search/
│       │   ├── index.blade.php             # Homepage (search box)
│       │   ├── results.blade.php           # Halaman hasil pencarian
│       │   └── detail.blade.php            # Halaman detail berita
│       └── debug/
│           └── info.blade.php              # Halaman informasi debug
│
├── routes/
│   └── web.php                             # Semua route aplikasi
│
└── .env                                    
