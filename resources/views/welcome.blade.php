<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel AI Assistant') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gray-100">
        <main class="container mx-auto px-4 py-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-8">Welcome to Laravel AI Assistant</h1>
            <p class="text-lg text-gray-600 mb-4">Your intelligent coding companion powered by AI.</p>
            
            @auth
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold mb-4">Get Started</h2>
                    <p class="mb-4">Welcome back! Ready to enhance your coding experience?</p>
                    <a href="{{ route('dashboard') }}" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition-colors">
                        Go to Dashboard
                    </a>
                </div>
            @else
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold mb-4">Join Us</h2>
                    <p class="mb-4">Sign up now to experience the power of AI-assisted development.</p>
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition-colors">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="inline-block bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 transition-colors">
                            Register
                        </a>
                    </div>
                </div>
            @endauth
        </main>
    </div>
</body>
</html>
