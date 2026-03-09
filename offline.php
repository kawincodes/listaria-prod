<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Offline — Listaria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0a1a 0%, #1a1025 50%, #0f0a1a 100%);
            color: #fff;
            text-align: center;
            padding: 2rem;
        }
        .icon-wrap {
            width: 100px; height: 100px;
            background: linear-gradient(135deg, rgba(107,33,168,0.3), rgba(147,51,234,0.15));
            border: 2px solid rgba(147,51,234,0.3);
            border-radius: 28px;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem;
            margin-bottom: 2rem;
            animation: pulse 2.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(147,51,234,0.3); }
            50% { box-shadow: 0 0 0 16px rgba(147,51,234,0); }
        }
        .logo { width: 48px; height: 48px; border-radius: 12px; margin-bottom: 2rem; object-fit: cover; }
        h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 0.75rem; letter-spacing: -0.5px; }
        p {
            font-size: 1rem; color: rgba(255,255,255,0.6);
            max-width: 360px; line-height: 1.65; margin-bottom: 2rem;
        }
        .btn-row { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.75rem 1.6rem;
            border-radius: 12px;
            font-size: 0.9rem; font-weight: 700;
            text-decoration: none; cursor: pointer; border: none;
            transition: transform 0.15s, opacity 0.15s;
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-primary { background: linear-gradient(135deg, #6B21A8, #9333EA); color: #fff; }
        .btn-ghost {
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.8);
        }
        .status {
            margin-top: 2.5rem;
            font-size: 0.78rem; color: rgba(255,255,255,0.3);
            display: flex; align-items: center; gap: 6px;
        }
        .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #ef4444;
            animation: blink 1.5s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
        .cached-section {
            margin-top: 2.5rem;
            padding: 1.2rem 1.5rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            max-width: 380px; width: 100%;
            text-align: left;
        }
        .cached-section h3 { font-size: 0.8rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 1rem; }
        .cached-link {
            display: flex; align-items: center; gap: 10px;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-decoration: none; color: rgba(255,255,255,0.75);
            font-size: 0.88rem; font-weight: 500;
            transition: color 0.15s;
        }
        .cached-link:last-child { border-bottom: none; }
        .cached-link:hover { color: #c084fc; }
        .cached-link span { font-size: 1.1rem; }
    </style>
</head>
<body>
    <img src="/assets/logo.jpg" alt="Listaria" class="logo">
    <div class="icon-wrap">📡</div>
    <h1>You're offline</h1>
    <p>It looks like you've lost your internet connection. Check your network and try again, or browse pages you've visited before.</p>
    <div class="btn-row">
        <button class="btn btn-primary" onclick="window.location.reload()">Try again</button>
        <a href="/" class="btn btn-ghost">Go home</a>
    </div>

    <div class="cached-section">
        <h3>Pages available offline</h3>
        <a href="/" class="cached-link"><span>🏠</span> Home</a>
        <a href="/index.php" class="cached-link"><span>🛍️</span> Browse Listings</a>
        <a href="/profile.php" class="cached-link"><span>👤</span> My Profile</a>
        <a href="/sell.php" class="cached-link"><span>💜</span> Sell an Item</a>
    </div>

    <div class="status">
        <div class="dot"></div>
        No internet connection
    </div>

    <script>
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>
