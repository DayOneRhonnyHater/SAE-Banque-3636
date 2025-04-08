# FinBot/python/chatbot.py
import sys
import json
# Configurer l'API avec la clé
import os
from dotenv import load_dotenv
import google.generativeai as genai

# Charger le .env depuis le dossier racine
env_path = os.path.join(os.path.dirname(__file__), '../../.env')
load_dotenv(env_path)  # Correction cruciale du chemin

# Configuration explicite
genai.configure(api_key=os.getenv('GOOGLE_API_KEY'))
# Fonction principale pour générer une réponse
def generate_response(message, context=None):
    model = genai.GenerativeModel('gemini-pro')
    
    # Préparer le message avec contexte si disponible
    prompt = message
    if context:
        prompt = f"Contexte: {json.dumps(context)}\n\nQuestion: {message}"
    
    # Générer la réponse
    response = model.generate_content(prompt)
    
    return response.text

# Point d'entrée pour l'appel depuis PHP
if __name__ == "__main__":
    # Récupérer les arguments
    input_data = json.loads(sys.argv[1])
    message = input_data.get('message', '')
    context = input_data.get('context', {})
    
    # Générer et retourner la réponse
    response = generate_response(message, context)
    print(json.dumps({"response": response}))
