<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cattr demo is being reset</title>
    <style>
        :root { --bg: #f5f7fb; --card: #fff; --text: #1e1f26; --muted: #6b6f80; --accent: #2e2ef9; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #15161c; --card: #1f2029; --text: #ececf1; --muted: #a0a3b1; --accent: #7b7bff; }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 16px; background: var(--bg); color: var(--text);
            font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .card {
            max-width: 420px; width: 100%; padding: 40px 32px; border-radius: 12px; text-align: center;
            background: var(--card); box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }
        .spinner {
            width: 48px; height: 48px; margin: 0 auto 24px; border-radius: 50%;
            border: 4px solid color-mix(in srgb, var(--accent) 20%, transparent); border-top-color: var(--accent);
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .spinner { animation-duration: 3s; } }
        h1 { margin: 0 0 8px; font-size: 20px; }
        p { margin: 0; color: var(--muted); }
    </style>
</head>
<body>
    <main class="card" role="status" aria-live="polite">
        <div class="spinner" aria-hidden="true"></div>
        <h1>The demo is being reset</h1>
        <p>Fresh demo data will be ready in a few seconds. This page reloads by itself.</p>
    </main>
</body>
</html>
