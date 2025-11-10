<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ACADEX') }}</title>

    <!-- Preload Background Image -->
    <link rel="preload" as="image" href="/images/bg.jpg">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">

    <!-- CDNFonts: Feeling Passionate -->
    <link href="https://fonts.cdnfonts.com/css/feeling-passionate" rel="stylesheet">

    <!-- Tailwind & App Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background-color: #023336; /* Fallback color */
            background: url('/images/bg.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.6);
            min-height: 100vh;
        }

        .branding-container {
            position: absolute;
            top: 50%;
            left: 10%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 1px;
            color: white;
        }

        .branding-container img {
            height: 250px;
            width: auto;
        }

        .branding-text h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 4.5rem;
            font-weight: bold;
            margin: 0;
        }

        .branding-text p {
            font-family: 'Feeling Passionate', cursive;
            font-size: 1.5rem;
            margin: 0;
        }

        .login-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 100vh;
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Smooth scroll */
        .glass-card::-webkit-scrollbar {
            width: 6px;
        }
        .glass-card::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
    </style>
</head>
<body class="text-white">

    <!-- Branding Section -->
    <div class="branding-container">
        <img src="/logo.jpg" alt="ACADEX Logo">
        <div class="branding-text">
            <h1>ACADEX</h1>
            <p>Fides et Servitium</p>
        </div>
    </div>

    <!-- Login Card -->
    <div class="overlay">
        <div class="container login-container">
            <div class="col-md-4 col-lg-4 glass-card text-white p-4">
                <!-- Dynamic Content -->
                @yield('contents')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Alpine.js for interactivity (optional) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('passwordToggle', () => ({
                showPassword: false,
                togglePassword() {
                    this.showPassword = !this.showPassword;
                }
            }));
        });
    </script>

</body>
</html>
