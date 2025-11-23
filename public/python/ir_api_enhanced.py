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

print("Starting Enhanced News IR System API...")
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

def preprocess_text(text, stemmer=None, stopwords=None):
    """Preprocess text dengan Sastrawi"""
    if pd.isna(text) or text is None:
        return ""

    text = str(text).lower()

    # Remove special characters, numbers, and extra spaces
    text = re.sub(r'http\S+|www\S+|[^a-z\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()

    # Tokenize
    tokens = text.split()

    # Stopword removal
    if stopwords and tokens:
        tokens = [token for token in tokens if token not in stopwords and len(token) > 2]

    # Stemming
    if stemmer and tokens:
        try:
            text_to_stem = ' '.join(tokens)
            tokens = stemmer.stem(text_to_stem).split()
        except Exception as e:
            print(f"Stemming warning: {e}")

    return " ".join(tokens)

def load_bbc_data(bbc_path):
    """Load BBC News dataset"""
    try:
        print(f"Loading BBC data from: {bbc_path}")
        bbc_df = pd.read_csv(bbc_path)
        print(f"BBC data shape: {bbc_df.shape}")
        print(f"BBC columns: {bbc_df.columns.tolist()}")

        # Map BBC columns to standard format
        bbc_df = bbc_df.rename(columns={
            'title': 'title',
            'content': 'content',
            'category': 'category'
        })

        # Add source identifier
        bbc_df['source'] = 'BBC News'

        # Select relevant columns
        bbc_df = bbc_df[['title', 'content', 'category', 'source']]

        print(f"BBC data processed: {len(bbc_df)} articles")
        return bbc_df

    except Exception as e:
        print(f"Error loading BBC data: {e}")
        return None

def load_indonesia_data(indo_path):
    """Load Indonesia News dataset"""
    try:
        print(f"Loading Indonesia data from: {indo_path}")
        indo_df = pd.read_csv(indo_path)
        print(f"Indonesia data shape: {indo_df.shape}")
        print(f"Indonesia columns: {indo_df.columns.tolist()}")

        # Map Indonesia dataset columns to standard format
        # Sesuaikan dengan struktur final_merge_dataset.csv
        column_mapping = {}
        for col in indo_df.columns:
            col_lower = col.lower()
            if 'title' in col_lower or 'judul' in col_lower:
                column_mapping[col] = 'title'
            elif 'content' in col_lower or 'isi' in col_lower or 'berita' in col_lower:
                column_mapping[col] = 'content'
            elif 'category' in col_lower or 'kategori' in col_lower:
                column_mapping[col] = 'category'

        indo_df = indo_df.rename(columns=column_mapping)

        # Add missing columns if necessary
        if 'title' not in indo_df.columns:
            indo_df['title'] = indo_df.iloc[:, 0]  # Use first column as title

        if 'content' not in indo_df.columns:
            # Combine all text columns for content
            text_columns = [col for col in indo_df.columns if indo_df[col].dtype == 'object']
            indo_df['content'] = indo_df[text_columns].fillna('').astype(str).agg(' '.join, axis=1)

        if 'category' not in indo_df.columns:
            indo_df['category'] = 'General'

        # Add source identifier
        indo_df['source'] = 'Indonesia News'

        # Select relevant columns
        indo_df = indo_df[['title', 'content', 'category', 'source']]

        print(f"Indonesia data processed: {len(indo_df)} articles")
        return indo_df

    except Exception as e:
        print(f"Error loading Indonesia data: {e}")
        return None

def load_and_preprocess_datasets(bbc_path, indo_path):
    """Load and combine both datasets"""
    global df_all, stemmer, stopwords

    try:
        # Initialize Sastrawi
        print("Initializing Sastrawi...")
        stemmer = StemmerFactory().create_stemmer()
        stopwords = set(StopWordRemoverFactory().get_stop_words())
        print("Sastrawi initialized successfully")

        # Load both datasets
        bbc_df = load_bbc_data(bbc_path)
        indo_df = load_indonesia_data(indo_path)

        if bbc_df is None and indo_df is None:
            raise ValueError("Failed to load both datasets")

        # Combine datasets
        dfs = []
        if bbc_df is not None:
            dfs.append(bbc_df)
        if indo_df is not None:
            dfs.append(indo_df)

        df_all = pd.concat(dfs, ignore_index=True)
        print(f"Combined dataset shape: {df_all.shape}")

        # Clean data
        df_all['title'] = df_all['title'].fillna('No Title')
        df_all['content'] = df_all['content'].fillna('')
        df_all['category'] = df_all['category'].fillna('General')

        # Remove empty content
        initial_count = len(df_all)
        df_all = df_all[df_all['content'].str.strip() != '']
        final_count = len(df_all)
        print(f"Removed {initial_count - final_count} empty documents")

        if len(df_all) == 0:
            raise ValueError("No valid documents after cleaning")

        # Preprocessing
        print("Preprocessing text data...")
        df_all['processed'] = df_all['content'].apply(
            lambda x: preprocess_text(x, stemmer=stemmer, stopwords=stopwords)
        )

        # Remove empty processed texts
        df_all = df_all[df_all['processed'].str.strip() != '']
        print(f"Final dataset size: {len(df_all)} documents")

        # Show sample data
        print("\nSample data:")
        for i in range(min(3, len(df_all))):
            print(f"Doc {i}: {df_all.iloc[i]['title'][:50]}... | Category: {df_all.iloc[i]['category']} | Source: {df_all.iloc[i]['source']}")

        return True

    except Exception as e:
        print(f"Error loading datasets: {e}")
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
        'sources': df_all['source'].value_counts().to_dict() if df_all is not None else {}
    }
    return jsonify(status)

