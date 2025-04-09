from google import genai
import sys
import json
import os

# Configuration
GEMINI_MODEL = "gemini-2.0-flash"
MAX_RETRIES = 3

def initialize_client():
    """Initialise le client Gemini avec la clé API"""
    api_key = "AIzaSyCJlDl3FQJKgqql8rK29tAwsU08xxtqcUA"
    if not api_key:
        raise ValueError("Clé API Gemini manquante dans les variables d'environnement")
    return genai.Client(api_key=api_key)

def generate_response(client, message, context=None):
    """Génère une réponse avec contexte et gestion d'erreurs"""
    try:
        system_prompt = """Vous êtes FinBot, assistant bancaire expert. Règles :
        - Réponses concises (<100 mots)
        - Exclusivement en français
        - Donnez des informations exactes
        - Orientez vers un conseiller si nécessaire"""
        
        full_prompt = f"{system_prompt}\n\nContexte: {json.dumps(context)}\nQuestion: {message}"
        
        response = client.models.generate_content(
            model=GEMINI_MODEL,
            contents=full_prompt,
            generation_config={
                "temperature": 0.3,
                "max_output_tokens": 512
            }
        )
        return response.text.strip()
    except Exception as e:
        return f"Erreur Gemini : {str(e)}"

if __name__ == "__main__":
    try:
        client = initialize_client()
        input_data = json.loads(sys.argv[1])
        message = input_data.get('message', '')
        context = input_data.get('context', {})
        
        response = generate_response(client, message, context)
        print(json.dumps({"response": response}))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))
