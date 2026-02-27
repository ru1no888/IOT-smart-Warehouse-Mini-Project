<?php
require 'config.php';
require 'db.php';
require 'security_logger.php';

// ✅ เพิ่ม Rate Limiting สำหรับ Login Page
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
checkRateLimit($ip);

$error = "";

// ถ้า login อยู่แล้ว ให้ไป index.html
if (isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($user) || empty($pass)) {
        $error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    } else {
        // ดึง IP ผู้ใช้
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // ตรวจสอบว่า IP ถูก block หรือไม่
        $block_file = sys_get_temp_dir() . '/blocked_ips.json';
        $is_blocked = false;
        
        if (file_exists($block_file)) {
            $blocked = json_decode(file_get_contents($block_file), true);
            if (isset($blocked[$ip]) && $blocked[$ip] > time()) {
                $remaining = ceil(($blocked[$ip] - time()) / 60);
                $error = "IP ของคุณถูกบล็อคชั่วคราว กรุณารอ $remaining นาที";
                $is_blocked = true;
            }
        }

        if (!$is_blocked) {
            // ตรวจสอบ User ด้วย Prepared Statement
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($pass, $row['password'])) {
                    // SUCCESS - Login สำเร็จ
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    
                    // ลบข้อมูล failed attempt
                    $attempts_file = sys_get_temp_dir() . '/login_attempts_' . md5($ip) . '.txt';
                    if (file_exists($attempts_file)) {
                        unlink($attempts_file);
                    }

                    logSecurityEvent('login', "User $user logged in successfully", 'success');

                    header("Location: index.html");
                    exit();
                } else {
                    // รหัสผ่านผิด
                    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                    checkAndBlockFailedLogin($user, $ip);
                    logSecurityEvent('login', "Failed login attempt for: $user", 'failed');
                }
            } else {
                // ไม่มี user นี้ในระบบ
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                checkAndBlockFailedLogin($user, $ip);
                logSecurityEvent('login', "Failed login attempt for unknown user: $user", 'failed');
            }
            
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 QUANTUM NEXUS // LOGIN PORTAL</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0a1a;
            color: #fff;
            overflow: hidden;
            min-height: 100vh;
            position: relative;
        }

        /* 🌌 Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(0, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(255, 0, 255, 0.15) 0%, transparent 50%);
            animation: aurora 8s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes aurora {
            0%, 100% { 
                background: radial-gradient(ellipse at 20% 30%, rgba(0, 255, 255, 0.15) 0%, transparent 50%),
                            radial-gradient(ellipse at 80% 70%, rgba(255, 0, 255, 0.15) 0%, transparent 50%);
            }
            50% { 
                background: radial-gradient(ellipse at 70% 60%, rgba(255, 0, 255, 0.2) 0%, transparent 50%),
                            radial-gradient(ellipse at 30% 40%, rgba(0, 255, 255, 0.2) 0%, transparent 50%);
            }
        }

        /* ⭐ Canvas for Particles */
        #particles {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }

        /* 🎴 Login Card */
        .login-card {
            background: rgba(10, 10, 30, 0.8);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 15px;
            padding: 50px 40px;
            width: 90%;
            max-width: 420px;
            backdrop-filter: blur(15px) saturate(180%);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.6),
                inset 0 0 20px rgba(0, 255, 255, 0.05);
            animation: slideUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.2), transparent);
            animation: shine 3s infinite;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .login-card h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, #00ffff, #ff00ff, #ffff00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            margin-bottom: 10px;
            animation: glitch 5s infinite;
            letter-spacing: 2px;
        }

        @keyframes glitch {
            0%, 90%, 100% { transform: translate(0); }
            92% { transform: translate(-2px, 2px); }
            94% { transform: translate(2px, -2px); }
            96% { transform: translate(-2px, -2px); }
        }

        .login-card p {
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ⚠️ Error Message */
        .error-box {
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.2), rgba(255, 0, 60, 0.1));
            border: 2px solid #ff0080;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            color: #ff88cc;
            font-weight: 700;
            animation: shake 0.5s ease-in-out, pulse-error 2s infinite;
            box-shadow: 0 0 20px rgba(255, 0, 128, 0.3), inset 0 0 10px rgba(255, 0, 128, 0.1);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes pulse-error {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 128, 0.3), inset 0 0 10px rgba(255, 0, 128, 0.1); }
            50% { box-shadow: 0 0 35px rgba(255, 0, 128, 0.6), inset 0 0 20px rgba(255, 0, 128, 0.2); }
        }

        /* 📝 Input Fields */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.8rem;
            color: #00ffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(0, 255, 255, 0.05);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.95rem;
            transition: all 0.3s;
            position: relative;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .input-group input:focus {
            outline: none;
            border-color: #00ffff;
            background: rgba(0, 255, 255, 0.1);
            box-shadow: 
                0 0 20px rgba(0, 255, 255, 0.4),
                inset 0 0 10px rgba(0, 255, 255, 0.1);
        }

        /* 🔐 Icon beside input */
        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 40px;
            font-size: 1.3rem;
            opacity: 0.5;
            transition: all 0.3s;
        }

        .input-group input:focus ~ .input-icon {
            opacity: 1;
            transform: scale(1.2);
            color: #00ffff;
        }

        /* 🔘 Submit Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.2), rgba(255, 0, 255, 0.2));
            border: 2px solid #00ffff;
            color: #00ffff;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            margin-top: 10px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.5), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .btn-login:hover {
            background: rgba(0, 255, 255, 0.2);
            box-shadow: 
                0 0 30px rgba(0, 255, 255, 0.6),
                inset 0 0 20px rgba(0, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(2px);
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        /* 📱 Loading animation */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(0, 255, 255, 0.3);
            border-top: 3px solid #00ffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 🔗 Links */
        .auth-links {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .link-btn {
            color: #00ffff;
            text-decoration: none;
            font-size: 0.85rem;
            padding: 8px 15px;
            border: 1px solid rgba(0, 255, 255, 0.5);
            border-radius: 5px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .link-btn:hover {
            background: rgba(0, 255, 255, 0.1);
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.4);
        }

        /* ✅ Success Animation */
        .success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 255, 136, 0.2);
            border: 3px solid #00ff88;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            z-index: 1000;
            animation: successPop 0.6s ease-out;
            box-shadow: 0 0 50px rgba(0, 255, 136, 0.6);
        }

        @keyframes successPop {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.5);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        .success-message h2 {
            color: #00ff88;
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            margin: 0;
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

        .orb-cyan {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #00ffff, transparent);
            top: 10%;
            left: 15%;
            animation: float 15s ease-in-out infinite;
        }

        .orb-magenta {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #ff00ff, transparent);
            bottom: 10%;
            right: 20%;
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            33% { transform: translate(50px, -50px); }
            66% { transform: translate(-50px, 50px); }
        }

        /* 📊 Code Rain Effect */
        .code-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
            color: rgba(0, 255, 255, 0.1);
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            white-space: pre;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <!-- ⭐ Floating Orbs -->
    <div class="orb orb-cyan"></div>
    <div class="orb orb-magenta"></div>

    <!-- ⭐ Particle Canvas -->
    <canvas id="particles"></canvas>

    <!-- 📝 Code Rain -->
    <div class="code-rain" id="codeRain"></div>

    <div class="container">
        <div class="login-card">
            <h1>⚡ LOGIN</h1>
            <p>🔐 QUANTUM NEXUS SECURE PORTAL</p>
            
            <?php if($error): ?>
            <div class="error-box">🔴 <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="input-group">
                    <label>👤 USERNAME</label>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="ชื่อผู้ใช้" 
                        required 
                        autocomplete="username"
                    >
                    <span class="input-icon">👤</span>
                </div>

                <div class="input-group">
                    <label>🔐 PASSWORD</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="รหัสผ่าน" 
                        required 
                        autocomplete="current-password"
                    >
                    <span class="input-icon">🔒</span>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span id="btnText">▶ ENTER SYSTEM</span>
                </button>
            </form>

            <div class="auth-links">
                <a href="register.php" class="link-btn">📝 Register</a>
                <a href="javascript:void(0)" class="link-btn" onclick="resetPassword()">🆘 Help</a>
            </div>
        </div>
    </div>

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
                this.size = Math.random() * 2 + 0.5;
                this.speedX = Math.random() * 0.3 - 0.15;
                this.speedY = Math.random() * 0.3 - 0.15;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }
            
            draw() {
                ctx.fillStyle = `rgba(0, 255, 255, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        const particles = [];
        for (let i = 0; i < 100; i++) {
            particles.push(new Particle());
        }

        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // 📊 CODE RAIN EFFECT
        const codeRain = document.getElementById('codeRain');
        const codes = ['01', '10', '0', '1', '>', '<', '{', '}', '[', ']', '°C', '%', '♦', '♠', '♣', '♥'];
        
        let rainText = '';
        for (let i = 0; i < 200; i++) {
            rainText += codes[Math.floor(Math.random() * codes.length)] + ' ';
            if (i % 30 === 0) rainText += '\n';
        }
        codeRain.textContent = rainText;

        // 🎮 FORM SUBMIT ANIMATION
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            btn.classList.add('loading');
            btnText.innerHTML = '<span class="loading-spinner"></span>ACCESSING...';
        });

        // 🎨 INPUT FOCUS EFFECTS
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.background = 'rgba(0, 255, 255, 0.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.background = 'transparent';
            });
        });

        // 💬 KEYBOARD SHORTCUTS
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                document.getElementById('loginForm').submit();
            }
        });

        // 🌐 RESIZE CANVAS
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        function resetPassword() {
            alert('⚠️ Contact Administrator for password reset\n📧 support@nexus.local');
        }
    </script>
</body>
</html>