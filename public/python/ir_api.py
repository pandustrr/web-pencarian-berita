import os
import sys
import json
import re
import pandas as pd
import numpy as np
from flask import Flask, request, jsonify
from flask_cors import CORS
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

print("Starting News IR System API...")
print(f"Python version: {sys.version}")

app = Flask(__name__)
CORS(app)

# Global variables
df_all = None
vectorizer = None
tfidf_matrix = None
stemmer = None
stopwords = None

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
    """Load and preprocess the CSV data"""
    global df_all, stemmer, stopwords

    try:
        print(f"Loading data from: {csv_path}")

        # Load CSV dengan handling error yang lebih baik
        try:
            df_all = pd.read_csv(csv_path, encoding='utf-8')
        except UnicodeDecodeError:
            # Coba encoding lain jika UTF-8 gagal
            df_all = pd.read_csv(csv_path, encoding='latin-1')

        print(f"Loaded data shape: {df_all.shape}")
        print(f"Columns: {df_all.columns.tolist()}")

        # Initialize Sastrawi
        print("Initializing Sastrawi...")
        stemmer = StemmerFactory().create_stemmer()
        stopwords = set(StopWordRemoverFactory().get_stop_words())
        print("Sastrawi initialized successfully")

        # Determine text column - prioritaskan 'content' atau 'title'
        text_col = None
        for col in ['content', 'title', 'text', 'article', 'body', 'news', 'judul', 'isi', 'translated']:
            if col in df_all.columns:
                text_col = col
                print(f"Found text column: {text_col}")
                break

        if text_col is None:
            # Use first string column
            for col in df_all.columns:
                if df_all[col].dtype == 'object':
                    text_col = col
                    print(f"Using first string column: {text_col}")
                    break

        if text_col is None:
            raise ValueError("No suitable text column found")

        print(f"Using text column: {text_col}")

        # Check for NaN values and fill them
        print(f"NaN values in {text_col}: {df_all[text_col].isna().sum()}")
        df_all[text_col] = df_all[text_col].fillna('')

        # Preprocessing
        print("Preprocessing text data...")
        df_all['processed'] = df_all[text_col].apply(
            lambda x: preprocess_text(x, stemmer=stemmer, stopwords=stopwords)
        )

        print("Preprocessing completed!")
        print(f"Sample original text: {df_all[text_col].iloc[0][:100]}...")
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

        vectorizer = TfidfVectorizer(max_features=30000)
        tfidf_matrix = vectorizer.fit_transform(df_all['processed'])

        print(f"TF-IDF matrix shape: {tfidf_matrix.shape}")
        print(f"Vocabulary size: {len(vectorizer.get_feature_names_out())}")

        return True

    except Exception as e:
        print(f"Error building TF-IDF model: {e}")
        return False

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    status = {
        'status': 'healthy',
        'data_loaded': df_all is not None,
        'model_loaded': vectorizer is not None and tfidf_matrix is not None,
        'data_shape': df_all.shape if df_all is not None else None,
        'tfidf_shape': tfidf_matrix.shape if tfidf_matrix is not None else None
    }
    return jsonify(status)

