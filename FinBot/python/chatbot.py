from google.generativeai import GenerativeModel, configure
import sys
import json
import os

# Configuration
configure(api_key="AIzaSyCJlDl3FQJKgqql8rK29tAwsU08xxtqcUA")  # Clé dans .env
model = GenerativeModel('gemini-2.0-flash')  # Modèle moderne

def generate_response(message, context=None):
    try:
        system_prompt = """Vous êtes FinBot, assistant bancaire expert. Règles :
        - Réponses concises (<100 mots)
        - Exclusivement en français
        - Tutoiement autorisé
        - Ne pas inventer de produits
        - Orienter vers conseiller humain si besoin complexe"""
        
        full_prompt = f"{system_prompt}\n\nContexte : {json.dumps(context)}\nQuestion : {message}"
        
        response = model.generate_content(full_prompt)
        return response.text
    except Exception as e:
        return f"Erreur Gemini : {str(e)}"

if __name__ == "__main__":
    try:
        input_data = json.loads(sys.argv[1])
        message = input_data.get('message','')
        context = input_data.get('context', {})
        
        response = generate_response(message, context)
        print(json.dumps({"response": response}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
