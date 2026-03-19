<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connexion') - Weylo Admin</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Gradient animé en arrière-plan */
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483, #7b2cbf);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        /* Particules flottantes */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-100vh) translateX(50px);
                opacity: 0;
            }
        }

        /* Animation d'apparition */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="animated-gradient min-h-screen flex items-center justify-center p-4 overflow-hidden relative">
    <!-- Particules animées -->
    <div class="particles-container absolute inset-0 overflow-hidden pointer-events-none">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="width: 9px; height: 9px; left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="width: 11px; height: 11px; left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="width: 13px; height: 13px; left: 90%; animation-delay: 16s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 15%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 35%; animation-delay: 7s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 55%; animation-delay: 11s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 75%; animation-delay: 15s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 85%; animation-delay: 18s;"></div>
    </div>

    <!-- Contenu principal -->
    <div class="w-full max-w-md relative z-10 fade-in-up">
        @yield('content')
    </div>
</body>
</html>