@app.route('/init', methods=['POST', 'GET'])
def initialize_model():
    """Initialize the model with data"""
    global df_all, vectorizer, tfidf_matrix

    try:
        # Default data path
        data_path = 'preprocessed_news.csv'

        # Get custom data path if provided
        if request.method == 'POST':
            data = request.get_json()
            if data and 'data_path' in data:
                data_path = data['data_path']

        # Convert to absolute path
        if not os.path.isabs(data_path):
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

        return jsonify({
            'success': True,
            'message': f'Model initialized successfully with {len(df_all)} documents',
            'data_shape': df_all.shape,
            'tfidf_shape': tfidf_matrix.shape,
            'vocabulary_size': len(vectorizer.get_feature_names_out())
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
        else:
            data = request.get_json()
            query = data.get('query', '').strip()
            top_k = data.get('top_k', 10)

        if not query:
            return jsonify({
                'success': False,
                'error': 'Query is required'
            })

        print(f"Searching for: '{query}'")

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

        # Get top results
        top_indices = similarities.argsort()[-top_k:][::-1]

        # Prepare results
        results = []
        for idx in top_indices:
            if similarities[idx] > 0:  # Only include relevant results
                # Get the original text - prioritaskan 'content', lalu 'title'
                original_content = ""
                original_title = ""

                if 'content' in df_all.columns:
                    original_content = str(df_all.iloc[idx]['content'])
                if 'title' in df_all.columns:
                    original_title = str(df_all.iloc[idx]['title'])

                # Use content if available, otherwise use title
                display_text = original_content if original_content and original_content != 'nan' else original_title

                # If both are empty, use first string column
                if not display_text or display_text == 'nan':
                    for col in df_all.columns:
                        if df_all[col].dtype == 'object':
                            display_text = str(df_all.iloc[idx][col])
                            break

                # Clean up the text
                if display_text and display_text != 'nan':
                    results.append({
                        'id': int(idx),
                        'title': original_title[:100] + '...' if original_title and len(original_title) > 100 else original_title,
                        'content': display_text,
                        'category': str(df_all.iloc[idx]['category']) if 'category' in df_all.columns else 'General',
                        'score': float(similarities[idx]),
                        'similarity_percent': float(similarities[idx] * 100)
                    })

        print(f"Found {len(results)} relevant results")

        return jsonify({
            'success': True,
            'query': query,
            'processed_query': query_processed,
            'total_results': len(results),
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


@app.route('/test', methods=['GET'])
def test_search():
    """Test search endpoint"""
    global df_all, vectorizer, tfidf_matrix

    if df_all is None or vectorizer is None:
        return jsonify({
            'success': False,
            'error': 'Model not initialized'
        })

    try:
        query = "indonesia"
        top_k = 5

        # Preprocess query
        query_processed = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)

        # Transform query to TF-IDF
        query_vec = vectorizer.transform([query_processed])

        # Calculate cosine similarity
        similarities = cosine_similarity(query_vec, tfidf_matrix).flatten()

        # Get top results
        top_indices = similarities.argsort()[-top_k:][::-1]

        # Prepare results
        results = []
        for idx in top_indices:
            if similarities[idx] > 0:
                original_text = str(df_all.iloc[idx]['text']) if 'text' in df_all.columns else str(df_all.iloc[idx].iloc[0])
                results.append({
                    'id': int(idx),
                    'title': original_text[:100] + '...',
                    'content': original_text,
                    'score': float(similarities[idx]),
                    'similarity_percent': float(similarities[idx] * 100)
                })

        return jsonify({
            'success': True,
            'query': query,
            'processed_query': query_processed,
            'total_results': len(results),
            'results': results
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
        'message': 'News Information Retrieval System API',
        'version': '1.0',
        'endpoints': {
            'GET /health': 'Health check',
            'GET/POST /init': 'Initialize model with data',
            'POST /search': 'Search documents (requires JSON: {"query": "search terms", "top_k": 10})',
            'GET /test': 'Test search with sample query'
        }
    })

if __name__ == '__main__':
    print("=" * 60)
    print("NEWS IR SYSTEM API - TF-IDF + Cosine Similarity")
    print("=" * 60)

    # Auto-try to load data if preprocessed_news.csv exists
    data_path = os.path.join(os.path.dirname(__file__), '..', '..', 'storage', 'app', 'python_data', 'preprocessed_news.csv')
    if os.path.exists(data_path):
        print("Found preprocessed data, auto-initializing...")
        if load_and_preprocess_data(data_path):
            if build_tfidf_model():
                print("✓ Model auto-initialized successfully!")
            else:
                print("✗ Failed to build TF-IDF model")
        else:
            print("✗ Failed to load data")
    else:
        print("ℹ No preprocessed data found. Please initialize via /init endpoint")

    print("\n📡 Starting server on http://localhost:5000")
    print("💡 Use /init to initialize model if not auto-initialized")
    print("🔍 Use /search to search documents")
    print("=" * 60)

    app.run(host='0.0.0.0', port=5000, debug=False)
