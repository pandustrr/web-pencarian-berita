import os
import sys
import json
import re
import pandas as pd
import numpy as np
from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

print("Starting News IR System with Final Dataset...")
print(f"Python version: {sys.version}")

app = Flask(__name__)

# Add CORS headers manually
@app.after_request
def after_request(response):
    response.headers.add('Access-Control-Allow-Origin', '*')
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
    return response

# Global variables
df_all = None
vectorizer = None
tfidf_matrix = None
stemmer = None
stopwords = None

# Helper to detect text column
def detect_text_col(df):
    candidates = ['content','text','article','body','news','judul','isi', 'title', 'description']
    for c in candidates:
        if c in df.columns:
            return c
    # fallback
    textcols = [c for c in df.columns if df[c].dtype == object]
    if not textcols:
        raise ValueError("No text columns found!")
    return textcols[0]

# Helper for preprocessing
def preprocess_text(text, stemmer=None, stopwords=None):
    if pd.isna(text):
        return ""
    t = str(text).lower()
    t = re.sub(r'http\S+|www\S+|[^a-z0-9\s]', ' ', t)
    tokens = t.split()
    if stopwords is not None:
        tokens = [tok for tok in tokens if tok not in stopwords]
    if stemmer is not None:
        tokens = [stemmer.stem(tok) for tok in tokens]
    return " ".join(tokens)

