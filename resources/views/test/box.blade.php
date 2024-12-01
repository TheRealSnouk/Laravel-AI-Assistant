<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cyberpunk Box Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --neon-primary: #0ff;
            --neon-secondary: #f0f;
            --neon-flash: #ff0;
        }

        body {
            background-color: #0a0a0a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 0;
        }

        .cyber-box {
            position: relative;
            width: 300px;
            height: 300px;
            background: rgba(0, 255, 255, 0.05);
            border: 2px solid var(--neon-primary);
            animation: boxPulse 3s infinite;
            transform-style: preserve-3d;
            perspective: 1000px;
            transition: transform 0.3s ease;
        }

        .cyber-box::before,
        .cyber-box::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid var(--neon-secondary);
            animation: borderRotate 4s linear infinite;
            pointer-events: none;
        }

        .cyber-box::before {
            transform: rotate(45deg);
        }

        .cyber-box::after {
            transform: rotate(-45deg);
        }

        .cyber-box-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--neon-primary);
            text-shadow: 0 0 10px var(--neon-primary);
            font-size: 1.5em;
            text-align: center;
            width: 100%;
            padding: 20px;
            z-index: 1;
            user-select: none;
        }

        .corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid var(--neon-flash);
            animation: cornerFlash 2s infinite alternate;
            pointer-events: none;
        }

        .corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; }

        @keyframes boxPulse {
            0%, 100% { box-shadow: 0 0 20px var(--neon-primary); }
            50% { box-shadow: 0 0 40px var(--neon-primary); }
        }

        @keyframes borderRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes cornerFlash {
            0%, 100% { border-color: var(--neon-flash); }
            50% { border-color: transparent; }
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--neon-primary);
            opacity: 0.5;
            animation: scan 3s linear infinite;
            pointer-events: none;
        }

        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }

        .glitch-text {
            animation: glitch 3s infinite;
            cursor: pointer;
        }

        @keyframes glitch {
            0%, 100% { transform: none; opacity: 1; }
            92% { transform: skew(20deg); opacity: 0.75; }
            94% { transform: skew(-20deg); opacity: 0.75; }
            96% { transform: none; opacity: 0.9; }
        }

        .error-message {
            color: #ff0033;
            text-shadow: 0 0 10px #ff0033;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body class="overflow-hidden">
    <div class="error-message"></div>
    
    <div class="cyber-box">
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>
        <div class="scan-line"></div>
        <div class="cyber-box-content">
            <span class="glitch-text">SYSTEM</span>
            <br>
            <span class="text-sm opacity-75">INITIALIZED</span>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const box = document.querySelector('.cyber-box');
            let isAnimating = false;
            
            // 3D rotation effect
            box.addEventListener('mousemove', (e) => {
                if (isAnimating) return;
                
                const rect = box.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const rotateX = (y / rect.height - 0.5) * 20;
                const rotateY = (x / rect.width - 0.5) * 20;
                
                box.style.transform = `rotateX(${-rotateX}deg) rotateY(${rotateY}deg)`;
            });

            box.addEventListener('mouseleave', () => {
                if (isAnimating) return;
                box.style.transform = 'none';
            });

            // Click effect
            $('.glitch-text').click(function() {
                if (isAnimating) return;
                
                isAnimating = true;
                const originalTransform = box.style.transform;
                
                box.style.transform = `${originalTransform} scale(0.95)`;
                
                setTimeout(() => {
                    box.style.transform = originalTransform;
                    isAnimating = false;
                }, 200);
            });

            // Error handling
            function showError(message) {
                $('.error-message').text(message).fadeIn().delay(3000).fadeOut();
            }

            // Handle any AJAX errors globally
            $(document).ajaxError(function(event, jqXHR, settings, error) {
                showError('Error: ' + (jqXHR.responseJSON?.message || 'Something went wrong'));
            });
        });
    </script>
</body>
</html>
