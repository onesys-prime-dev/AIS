# VPS face recognition service setup

How `python-face-service` is set up to run continuously on the VPS, and
auto-restart on every deploy. This is one-time, by-hand setup — it is **not**
automated by any workflow or script in this repo, and must be repeated for
every separate site copy on the VPS (staging, testing, and eventually
production each need their own copy of all three pieces below).

`deploy.sh` (invoked by the old `deploy.yml`) assumes all of this already
exists; it only restarts the service and checks its health, it never creates
any of it. The current `deploy-reusable.yml` doesn't touch the service at all
(see the note at the bottom).

## Per-site values

| Site | Directory | systemd unit | Linux user | Port |
|---|---|---|---|---|
| staging | `/home/onesys/htdocs/AIS` | `ais-face-service` | `onesys` | 8100 |
| testing | `/home/onesystesting/htdocs/AIS-testing` | `ais-face-service-testing` | `onesystesting` | 8101 |

`deploy.sh` derives the unit name and port itself (from the site's directory
name, and from that site's own `python-face-service/.env` `PORT`), so a new
site copy doesn't need the script changed — only the three things below set
up for it.

## 1. venv, dependencies, and models

Inside that site's `python-face-service/` directory, as that site's Linux
user:

```bash
cd python-face-service
python3 -m venv venv
venv/bin/pip install -r requirements.txt

mkdir -p models
curl -L -o models/face_detection_yunet_2023mar.onnx \
  https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx
curl -L -o models/face_recognition_sface_2021dec.onnx \
  https://github.com/opencv/opencv_zoo/raw/main/models/face_recognition_sface/face_recognition_sface_2021dec.onnx

cp .env.example .env
# edit .env: set API_KEY (and PORT, if not 8100) for this site
```

`venv/`, `.env`, and `models/` are deliberately excluded from
`deploy-reusable.yml`'s rsync — they're created once, here, and are meant to
survive every later deploy untouched, not get overwritten from the repo.

The main app's own `.env` on that site also needs the matching
`FaceRecognition.apiKey` (same value as this `.env`'s `API_KEY`) and
`FaceRecognition.enabled = true`.

## 2. systemd unit

`/etc/systemd/system/ais-face-service.service` (or
`ais-face-service-testing.service` for the testing copy):

```ini
[Unit]
Description=AIS face recognition service (<site>)
After=network.target

[Service]
User=<site's Linux user, e.g. onesys>
WorkingDirectory=/home/onesys/htdocs/AIS/python-face-service
ExecStart=/home/onesys/htdocs/AIS/python-face-service/venv/bin/uvicorn app:app --host 127.0.0.1 --port 8100
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Then:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ais-face-service
```

`enable` (not just `start`) matters — it's what brings the service back up
after a VPS reboot, independent of any deploy ever running.

## 3. Sudoers rule for the deploy user

`deploy.sh` restarts the service over SSH as the site's own deploy user
(`onesys`/`onesystesting`), which isn't root. A `NOPASSWD` sudoers rule makes
that work non-interactively, e.g. in `/etc/sudoers.d/ais-face-service`:

```
onesys ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart ais-face-service, /usr/bin/systemctl status ais-face-service, /usr/bin/systemctl is-active ais-face-service
```

(a second line for `onesystesting` / `ais-face-service-testing`).

**This must whitelist the exact command strings** `deploy.sh` runs — no
extra flags. `deploy.sh` is deliberately careful not to pass things like
`--quiet` or `--no-pager` to `systemctl`, because that changes the invoked
command signature and sudo silently falls back to requiring a password,
which then fails non-interactively (no password prompt possible inside a
GitHub Actions SSH step) instead of giving a clear permission error.

Validate with (as that user):

```bash
sudo -n systemctl is-active ais-face-service
```

## Current gap: the new deploy workflow doesn't restart it

`deploy.yml` (deleted) used to call `deploy.sh`, which restarts the service
and polls `$FACE_URL/health` after every deploy. The new
`deploy-staging.yml` / `deploy-production.yml` → `deploy-reusable.yml` chain
replaces `deploy.yml` entirely, but `deploy-reusable.yml` only excludes
`python-face-service/venv`, `.env`, and `models` from its rsync — it never
restarts the systemd unit or health-checks it. So right now, deploying
through the new workflow updates `python-face-service`'s code on disk but
does not make the running service pick it up. Flagged here rather than
fixed silently; revisit `deploy-reusable.yml` if/when this needs closing.
