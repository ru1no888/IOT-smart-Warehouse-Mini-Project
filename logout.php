<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚪 LOGGING OUT...</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0a1a;
            color: #fff;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* 🌌 Background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(0, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(255, 0, 0, 0.15) 0%, transparent 50%);
            animation: auroraDark 6s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes auroraDark {
            0%, 100% { 
                background: radial-gradient(ellipse at 20% 30%, rgba(0, 255, 255, 0.15) 0%, transparent 50%),
                            radial-gradient(ellipse at 80% 70%, rgba(255, 0, 0, 0.15) 0%, transparent 50%);
            }
            50% { 
                background: radial-gradient(ellipse at 70% 60%, rgba(255, 0, 0, 0.2) 0%, transparent 50%),
                            radial-gradient(ellipse at 30% 40%, rgba(0, 255, 255, 0.2) 0%, transparent 50%);
            }
        }

        /* ⭐ Canvas */
        #particles {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .logout-container {
            position: relative;
            z-index: 10;
            text-align: center;
            animation: fadeInDown 0.8s ease-out;
        }

        .logout-card {
            background: rgba(10, 10, 30, 0.8);
            border: 3px solid #ff0080;
            border-radius: 15px;
            padding: 60px 50px;
            width: 90%;
            max-width: 450px;
            backdrop-filter: blur(15px) saturate(180%);
            box-shadow: 
                0 0 40px rgba(255, 0, 128, 0.4),
                inset 0 0 30px rgba(255, 0, 128, 0.1);
            animation: slideDown 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-60px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logout-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: slideOut 1.5s ease-in-out forwards;
            animation-delay: 1s;
        }

        @keyframes slideOut {
            0% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateX(100px) scale(0.5);
            }
        }

        .logout-card h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            color: #ff0080;
            margin-bottom: 15px;
            letter-spacing: 3px;
            text-shadow: 0 0 30px rgba(255, 0, 128, 0.6);
            animation: fadeOut 1.5s ease-out forwards;
            animation-delay: 1.2s;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(50px);
            }
        }

        .logout-card p {
            color: #888;
            font-size: 1.1rem;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: fadeOut 1.5s ease-out forwards;
            animation-delay: 1s;
        }

        .loading-dots {
            font-size: 2rem;
            margin-top: 30px;
            animation: fadeOut 1.5s ease-out forwards;
            animation-delay: 1.2s;
        }

        .dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin: 0 6px;
            background: #ff0080;
            border-radius: 50%;
            animation: blink 1.2s infinite;
            box-shadow: 0 0 10px #ff0080;
        }

        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink {
            0%, 20%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        .goodbye-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            color: #00ff88;
            opacity: 0;
            animation: goodbyeFadeInOut 2s ease-in-out 1.5s forwards;
            text-shadow: 0 0 30px rgba(0, 255, 136, 0.8),
                         0 0 60px rgba(0, 255, 136, 0.4);
            letter-spacing: 3px;
            z-index: 15;
            pointer-events: none;
        }

        @keyframes goodbyeFadeInOut {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.5);
            }
            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(1.5);
            }
        }

        .redirect-info {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid #00ffff;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            animation: slideInRight 0.6s ease-out 2.5s forwards;
            animation-fill-mode: both;
            opacity: 0;
            z-index: 20;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .screen-fade {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 100;
            animation: fadeToBlack 0.8s ease-out 3s forwards;
            opacity: 0;
        }

        @keyframes fadeToBlack {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        /* 🌐 Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
        }

        .orb-red {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #ff0080, transparent);
            top: 20%;
            left: 20%;
            animation: orbitOut 3s ease-in forwards;
        }

        .orb-cyan {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #00ffff, transparent);
            bottom: 20%;
            right: 20%;
            animation: orbitOut 3.5s ease-in forwards;
            animation-delay: 0.2s;
        }

        @keyframes orbitOut {
            from {
                opacity: 0.3;
                transform: translate(0, 0) scale(1);
            }
            to {
                opacity: 0;
                transform: translate(200px, 200px) scale(0.3);
            }
        }
    </style>
</head>
<body>
    <!-- 🌌 Floating Orbs -->
    <div class="orb orb-red"></div>
    <div class="orb orb-cyan"></div>

    <!-- ⭐ Particle Canvas -->
    <canvas id="particles"></canvas>

    <!-- 👋 Goodbye Message -->
    <div class="goodbye-text">GOODBYE 👋</div>

    <!-- 🚪 Logout Container -->
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">🚪</div>
            <h1>DISCONNECTED</h1>
            <p>🔌 SESSION TERMINATED</p>
            
            <div class="loading-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </div>

    <!-- 📍 Redirect Info -->
    <div class="redirect-info">
        🔄 Redirecting to login portal...
    </div>

    <!-- 🎬 Screen Fade -->
    <div class="screen-fade"></div>

    <script>
        // ⭐ PARTICLE ANIMATION
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 3 + 1;
                this.speedX = Math.random() * 0.5 - 0.25;
                this.speedY = Math.random() * 0.5 - 0.25;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                this.opacity -= 0.003;
                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }
            
            draw() {
                ctx.fillStyle = `rgba(255, 0, 128, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        const particles = [];
        for (let i = 0; i < 80; i++) {
            particles.push(new Particle());
        }

        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach((p, i) => {
                p.update();
                if (p.opacity > 0) {
                    p.draw();
                } else if (i < 10) {
                    particles[i] = new Particle();
                }
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // 🚪 AUTO REDIRECT
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 4000);

        // 🌐 RESIZE CANVAS
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>