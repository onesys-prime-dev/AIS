# AIS Face Recognition Service

Stateless face-embedding extractor used by `app/Services/FaceRecognitionService.php`.
Given a face image, returns a 128-float embedding vector. Nothing else — it
never touches the app's database and never decides identity. Runs entirely
offline on `127.0.0.1`; no data leaves this machine.

Uses OpenCV Zoo's **YuNet** (face detection) + **SFace** (face recognition/
embedding) models — both MIT-licensed, official, and run through
`opencv-python-headless`'s own bundled DNN backend. No `insightface` package,
no separate `onnxruntime` dependency, no third-party compiled binaries beyond
what `opencv-python-headless` itself ships.

Model files live in `models/` (downloaded from `opencv/opencv_zoo` on GitHub):
- `face_detection_yunet_2023mar.onnx`
- `face_recognition_sface_2021dec.onnx`

## Setup

**Use a standalone Python install (e.g. from python.org), not the Microsoft
Store version.** The Store's `python.exe` is an app-execution-alias stub —
it works fine interactively, but a venv built from it CANNOT be launched by a
Windows service (NSSM/SCM fails with "Unable to create process ..." pointing
at a `WindowsApps\...` path). A standalone install has a real `Lib`/`Scripts`/
`DLLs` layout under something like `C:\PythonXXX\`, not `WindowsApps`. Confirm
which you're using with `(Get-Item <path-to-python.exe>).VersionInfo` and by
checking the install directory has those folders directly in it.

Any Python 3.10+ standalone install works — this stack (`opencv-python-headless`,
`fastapi`, `numpy`, no `insightface`/`onnxruntime`) has solid prebuilt-wheel
coverage across recent versions.

> **Note on Windows Smart App Control:** if native extensions (e.g.
> `pydantic_core`) fail to import with `ImportError: DLL load failed ... An
> Application Control policy has blocked this file`, that's Windows Smart App
> Control, not a package problem. Check Event Viewer →
> `Microsoft-Windows-CodeIntegrity/Operational` for a matching block event to
> confirm. Fix: Windows Security → App & browser control → Smart App Control →
> Off, then restart. This is a one-way change (cannot be re-enabled without
> reinstalling Windows) — see the project's face-recognition planning notes
> for the WSL2 alternative if that trade-off isn't acceptable.

```bat
cd python-face-service
C:\PythonXXX\python.exe -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

**Model files aren't in git** (they're binaries, `.gitignore`'d) — download
them once:

```bat
mkdir models
curl -L -o models\face_detection_yunet_2023mar.onnx https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx
curl -L -o models\face_recognition_sface_2021dec.onnx https://github.com/opencv/opencv_zoo/raw/main/models/face_recognition_sface/face_recognition_sface_2021dec.onnx
```

**`.env` also isn't in git** (it holds a secret) — copy the template and fill
it in:

```bat
copy .env.example .env
php -r "echo bin2hex(random_bytes(24));"
```

Paste that generated value as `API_KEY` in this folder's `.env`, **and** as
`FaceRecognition.apiKey` in the main app's `.env` (copy the repo-root `env`
template to `.env` first if you don't have one yet) — the two must match, or
every request from PHP to this service will get `401 unauthorized`. Also set
`FaceRecognition.baseUrl` there if you're not using the default `127.0.0.1:8100`.

## Run (development / manual)

```bat
venv\Scripts\activate
uvicorn app:app --host 127.0.0.1 --port 8100
```

Confirm it's up:

```bat
curl http://127.0.0.1:8100/health
```

## Run (always-on Windows service, via NSSM)

`nssm.exe` isn't in git (it's a binary) — download the portable build once
(PowerShell):

```powershell
Invoke-WebRequest -Uri "https://nssm.cc/release/nssm-2.24.zip" -OutFile "nssm.zip"
Expand-Archive -Path "nssm.zip" -DestinationPath "nssm_extracted" -Force
Copy-Item "nssm_extracted\nssm-2.24\win64\nssm.exe" "nssm.exe"
Remove-Item -Recurse -Force "nssm.zip", "nssm_extracted"
```

Registering a service always requires an elevated (Run as Administrator)
terminal — this cannot be scripted around.

1. Open PowerShell/cmd **as Administrator**.
2. Register and start the service:

