"""
TF-IDF Search Engine dengan Filter Relevansi dan Anti-Duplikat
"""
import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from flask import Flask, request, jsonify
import os
import logging
from datetime import datetime
import re
import json

class TFIDFSearchEngine:
    def __init__(self, csv_path):
        self.csv_path = csv_path
        self.df = None
        self.vectorizer = None
        self.tfidf_matrix = None
        self.stemmer = None
        self.stopwords = None
        self.initialized = False

    def initialize(self):
        """Initialize the search engine with CSV data"""
        try:
            if not os.path.exists(self.csv_path):
                logging.error(f"CSV file not found: {self.csv_path}")
                return False

            # Load CSV dengan kolom yang sesuai dengan preprocessing Anda
            self.df = pd.read_csv(self.csv_path, encoding='utf-8')

            # Pastikan kolom yang diperlukan ada
            required_columns = ['text', 'processed']
            for col in required_columns:
                if col not in self.df.columns:
                    logging.error(f"Required column '{col}' not found in CSV")
                    return False

            # Isi NaN values
            self.df['processed'] = self.df['processed'].fillna('')
            self.df['text'] = self.df['text'].fillna('')

            # Inisialisasi TF-IDF Vectorizer
            self.vectorizer = TfidfVectorizer(
                max_features=30000,
                min_df=2,
                max_df=0.8,
                ngram_range=(1, 2)  # Gunakan unigram dan bigram
            )

            # Fit dan transform data
            self.tfidf_matrix = self.vectorizer.fit_transform(self.df['processed'])

            self.initialized = True
            logging.info(f"Search engine initialized with {len(self.df)} documents")
            return True

        except Exception as e:
            logging.error(f"Failed to initialize search engine: {str(e)}")
            return False

    def preprocess_query(self, query):
        """Preprocess query seperti di Colab notebook"""
        if not query:
            return ""

        # Case folding
        query = str(query).lower()

        # Remove special characters
        query = re.sub(r'http\S+|www\S+|[^a-z0-9\s]', ' ', query)

        # Tokenize
        tokens = query.split()

        # Stopword removal (sederhana)
        stopwords = {'dan', 'atau', 'dengan', 'pada', 'untuk', 'dari', 'yang', 'di', 'ke'}
        tokens = [tok for tok in tokens if tok not in stopwords]

        return " ".join(tokens)

    def search(self, query, top_k=10, min_similarity=0.1, deduplicate=True):
        """
        Search dengan filter relevansi dan anti-duplikat

        Args:
            query: Search query
            top_k: Maximum number of results
            min_similarity: Minimum similarity threshold (0-1)
            deduplicate: Whether to remove duplicate content

        Returns:
            List of search results
        """
        if not self.initialized:
            return []

        # Preprocess query
        q_prep = self.preprocess_query(query)
        if not q_prep:
            return []

        try:
            # Transform query ke TF-IDF space
            q_vec = self.vectorizer.transform([q_prep])

            # Hitung cosine similarity
            similarities = cosine_similarity(q_vec, self.tfidf_matrix).flatten()

            # Filter berdasarkan threshold
            threshold_mask = similarities > min_similarity
            if not threshold_mask.any():
                return []

            # Dapatkan indices yang memenuhi threshold
            relevant_indices = np.where(threshold_mask)[0]
            relevant_scores = similarities[relevant_indices]

            # Urutkan berdasarkan score
            sorted_indices = np.argsort(relevant_scores)[::-1]

            results = []
            seen_content = set() if deduplicate else None

            for idx in sorted_indices:
                if len(results) >= top_k * 2:  # Ambil lebih banyak untuk deduplikasi
                    break

                doc_idx = relevant_indices[idx]
                score = relevant_scores[idx]

                # Dapatkan data dokumen
                doc = {
                    'id': int(doc_idx) + 1,  # +1 karena database ID adalah 1-based (Laravel auto-increment)
                    'text': str(self.df.iloc[doc_idx]['text']),
                    'similarity': float(score),
                    'category': str(self.df.iloc[doc_idx].get('category', '')),
                    'source': str(self.df.iloc[doc_idx].get('source', '')),
                    'processed': str(self.df.iloc[doc_idx]['processed'])
                }

                # Tambahkan translated jika ada
                if 'translated' in self.df.columns:
                    doc['translated'] = str(self.df.iloc[doc_idx]['translated'])

                # Cek duplikat jika diaktifkan
                if deduplicate:
                    # Gunakan processed text untuk deteksi duplikat
                    content_key = doc['processed'][:100]  # Ambil 100 karakter pertama

                    # Skip jika konten terlalu mirip dengan yang sudah ada
                    is_duplicate = False
                    for seen in seen_content:
                        if self._calculate_similarity(content_key, seen) > 0.9:
                            is_duplicate = True
                            break

                    if not is_duplicate:
                        seen_content.add(content_key)
                        results.append(doc)
                else:
                    results.append(doc)

            # Batasi hasil akhir
            final_results = results[:top_k]

            # Hitung statistik
            stats = {
                'total_found': len(relevant_indices),
                'filtered_out': len(relevant_indices) - len(final_results),
                'min_similarity': min_similarity,
                'average_similarity': float(np.mean([r['similarity'] for r in final_results])) if final_results else 0
            }

            return final_results, stats

        except Exception as e:
            logging.error(f"Search error: {str(e)}")
            return [], {}

    def _calculate_similarity(self, text1, text2):
        """Calculate similarity between two texts"""
        if not text1 or not text2:
            return 0.0

        # Simple Jaccard similarity
        set1 = set(text1.split())
        set2 = set(text2.split())

        if not set1 or not set2:
            return 0.0

        intersection = len(set1.intersection(set2))
        union = len(set1.union(set2))

        return intersection / union if union > 0 else 0.0

    def get_stats(self):
        """Get engine statistics"""
        if not self.initialized:
            return {}

        return {
            'total_documents': len(self.df),
            'vocabulary_size': len(self.vectorizer.get_feature_names_out()),
            'is_initialized': self.initialized,
            'csv_file_exists': os.path.exists(self.csv_path),
            'last_update': datetime.now().isoformat()
        }

    def get_document(self, doc_id):
        """Get specific document by ID (1-based from Laravel)"""
        # Konversi dari 1-based (Laravel) ke 0-based (Python array)
        doc_index = doc_id - 1

        if not self.initialized or doc_index < 0 or doc_index >= len(self.df):
            return None

        try:
            doc = self.df.iloc[doc_index].to_dict()
            doc['id'] = doc_id  # Return 1-based ID
            return doc
        except Exception as e:
            logging.error(f"Failed to get document {doc_id}: {str(e)}")
            return None

