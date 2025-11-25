import os
import sys
import json
import re
import requests
import pandas as pd
import numpy as np
from tqdm import tqdm
import time
from langdetect import detect

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

from flask import Flask, request, jsonify

print("=== INFORMATION RETRIEVAL SYSTEM - DEBUG VERSION ===")
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

def detect_text_col(df):
    candidates = ['content','text','article','body','news','judul','isi', 'Content', 'Judul']
    for c in candidates:
        if c in df.columns:
            return c
    textcols = [c for c in df.columns if df[c].dtype == object]
    if not textcols:
        raise ValueError("No text columns found!")
    return textcols[0]

def preprocess_text(text, stemmer=None, stopwords=None):
    if pd.isna(text) or text is None:
        return ""

    # Case folding
    t = str(text).lower()

    # Hapus tanda baca/URL
    t = re.sub(r'http\S+|www\S+|[^a-z\s]', ' ', t)

    # Tokenisasi
    tokens = t.split()

    # Stopword removal
    if stopwords is not None:
        tokens = [tok for tok in tokens if tok not in stopwords and len(tok) > 2]

    # Stemming (Sastrawi)
    if stemmer is not None and tokens:
        try:
            text_to_stem = ' '.join(tokens)
            tokens = stemmer.stem(text_to_stem).split()
        except Exception as e:
            print(f"Stemming warning: {e}")

    return " ".join(tokens)

