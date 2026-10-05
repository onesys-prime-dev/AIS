"""
Stateless face-embedding extractor for the AIS in-house face recognition
replacement. This service does exactly one thing: turn a face image into an
embedding vector. It never touches a database and never decides identity —
all enrollment storage and match decisions live in the PHP app
(app/Services/FaceRecognitionService.php). Runs fully offline on localhost.

Uses OpenCV Zoo's YuNet (detection) + SFace (recognition) models, both
MIT-licensed, run through opencv-python-headless's own bundled DNN backend —
no third-party compiled packages, no separate onnxruntime dependency.
"""

import base64
import binascii
import logging
import os

import cv2
import numpy as np
from fastapi import FastAPI, Header, HTTPException, Request
from fastapi.responses import JSONResponse

from config import API_KEY, MODEL_NAME

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("face-service")

app = FastAPI(title="AIS Face Recognition Service")

MODELS_DIR = os.path.join(os.path.dirname(__file__), "models")
DETECTOR_PATH = os.path.join(MODELS_DIR, "face_detection_yunet_2023mar.onnx")
RECOGNIZER_PATH = os.path.join(MODELS_DIR, "face_recognition_sface_2021dec.onnx")

_detector: cv2.FaceDetectorYN | None = None
_recognizer: cv2.FaceRecognizerSF | None = None


def get_models():
    global _detector, _recognizer
    if _detector is None or _recognizer is None:
        logger.info("Loading YuNet + SFace models...")
        _detector = cv2.FaceDetectorYN_create(DETECTOR_PATH, "", (320, 320))
        _recognizer = cv2.FaceRecognizerSF_create(RECOGNIZER_PATH, "")
        logger.info("Models loaded.")
    return _detector, _recognizer


@app.on_event("startup")
def warm_up() -> None:
    # Load models at startup rather than on the first request, so the first
    # real login/enrollment isn't the one paying the load cost.
    get_models()


def decode_image(image_data_url: str) -> np.ndarray:
    raw = image_data_url.strip()
    if "," in raw:
        raw = raw.split(",", 1)[1]

    try:
        decoded = base64.b64decode(raw, validate=True)
    except (binascii.Error, ValueError):
        raise HTTPException(status_code=400, detail={"error": "invalid_image_data"})

    buffer = np.frombuffer(decoded, dtype=np.uint8)
    image = cv2.imdecode(buffer, cv2.IMREAD_COLOR)

    if image is None:
        raise HTTPException(status_code=400, detail={"error": "invalid_image_data"})

    return image


def require_api_key(x_api_key: str | None) -> None:
    if not API_KEY:
        raise HTTPException(status_code=500, detail={"error": "server_not_configured"})

    if not x_api_key or x_api_key != API_KEY:
        raise HTTPException(status_code=401, detail={"error": "unauthorized"})


@app.get("/health")
def health():
    return {"status": "ok", "model_version": MODEL_NAME}


@app.post("/embed")
async def embed(request: Request, x_api_key: str | None = Header(default=None)):
    require_api_key(x_api_key)

    body = await request.json()
    image_data_url = body.get("image", "")

    if not image_data_url:
        raise HTTPException(status_code=400, detail={"error": "missing_image"})

    image = decode_image(image_data_url)

    detector, recognizer = get_models()
    detector.setInputSize((image.shape[1], image.shape[0]))
    _, faces = detector.detect(image)

    if faces is None or len(faces) == 0:
        return JSONResponse(status_code=422, content={"error": "no_face_detected"})

    if len(faces) > 1:
        return JSONResponse(status_code=422, content={"error": "multiple_faces_detected"})

    face = faces[0]

    # face row: [x, y, w, h, 5 landmark (x,y) pairs..., detection_score]
    aligned = recognizer.alignCrop(image, face)
    feature = recognizer.feature(aligned)  # 128-d; PHP computes true cosine similarity, so normalization here doesn't matter

    embedding = feature.flatten().astype(float).tolist()
    quality_score = float(face[-1])

    return {
        "embedding": embedding,
        "model_version": MODEL_NAME,
        "quality_score": quality_score,
        "face_count": 1,
    }