# Flask App Setup
app = Flask(__name__)
logging.basicConfig(level=logging.INFO)

# Global search engine instance
search_engine = None

def initialize_engine():
    """Initialize search engine - called at startup"""
    global search_engine
    csv_path = os.path.join(os.path.dirname(__file__), 'preprocessed_news.csv')
    search_engine = TFIDFSearchEngine(csv_path)
    search_engine.initialize()

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    status = {
        'status': 'ok',
        'engine_initialized': search_engine.initialized if search_engine else False,
        'csv_file_exists': os.path.exists(search_engine.csv_path) if search_engine else False,
        'timestamp': datetime.now().isoformat()
    }
    return jsonify(status)

@app.route('/stats', methods=['GET'])
def get_stats():
    """Get engine statistics"""
    if not search_engine or not search_engine.initialized:
        return jsonify({'error': 'Engine not initialized'}), 503

    stats = search_engine.get_stats()
    return jsonify({'stats': stats})

@app.route('/search', methods=['GET'])
def search():
    """Search endpoint dengan filter"""
    if not search_engine or not search_engine.initialized:
        return jsonify({'error': 'Engine not initialized'}), 503

    try:
        # Get parameters
        query = request.args.get('query', '')
        top_k = request.args.get('top_k', 10)
        min_similarity = request.args.get('min_similarity', 0.1)
        deduplicate = request.args.get('deduplicate', 'true').lower() == 'true'

        # Parse top_k
        if top_k == 'all':
            top_k = 1000  
        else:
            try:
                top_k = int(top_k)
            except:
                top_k = 10

        # Parse min_similarity
        try:
            min_similarity = float(min_similarity)
            if min_similarity < 0 or min_similarity > 1:
                min_similarity = 0.1
        except:
            min_similarity = 0.1

        # Perform search
        results, stats = search_engine.search(
            query=query,
            top_k=top_k,
            min_similarity=min_similarity,
            deduplicate=deduplicate
        )

        return jsonify({
            'query': query,
            'results': results,
            'results_count': len(results),
            'stats': stats,
            'parameters': {
                'top_k': top_k,
                'min_similarity': min_similarity,
                'deduplicate': deduplicate
            }
        })

    except Exception as e:
        logging.error(f"Search endpoint error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/document/<int:doc_id>', methods=['GET'])
def get_document(doc_id):
    """Get specific document"""
    if not search_engine or not search_engine.initialized:
        return jsonify({'error': 'Engine not initialized'}), 503

    try:
        document = search_engine.get_document(doc_id)
        if document:
            return jsonify({'document': document})
        else:
            return jsonify({'error': 'Document not found'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/rebuild', methods=['POST'])
def rebuild():
    """Rebuild search engine"""
    global search_engine

    try:
        csv_path = os.path.join(os.path.dirname(__file__), 'preprocessed_news.csv')
        search_engine = TFIDFSearchEngine(csv_path)
        success = search_engine.initialize()

        if success:
            return jsonify({
                'success': True,
                'message': 'Search engine rebuilt successfully',
                'stats': search_engine.get_stats()
            })
        else:
            return jsonify({
                'success': False,
                'message': 'Failed to rebuild search engine'
            }), 500
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error: {str(e)}'
        }), 500

if __name__ == '__main__':
    # Initialize engine on startup
    csv_path = os.path.join(os.path.dirname(__file__), 'preprocessed_news.csv')
    search_engine = TFIDFSearchEngine(csv_path)

    if search_engine.initialize():
        print(f"✅ Search engine initialized with {len(search_engine.df)} documents")
        print(f"📊 Vocabulary size: {len(search_engine.vectorizer.get_feature_names_out())}")
    else:
        print("❌ Failed to initialize search engine")

    # Run Flask app
    app.run(host='127.0.0.1', port=5000, debug=False)
