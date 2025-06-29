import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.feature_extraction.text import TfidfVectorizer
import sys
import json
import mysql.connector
from mysql.connector import Error
import logging
from difflib import SequenceMatcher
import unicodedata
import re

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    stream=sys.stderr  
)

DB_CONFIG = {
    'host': 'localhost',
    'database': 'book',
    'user': 'root',
    'password': '',
    'charset': 'utf8mb4',
    'use_unicode': True,
    'collation': 'utf8mb4_unicode_ci'
}

def log_debug(message):
    logging.info(f"DEBUG: {message}")

def get_db_connection():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        # Set connection encoding
        connection.set_charset_collation('utf8mb4', 'utf8mb4_unicode_ci')
        log_debug("Database connection successful")
        return connection
    except Error as e:
        log_debug(f"Database connection failed: {str(e)}")
        return None

def normalize_title(title):
    """Normalize title for better matching"""
    if not title:
        return ""
    
    # Convert to string if not already
    title = str(title)
    
    # Normalize unicode characters
    title = unicodedata.normalize('NFKD', title)
    
    # Remove or replace problematic characters
    title = title.replace("'", "'")  # Replace curly apostrophe with straight
    title = title.replace("'", "'")  # Replace another curly apostrophe
    title = title.replace(""", '"')  # Replace curly quote
    title = title.replace(""", '"')  # Replace another curly quote
    title = title.replace("–", "-")  # Replace en dash
    title = title.replace("—", "-")  # Replace em dash
    
    # Remove extra whitespace
    title = re.sub(r'\s+', ' ', title).strip()
    
    return title

def load_books_from_database():
    try:
        connection = get_db_connection()
        if not connection:
            return None
        
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SET NAMES utf8mb4")
        cursor.execute("SET CHARACTER SET utf8mb4")
        
        query = """
        SELECT b.id, b.title, b.author, b.description, b.price, b.rating, 
               b.image, c.name as category_name, b.category_id
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.title IS NOT NULL AND b.author IS NOT NULL
        """
        cursor.execute(query)
        books = cursor.fetchall()
        
        cursor.close()
        connection.close()
        
        if books:
            # Normalize titles when creating DataFrame
            for book in books:
                book['title'] = normalize_title(book['title'])
                book['author'] = normalize_title(book['author'])
                if book['description']:
                    book['description'] = normalize_title(book['description'])
                if book['category_name']:
                    book['category_name'] = normalize_title(book['category_name'])
        
        log_debug(f"Loaded {len(books)} books from database")
        return pd.DataFrame(books) if books else None
        
    except Error as e:
        log_debug(f"Database error: {str(e)}")
        return None

def load_user_interactions():
    try:
        connection = get_db_connection()
        if not connection:
            return None
        
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SET NAMES utf8mb4")
        cursor.execute("SET CHARACTER SET utf8mb4")
        
        # Enhanced interaction scoring
        query = """
        SELECT DISTINCT u.id as user_id, b.id as book_id, b.title, 
               1.0 as interaction_score
        FROM users u
        JOIN cart c ON u.id = c.user_id
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN books b ON ci.book_id = b.id
        
        UNION ALL
        
        SELECT DISTINCT u.id as user_id, b.id as book_id, b.title,
               3.0 as interaction_score
        FROM users u
        JOIN orders o ON u.id = o.user_id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN books b ON oi.book_id = b.id
        """
        
        cursor.execute(query)
        interactions = cursor.fetchall()
        
        cursor.close()
        connection.close()
        
        if interactions:
            # Normalize titles in interactions
            for interaction in interactions:
                interaction['title'] = normalize_title(interaction['title'])
        
        log_debug(f"Loaded {len(interactions)} user interactions")
        return pd.DataFrame(interactions) if interactions else None
        
    except Error as e:
        log_debug(f"Error loading interactions: {str(e)}")
        return None

