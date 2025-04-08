# test_api.py
import google.generativeai as genai
genai.configure(api_key=os.getenv('GOOGLE_API_KEY'))

model = genai.GenerativeModel('gemini-pro')
print(model.generate_content("Hello").text)

