<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weylo - Ouvrir dans l'application</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 400px;
        }
        .logo {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: #fff;
            color: #6366F1;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        .btn:hover { transform: scale(1.05); }
        .btn-secondary {
            display: inline-block;
            margin-top: 1rem;
            padding: 10px 24px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .spinner {
            margin: 1.5rem auto;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Weylo</div>
        <div class="spinner" id="spinner"></div>
        <p class="message" id="message">Ouverture de l'application...</p>
        <div id="buttons" style="display: none;">
            <a href="{{ $playStoreUrl }}" class="btn">Installer sur Google Play</a>
            <br>
            <a href="{{ $appDeepLink }}" class="btn-secondary">Ouvrir dans l'app</a>
        </div>
    </div>

    <script>
        (function() {
            var appDeepLink = @json($appDeepLink);
            var playStoreUrl = @json($playStoreUrl);
            var timeout;

            // Tenter d'ouvrir l'app via le deep link
            window.location.href = appDeepLink;

            // Si l'app ne s'ouvre pas dans 1.5s, montrer les boutons
            timeout = setTimeout(function() {
                document.getElementById('spinner').style.display = 'none';
                document.getElementById('message').textContent = 'L\'application n\'est pas encore installee ?';
                document.getElementById('buttons').style.display = 'block';
            }, 1500);

            // Si la page perd le focus (app ouverte), annuler le timeout
            window.addEventListener('blur', function() {
                clearTimeout(timeout);
            });

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    clearTimeout(timeout);
                }
            });
        })();
    </script>
</body>
</html>