def create_weighted_features(books_df):
    """Create weighted content features giving more importance to title and author"""
    books_df['features'] = (
        (books_df['title'].fillna('').astype(str) + ' ') * 3 +  # Title gets 3x weight
        (books_df['author'].fillna('').astype(str) + ' ') * 2 +  # Author gets 2x weight
        books_df['category_name'].fillna('').astype(str) + ' ' +
        books_df['description'].fillna('').astype(str)
    )
    return books_df

def calculate_rating_weight(rating):
    """Calculate weight based on rating (0-5 scale)"""
    if pd.isna(rating) or rating == 0:
        return 0.5  # Default weight for unrated books
    return float(rating) / 5.0

def find_similar_title(input_title, title_list, threshold=0.5):
    """Enhanced title matching with better normalization and higher threshold"""
    
    # Normalize input title
    input_title_normalized = normalize_title(input_title).lower()
    log_debug(f"Looking for similar title to: '{input_title}' -> normalized: '{input_title_normalized}'")
    
    # Create normalized title list
    normalized_titles = [(normalize_title(title).lower(), title) for title in title_list]
    
    # 1. Exact match (normalized)
    for normalized_title, original_title in normalized_titles:
        if normalized_title == input_title_normalized:
            log_debug(f"Exact match found: '{original_title}'")
            return original_title
    
    # 2. Partial match - input contains title
    for normalized_title, original_title in normalized_titles:
        if normalized_title and normalized_title in input_title_normalized:
            log_debug(f"Partial match found (input contains title): '{original_title}'")
            return original_title
    
    # 3. Partial match - title contains input
    for normalized_title, original_title in normalized_titles:
        if input_title_normalized and input_title_normalized in normalized_title:
            log_debug(f"Partial match found (title contains input): '{original_title}'")
            return original_title
    
    # 4. Fuzzy matching with higher threshold
    best_match = None
    best_ratio = 0
    
    for normalized_title, original_title in normalized_titles:
        if normalized_title:  # Skip empty titles
            ratio = SequenceMatcher(None, input_title_normalized, normalized_title).ratio()
            if ratio > best_ratio and ratio >= threshold:
                best_ratio = ratio
                best_match = original_title
    
    if best_match:
        log_debug(f"Fuzzy match found: '{best_match}' (ratio: {best_ratio:.2f})")
    else:
        log_debug("No similar title found")
        log_debug(f"Input: '{input_title_normalized}'")
        sample_titles = [title for _, title in normalized_titles[:5]]
        log_debug(f"Sample available titles: {sample_titles}")
    
    return best_match

def get_collaborative_recommendations(book_title, books_df, interactions_df, num_recommendations=6):
    if interactions_df is None or len(interactions_df) == 0:
        log_debug("No interaction data available for collaborative filtering")
        return None
    
    try:
        title_list = books_df['title'].tolist()
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            return None
        
        target_book = books_df[books_df['title'] == matched_title].iloc[0]
        target_book_id = target_book['id']
        
        user_book_matrix = interactions_df.pivot_table(
            index='user_id', 
            columns='book_id', 
            values='interaction_score', 
            fill_value=0
        )
        
        if target_book_id not in user_book_matrix.columns:
            log_debug("Target book not found in interaction matrix")
            return None
        
        book_similarity = cosine_similarity(user_book_matrix.T)
        book_indices = {book_id: idx for idx, book_id in enumerate(user_book_matrix.columns)}
        
        if target_book_id not in book_indices:
            return None
        
        target_idx = book_indices[target_book_id]
        similarities = book_similarity[target_idx]
        
        similar_books = []
        for book_id, similarity_score in zip(user_book_matrix.columns, similarities):
            # Increased threshold for better quality
            if book_id != target_book_id and similarity_score > 0.2:
                book_info = books_df[books_df['id'] == book_id]
                if len(book_info) > 0:
                    book_info = book_info.iloc[0]
                    # Calculate rating weight
                    rating_weight = calculate_rating_weight(book_info['rating'])
                    # Combine similarity with rating weight
                    weighted_score = float(similarity_score) * rating_weight
                    
                    similar_books.append({
                        'book_id': int(book_id),
                        'title': str(book_info['title']),
                        'author': str(book_info['author']),
                        'description': str(book_info['description']) if book_info['description'] else '',
                        'price': float(book_info['price']) if book_info['price'] else 0,
                        'rating': float(book_info['rating']) if book_info['rating'] else 0,
                        'image': str(book_info['image']) if book_info['image'] else '',
                        'category_name': str(book_info['category_name']) if book_info['category_name'] else '',
                        'similarity_score': float(similarity_score),
                        'weighted_score': weighted_score,
                        'source': 'collaborative'
                    })
        
        # Sort by weighted score instead of just similarity
        similar_books.sort(key=lambda x: x['weighted_score'], reverse=True)
        
        log_debug(f"Generated {len(similar_books[:num_recommendations])} collaborative recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': similar_books[:num_recommendations],
            'method': 'collaborative_filtering'
        }
        
    except Exception as e:
        log_debug(f"Error in collaborative filtering: {str(e)}")
        return None

