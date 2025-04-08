# test_credentials.py
import os
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '../../.env'))
print("GOOGLE_API_KEY:", os.getenv('GOOGLE_API_KEY') is not None)
