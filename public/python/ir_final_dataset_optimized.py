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
from tqdm import tqdm

print("Starting Optimized News IR System with Final Dataset...")
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

# Helper for preprocessing dengan progress
def preprocess_text(text, stemmer=None, stopwords=None):
    if pd.isna(text) or text is None:
        return ""
    t = str(text).lower()
    t = re.sub(r'http\S+|www\S+|[^a-z\s]', ' ', t)  # Simplified regex
    tokens = t.split()
    if stopwords is not None:
        tokens = [tok for tok in tokens if tok not in stopwords and len(tok) > 2]
    if stemmer is not None and tokens:
        try:
            text_to_stem = ' '.join(tokens)
            tokens = stemmer.stem(text_to_stem).split()
        except Exception as e:
            pass  # Skip stemming error
    return " ".join(tokens)

def load_and_preprocess_data(csv_path):
    """Load and preprocess the final_merge_dataset.csv dengan optimasi"""
    global df_all, stemmer, stopwords

    try:
        print(f"Loading data from: {csv_path}")

        # Load hanya kolom yang diperlukan untuk menghemat memory
        df_all = pd.read_csv(csv_path, usecols=['Judul', 'Content', 'source'])
        print(f"Loaded data shape: {df_all.shape}")

        # Initialize Sastrawi
        print("Initializing Sastrawi...")
        stemmer = StemmerFactory().create_stemmer()
        stopwords = set(StopWordRemoverFactory().get_stop_words())
        print("Sastrawi initialized successfully")

        # Handle missing values
        df_all['Judul'] = df_all['Judul'].fillna('No Title')
        df_all['Content'] = df_all['Content'].fillna('')
        df_all['source'] = df_all['source'].fillna('Unknown')

        # Gunakan Content sebagai text utama, fallback ke Judul
        df_all['content_std'] = df_all['Content']
        df_all['title_std'] = df_all['Judul']
        df_all['category_std'] = df_all['source']  # Use source as category

        # Preprocessing dengan progress bar
        print("Preprocessing text data...")
        tqdm.pandas(desc="Preprocessing")
        df_all['processed'] = df_all['content_std'].progress_apply(
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
        print(f"Final dataset size: {len(df_all)} documents")

        return True

    except Exception as e:
        print(f"Error loading data: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

def build_tfidf_model():
    """Build TF-IDF model dengan optimasi"""
    global vectorizer, tfidf_matrix

    try:
        print("Building TF-IDF model...")

        # Gunakan parameter yang lebih optimal untuk dataset besar
        vectorizer = TfidfVectorizer(
            max_features=10000,  # Kurangi features
            min_df=5,           # Hanya terms yang muncul minimal 5 dokumen
            max_df=0.7,         # Hapus terms yang muncul di >70% dokumen
            stop_words=None
        )

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
        'tfidf_shape': tfidf_matrix.shape if tfidf_matrix is not None else None,
        'document_count': len(df_all) if df_all is not None else 0,
    }
    return jsonify(status)

@app.route('/init', methods=['POST', 'GET'])
def initialize_model():
    """Initialize the model"""
    global df_all, vectorizer, tfidf_matrix

    try:
        data_path = 'final_merge_dataset.csv'
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
        data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', data_path)

        print(f"Initializing with data path: {data_path}")

        if not os.path.exists(data_path):
            return jsonify({
                'success': False,
                'error': f'Data file not found: {data_path}'
            })

        if not load_and_preprocess_data(data_path):
            return jsonify({
                'success': False,
                'error': 'Failed to load and preprocess data'
            })

        if not build_tfidf_model():
            return jsonify({
                'success': False,
                'error': 'Failed to build TF-IDF model'
            })

        stats = {
            'total_documents': len(df_all),
            'sources': df_all['source'].value_counts().to_dict(),
            'tfidf_shape': tfidf_matrix.shape,
            'vocabulary_size': len(vectorizer.get_feature_names_out())
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
        top_indices = np.argsort(similarities)[-top_k:][::-1]

        # Prepare results
        results = []
        for idx in top_indices:
            score = float(similarities[idx])
            if score > 0.001:
                results.append({
                    'id': int(idx),
                    'title': str(df_all.iloc[idx]['title_std']),
                    'content': str(df_all.iloc[idx]['content_std']),
                    'source': str(df_all.iloc[idx]['source']),
                    'score': score,
                    'similarity_percent': min(score * 100, 100.0)
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
        return jsonify({
            'success': False,
            'error': error_msg
        })

@app.route('/')
def home():
    """Home endpoint"""
    return jsonify({
        'message': 'Optimized News IR System API',
        'version': '1.1',
        'dataset': 'final_merge_dataset.csv (Optimized)',
        'features': 'TF-IDF + Cosine Similarity + Sastrawi Preprocessing'
    })

if __name__ == '__main__':
    print("=" * 70)
    print("OPTIMIZED NEWS IR SYSTEM API")
    print("=" * 70)
    print("📊 Dataset: final_merge_dataset.csv")
    print("⚡ Optimized for large dataset (80k+ documents)")
    print("🔧 Features: TF-IDF + Cosine Similarity + Sastrawi")
    print("=" * 70)

    # Auto-initialize
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', 'final_merge_dataset.csv')

    if os.path.exists(data_path):
        print("Found dataset file, auto-initializing...")
        if load_and_preprocess_data(data_path):
            if build_tfidf_model():
                print("✓ Model auto-initialized successfully!")
                print(f"✓ Loaded {len(df_all)} documents")
            else:
                print("✗ Failed to build TF-IDF model")
        else:
            print("✗ Failed to load dataset")
    else:
        print(f"ℹ Dataset file not found at: {data_path}")

    print("\n📡 Starting server on http://localhost:5000")
    print("=" * 70)

    app.run(host='0.0.0.0', port=5000, debug=False)