def get_content_recommendations(book_title, books_df, num_recommendations=6):
    try:
        books_df = create_weighted_features(books_df)
        
        title_list = books_df['title'].tolist()
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            return None
        
        # Enhanced TF-IDF with better parameters
        tfidf = TfidfVectorizer(
            max_features=2000,  # Increased features
            stop_words='english', 
            lowercase=True,
            ngram_range=(1, 2),  # Include bigrams
            min_df=2,  # Ignore very rare terms
            max_df=0.8  # Ignore very common terms
        )
        tfidf_matrix = tfidf.fit_transform(books_df['features'])
        
        matched_idx = books_df[books_df['title'] == matched_title].index[0]
        
        similarity_matrix = cosine_similarity(tfidf_matrix)
        scores = list(enumerate(similarity_matrix[matched_idx]))
        sorted_scores = sorted(scores, key=lambda x: x[1], reverse=True)
        
        recommendations = []
        count = 0
        for i, score in sorted_scores[1:]:
            if count >= num_recommendations:
                break
            # Increased threshold for better quality
            if score > 0.3:
                book = books_df.iloc[i]
                # Calculate rating weight
                rating_weight = calculate_rating_weight(book['rating'])
                # Combine similarity with rating weight
                weighted_score = float(score) * rating_weight
                
                recommendations.append({
                    'book_id': int(book['id']),
                    'title': str(book['title']),
                    'author': str(book['author']),
                    'description': str(book['description']) if book['description'] else '',
                    'price': float(book['price']) if book['price'] else 0,
                    'rating': float(book['rating']) if book['rating'] else 0,
                    'image': str(book['image']) if book['image'] else '',
                    'category_name': str(book['category_name']) if book['category_name'] else '',
                    'similarity_score': float(score),
                    'weighted_score': weighted_score,
                    'source': 'content_based'
                })
                count += 1
        
        # Sort by weighted score
        recommendations.sort(key=lambda x: x['weighted_score'], reverse=True)
        
        log_debug(f"Generated {len(recommendations)} content-based recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': recommendations,
            'method': 'content_based'
        }
        
    except Exception as e:
        log_debug(f"Error in content-based filtering: {str(e)}")
        return None

