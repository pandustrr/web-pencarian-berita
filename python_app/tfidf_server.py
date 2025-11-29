#!/usr/bin/env python3
"""
SINGLE FILE Python TF-IDF Search Engine Server
Integrasi Laravel dengan Python untuk Pencarian Berita
"""

import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from flask import Flask, request, jsonify
import os
import logging
import sys
from waitress import serve

# ================================
# 🎯 CONFIGURATION - CSV di ROOT python_app
# ================================
# Dapatkan path direktori saat ini (python_app)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_PATH = os.path.join(BASE_DIR, 'preprocessed_news.csv')  # ← CSV di root python_app

HOST = '127.0.0.1'
PORT = 5000

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler(sys.stdout)]
)
logger = logging.getLogger(__name__)

# ================================
# 🧠 TF-IDF SEARCH ENGINE CLASS
# ================================
class TFIDFSearchEngine:
    def __init__(self, csv_path: str):
        self.csv_path = csv_path
        self.df = None
        self.vectorizer = None
        self.tfidf_matrix = None
        self.is_initialized = False

    def initialize(self):
        """Initialize the search engine with CSV data"""
        try:
            logger.info(f"📂 Loading CSV from: {self.csv_path}")

            if not os.path.exists(self.csv_path):
                raise FileNotFoundError(f"CSV file not found: {self.csv_path}")

            # Load CSV data
            self.df = pd.read_csv(self.csv_path)
            logger.info(f"✅ CSV loaded successfully. Shape: {self.df.shape}")

            # Tampilkan sample data untuk debugging
            logger.info(f"📊 Sample data - Columns: {self.df.columns.tolist()}")
            if len(self.df) > 0:
                sample_text = self.df.iloc[0]['text'] if 'text' in self.df.columns else self.df.iloc[0].get('processed', 'No text')
                logger.info(f"📝 First record sample: {str(sample_text)[:100]}...")

            # Validasi kolom
            if 'processed' not in self.df.columns:
                logger.warning("Column 'processed' not found, using 'text' instead")
                self.df['processed'] = self.df['text'].fillna('')
            else:
                self.df['processed'] = self.df['processed'].fillna(self.df['text'] if 'text' in self.df.columns else '')

            # Handle missing values
            self.df['processed'] = self.df['processed'].fillna('')
            self.df = self.df[self.df['processed'].str.strip() != '']

            logger.info(f"🧹 Data cleaned. Final shape: {self.df.shape}")

            # Initialize TF-IDF Vectorizer
            self.vectorizer = TfidfVectorizer(
                max_features=10000,
                min_df=2,
                max_df=0.8,
                ngram_range=(1, 2),
                stop_words=None  # We handle stopwords in preprocessing
            )

            # Fit TF-IDF
            self.tfidf_matrix = self.vectorizer.fit_transform(self.df['processed'])

            logger.info(f"🎯 TF-IDF initialized. Vocabulary size: {len(self.vectorizer.vocabulary_)}")
            logger.info(f"📈 TF-IDF matrix shape: {self.tfidf_matrix.shape}")

            self.is_initialized = True
            return True

        except Exception as e:
            logger.error(f"❌ Failed to initialize search engine: {str(e)}")
            self.is_initialized = False
            return False

    def search(self, query: str, top_k: int = 10):
        """Search using TF-IDF and Cosine Similarity - TANPA BATASAN"""
        if not self.is_initialized:
            logger.error("Search engine not initialized")
            return []

        try:
            if not query or not query.strip():
                return []

            # Preprocess query
            query_processed = query.lower().strip()

            # Transform query to TF-IDF vector
            query_vec = self.vectorizer.transform([query_processed])

            # Calculate cosine similarity
            similarities = cosine_similarity(query_vec, self.tfidf_matrix).flatten()

            # Get top K results - TANPA BATASAN, gunakan semua yang ada similarity > 0
            if top_k == 'all' or top_k <= 0:
                # Ambil semua hasil dengan similarity > 0.001
                top_indices = np.where(similarities > 0.001)[0]
                # Urutkan berdasarkan score tertinggi
                top_indices = top_indices[np.argsort(similarities[top_indices])[::-1]]
            else:
                # Ambil top K results
                top_indices = similarities.argsort()[-top_k:][::-1]

            results = []
            for idx in top_indices:
                if similarities[idx] > 0.001:  # Minimum similarity threshold
                    # Get document data
                    doc_data = self.df.iloc[idx]

                    results.append({
                        'index': int(idx),
                        'score': float(similarities[idx]),
                        'similarity_percentage': round(similarities[idx] * 100, 2),
                        'original_text': str(doc_data['text']) if 'text' in doc_data else '',
                        'translated_text': str(doc_data['translated']) if 'translated' in doc_data else '',
                        'processed_text': str(doc_data['processed']),
                        'category': str(doc_data['category']) if 'category' in doc_data else 'General',
                        'source': str(doc_data['source']) if 'source' in doc_data else 'CSV'
                    })

            logger.info(f"🔍 Search: '{query}' → {len(results)} results (top_k: {top_k})")
            return results

        except Exception as e:
            logger.error(f"❌ Search error: {str(e)}")
            return []

    def get_document(self, doc_id: int):
        """Get document by ID"""
        if not self.is_initialized or doc_id >= len(self.df):
            return None

        try:
            doc_data = self.df.iloc[doc_id]
            return {
                'id': doc_id,
                'original_text': str(doc_data['text']) if 'text' in doc_data else '',
                'translated_text': str(doc_data['translated']) if 'translated' in doc_data else '',
                'processed_text': str(doc_data['processed']),
                'category': str(doc_data['category']) if 'category' in doc_data else 'General',
                'source': str(doc_data['source']) if 'source' in doc_data else 'CSV'
            }
        except Exception as e:
            logger.error(f"Error getting document {doc_id}: {str(e)}")
            return None

    def get_stats(self):
        """Get search engine statistics"""
        if not self.is_initialized:
            return {'status': 'not_initialized'}

        return {
            'status': 'initialized',
            'total_documents': len(self.df),
            'vocabulary_size': len(self.vectorizer.vocabulary_),
            'matrix_shape': self.tfidf_matrix.shape,
            'csv_path': self.csv_path,
            'columns': self.df.columns.tolist(),
            'sample_records': min(5, len(self.df))
        }