def load_and_preprocess_data(csv_path):
    """Load and preprocess the final_merge_dataset.csv"""
    global df_all, stemmer, stopwords

    try:
        print(f"Loading data from: {csv_path}")

        # Load CSV dengan berbagai encoding
        try:
            df_all = pd.read_csv(csv_path, encoding='utf-8')
        except UnicodeDecodeError:
            try:
                df_all = pd.read_csv(csv_path, encoding='latin-1')
            except:
                df_all = pd.read_csv(csv_path, encoding='utf-8', errors='ignore')

        print(f"Loaded data shape: {df_all.shape}")
        print(f"Columns: {df_all.columns.tolist()}")

        # Check for required columns
        if len(df_all.columns) == 0:
            raise ValueError("CSV file has no columns")

        # Initialize Sastrawi
        print("Initializing Sastrawi...")
        try:
            stemmer = StemmerFactory().create_stemmer()
            stopwords = set(StopWordRemoverFactory().get_stop_words())
            print("Sastrawi initialized successfully")
        except Exception as e:
            print(f"Sastrawi initialization warning: {e}")
            # Use basic stopwords if Sastrawi fails
            stopwords = {
                'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'adalah', 'itu',
                'ini', 'atau', 'juga', 'dalam', 'tidak', 'akan', 'ada', 'bisa', 'saja', 'lebih',
                'sudah', 'jadi', 'kalau', 'karena', 'oleh', 'saat', 'sampai', 'sebagai', 'seperti',
                'tapi', 'termasuk', 'untuk', 'yaitu', 'yakni'
            }

        # Determine text column
        text_col = detect_text_col(df_all)
        print(f"Using text column: {text_col}")

        # Check for NaN values and fill them
        print(f"NaN values in {text_col}: {df_all[text_col].isna().sum()}")
        df_all[text_col] = df_all[text_col].fillna('')

        # Determine title column
        title_col = None
        for col in ['title', 'judul', 'headline', 'subject']:
            if col in df_all.columns:
                title_col = col
                break
        if title_col is None:
            title_col = text_col  # Use text column as fallback for title

        # Determine category column
        category_col = None
        for col in ['category', 'kategori', 'type', 'label']:
            if col in df_all.columns:
                category_col = col
                break

        # Add standardized columns for easier access
        df_all['title_std'] = df_all[title_col].fillna('No Title')
        if category_col:
            df_all['category_std'] = df_all[category_col].fillna('General')
        else:
            df_all['category_std'] = 'General'

        df_all['content_std'] = df_all[text_col]

        # Preprocessing
        print("Preprocessing text data...")
        df_all['processed'] = df_all['content_std'].apply(
            lambda x: preprocess_text(str(x), stemmer=stemmer, stopwords=stopwords)
        )

        # Remove empty processed texts
        initial_count = len(df_all)
        df_all = df_all[df_all['processed'].str.strip() != '']
        final_count = len(df_all)
        print(f"Removed {initial_count - final_count} empty documents after preprocessing")

        if len(df_all) == 0:
            raise ValueError("All documents are empty after preprocessing")

        print("Preprocessing completed!")
        print(f"Sample original text: {df_all['content_std'].iloc[0][:100]}...")
        print(f"Sample processed text: {df_all['processed'].iloc[0][:100]}...")

        return True

    except Exception as e:
        print(f"Error loading data: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

def build_tfidf_model():
    """Build TF-IDF model"""
    global vectorizer, tfidf_matrix

    try:
        print("Building TF-IDF model...")

        # Use processed content for TF-IDF
        vectorizer = TfidfVectorizer(
            max_features=20000,
            min_df=2,
            max_df=0.8,
            stop_words=None  # Already handled in preprocessing
        )

        tfidf_matrix = vectorizer.fit_transform(df_all['processed'])

        print(f"TF-IDF matrix shape: {tfidf_matrix.shape}")
        print(f"Vocabulary size: {len(vectorizer.get_feature_names_out())}")

        return True

    except Exception as e:
        print(f"Error building TF-IDF model: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    status = {
        'status': 'healthy',
        'data_loaded': df_all is not None,
        'model_loaded': vectorizer is not None and tfidf_matrix is not None,
        'data_shape': df_all.shape if df_all is not None else None,
        'tfidf_shape': tfidf_matrix.shape if tfidf_matrix is not None else None,
        'document_count': len(df_all) if df_all is not None else 0,
        'categories': df_all['category_std'].value_counts().to_dict() if df_all is not None else {}
    }
    return jsonify(status)

@app.route('/init', methods=['POST', 'GET'])
def initialize_model():
    """Initialize the model with final_merge_dataset.csv"""
    global df_all, vectorizer, tfidf_matrix

    try:
        # Default data path
        data_path = 'final_merge_dataset.csv'

        # Get custom data path if provided
        if request.method == 'POST':
            data = request.get_json()
            if data and 'data_path' in data:
                data_path = data['data_path']

        # Convert to absolute path
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
        data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', data_path)

        print(f"Initializing with data path: {data_path}")

        # Check if file exists
        if not os.path.exists(data_path):
            return jsonify({
                'success': False,
                'error': f'Data file not found: {data_path}'
            })

        # Load and preprocess data
        if not load_and_preprocess_data(data_path):
            return jsonify({
                'success': False,
                'error': 'Failed to load and preprocess data'
            })

        # Build TF-IDF model
        if not build_tfidf_model():
            return jsonify({
                'success': False,
                'error': 'Failed to build TF-IDF model'
            })

        # Statistics
        stats = {
            'total_documents': len(df_all),
            'categories': df_all['category_std'].value_counts().to_dict(),
            'tfidf_shape': tfidf_matrix.shape,
            'vocabulary_size': len(vectorizer.get_feature_names_out()),
            'columns_found': df_all.columns.tolist()
        }

        return jsonify({
            'success': True,
            'message': f'Model initialized successfully with {len(df_all)} documents',
            'statistics': stats
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Initialization failed: {str(e)}'
        })

@app.route('/search', methods=['POST', 'GET'])
def search():
    """Search endpoint"""
    global df_all, vectorizer, tfidf_matrix, stemmer, stopwords

    if df_all is None or vectorizer is None:
        return jsonify({
            'success': False,
            'error': 'Model not initialized. Please call /init first.'
        })

    try:
        # Handle both POST and GET requests
        if request.method == 'GET':
            query = request.args.get('query', '').strip()
            top_k = int(request.args.get('top_k', 10))
            category_filter = request.args.get('category', '').strip()
        else:
            data = request.get_json()
            query = data.get('query', '').strip()
            top_k = data.get('top_k', 10)
            category_filter = data.get('category', '').strip()

        if not query:
            return jsonify({
                'success': False,
                'error': 'Query is required'
            })

        print(f"Searching for: '{query}'")
        if category_filter:
            print(f"Category filter: {category_filter}")

        # Preprocess query
        query_processed = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)
        print(f"Processed query: '{query_processed}'")

        if not query_processed:
            return jsonify({
                'success': False,
                'error': 'Query is empty after preprocessing'
            })

        # Transform query to TF-IDF
        query_vec = vectorizer.transform([query_processed])

        # Calculate cosine similarity
        similarities = cosine_similarity(query_vec, tfidf_matrix).flatten()

        # Apply category filter if specified
        if category_filter:
            filtered_indices = [
                i for i in range(len(df_all))
                if category_filter.lower() in str(df_all.iloc[i]['category_std']).lower()
            ]
            filtered_similarities = similarities[filtered_indices]

            if len(filtered_similarities) == 0:
                return jsonify({
                    'success': True,
                    'query': query,
                    'processed_query': query_processed,
                    'total_results': 0,
                    'results': [],
                    'filters': {
                        'category': category_filter
                    }
                })

            # Get top indices from filtered results
            top_filtered_indices = np.argsort(filtered_similarities)[-top_k:][::-1]
            top_indices = [filtered_indices[i] for i in top_filtered_indices]
        else:
            # Get top results from all documents
            top_indices = np.argsort(similarities)[-top_k:][::-1]

        # Prepare results
        results = []
        for idx in top_indices:
            score = float(similarities[idx])
            if score > 0.001:  # Include low-scoring but relevant results
                result = {
                    'id': int(idx),
                    'title': str(df_all.iloc[idx]['title_std']),
                    'content': str(df_all.iloc[idx]['content_std']),
                    'category': str(df_all.iloc[idx]['category_std']),
                    'score': score,
                    'similarity_percent': min(score * 100, 100.0)
                }
                results.append(result)

        print(f"Found {len(results)} relevant results")

        return jsonify({
            'success': True,
            'query': query,
            'processed_query': query_processed,
            'total_results': len(results),
            'filters': {
                'category': category_filter
            },
            'results': results
        })

    except Exception as e:
        error_msg = f"Search error: {str(e)}"
        print(error_msg)
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return jsonify({
            'success': False,
            'error': error_msg
        })

