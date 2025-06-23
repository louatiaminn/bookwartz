import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.feature_extraction.text import TfidfVectorizer
from scipy.sparse import csr_matrix
import sys
import json
import os
import mysql.connector
from mysql.connector import Error
import warnings
from difflib import SequenceMatcher
import logging

# Configure logging to send debug info to stderr instead of stdout
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    stream=sys.stderr  # Important: send logs to stderr, not stdout
)

# Suppress warnings to stderr
warnings.filterwarnings("ignore", category=FutureWarning)
warnings.filterwarnings("ignore", category=pd.errors.DtypeWarning)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'database': 'book',
    'user': 'root',
    'password': ''
}

def log_debug(message):
    """Log debug messages to stderr"""
    logging.info(f"DEBUG: {message}")

def get_db_connection():
    """Get database connection"""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        log_debug("Database connection successful")
        return connection
    except Error as e:
        log_debug(f"Database connection failed: {str(e)}")
        return None

def load_database_books():
    """Load books from database"""
    try:
        connection = get_db_connection()
        if not connection:
            log_debug("No database connection available")
            return None
        
        cursor = connection.cursor(dictionary=True)
        query = """
        SELECT b.id, b.title, b.author, b.description, b.category_id
        FROM books b
        WHERE b.title IS NOT NULL AND b.author IS NOT NULL
        """
        cursor.execute(query)
        books = cursor.fetchall()
        
        cursor.close()
        connection.close()
        
        log_debug(f"Loaded {len(books) if books else 0} books from database")
        return pd.DataFrame(books) if books else None
        
    except Error as e:
        log_debug(f"Database error: {str(e)}")
        return None

def load_csv_data():
    """Load and preprocess the CSV data"""
    try:
        # Try different paths for CSV files
        data_paths = [
            'data/',
            '../data/',
            '../../data/',
            os.path.join(os.path.dirname(__file__), 'data/'),
            os.path.join(os.path.dirname(__file__), '../data/'),
            os.path.join(os.path.dirname(__file__), '../../data/'),
            '/var/www/html/data/',  # Common web server path
            './data/'  # Current directory
        ]
        
        books_df = None
        ratings_df = None
        
        for base_path in data_paths:
            try:
                books_path = os.path.join(base_path, 'books.csv')
                ratings_path = os.path.join(base_path, 'ratings.csv')
                
                if os.path.exists(books_path):
                    log_debug(f"Found books.csv at: {books_path}")
                    books_df = pd.read_csv(books_path, sep=';', on_bad_lines='skip', 
                                         encoding='latin-1', low_memory=False)
                    
                    # Try to load ratings if available
                    if os.path.exists(ratings_path):
                        log_debug(f"Found ratings.csv at: {ratings_path}")
                        ratings_df = pd.read_csv(ratings_path, sep=';', encoding='latin-1')
                    
                    break
            except Exception as e:
                log_debug(f"Error loading CSV from {base_path}: {str(e)}")
                continue
        
        log_debug(f"CSV loading result - Books: {len(books_df) if books_df is not None else 0}, Ratings: {len(ratings_df) if ratings_df is not None else 0}")
        return books_df, ratings_df
        
    except Exception as e:
        log_debug(f"CSV loading error: {str(e)}")
        return None, None

def preprocess_csv_data(books_df, ratings_df=None):
    """Preprocess CSV data - now works with just books data"""
    try:
        log_debug("Starting CSV data preprocessing")
        
        # Rename columns to standard format
        column_mapping = {
            'Book-Title': 'title',
            'Book-Author': 'author',
            'Image-URL-L': 'image_url',
            'Image-URL-M': 'image_url',
            'Image-URL-S': 'image_url',
            'Year-Of-Publication': 'year',
            'Publisher': 'publisher'
        }
        
        books_df.rename(columns=column_mapping, inplace=True)
        
        # Clean the data
        books_df = books_df.dropna(subset=['title', 'author'])
        books_df = books_df.drop_duplicates(subset=['title', 'author'])
        log_debug(f"After cleaning: {len(books_df)} books")
        
        # If we have ratings data, use collaborative filtering
        if ratings_df is not None and len(ratings_df) > 0:
            try:
                log_debug("Attempting collaborative filtering")
                ratings_df.rename(columns={
                    'User-ID': 'user_id',
                    'Book-Rating': 'rating'
                }, inplace=True)

                # Filter active users (those who rated more than 50 books)
                user_counts = ratings_df['user_id'].value_counts()
                active_users = user_counts[user_counts > 50].index
                ratings_filtered = ratings_df[ratings_df['user_id'].isin(active_users)]

                # Merge with books data
                rated_books = ratings_filtered.merge(books_df, on='ISBN', how='inner')
                
                # Filter books with at least 20 ratings
                book_rating_counts = rated_books.groupby('title')['rating'].count().reset_index()
                book_rating_counts.rename(columns={'rating': 'num_ratings'}, inplace=True)
                
                final_rating = rated_books.merge(book_rating_counts, on='title')
                final_rating = final_rating[final_rating['num_ratings'] >= 20]
                final_rating.drop_duplicates(['user_id', 'title'], inplace=True)

                # Create pivot table
                book_pivot = final_rating.pivot_table(columns='user_id', index='title', values='rating')
                book_pivot.fillna(0, inplace=True)

                if len(book_pivot) > 0:
                    log_debug(f"Collaborative filtering successful: {len(book_pivot)} books")
                    book_sparse = csr_matrix(book_pivot)
                    similarity_scores = cosine_similarity(book_sparse)
                    return book_pivot, similarity_scores, 'collaborative'
            except Exception as e:
                log_debug(f"Collaborative filtering failed: {str(e)}")
        
        # Fall back to content-based filtering using just books data
        log_debug("Using content-based filtering")
        books_df['features'] = (
            books_df['title'].fillna('') + ' ' + 
            books_df['author'].fillna('') + ' ' + 
            books_df.get('publisher', '').fillna('') + ' ' + 
            books_df.get('year', '').astype(str).fillna('')
        )
        
        # Use TF-IDF for content similarity
        tfidf = TfidfVectorizer(max_features=1000, stop_words='english', lowercase=True)
        tfidf_matrix = tfidf.fit_transform(books_df['features'])
        
        similarity_matrix = cosine_similarity(tfidf_matrix)
        log_debug(f"Content-based filtering successful: {similarity_matrix.shape}")
        
        return books_df, similarity_matrix, 'content'
        
    except Exception as e:
        log_debug(f"Preprocessing error: {str(e)}")
        return None, None, None

