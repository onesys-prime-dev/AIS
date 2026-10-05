# Facial Recognition Setup Guide

Step-by-step setup for the in-house face recognition service
(`python-face-service/`) after `git pull`, including the two real gotchas
hit while setting this up: Windows Smart App Control blocking native Python
extensions, and Microsoft Store Python venvs silently breaking Windows
service registration.

## 1. Navigate to the service folder

**Where:** a regular terminal (Command Prompt or PowerShell), starting from
the repo root (`C:\xampp\htdocs\ais`).

```bat
cd python-face-service
```

## 2. Check what Python you have

**Where:** the same terminal, still inside `python-face-service`.

You need a **standalone install** (from python.org), not the Microsoft
Store version. The Store one works fine for steps 3–9 below, but will
silently break later if you try to run this as an always-on Windows
service (step 11).

```powershell
(Get-Item "C:\PythonXXX\python.exe").VersionInfo
```

If the install directory has `Lib\`, `Scripts\`, `DLLs\` folders directly
inside it, it's standalone — good. If you only have the Store version and
don't yet know whether you'll need the service later, it's simplest to
just install a standalone Python 3.10+ from python.org now.

## 3. Create the venv

**Where:** same terminal, inside `python-face-service`.

```bat
C:\PythonXXX\python.exe -m venv venv
venv\Scripts\activate
```

## 4. Install dependencies

**Where:** same terminal — the venv you just activated (prompt should show
`(venv)`).

```bat
pip install -r requirements.txt
```

## 5. If that fails with a DLL load error

```
ImportError: DLL load failed ... An Application Control policy has blocked this file
```

That's Windows **Smart App Control**, not a package problem:

1. **Where:** the **Windows Security** app (GUI, not the terminal) — search
   for it in the Start menu. Go to App & browser control → Smart App
   Control → **Off**.
2. **Where:** anywhere — restart the machine (required — the change
   doesn't apply until reboot).
3. ⚠️ **This is a one-way change** — Microsoft doesn't allow re-enabling it
   without reinstalling Windows. If that's not acceptable, WSL2 is the
   alternative (runs Linux, sidesteps Smart App Control entirely) — ask if
   you need those steps instead.
4. **Where:** back in the terminal from step 4, venv still active — retry
   `pip install -r requirements.txt`.

## 6. Download the model files

**Where:** same terminal, inside `python-face-service`.

Not in git — they're binaries.

```bat
mkdir models
curl -L -o models\face_detection_yunet_2023mar.onnx https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx
curl -L -o models\face_recognition_sface_2021dec.onnx https://github.com/opencv/opencv_zoo/raw/main/models/face_recognition_sface/face_recognition_sface_2021dec.onnx
```

## 7. Set up this service's `.env`

**Where:** same terminal, inside `python-face-service`, for the two
commands below. The `API_KEY` line itself you paste in with a text editor
(Notepad, VS Code, etc.) — open `python-face-service\.env` after copying it.

```bat
copy .env.example .env
php -r "echo bin2hex(random_bytes(24));"
```

Paste that generated value as `API_KEY` in `python-face-service\.env`.

## 8. Set up the main app's `.env` (if you don't already have one)

**Where:** same terminal for the commands; a text editor for the edit.

```bat
cd ..
copy env .env
```

Open `.env` in a text editor, find the `FaceRecognition.*` block, uncomment
it, and set `FaceRecognition.apiKey` to the **same** value generated in
step 7. Leave `FaceRecognition.enabled = false` for now.

## 9. Test it manually

**Where:** back in `python-face-service` — reuse the same terminal (`cd ..`
in step 8 took you to the repo root, so `cd` back in).

```bat
cd python-face-service
venv\Scripts\activate
uvicorn app:app --host 127.0.0.1 --port 8100
```

This terminal will sit here running the server — leave it open.

**Where:** open a **second, separate terminal window** for the health check
(the first one is busy running `uvicorn`):

```bat
curl http://127.0.0.1:8100/health
```

Should return `{"status":"ok","model_version":"opencv-sface"}`. Once
confirmed, go back to the first terminal and stop the server with `Ctrl+C`.

## 10. Run the DB migration

**Where:** a terminal at the repo root (`C:\xampp\htdocs\ais`) — either the
second terminal from step 9, or `cd ..` from `python-face-service` in the
first one.

Adds the `face_embeddings` table.

```bat
cd ..
php spark migrate
```

## 11. (Optional) Register as a Windows service via NSSM

**Where:** PowerShell or cmd, running **as Administrator** (right-click →
"Run as administrator") — registering a Windows service always needs
elevation. For always-on operation. See the **"Run (always-on Windows
service)"** section in `python-face-service/README.md` for the exact
commands (downloading `nssm.exe`, registering, and the fix if the service
fails to start due to a Store-Python venv).

## 12. Flip it on

**Where:** a text editor for the `.env` change; your web **browser** for
testing.

Set `FaceRecognition.enabled = true` in the main app's `.env`, then enroll
a face via `settings/face-recognition` in the browser and test login.