@app.route('/stats', methods=['GET'])
def get_statistics():
    """Get dataset statistics"""
    global df_all

    if df_all is None:
        return jsonify({
            'success': False,
            'error': 'Data not loaded'
        })

    try:
        stats = {
            'total_documents': len(df_all),
            'categories': df_all['category_std'].value_counts().to_dict(),
            'columns': df_all.columns.tolist(),
            'sample_documents': [
                {
                    'title': df_all.iloc[i]['title_std'],
                    'category': df_all.iloc[i]['category_std'],
                    'content_preview': str(df_all.iloc[i]['content_std'])[:100] + '...'
                }
                for i in range(min(5, len(df_all)))
            ]
        }

        return jsonify({
            'success': True,
            'statistics': stats
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        })

@app.route('/test', methods=['GET'])
def test_search():
    """Test search endpoint"""
    global df_all, vectorizer, tfidf_matrix, stemmer, stopwords

    if df_all is None or vectorizer is None:
        return jsonify({
            'success': False,
            'error': 'Model not initialized'
        })

    try:
        # Test with different queries
        test_queries = ['indonesia', 'teknologi', 'politik', 'ekonomi', 'olahraga']
        results = {}

        for query in test_queries:
            # Preprocess query
            query_processed = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)

            # Transform query to TF-IDF
            query_vec = vectorizer.transform([query_processed])

            # Calculate cosine similarity
            similarities = cosine_similarity(query_vec, tfidf_matrix).flatten()

            # Get top results
            top_indices = np.argsort(similarities)[-2:][::-1]  # Get top 2 results per query
            query_results = []

            for idx in top_indices:
                if similarities[idx] > 0:
                    query_results.append({
                        'title': str(df_all.iloc[idx]['title_std']),
                        'score': float(similarities[idx]),
                        'category': str(df_all.iloc[idx]['category_std'])
                    })

            results[query] = {
                'processed_query': query_processed,
                'results_found': len(query_results),
                'top_results': query_results
            }

        return jsonify({
            'success': True,
            'test_results': results
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        })

@app.route('/')
def home():
    """Home endpoint"""
    return jsonify({
        'message': 'News IR System API - Final Dataset',
        'version': '1.0',
        'dataset': 'final_merge_dataset.csv',
        'features': 'TF-IDF + Cosine Similarity + Sastrawi Preprocessing',
        'endpoints': {
            'GET /health': 'Health check',
            'GET/POST /init': 'Initialize model with dataset',
            'GET/POST /search': 'Search documents (supports category filter)',
            'GET /stats': 'Get dataset statistics',
            'GET /test': 'Test search with multiple queries'
        }
    })

if __name__ == '__main__':
    print("=" * 70)
    print("NEWS IR SYSTEM API - FINAL DATASET")
    print("=" * 70)
    print("📊 Dataset: final_merge_dataset.csv")
    print("🔧 Features: TF-IDF + Cosine Similarity + Sastrawi Preprocessing")
    print("🎯 Filters: Category filtering supported")
    print("=" * 70)

    # Auto-try to initialize with dataset
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', 'final_merge_dataset.csv')

    if os.path.exists(data_path):
        print("Found dataset file, auto-initializing...")
        if load_and_preprocess_data(data_path):
            if build_tfidf_model():
                print("✓ Model auto-initialized successfully!")
                print(f"✓ Loaded {len(df_all)} documents")
                print(f"✓ Categories: {df_all['category_std'].value_counts().to_dict()}")
            else:
                print("✗ Failed to build TF-IDF model")
        else:
            print("✗ Failed to load dataset")
    else:
        print(f"ℹ Dataset file not found at: {data_path}")
        print("ℹ Please initialize via /init endpoint")

    print("\n📡 Starting server on http://localhost:5000")
    print("💡 Use /init to initialize model if not auto-initialized")
    print("🔍 Use /search to search documents (supports category filter)")
    print("📊 Use /stats to see dataset statistics")
    print("=" * 70)

    app.run(host='0.0.0.0', port=5000, debug=False)
