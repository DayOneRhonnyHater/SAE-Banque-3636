# FinBot/python/chatbot.py
import sys
import json
import os
import google.generativeai as genai

# Configurer l'API avec la clé
api_key = os.environ.get('GEMINI_API_KEY')
genai.configure(api_key=api_key)

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