def get_category_recommendations(book_title, books_df, num_recommendations=3):
    try:
        title_list = books_df['title'].tolist()
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            return None
        
        target_book = books_df[books_df['title'] == matched_title].iloc[0]
        target_category = target_book['category_id']
        
        if pd.isna(target_category):
            return None
        
        same_category_books = books_df[
            (books_df['category_id'] == target_category) & 
            (books_df['title'] != matched_title)
        ]
        
        if len(same_category_books) == 0:
            return None
        
        # Sort by rating within same category
        same_category_books = same_category_books.sort_values('rating', ascending=False, na_last=True)
        
        recommendations = []
        for _, book in same_category_books.head(num_recommendations).iterrows():
            rating_weight = calculate_rating_weight(book['rating'])
            weighted_score = 0.8 * rating_weight  # Base category similarity * rating weight
            
            recommendations.append({
                'book_id': int(book['id']),
                'title': str(book['title']),
                'author': str(book['author']),
                'description': str(book['description']) if book['description'] else '',
                'price': float(book['price']) if book['price'] else 0,
                'rating': float(book['rating']) if book['rating'] else 0,
                'image': str(book['image']) if book['image'] else '',
                'category_name': str(book['category_name']) if book['category_name'] else '',
                'similarity_score': 0.8,
                'weighted_score': weighted_score,
                'source': 'same_category'
            })
        
        log_debug(f"Generated {len(recommendations)} category-based recommendations")
        return {
            'matched_title': matched_title,
            'recommendations': recommendations,
            'method': 'category_based'
        }
        
    except Exception as e:
        log_debug(f"Error in category-based filtering: {str(e)}")
        return None

def recommend_books(book_title):
    try:
        # Normalize input title
        book_title = normalize_title(book_title)
        log_debug(f"Starting recommendation for: '{book_title}'")
        
        books_df = load_books_from_database()
        if books_df is None or len(books_df) == 0:
            return {
                'error': 'No books found in database',
                'debug_info': {'books_count': 0}
            }
        
        interactions_df = load_user_interactions()
        
        results = {
            'original_input': book_title,
            'recommendations': [],
            'method_used': [],
            'debug_info': {
                'books_count': len(books_df),
                'has_interactions': interactions_df is not None and len(interactions_df) > 0
            }
        }
        
        # Try collaborative filtering first
        collaborative_results = get_collaborative_recommendations(book_title, books_df, interactions_df)
        if collaborative_results and len(collaborative_results['recommendations']) > 0:
            results['recommendations'].extend(collaborative_results['recommendations'])
            results['method_used'].append('collaborative')
            results['debug_info']['matched_title'] = collaborative_results['matched_title']
        
        # Fill remaining slots with content-based recommendations
        if len(results['recommendations']) < 6:
            content_results = get_content_recommendations(book_title, books_df)
            if content_results:
                for rec in content_results['recommendations']:
                    if len(results['recommendations']) >= 6:
                        break
                    # Avoid duplicates
                    if not any(r['book_id'] == rec['book_id'] for r in results['recommendations']):
                        results['recommendations'].append(rec)
                results['method_used'].append('content_based')
                if 'matched_title' not in results['debug_info']:
                    results['debug_info']['matched_title'] = content_results['matched_title']
        
        # Fill remaining slots with category-based recommendations
        if len(results['recommendations']) < 6:
            category_results = get_category_recommendations(book_title, books_df)
            if category_results:
                for rec in category_results['recommendations']:
                    if len(results['recommendations']) >= 6:
                        break
                    # Avoid duplicates
                    if not any(r['book_id'] == rec['book_id'] for r in results['recommendations']):
                        results['recommendations'].append(rec)
                results['method_used'].append('category_based')
        
        # Final sort by weighted score for best quality recommendations first
        results['recommendations'].sort(key=lambda x: x.get('weighted_score', x.get('similarity_score', 0)), reverse=True)
        
        if not results['recommendations']:
            return {
                'error': f"No recommendations found for '{book_title}'",
                'debug_info': results['debug_info']
            }
        
        log_debug(f"Generated {len(results['recommendations'])} total recommendations")
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
        
        # Force UTF-8 encoding for output
        import codecs
        if hasattr(sys.stdout, 'reconfigure'):
            sys.stdout.reconfigure(encoding='utf-8')
        else:
            sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer)
        
        print(json.dumps(result, ensure_ascii=False, indent=None))
        
    except Exception as e:
        error_result = {
            'error': f"Script execution error: {str(e)}",
            'debug_info': {'exception': str(e)}
        }
        try:
            print(json.dumps(error_result, ensure_ascii=False, indent=None))
        except:
            # Fallback to ASCII-safe output
            print(json.dumps(error_result, ensure_ascii=True, indent=None))

if __name__ == "__main__":
    main()