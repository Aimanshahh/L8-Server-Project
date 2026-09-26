# Preview run doc — GPS Server (Laravel)

This doc records how to reproduce the running preview from a fresh checkout
of this thread's worktree.

## Reproduce uncommitted artifacts

- There are no special uncommitted artifacts to copy. The Laravel `.env` is
  already present in the worktree root (`C:\Users\p.c\Desktop\laravelProject\.env`)
  and is shared with the main checkout (no `.env.local` exists). Do NOT symlink
  or copy secret values between checkouts — if this worktree is a separate clone,
  re-create its own `.env` from `.env.example` / the main checkout's `.env`
  using the same process the project normally uses. Port numbers and DB creds
  may differ per worktree.

## Run the server

This project is a Laravel 8 app served via `php artisan serve` and front-end
assets are compiled with `gulp` (BrowserSync). The simplest live preview that
reloads on CSS/JS changes is:

1. From the project root (`C:\Users\p.c\Desktop\laravelProject`), start the
   PHP dev server on a free port, e.g. 8000:

   ```
   php artisan serve --port=8000
   ```

   If 8000 is in use, pick another free port and pass `--port=<free>`.

2. (Optional) In a separate shell, start `gulp` for CSS/JS live reload:

   ```
   npx gulp
   ```

   The dev server does not require `gulp` to be running — compiled assets under
   `public/assets/css/*.css` and `public/assets/js/*.js` are served directly.

## Access

Open `http://127.0.0.1:8000` (or the port you chose) in the Preview tab.