def load_and_preprocess_simple(csv_path, sample_size=2000):
    """Load dan preprocessing yang sederhana dan aman"""
    global df_all, stemmer, stopwords

    try:
        print(f"📂 Loading dataset from: {csv_path}")

        # Load dataset
        df_all = pd.read_csv(csv_path, nrows=sample_size)
        print(f"📊 Dataset shape: {df_all.shape}")
        print(f"📋 Columns: {df_all.columns.tolist()}")

        # Deteksi kolom teks
        text_col = detect_text_col(df_all)
        print(f"✅ Using text column: {text_col}")

        # Initialize Sastrawi
        print("🔧 Initializing Sastrawi...")
        stemmer = StemmerFactory().create_stemmer()
        stopwords = set(StopWordRemoverFactory().get_stop_words())
        print("✅ Sastrawi initialized")

        # Handle missing values
        df_all[text_col] = df_all[text_col].fillna('')

        # Add standardized columns
        df_all['title_std'] = df_all.get('Judul', 'No Title')
        df_all['content_std'] = df_all[text_col]
        df_all['source_std'] = df_all.get('source', 'Unknown')

        # Preprocessing sederhana
        print("🛠️ Preprocessing text data...")

        # Gunakan apply biasa tanpa progress_apply untuk menghindari issue
        df_all['processed'] = df_all['content_std'].apply(
            lambda x: preprocess_text(str(x), stemmer=stemmer, stopwords=stopwords)
        )

        # Remove empty processed texts
        initial_count = len(df_all)
        df_all = df_all[df_all['processed'].str.strip() != '']
        final_count = len(df_all)
        print(f"📝 Removed {initial_count - final_count} empty documents")

        print("✅ Preprocessing completed!")
        print(f"📊 Final dataset: {len(df_all)} documents")

        # Debug: Check data types
        print(f"🔍 Data types - processed: {type(df_all['processed'].iloc[0])}")
        print(f"🔍 Sample processed text: '{df_all['processed'].iloc[0]}'")

        return True

    except Exception as e:
        print(f"❌ Error in preprocessing: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

def build_tfidf_safe():
    """Build TF-IDF dengan error handling"""
    global vectorizer, tfidf_matrix

    try:
        print("🔨 Building TF-IDF matrix...")

        # Pastikan data processed valid
        processed_texts = df_all['processed'].tolist()
        print(f"🔍 First 3 processed texts: {processed_texts[:3]}")

        # Check for any non-string values
        for i, text in enumerate(processed_texts):
            if not isinstance(text, str):
                print(f"⚠️ Non-string at index {i}: {type(text)} - {text}")
                processed_texts[i] = str(text)

        vectorizer = TfidfVectorizer(
            max_features=10000,
            min_df=2,
            max_df=0.8
        )

        tfidf_matrix = vectorizer.fit_transform(processed_texts)

        print(f"✅ TF-IDF shape: {tfidf_matrix.shape}")
        print(f"📚 Vocabulary size: {len(vectorizer.get_feature_names_out())}")

        # Debug: Check matrix
        print(f"🔍 TF-IDF matrix type: {type(tfidf_matrix)}")
        print(f"🔍 TF-IDF matrix dtype: {tfidf_matrix.dtype}")

        return True

    except Exception as e:
        print(f"❌ Error building TF-IDF: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

def search_query_safe(query, top_k=10):
    """Search dengan error handling yang komprehensif"""
    if vectorizer is None or tfidf_matrix is None:
        print("❌ Model not initialized")
        return None

    try:
        print(f"🔍 Processing query: '{query}'")

        # Preprocess query
        q_prep = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)
        print(f"🔍 Processed query: '{q_prep}'")

        if not q_prep:
            print("❌ Query empty after preprocessing")
            return None

        # Transform query ke TF-IDF
        print("🔍 Transforming query to TF-IDF...")
        q_vec = vectorizer.transform([q_prep])
        print(f"🔍 Query vector shape: {q_vec.shape}")
        print(f"🔍 Query vector type: {type(q_vec)}")

        # Hitung Cosine Similarity dengan error handling
        print("🔍 Calculating cosine similarity...")
        try:
            similarities = cosine_similarity(q_vec, tfidf_matrix)
            print(f"🔍 Similarities shape: {similarities.shape}")
            print(f"🔍 Similarities type: {type(similarities)}")

            # Flatten dan konversi ke numpy array
            similarities = similarities.flatten()
            similarities = np.asarray(similarities, dtype=np.float64)
            print(f"🔍 Similarities after flatten: {similarities.shape}")
            print(f"🔍 Similarities sample: {similarities[:5]}")

        except Exception as e:
            print(f"❌ Cosine similarity error: {e}")
            return None

        # Get top results
        print("🔍 Getting top results...")
        try:
            # Gunakan argsort dengan careful handling
            top_indices = np.argsort(similarities)[-top_k:][::-1]
            print(f"🔍 Top indices: {top_indices}")
        except Exception as e:
            print(f"❌ Argsort error: {e}")
            # Fallback manual
            indexed_scores = list(enumerate(similarities))
            indexed_scores.sort(key=lambda x: x[1], reverse=True)
            top_indices = [idx for idx, score in indexed_scores[:top_k]]

        # Prepare results
        results = []
        for i, idx in enumerate(top_indices):
            score = float(similarities[idx])
            print(f"🔍 Result {i}: index={idx}, score={score}")

            if score > 0.001:  # Threshold rendah untuk testing
                try:
                    result = {
                        'id': int(idx),
                        'title': str(df_all.iloc[idx]['title_std']),
                        'content': str(df_all.iloc[idx]['content_std']),
                        'source': str(df_all.iloc[idx]['source_std']),
                        'score': score,
                        'similarity_percent': min(score * 100, 100.0),
                        'rank': i + 1
                    }
                    results.append(result)
                    print(f"✅ Added result: {result['title'][:30]}...")
                except Exception as e:
                    print(f"❌ Error creating result for index {idx}: {e}")

        print(f"✅ Found {len(results)} relevant results")
        return results

    except Exception as e:
        print(f"❌ Search error: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return None

# Flask Routes
@app.route('/health', methods=['GET'])
def health_check():
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
    global df_all, vectorizer, tfidf_matrix

    try:
        data_path = 'final_merge_dataset.csv'
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
        data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', data_path)

        print(f"🎯 Initializing with: {data_path}")

        if not os.path.exists(data_path):
            return jsonify({
                'success': False,
                'error': f'Data file not found: {data_path}'
            })

        # Reset variables
        df_all = None
        vectorizer = None
        tfidf_matrix = None

        if not load_and_preprocess_simple(data_path, sample_size=1000):
            return jsonify({
                'success': False,
                'error': 'Failed to load and preprocess data'
            })

        if not build_tfidf_safe():
            return jsonify({
                'success': False,
                'error': 'Failed to build TF-IDF matrix'
            })

        return jsonify({
            'success': True,
            'message': f'Debug system initialized with {len(df_all)} documents',
            'statistics': {
                'documents': len(df_all),
                'tfidf_shape': tfidf_matrix.shape,
                'vocabulary': len(vectorizer.get_feature_names_out())
            }
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Initialization failed: {str(e)}'
        })

@app.route('/search', methods=['POST', 'GET'])
def search():
    global df_all, vectorizer, tfidf_matrix

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

        print(f"🎯 Search request: '{query}'")

        results = search_query_safe(query, top_k)

        if results is None:
            return jsonify({
                'success': False,
                'error': 'Search failed - check server logs for details'
            })

        q_prep = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)

        return jsonify({
            'success': True,
            'query': query,
            'processed_query': q_prep,
            'total_results': len(results),
            'results': results
        })

    except Exception as e:
        error_msg = f"Search endpoint error: {str(e)}"
        print(f"❌ {error_msg}")
        return jsonify({
            'success': False,
            'error': error_msg
        })

@app.route('/debug', methods=['GET'])
def debug_info():
    """Endpoint untuk debugging"""
    debug_info = {
        'df_all_type': str(type(df_all)),
        'df_all_shape': df_all.shape if df_all is not None else None,
        'vectorizer_ready': vectorizer is not None,
        'tfidf_ready': tfidf_matrix is not None,
        'sample_processed_texts': df_all['processed'].head(3).tolist() if df_all is not None else None,
        'sample_titles': df_all['title_std'].head(3).tolist() if df_all is not None else None
    }
    return jsonify(debug_info)

@app.route('/')
def home():
    return jsonify({
        'message': 'DEBUG Information Retrieval System',
        'version': 'debug-1.0',
        'endpoints': [
            'GET /health - System status',
            'GET /init - Initialize model',
            'GET /search?query=QUERY - Search documents',
            'GET /debug - Debug information'
        ]
    })

if __name__ == '__main__':
    print("=" * 70)
    print("DEBUG IR SYSTEM - WITH COMPREHENSIVE ERROR HANDLING")
    print("=" * 70)

    # Auto-initialize dengan sample kecil
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', 'final_merge_dataset.csv')

    if os.path.exists(data_path):
        print("🚀 Auto-initializing with debug sample...")
        if load_and_preprocess_simple(data_path, sample_size=500):
            if build_tfidf_safe():
                print("✅ Debug system ready!")
                print(f"✅ {len(df_all)} sample documents loaded")
            else:
                print("❌ TF-IDF failed")
        else:
            print("❌ Preprocessing failed")
    else:
        print(f"ℹ Dataset not found: {data_path}")

    print("\n🌐 Server: http://localhost:5000")
    print("🔧 Use /init to reinitialize")
    print("🐛 Use /debug for debug info")
    print("🔍 Use /search?query=halo to test")
    print("=" * 70)

    app.run(host='0.0.0.0', port=5000, debug=False)
