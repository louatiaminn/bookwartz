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

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    stream=sys.stderr  
)

DB_CONFIG = {
    'host': 'localhost',
    'database': 'book',
    'user': 'root',
    'password': ''
}

def log_debug(message):
    logging.info(f"DEBUG: {message}")

def get_db_connection():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        log_debug("Database connection successful")
        return connection
    except Error as e:
        log_debug(f"Database connection failed: {str(e)}")
        return None

def load_books_from_database():
    try:
        connection = get_db_connection()
        if not connection:
            return None
        
        cursor = connection.cursor(dictionary=True)
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
        
        query = """
        SELECT DISTINCT u.id as user_id, b.id as book_id, b.title, 
               1 as interaction_score
        FROM users u
        JOIN cart c ON u.id = c.user_id
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN books b ON ci.book_id = b.id
        
        UNION ALL
        
        SELECT DISTINCT u.id as user_id, b.id as book_id, b.title,
               2 as interaction_score
        FROM users u
        JOIN orders o ON u.id = o.user_id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN books b ON oi.book_id = b.id
        """
        
        cursor.execute(query)
        interactions = cursor.fetchall()
        
        cursor.close()
        connection.close()
        
        log_debug(f"Loaded {len(interactions)} user interactions")
        return pd.DataFrame(interactions) if interactions else None
        
    except Error as e:
        log_debug(f"Error loading interactions: {str(e)}")
        return None

def create_content_features(books_df):
    books_df['features'] = (
        books_df['title'].fillna('') + ' ' + 
        books_df['author'].fillna('') + ' ' + 
        books_df['description'].fillna('') + ' ' + 
        books_df['category_name'].fillna('')
    )
    return books_df

def find_similar_title(input_title, title_list, threshold=0.4):
    input_title_lower = input_title.lower().strip()
    log_debug(f"Looking for similar title to: '{input_title}'")
    
    for title in title_list:
        if title.lower().strip() == input_title_lower:
            log_debug(f"Exact match found: '{title}'")
            return title
    
    for title in title_list:
        if input_title_lower in title.lower():
            log_debug(f"Partial match found: '{title}'")
            return title
    
    for title in title_list:
        if title.lower().strip() in input_title_lower:
            log_debug(f"Reverse partial match found: '{title}'")
            return title
    
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
            if book_id != target_book_id and similarity_score > 0.1:
                book_info = books_df[books_df['id'] == book_id]
                if len(book_info) > 0:
                    book_info = book_info.iloc[0]
                    similar_books.append({
                        'book_id': int(book_id),
                        'title': book_info['title'],
                        'author': book_info['author'],
                        'description': book_info['description'],
                        'price': float(book_info['price']) if book_info['price'] else 0,
                        'image': book_info['image'],
                        'category_name': book_info['category_name'],
                        'similarity_score': float(similarity_score),
                        'source': 'collaborative'
                    })
        
        similar_books.sort(key=lambda x: x['similarity_score'], reverse=True)
        
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
        books_df = create_content_features(books_df)
        
        title_list = books_df['title'].tolist()
        matched_title = find_similar_title(book_title, title_list)
        
        if matched_title is None:
            return None
        
        tfidf = TfidfVectorizer(max_features=1000, stop_words='english', lowercase=True)
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
            if score > 0.1:
                book = books_df.iloc[i]
                recommendations.append({
                    'book_id': int(book['id']),
                    'title': book['title'],
                    'author': book['author'],
                    'description': book['description'],
                    'price': float(book['price']) if book['price'] else 0,
                    'image': book['image'],
                    'category_name': book['category_name'],
                    'similarity_score': float(score),
                    'source': 'content_based'
                })
                count += 1
        
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
        
        recommendations = []
        for _, book in same_category_books.head(num_recommendations).iterrows():
            recommendations.append({
                'book_id': int(book['id']),
                'title': book['title'],
                'author': book['author'],
                'description': book['description'],
                'price': float(book['price']) if book['price'] else 0,
                'image': book['image'],
                'category_name': book['category_name'],
                'similarity_score': 0.8,
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
        
        collaborative_results = get_collaborative_recommendations(book_title, books_df, interactions_df)
        if collaborative_results and len(collaborative_results['recommendations']) > 0:
            results['recommendations'].extend(collaborative_results['recommendations'])
            results['method_used'].append('collaborative')
            results['debug_info']['matched_title'] = collaborative_results['matched_title']
        
        if len(results['recommendations']) < 6:
            content_results = get_content_recommendations(book_title, books_df)
            if content_results:
                for rec in content_results['recommendations']:
                    if len(results['recommendations']) >= 6:
                        break
                    if not any(r['book_id'] == rec['book_id'] for r in results['recommendations']):
                        results['recommendations'].append(rec)
                results['method_used'].append('content_based')
                if 'matched_title' not in results['debug_info']:
                    results['debug_info']['matched_title'] = content_results['matched_title']
        
        if len(results['recommendations']) < 6:
            category_results = get_category_recommendations(book_title, books_df)
            if category_results:
                for rec in category_results['recommendations']:
                    if len(results['recommendations']) >= 6:
                        break
                    if not any(r['book_id'] == rec['book_id'] for r in results['recommendations']):
                        results['recommendations'].append(rec)
                results['method_used'].append('category_based')
        
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
        
        print(json.dumps(result, ensure_ascii=False, indent=None))
        
    except Exception as e:
        error_result = {
            'error': f"Script execution error: {str(e)}",
            'debug_info': {'exception': str(e)}
        }
        print(json.dumps(error_result, ensure_ascii=False, indent=None))

if __name__ == "__main__":
    main()