# ================================
# 🌐 FLASK APP & ROUTES
# ================================
app = Flask(__name__)

# Global search engine instance
search_engine = TFIDFSearchEngine(CSV_PATH)

@app.route('/')
def home():
    """Home page dengan info endpoints"""
    return jsonify({
        'message': 'Python TF-IDF Search Engine API',
        'endpoints': {
            'GET /health': 'Health check',
            'GET /stats': 'Engine statistics',
            'GET /search?query=text&top_k=10': 'Search documents',
            'GET /document/<id>': 'Get document by ID',
            'GET /test?query=text': 'Test search',
            'POST /init': 'Manual initialization'
        },
        'status': 'running',
        'csv_location': CSV_PATH
    })

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy' if search_engine.is_initialized else 'unhealthy',
        'engine_initialized': search_engine.is_initialized,
        'service': 'python_tfidf_search',
        'csv_file_exists': os.path.exists(CSV_PATH),
        'csv_location': CSV_PATH,
        'timestamp': pd.Timestamp.now().isoformat()
    })

@app.route('/stats', methods=['GET'])
def get_stats():
    """Get search engine statistics"""
    try:
        stats = search_engine.get_stats()
        return jsonify({
            'success': True,
            'stats': stats
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/search', methods=['GET', 'POST'])
def search():
    """Search endpoint - MAIN API - SUPPORT ALL RESULTS"""
    try:
        # Check if engine is initialized
        if not search_engine.is_initialized:
            return jsonify({'error': 'Search engine not initialized'}), 500

        # Get parameters
        if request.method == 'POST':
            data = request.get_json()
            query = data.get('query', '')
            top_k = data.get('top_k', 10)
        else:
            query = request.args.get('query', '')
            top_k = request.args.get('top_k', 10)

        if not query:
            return jsonify({'error': 'Query parameter is required'}), 400

        # Handle 'all' parameter
        if top_k == 'all':
            top_k = 'all'  # Kirim string 'all' ke search engine
        else:
            try:
                top_k = int(top_k)
                # Batasi maksimal 1000 untuk performance
                top_k = min(top_k, 1000)
            except (ValueError, TypeError):
                top_k = 10

        # Perform search
        results = search_engine.search(query, top_k)

        return jsonify({
            'success': True,
            'query': query,
            'top_k': top_k,
            'results_count': len(results),
            'results': results,
            'engine': 'python_tfidf',
            'timestamp': pd.Timestamp.now().isoformat()
        })

    except Exception as e:
        logger.error(f"Search API error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/document/<int:doc_id>', methods=['GET'])
def get_document(doc_id):
    """Get document by ID"""
    try:
        if not search_engine.is_initialized:
            return jsonify({'error': 'Search engine not initialized'}), 500

        document = search_engine.get_document(doc_id)
        if document:
            return jsonify({
                'success': True,
                'document': document
            })
        else:
            return jsonify({'error': 'Document not found'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/test', methods=['GET'])
def test_search():
    """Test search dengan sample query"""
    try:
        if not search_engine.is_initialized:
            return jsonify({'error': 'Search engine not initialized'}), 500

        query = request.args.get('query', 'gempa')
        results = search_engine.search(query, 5)

        return jsonify({
            'success': True,
            'query': query,
            'results_count': len(results),
            'sample_results': [
                {
                    'index': r['index'],
                    'score': r['score'],
                    'similarity_percentage': r['similarity_percentage'],
                    'preview_text': r['original_text'][:150] + '...' if len(r['original_text']) > 150 else r['original_text']
                }
                for r in results[:3]  # Hanya ambil 3 sample
            ]
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/init', methods=['POST', 'GET'])
def manual_init():
    """Manual initialization endpoint"""
    try:
        success = search_engine.initialize()
        if success:
            return jsonify({
                'success': True,
                'message': 'Search engine initialized successfully'
            })
        else:
            return jsonify({
                'success': False,
                'message': 'Failed to initialize search engine'
            }), 500
    except Exception as e:
        return jsonify({'error': str(e)}), 500

# ================================
# 🚀 STARTUP & MAIN
# ================================
def check_dependencies():
    """Check if required packages are installed"""
    try:
        import pandas
        import sklearn
        import flask
        import waitress
        logger.info("✅ All dependencies are installed")
        return True
    except ImportError as e:
        logger.error(f"❌ Missing dependency: {e}")
        return False

def main():
    """Main function to start the server"""
    print("🚀" + "="*60)
    print("   Python TF-IDF Search Engine Server")
    print("="*60)

    # Display current directory and CSV path
    print(f"📁 Current directory: {BASE_DIR}")
    print(f"📊 CSV file location: {CSV_PATH}")
    print("="*60)

    # Check dependencies
    if not check_dependencies():
        print("❌ Please install dependencies:")
        print("   pip install pandas scikit-learn flask waitress")
        return

    # Check CSV file
    if not os.path.exists(CSV_PATH):
        print(f"❌ CSV file not found: {CSV_PATH}")
        print("Please make sure preprocessed_news.csv is in the same folder as this script")
        print("Current files in directory:")
        for file in os.listdir(BASE_DIR):
            print(f"   - {file}")
        return

    print(f"✅ CSV file found: {CSV_PATH}")

    # Initialize search engine
    print("🔧 Initializing TF-IDF Search Engine...")
    success = search_engine.initialize()

    if not success:
        print("❌ Failed to initialize search engine. Check the CSV file format.")
        return

    print("✅ TF-IDF Search Engine initialized successfully!")
    print("🌐 Starting server...")
    print("="*60)

    # Display endpoints info
    print("\n📍 AVAILABLE ENDPOINTS:")
    print("   GET  /              - Server info")
    print("   GET  /health        - Health check")
    print("   GET  /stats         - Engine statistics")
    print("   GET  /search        - Search (query, top_k)")
    print("   GET  /document/<id> - Get document by ID")
    print("   GET  /test          - Test search")
    print("   POST /init          - Manual re-initialize")
    print("\n🔍 EXAMPLE USAGE:")
    print(f"   curl http://{HOST}:{PORT}/health")
    print(f'   curl "http://{HOST}:{PORT}/search?query=gempa&top_k=all"')
    print(f"   curl http://{HOST}:{PORT}/document/0")
    print("="*60)
    print("Server is running. Press Ctrl+C to stop.")
    print("="*60)

    # Start production server
    serve(app, host=HOST, port=PORT)

if __name__ == '__main__':
    main()
