# Running this project locally (Windows)

## 1. Start the Redis dependency (Memurai) — DO THIS FIRST

The app needs Redis: `config/session.php`, `config/cache.php` and `config/queue.php` all
default to `redis`, and `.env` does not override them. Redis is provided by **Memurai**
(`C:\Program Files\Memurai`), installed as a Windows service named `Memurai`
(StartMode `Auto`).

**Symptom when it is not running:**

```
No connection could be made because the target machine actively refused it [tcp://127.0.0.1:6379]
```

**Cause to check:** Memurai's free **Developer licence enforces an automatic shutdown after
10 days**. The log ends with:

```
# Memurai automatic shutdown...
# Memurai will now exit (_without_ saving), bye bye...
```

Affected log: `C:\Program Files\Memurai\memurai-log.txt`.

### Option A — start the Windows service (preferred; needs an Administrator shell)

```
net start Memurai
```

### Option B — run it as the current user (no admin needed)

`memurai.exe` cannot write its own log/dump inside `C:\Program Files\Memurai` as a normal
user (ACL is denied), so point it at a writable dir. Do **not** copy secrets — these are
runtime data paths only.

```
mkdir "%CD%\.freebuff\memurai-data"
copy "C:\Program Files\Memurai\dump.rdb" "%CD%\.freebuff\memurai-data\dump.rdb"

powershell -NoProfile -Command "$d='%CD%\.freebuff\memurai-data'; Start-Process -FilePath 'C:\Program Files\Memurai\memurai.exe' -ArgumentList '\"C:\Program Files\Memurai\memurai.conf\"','--logfile',($d+'\memurai.log'),'--dir',$d -RedirectStandardOutput ($d+'\out.log') -RedirectStandardError ($d+'\err.log') -WindowStyle Hidden"
```

### Verify (never skip)

```
"C:\Program Files\Memurai\memurai-cli.exe" -h 127.0.0.1 -p 6379 ping      # -> PONG
"C:\Program Files\Memurai\memurai-cli.exe" -h 127.0.0.1 -p 6379 -n 1 dbsize
```

`REDIS_DB` defaults to `1`, so application keys live in db 1, not db 0.

### If it keeps dying

Either activate a real Memurai licence, or point the app at non-Redis drivers for local dev
by adding to `.env` (application behaviour change — confirm with the owner first):

```
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync
```

## 2. Run the Laravel dev server

```
php -S 127.0.0.1:8000 server.php
```

The site is then at http://127.0.0.1:8000 (`/admin/billing/index` redirects to login with
HTTP 302 when unauthenticated, which is the healthy response).

**Gotcha:** only ONE listener should exist on port 8000. Two `php -S 127.0.0.1:8000`
processes can bind the same port on Windows and serve requests unpredictably. Check first:

```
netstat -ano | grep ":8000 "
```
