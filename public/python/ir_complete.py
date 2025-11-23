import os
import sys
import json
import re
import requests
import pandas as pd
import numpy as np
import time
from tqdm import tqdm
from langdetect import detect

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory
from flask import Flask, request, jsonify

print("=== INFORMATION RETRIEVAL SYSTEM - COMPLETE VERSION ===")
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

# ✅ 1. Helper to detect text column
def detect_text_col(df):
    candidates = ['content','text','article','body','news','judul','isi']
    for c in candidates:
        if c in df.columns:
            return c
    # fallback
    textcols = [c for c in df.columns if df[c].dtype == object]
    if not textcols:
        raise ValueError("No text columns found!")
    return textcols[0]

# ✅ 2. Helper for preprocessing (sesuai ketentuan)
def preprocess_text(text, stemmer=None, stopwords=None):
    if pd.isna(text):
        return ""

    # ✅ Case folding
    t = str(text).lower()

    # ✅ Hapus tanda baca/URL
    t = re.sub(r'http\S+|www\S+|[^a-z0-9\s]', ' ', t)

    # ✅ Tokenisasi
    tokens = t.split()

    # ✅ Stopword removal
    if stopwords is not None:
        tokens = [tok for tok in tokens if tok not in stopwords]

    # ✅ Stemming (Sastrawi)
    if stemmer is not None:
        tokens = [stemmer.stem(tok) for tok in tokens]

    return " ".join(tokens)

# ✅ 3. Helper for DeepL translation
DEEPL_API_KEY = os.getenv("DEEPL_API_KEY")
DEEPL_API_URL = "https://api-free.deepl.com/v2/translate"

def translate_batch(texts, target_lang="ID", batch_size=10):
    if not DEEPL_API_KEY:
        print("Tidak ada DEEPL_API_KEY, skip translation.")
        return texts

    headers = {"Authorization": f"DeepL-Auth-Key {DEEPL_API_KEY}"}
    translated = []

    print(f"Translating {len(texts)} texts via DeepL...")
    for i in tqdm(range(0, len(texts), batch_size), desc="Translating"):
        batch = texts[i:i+batch_size]
        data = [('target_lang', target_lang)] + [('text', t) for t in batch]
        try:
            r = requests.post(DEEPL_API_URL, headers=headers, data=data, timeout=60)
            r.raise_for_status()
            tr = r.json()['translations']
            translated.extend([x['text'] for x in tr])
        except requests.exceptions.RequestException as e:
            print(f"Error during DeepL translation: {e}")
            translated.extend(batch)
        time.sleep(0.3)

    return translated