def find_similar_title(input_title, title_list, threshold=0.4):
    """Find the most similar title using string similarity"""
    input_title_lower = input_title.lower().strip()
    log_debug(f"Looking for similar title to: '{input_title}'")
    
    # Exact match first
    for title in title_list:
        if title.lower().strip() == input_title_lower:
            log_debug(f"Exact match found: '{title}'")
            return title
    
    # Partial match - check if input is contained in any title
    for title in title_list:
        if input_title_lower in title.lower():
            log_debug(f"Partial match found (input in title): '{title}'")
            return title
    
    # Partial match - check if any title is contained in input
    for title in title_list:
        if title.lower().strip() in input_title_lower:
            log_debug(f"Partial match found (title in input): '{title}'")
            return title
    
    # Fuzzy matching
    best_match = None
    best_ratio = 0
    
    for title in title_list:
        ratio = SequenceMatcher(None, input_title_lower, title.lower()).ratio()
        if ratio > best_ratio and ratio >= threshold:
            best_ratio = ratio
            best_match = title
    
    if best_match:
        log_debug(f"Fuzzy match found: '{best_match}' (ratio: {best_ratio:.2f})")
    else:
        log_debug("No similar title found")
    
    return best_match

def get_csv_collaborative_recommendations(book_title, book_pivot, similarity_scores, num_recommendations=6):
    """Get recommendations from CSV data using collaborative filtering"""
    try:
        title_list = book_pivot.index.tolist() if hasattr(book_pivot, 'index') else list(book_pivot.keys())
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            log_debug("No matching title found for collaborative filtering")
            return None
        
        book_id = np.where(book_pivot.index == matched_title)[0][0]
        scores = list(enumerate(similarity_scores[book_id]))
        sorted_scores = sorted(scores, key=lambda x: x[1], reverse=True)
        
        recommendations = []
        for i, score in sorted_scores[1:num_recommendations+1]:
            if i < len(book_pivot.index) and score > 0.1:  # Only include meaningful similarities
                recommendations.append({
                    'title': book_pivot.index[i],
                    'similarity_score': float(score),
                    'source': 'csv_collaborative'
                })
        
        log_debug(f"Generated {len(recommendations)} collaborative recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': recommendations,
            'method': 'collaborative_filtering'
        }
        
    except Exception as e:
        log_debug(f"Error in collaborative recommendations: {str(e)}")
        return None

def get_csv_content_recommendations(book_title, books_df, similarity_matrix, num_recommendations=6):
    """Get recommendations from CSV data using content-based filtering"""
    try:
        title_list = books_df['title'].tolist()
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            log_debug("No matching title found for content-based filtering")
            return None
        
        matched_idx = books_df[books_df['title'] == matched_title].index[0]
        
        # Get similarity scores for the matched book
        scores = list(enumerate(similarity_matrix[matched_idx]))
        sorted_scores = sorted(scores, key=lambda x: x[1], reverse=True)
        
        recommendations = []
        count = 0
        for i, score in sorted_scores[1:]:  # Skip the book itself
            if count >= num_recommendations:
                break
            if i < len(books_df) and score > 0.1:  # Only include meaningful similarities
                book = books_df.iloc[i]
                recommendations.append({
                    'title': book['title'],
                    'author': book['author'],
                    'similarity_score': float(score),
                    'source': 'csv_content'
                })
                count += 1
        
        log_debug(f"Generated {len(recommendations)} content-based recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': recommendations,
            'method': 'content_based'
        }
        
    except Exception as e:
        log_debug(f"Error in content-based recommendations: {str(e)}")
        return None

