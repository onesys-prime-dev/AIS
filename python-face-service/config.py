import os

from dotenv import load_dotenv

load_dotenv()

API_KEY = os.environ.get("API_KEY", "")
HOST = os.environ.get("HOST", "127.0.0.1")
PORT = int(os.environ.get("PORT", "8100"))
MODEL_NAME = os.environ.get("MODEL_NAME", "opencv-sface")
