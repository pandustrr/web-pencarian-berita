WEB-BERITA/
├── 📁 app/
│   ├── 📁 Http/
│   │   └── 📁 Controllers/
│   │       ├── 📄 Controller.php          # Base controller
│   │       ├── 📄 SearchController.php    # MAIN CONTROLLER
│   │       └── 📄 SystemController.php    # System utilities
│   └── 📁 Models/
│       └── 📄 News.php                    
├── 📁 python_app/
│   ├── 📄 tfidf_server.py                 # PYTHON TF-IDF SERVER
│   └── 📄 preprocessed_news.csv           # DATASET
├── 📁 resources/
│   └── 📁 views/
│       ├── 📁 layouts/
│       │   └── 📄 app.blade.php           # MAIN LAYOUT
│       ├── 📁 search/
│       │   ├── 📄 index.blade.php         # HOMEPAGE
│       │   ├── 📄 results.blade.php       # SEARCH RESULTS
│       │   └── 📄 detail.blade.php        # DETAIL
│       └── 📁 debug/
│           └── 📄 info.blade.php          # DEBUG INFO
├── 📄 routes/
│   └── 📄 web.php                         
└── 📄 .env                                