def load_and_preprocess_complete(csv_path, use_sample=True, sample_size=5000):
    """Load dan preprocessing sesuai ketentuan lengkap"""
    global df_all, stemmer, stopwords

    try:
        print(f"📂 Loading dataset from: {csv_path}")

        # ✅ Load dataset
        if use_sample:
            df_all = pd.read_csv(csv_path, nrows=sample_size)
            print(f"📊 Using sample: {df_all.shape}")
        else:
            df_all = pd.read_csv(csv_path)
            print(f"📊 Full dataset: {df_all.shape}")

        print(f"📋 Columns: {df_all.columns.tolist()}")

        # ✅ Deteksi kolom teks
        text_col = detect_text_col(df_all)
        print(f"✅ Text column detected: {text_col}")

        # ✅ Initialize Sastrawi
        print("🔧 Initializing Sastrawi...")
        stemmer = StemmerFactory().create_stemmer()
        stopwords = set(StopWordRemoverFactory().get_stop_words())
        print("✅ Sastrawi initialized")

        # Handle missing values
        df_all[text_col] = df_all[text_col].fillna('')

        # ✅ DeepL Translation (jika ada API key)
        if DEEPL_API_KEY:
            print("🌍 Checking for translation needs...")
            texts = df_all[text_col].astype(str).tolist()

            # Deteksi bahasa untuk subset
            subset_size = min(1000, len(texts))
            mask_non_id = []

            print("🔍 Detecting languages...")
            for t in tqdm(texts[:subset_size], desc="Language Detection"):
                try:
                    lang = detect(t[:500] if len(t) > 500 else t)
                    mask_non_id.append(lang != 'id')
                except:
                    mask_non_id.append(False)

            to_translate = [t for t, m in zip(texts[:subset_size], mask_non_id) if m]

            if to_translate:
                print(f"🔄 Translating {len(to_translate)} non-Indonesian texts...")
                translated = translate_batch(to_translate)

                # Apply translations
                j = 0
                for i in range(len(df_all)):
                    if i < subset_size and mask_non_id[i]:
                        df_all.at[i, text_col] = translated[j]
                        j += 1
                print("✅ Translation completed")
            else:
                print("✅ All texts are already in Indonesian")
        else:
            print("ℹ No DeepL API key, using original texts")

        # ✅ Preprocessing lengkap
        print("🛠️ Starting complete preprocessing...")
        tqdm.pandas(desc="Preprocessing")
        df_all["processed"] = df_all[text_col].progress_apply(
            lambda x: preprocess_text(x, stemmer=stemmer, stopwords=stopwords)
        )

        # Remove empty processed texts
        initial_count = len(df_all)
        df_all = df_all[df_all['processed'].str.strip() != '']
        final_count = len(df_all)
        print(f"📝 Removed {initial_count - final_count} empty documents")

        # Add standardized columns
        df_all['title_std'] = df_all.get('Judul', 'No Title')
        df_all['content_std'] = df_all[text_col]
        df_all['source_std'] = df_all.get('source', 'Unknown')

        print("✅ Preprocessing completed!")
        print(f"📊 Final dataset: {len(df_all)} documents")

        # Show samples
        print("\n📄 Sample results:")
        for i in range(min(3, len(df_all))):
            orig = df_all.iloc[i]['content_std'][:80] + '...' if len(df_all.iloc[i]['content_std']) > 80 else df_all.iloc[i]['content_std']
            proc = df_all.iloc[i]['processed'][:80] + '...' if len(df_all.iloc[i]['processed']) > 80 else df_all.iloc[i]['processed']
            print(f"  {i+1}. Original: {orig}")
            print(f"     Processed: {proc}\n")

        return True

    except Exception as e:
        print(f"❌ Error in preprocessing: {e}")
        import traceback
        print(f"Traceback: {traceback.format_exc()}")
        return False

# ✅ 4. Build TF-IDF Matrix
def build_tfidf_matrix():
    """Membangun representasi dokumen dengan TF-IDF"""
    global vectorizer, tfidf_matrix

    try:
        print("🔨 Building TF-IDF matrix...")

        vectorizer = TfidfVectorizer(max_features=30000)
        tfidf_matrix = vectorizer.fit_transform(df_all["processed"])

        print(f"✅ TF-IDF shape: {tfidf_matrix.shape}")
        print(f"📚 Vocabulary size: {len(vectorizer.get_feature_names_out())}")

        return True

    except Exception as e:
        print(f"❌ Error building TF-IDF: {e}")
        return False