@app.route('/init', methods=['POST', 'GET'])
def initialize_model():
    """Initialize the model with both datasets"""
    global df_all, vectorizer, tfidf_matrix

    try:
        # Default data paths
        bbc_path = 'bbc-news-data.csv'
        indo_path = 'final_merge_dataset.csv'

        # Get custom data paths if provided
        if request.method == 'POST':
            data = request.get_json()
            if data:
                bbc_path = data.get('bbc_path', bbc_path)
                indo_path = data.get('indo_path', indo_path)

        # Convert to absolute paths
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
        data_dir = os.path.join(base_dir, 'storage', 'app', 'python_data')

        bbc_path = os.path.join(data_dir, bbc_path)
        indo_path = os.path.join(data_dir, indo_path)

        print(f"BBC data path: {bbc_path}")
        print(f"Indonesia data path: {indo_path}")

        # Check if files exist
        if not os.path.exists(bbc_path):
            return jsonify({
                'success': False,
                'error': f'BBC data file not found: {bbc_path}'
            })

        if not os.path.exists(indo_path):
            return jsonify({
                'success': False,
                'error': f'Indonesia data file not found: {indo_path}'
            })

        # Load and preprocess datasets
        if not load_and_preprocess_datasets(bbc_path, indo_path):
            return jsonify({
                'success': False,
                'error': 'Failed to load and preprocess datasets'
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
            'sources': df_all['source'].value_counts().to_dict(),
            'categories': df_all['category'].value_counts().to_dict(),
            'tfidf_shape': tfidf_matrix.shape,
            'vocabulary_size': len(vectorizer.get_feature_names_out())
        }

        return jsonify({
            'success': True,
            'message': f'Model initialized successfully with {len(df_all)} documents from multiple sources',
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
            source_filter = request.args.get('source', '').strip()
        else:
            data = request.get_json()
            query = data.get('query', '').strip()
            top_k = data.get('top_k', 10)
            category_filter = data.get('category', '').strip()
            source_filter = data.get('source', '').strip()

        if not query:
            return jsonify({
                'success': False,
                'error': 'Query is required'
            })

        print(f"Searching for: '{query}'")
        if category_filter:
            print(f"Category filter: {category_filter}")
        if source_filter:
            print(f"Source filter: {source_filter}")

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

        # Apply filters if specified
        filtered_indices = list(range(len(df_all)))

        if category_filter:
            filtered_indices = [i for i in filtered_indices
                              if category_filter.lower() in str(df_all.iloc[i]['category']).lower()]

        if source_filter:
            filtered_indices = [i for i in filtered_indices
                              if source_filter.lower() in str(df_all.iloc[i]['source']).lower()]

        # Get top results from filtered indices
        filtered_similarities = similarities[filtered_indices]

        if len(filtered_similarities) == 0:
            return jsonify({
                'success': True,
                'query': query,
                'processed_query': query_processed,
                'total_results': 0,
                'results': [],
                'filters': {
                    'category': category_filter,
                    'source': source_filter
                }
            })

        # Get top indices from filtered results
        top_filtered_indices = np.argsort(filtered_similarities)[-top_k:][::-1]
        top_indices = [filtered_indices[i] for i in top_filtered_indices]

        # Prepare results
        results = []
        for idx in top_indices:
            score = float(similarities[idx])
            if score > 0.001:  # Include low-scoring but relevant results
                result = {
                    'id': int(idx),
                    'title': str(df_all.iloc[idx]['title']),
                    'content': str(df_all.iloc[idx]['content']),
                    'category': str(df_all.iloc[idx]['category']),
                    'source': str(df_all.iloc[idx]['source']),
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
                'category': category_filter,
                'source': source_filter
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
            'sources': df_all['source'].value_counts().to_dict(),
            'categories': df_all['category'].value_counts().to_dict(),
            'sample_documents': [
                {
                    'title': df_all.iloc[i]['title'],
                    'category': df_all.iloc[i]['category'],
                    'source': df_all.iloc[i]['source']
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
    global df_all, vectorizer, tfidf_matrix

    if df_all is None or vectorizer is None:
        return jsonify({
            'success': False,
            'error': 'Model not initialized'
        })

    try:
        # Test with different queries
        test_queries = ['indonesia', 'technology', 'sports', 'politik']
        results = {}

        for query in test_queries:
            # Simulate search
            query_processed = preprocess_text(query, stemmer=stemmer, stopwords=stopwords)
            query_vec = vectorizer.transform([query_processed])
            similarities = cosine_similarity(query_vec, tfidf_matrix).flatten()

            top_indices = np.argsort(similarities)[-3:][::-1]
            query_results = []

            for idx in top_indices:
                if similarities[idx] > 0:
                    query_results.append({
                        'title': str(df_all.iloc[idx]['title']),
                        'score': float(similarities[idx]),
                        'category': str(df_all.iloc[idx]['category'])
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
        'message': 'Enhanced News IR System API - Multiple Datasets',
        'version': '2.0',
        'datasets': ['BBC News', 'Indonesia News'],
        'endpoints': {
            'GET /health': 'Health check',
            'GET/POST /init': 'Initialize model with both datasets',
            'GET/POST /search': 'Search documents (supports category/source filters)',
            'GET /stats': 'Get dataset statistics',
            'GET /test': 'Test search with multiple queries'
        }
    })

if __name__ == '__main__':
    print("=" * 70)
    print("ENHANCED NEWS IR SYSTEM API - MULTIPLE DATASETS")
    print("=" * 70)
    print("📊 Datasets: BBC News + Indonesia News")
    print("🔧 Features: TF-IDF + Cosine Similarity + Sastrawi Preprocessing")
    print("🎯 Filters: Category and Source filtering")
    print("=" * 70)

    # Auto-try to initialize with both datasets
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    data_dir = os.path.join(base_dir, 'storage', 'app', 'python_data')

    bbc_path = os.path.join(data_dir, 'bbc-news-data.csv')
    indo_path = os.path.join(data_dir, 'final_merge_dataset.csv')

    if os.path.exists(bbc_path) and os.path.exists(indo_path):
        print("Found both dataset files, auto-initializing...")
        if load_and_preprocess_datasets(bbc_path, indo_path):
            if build_tfidf_model():
                print("✓ Model auto-initialized successfully!")
                print(f"✓ Loaded {len(df_all)} documents")
                print(f"✓ Sources: {df_all['source'].value_counts().to_dict()}")
            else:
                print("✗ Failed to build TF-IDF model")
        else:
            print("✗ Failed to load datasets")
    else:
        print("ℹ Dataset files not found. Please initialize via /init endpoint")
        if not os.path.exists(bbc_path):
            print(f"  Missing: {bbc_path}")
        if not os.path.exists(indo_path):
            print(f"  Missing: {indo_path}")

    print("\n📡 Starting server on http://localhost:5000")
    print("💡 Use /init to initialize model if not auto-initialized")
    print("🔍 Use /search to search documents (supports filters)")
    print("📊 Use /stats to see dataset statistics")
    print("=" * 70)

    app.run(host='0.0.0.0', port=5000, debug=False)