```bat
C:\xampp\htdocs\ais\python-face-service\nssm.exe install AISFaceRecognition "C:\xampp\htdocs\ais\python-face-service\venv\Scripts\python.exe" "-m uvicorn app:app --host 127.0.0.1 --port 8100"
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition AppDirectory "C:\xampp\htdocs\ais\python-face-service"
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition AppExit Default Restart
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition Start SERVICE_AUTO_START
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition AppStdout "C:\xampp\htdocs\ais\python-face-service\service-stdout.log"
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition AppStderr "C:\xampp\htdocs\ais\python-face-service\service-stderr.log"
C:\xampp\htdocs\ais\python-face-service\nssm.exe start AISFaceRecognition
```

(The `AppStdout`/`AppStderr` lines aren't required, but are worth setting up
front — they're what actually reveals a startup crash, since `Get-Service`
alone just shows "Paused" with no explanation.)

3. Confirm: `curl http://127.0.0.1:8100/health`

**If the service won't start** (`Get-Service` shows `Paused`, or
`service-stderr.log` says `Unable to create process using
"...WindowsApps...python.exe"`) — the venv was built from the Microsoft Store
Python. Rebuild it from a standalone install (see Setup above), then repoint
the existing service instead of re-registering it:

```bat
C:\xampp\htdocs\ais\python-face-service\nssm.exe set AISFaceRecognition Application "C:\xampp\htdocs\ais\python-face-service\venv\Scripts\python.exe"
C:\xampp\htdocs\ais\python-face-service\nssm.exe restart AISFaceRecognition
```

Stop/remove if needed: `nssm.exe stop AISFaceRecognition` / `nssm.exe remove AISFaceRecognition confirm`.

## API

### `POST /embed`

Headers: `X-API-Key: <shared secret>`
Body: `{"image": "<base64 JPEG/PNG, data URL or raw base64>"}`

Success (200):
```json
{
  "embedding": [0.0123, -0.0456, ...],
  "model_version": "opencv-sface",
  "quality_score": 0.95,
  "face_count": 1
}
```

Failure (422): `{"error": "no_face_detected"}` or `{"error": "multiple_faces_detected"}`
Failure (400): `{"error": "missing_image"}` or `{"error": "invalid_image_data"}`
Failure (401): `{"error": "unauthorized"}` — missing/wrong `X-API-Key`

### `GET /health`

Unauthenticated. `{"status": "ok", "model_version": "opencv-sface"}`

## Similarity threshold

`FaceRecognition.similarityThreshold` in the PHP app's `.env` gates whether a
match counts as a successful login. It must NOT be copied from the old
CompreFace value (0.85) — that was calibrated for a different embedding
space. To (re)calibrate:

1. Enroll a couple of test accounts (`settings/face-recognition`, 2-3 captures each).
2. Attempt verification with the correct account and with a different
   enrolled account, logging the `similarity` value `FaceRecognitionService::recognize()`
   returns for each attempt (temporarily log it, or inspect via a debugger).
3. Pick a threshold between the highest impostor (wrong-person) score and the
   lowest genuine (right-person) score, biased toward the impostor side —
   false accepts are worse than asking someone to retry.
4. Set `FaceRecognition.similarityThreshold` in the main app's `.env` accordingly.

A same-photo re-embed (sanity check during development) should score very
close to 1.0 cosine similarity — confirmed during initial setup.

## Troubleshooting

- **`ImportError: DLL load failed ... Application Control policy`** — see the
  Smart App Control note in Setup above.
- **`pip install` tries to compile numpy/opencv from source** — you're likely
  on a Python version without prebuilt wheels yet for one of these packages;
  try a more recent or slightly older standalone Python 3.10+.
- **Service shows `Paused` in `Get-Service`, or `service-stderr.log` says
  `Unable to create process using "...WindowsApps...python.exe"`** — the venv
  was built from the Microsoft Store Python (an app-execution-alias stub that
  services can't launch). Rebuild the venv from a standalone install — see
  the NSSM section above for how to repoint an already-registered service.
- **`no_face_detected` on a photo that clearly has a face** — YuNet needs a
  reasonably front-facing, reasonably lit face; low-res/heavily-cropped/odd-angle
  photos can fail detection even when the file itself is a valid image.
- **`/health` returns nothing / connection refused** — the service isn't
  running, or `FaceRecognition.baseUrl` in the PHP app's `.env` doesn't match
  the port this service is bound to.