# ✅ 5. Search function dengan Cosine Similarity
def search_query(query, top_k=10):
    """Pencarian dengan Cosine Similarity"""
    if vectorizer is None or tfidf_matrix is None:
        return None

    # Preprocess query
    q_prep = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)
    if not q_prep:
        return None

    try:
        # Transform query ke TF-IDF
        q_vec = vectorizer.transform([q_prep])

        # ✅ Hitung Cosine Similarity
        sims = cosine_similarity(q_vec, tfidf_matrix).flatten()

        # Get top results
        idx = sims.argsort()[-top_k:][::-1]

        # Prepare results
        results = []
        for i, rank in enumerate(idx):
            if sims[rank] > 0:
                results.append({
                    'id': int(rank),
                    'title': str(df_all.iloc[rank]['title_std']),
                    'content': str(df_all.iloc[rank]['content_std']),
                    'source': str(df_all.iloc[rank]['source_std']),
                    'score': float(sims[rank]),
                    'similarity_percent': float(sims[rank] * 100),
                    'rank': i + 1
                })

        return results

    except Exception as e:
        print(f"Search error: {e}")
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
        'deepl_available': bool(DEEPL_API_KEY)
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

        # Use sample for faster initialization
        if not load_and_preprocess_complete(data_path, use_sample=True, sample_size=5000):
            return jsonify({
                'success': False,
                'error': 'Failed to load and preprocess data'
            })

        if not build_tfidf_matrix():
            return jsonify({
                'success': False,
                'error': 'Failed to build TF-IDF matrix'
            })

        stats = {
            'total_documents': len(df_all),
            'tfidf_shape': tfidf_matrix.shape,
            'vocabulary_size': len(vectorizer.get_feature_names_out()),
            'deepl_used': bool(DEEPL_API_KEY),
            'preprocessing_steps': [
                'Case folding',
                'URL/punctuation removal',
                'Tokenization',
                'Stopword removal',
                'Stemming (Sastrawi)'
            ]
        }

        return jsonify({
            'success': True,
            'message': f'Complete IR system initialized with {len(df_all)} documents',
            'statistics': stats,
            'features': 'TF-IDF + Cosine Similarity + Full Preprocessing'
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

        print(f"🔍 Searching for: '{query}'")

        results = search_query(query, top_k)

        if results is None:
            return jsonify({
                'success': False,
                'error': 'Search failed'
            })

        # Preprocess query untuk display
        q_prep = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)

        return jsonify({
            'success': True,
            'query': query,
            'processed_query': q_prep,
            'total_results': len(results),
            'results': results
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Search error: {str(e)}'
        })

@app.route('/test', methods=['GET'])
def test_search():
    """Test dengan berbagai query"""
    global df_all, vectorizer, tfidf_matrix

    if df_all is None or vectorizer is None:
        return jsonify({
            'success': False,
            'error': 'Model not initialized'
        })

    try:
        test_queries = ['indonesia', 'teknologi', 'politik', 'ekonomi', 'kesehatan']
        results = {}

        for query in test_queries:
            search_results = search_query(query, 2)
            if search_results:
                results[query] = {
                    'processed_query': preprocess_text(query, stemmer=stemmer, stopwords=stopwords),
                    'results_found': len(search_results),
                    'top_score': search_results[0]['score'] if search_results else 0
                }

        return jsonify({
            'success': True,
            'test_queries': results
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        })

@app.route('/')
def home():
    return jsonify({
        'message': 'COMPLETE Information Retrieval System',
        'version': '1.0',
        'dataset': 'final_merge_dataset.csv',
        'features': [
            'TF-IDF Document Representation',
            'Cosine Similarity Calculation',
            'DeepL Translation (if API key available)',
            'Complete Preprocessing: Case Folding, Tokenization, Stopword Removal, Stemming'
        ],
        'endpoints': [
            'GET /health - System status',
            'GET /init - Initialize model',
            'GET /search?query=QUERY - Search documents',
            'GET /test - Test search functionality'
        ]
    })

if __name__ == '__main__':
    print("=" * 70)
    print("INFORMATION RETRIEVAL SYSTEM - COMPLETE IMPLEMENTATION")
    print("=" * 70)
    print("✅ All requirements implemented:")
    print("   📚 Import libraries")
    print("   📥 Load dataset")
    print("   🔍 Detect text columns")
    print("   🌍 DeepL translation")
    print("   🛠️ Complete preprocessing")
    print("   📊 TF-IDF document representation")
    print("   📐 Cosine similarity calculation")
    print("=" * 70)

    # Auto-initialize dengan sample
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    data_path = os.path.join(base_dir, 'storage', 'app', 'python_data', 'final_merge_dataset.csv')

    if os.path.exists(data_path):
        print("🚀 Auto-initializing with sample data...")
        if load_and_preprocess_complete(data_path, use_sample=True, sample_size=3000):
            if build_tfidf_matrix():
                print("✅ System ready!")
                print(f"✅ {len(df_all)} documents processed")
            else:
                print("❌ TF-IDF failed")
        else:
            print("❌ Preprocessing failed")
    else:
        print(f"ℹ Dataset not found: {data_path}")

    print("\n🌐 Server: http://localhost:5000")
    print("💡 Use /init to reinitialize")
    print("🔍 Use /search?query=YOUR_QUERY to search")
    print("=" * 70)

    app.run(host='0.0.0.0', port=5000, debug=False)