def get_db_recommendations(book_title, db_books, similarity_matrix, num_recommendations=6):
    """Get recommendations from database using content-based filtering"""
    try:
        matched_idx = None
        matched_title = find_similar_title(book_title, db_books['title'].tolist())
        
        if matched_title is None:
            log_debug("No matching title found in database")
            return None
        
        matched_idx = db_books[db_books['title'] == matched_title].index[0]
        
        # Get similarity scores for the matched book
        scores = list(enumerate(similarity_matrix[matched_idx]))
        sorted_scores = sorted(scores, key=lambda x: x[1], reverse=True)
        
        recommendations = []
        count = 0
        for i, score in sorted_scores[1:]:  # Skip the book itself
            if count >= num_recommendations:
                break
            if i < len(db_books) and score > 0.1:
                book = db_books.iloc[i]
                recommendations.append({
                    'title': book['title'],
                    'author': book['author'],
                    'similarity_score': float(score),
                    'source': 'database'
                })
                count += 1
        
        log_debug(f"Generated {len(recommendations)} database recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': recommendations,
            'method': 'content_based'
        }
        
    except Exception as e:
        log_debug(f"Error in database recommendations: {str(e)}")
        return None

def recommend_books(book_title):
    """Main recommendation function - prioritizes CSV data when database is empty"""
    try:
        log_debug(f"Starting recommendation process for: '{book_title}'")
        
        results = {
            'original_input': book_title,
            'recommendations': [],
            'method_used': [],
            'debug_info': {}
        }
        
        # First, try to load CSV data
        books_df, ratings_df = load_csv_data()
        results['debug_info']['csv_loaded'] = books_df is not None
        results['debug_info']['ratings_loaded'] = ratings_df is not None
        
        if books_df is not None:
            results['debug_info']['csv_books_count'] = len(books_df)
            
            # Preprocess CSV data
            processed_data, similarity_data, method_type = preprocess_csv_data(books_df, ratings_df)
            
            if processed_data is not None and similarity_data is not None:
                results['debug_info']['csv_method'] = method_type
                
                if method_type == 'collaborative':
                    csv_results = get_csv_collaborative_recommendations(book_title, processed_data, similarity_data)
                else:
                    csv_results = get_csv_content_recommendations(book_title, processed_data, similarity_data)
                
                if csv_results:
                    results['recommendations'].extend(csv_results['recommendations'])
                    results['method_used'].append(method_type + '_csv')
                    results['debug_info']['csv_match'] = csv_results['matched_title']
        
        # Try database as fallback
        db_books = load_database_books()
        results['debug_info']['db_loaded'] = db_books is not None
        
        if db_books is not None and len(db_books) > 0:
            results['debug_info']['db_books_count'] = len(db_books)
            
            # Create content-based similarity for database books
            db_books['features'] = (
                db_books['title'].fillna('') + ' ' + 
                db_books['author'].fillna('') + ' ' + 
                db_books['description'].fillna('')
            )
            
            tfidf = TfidfVectorizer(max_features=1000, stop_words='english', lowercase=True)
            tfidf_matrix = tfidf.fit_transform(db_books['features'])
            similarity_matrix = cosine_similarity(tfidf_matrix)
            
            db_results = get_db_recommendations(book_title, db_books, similarity_matrix)
            if db_results:
                # Only add DB recommendations if we don't have enough from CSV
                if len(results['recommendations']) < 5:
                    results['recommendations'].extend(db_results['recommendations'])
                    results['method_used'].append('content_based_db')
                    results['debug_info']['db_match'] = db_results['matched_title']
        
        # Remove duplicates and limit results
        seen_titles = set()
        unique_recommendations = []
        for rec in results['recommendations']:
            if rec['title'] not in seen_titles:
                seen_titles.add(rec['title'])
                unique_recommendations.append(rec)
                if len(unique_recommendations) >= 6:
                    break
        
        results['recommendations'] = unique_recommendations
        
        if not results['recommendations']:
            log_debug("No recommendations found")
            return {
                'error': f"No recommendations found for '{book_title}'. The book might not be in our dataset or the title might need to be more specific.",
                'debug_info': results['debug_info']
            }
        
        log_debug(f"Successfully generated {len(results['recommendations'])} recommendations")
        return results
        
    except Exception as e:
        log_debug(f"Main function error: {str(e)}")
        return {
            'error': f"Error generating recommendations: {str(e)}",
            'debug_info': {'exception': str(e)}
        }

def main():
    try:
        if len(sys.argv) < 2:
            result = {"error": "Please provide a book name as an argument."}
        else:
            book_input = sys.argv[1]
            result = recommend_books(book_input)
        
        # CRITICAL: Only output JSON to stdout
        print(json.dumps(result, ensure_ascii=False, indent=None))
        
    except Exception as e:
        # Even errors should be valid JSON
        error_result = {
            'error': f"Script execution error: {str(e)}",
            'debug_info': {'exception': str(e)}
        }
        print(json.dumps(error_result, ensure_ascii=False, indent=None))

if __name__ == "__main__":
    main()