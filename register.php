<?php
require 'config.php';
require 'db.php';
require 'security_logger.php';

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ CSRF TOKEN CHECK
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $msg = "❌ ความปลอดภัย: CSRF Token ไม่ถูกต้อง";
        logSecurityEvent('INVALID_CSRF', 'Register');
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        // ✅ INPUT VALIDATION
        if (empty($user) || empty($pass) || empty($confirm_pass)) {
            $msg = "❌ กรุณาระบุข้อมูลให้ครบถ้วน";
        } elseif (strlen($user) < 3 || strlen($user) > 20) {
            $msg = "❌ ชื่อผู้ใช้ต้องมีความยาว 3-20 ตัวอักษร";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $user)) {
            $msg = "❌ ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, A-Z, 0-9, และ _";
        } elseif (strlen($pass) < 6) {
            $msg = "❌ รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        } elseif ($pass !== $confirm_pass) {
            $msg = "❌ รหัสผ่านไม่ตรงกัน!";
        } else {
            // ✅ CHECK DUPLICATE USER WITH PREPARED STATEMENT
            $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkUser->bind_param("s", $user);
            $checkUser->execute();
            $result = $checkUser->get_result();

            if ($result->num_rows > 0) {
                $msg = "❌ ชื่อผู้ใช้นี้ถูกใช้ไปแล้ว!";
                logSecurityEvent('DUPLICATE_USERNAME', "User: $user");
            } else {
                // ✅ HASH PASSWORD
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $user, $hashed_pass);
                
                if ($stmt->execute()) {
                    $msg = "✅ ลงทะเบียนสำเร็จ! <a href='login.php' style='color:#00ffff;font-weight:bold;'>คลิกเพื่อเข้าสู่ระบบ</a>";
                    logSecurityEvent('USER_REGISTERED', $user);
                } else {
                    $msg = "❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                    logSecurityEvent('REGISTRATION_ERROR', "User: $user, Error: " . $stmt->error);
                }
            }
        }
    }
}

// Generate CSRF Token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 QUANTUM NEXUS // REGISTRATION PORTAL</title>
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
                radial-gradient(ellipse at 20% 30%, rgba(0, 255, 136, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(0, 255, 255, 0.15) 0%, transparent 50%);
            animation: auroraGreen 8s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes auroraGreen {
            0%, 100% { 
                background: radial-gradient(ellipse at 20% 30%, rgba(0, 255, 136, 0.15) 0%, transparent 50%),
                            radial-gradient(ellipse at 80% 70%, rgba(0, 255, 255, 0.15) 0%, transparent 50%);
            }
            50% { 
                background: radial-gradient(ellipse at 70% 60%, rgba(0, 255, 255, 0.2) 0%, transparent 50%),
                            radial-gradient(ellipse at 30% 40%, rgba(0, 255, 136, 0.2) 0%, transparent 50%);
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
            padding: 20px;
        }

        /* 🎴 Register Card */
        .register-card {
            background: rgba(10, 10, 30, 0.8);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 15px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(15px) saturate(180%);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.6),
                inset 0 0 20px rgba(0, 255, 136, 0.05);
            animation: slideUp 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.2), transparent);
            animation: shine 3s infinite;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .register-card h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, #00ff88, #00ffff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
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

        .register-card p {
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ⚠️ Message Boxes */
        .message-box {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            animation: slideInMessage 0.5s ease-out;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }

        @keyframes slideInMessage {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 152, 0, 0.1));
            border: 2px solid #ffc107;
            color: #ffc107;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(255, 0, 128, 0.2), rgba(255, 0, 60, 0.1));
            border: 2px solid #ff0080;
            color: #ff88cc;
            box-shadow: 0 0 20px rgba(255, 0, 128, 0.3);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2), rgba(0, 200, 136, 0.1));
            border: 2px solid #00ff88;
            color: #00ff88;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .alert-success a {
            color: #00ffff;
            text-decoration: none;
            font-weight: 700;
            border-bottom: 2px solid #00ffff;
            transition: all 0.3s;
        }

        .alert-success a:hover {
            color: #00ff88;
            border-bottom-color: #00ff88;
        }

        /* 📝 Input Fields */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.8rem;
            color: #00ff88;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .input-group input:focus {
            outline: none;
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 
                0 0 20px rgba(0, 255, 136, 0.4),
                inset 0 0 10px rgba(0, 255, 136, 0.1);
        }

        /* ✅ Strength Meter */
        .password-strength {
            display: none;
            margin-top: 8px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength.show {
            display: block;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .password-info {
            font-size: 0.75rem;
            color: #888;
            margin-top: 5px;
        }

        /* 🔘 Submit Button */
        .btn-register {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2), rgba(0, 255, 200, 0.2));
            border: 2px solid #00ff88;
            color: #00ff88;
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

        .btn-register::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.5), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-register:hover::before {
            transform: translateX(100%);
        }

        .btn-register:hover {
            background: rgba(0, 255, 136, 0.2);
            box-shadow: 
                0 0 30px rgba(0, 255, 136, 0.6),
                inset 0 0 20px rgba(0, 255, 136, 0.3);
            transform: translateY(-2px);
        }

        /* 🔗 Links */
        .auth-links {
            margin-top: 25px;
            text-align: center;
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
            display: inline-block;
        }

        .link-btn:hover {
            background: rgba(0, 255, 255, 0.1);
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.4);
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

        .orb-green {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #00ff88, transparent);
            top: 10%;
            left: 15%;
            animation: float 15s ease-in-out infinite;
        }

        .orb-cyan {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, #00ffff, transparent);
            bottom: 10%;
            right: 20%;
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            33% { transform: translate(50px, -50px); }
            66% { transform: translate(-50px, 50px); }
        }
    </style>
</head>
<body>
    <!-- 🌌 Floating Orbs -->
    <div class="orb orb-green"></div>
    <div class="orb orb-cyan"></div>

    <!-- ⭐ Particle Canvas -->
    <canvas id="particles"></canvas>

    <div class="container">
        <div class="register-card">
            <h1>📝 REGISTER</h1>
            <p>🆕 CREATE QUANTUM ACCESS</p>
            
            <?php if($msg): ?>
            <div class="message-box <?php 
                if (strpos($msg, '✅') !== false) echo 'alert-success';
                elseif (strpos($msg, 'ยืนยัน')) echo 'alert-warning';
                else echo 'alert-danger';
            ?>">
                <?php echo $msg; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="input-group">
                    <label>👤 USERNAME</label>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="ตั้งชื่อผู้ใช้ (ต้องเป็น 3-20 ตัวอักษร)" 
                        required 
                        minlength="3"
                        maxlength="20"
                        pattern="[a-zA-Z0-9_]+"
                        title="ใช้ได้เฉพาะ a-z, A-Z, 0-9, และ _"
                    >
                </div>

                <div class="input-group">
                    <label>🔐 PASSWORD</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="ตั้งรหัสผ่าน (ต้องเป็น 6+ ตัวอักษร)" 
                        required 
                        minlength="6"
                        id="passwordInput"
                    >
                    <div class="password-strength" id="strengthMeter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-info" id="strengthText"></div>
                </div>

                <div class="input-group">
                    <label>✓ CONFIRM PASSWORD</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        placeholder="ยืนยันรหัสผ่านอีกครั้ง" 
                        required 
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    ✓ CREATE ACCOUNT
                </button>
            </form>

            <div class="auth-links">
                มีบัญชีอยู่แล้ว? <a href="login.php" class="link-btn">🔐 LOGIN</a>
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
                ctx.fillStyle = `rgba(0, 255, 136, ${this.opacity})`;
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

        // 🔐 PASSWORD STRENGTH METER
        const passwordInput = document.getElementById('passwordInput');
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let color = '';

            if (password.length >= 6) strength += 20;
            if (password.length >= 12) strength += 20;
            if (/[a-z]/.test(password)) strength += 10;
            if (/[A-Z]/.test(password)) strength += 10;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 25;

            if (password.length === 0) {
                strengthMeter.classList.remove('show');
            } else {
                strengthMeter.classList.add('show');
                if (strength < 30) {
                    text = '❌ ออนแอ่น';
                    color = '#ff0080';
                } else if (strength < 50) {
                    text = '⚠️ ปานกลาง';
                    color = '#ffc107';
                } else if (strength < 75) {
                    text = '✓ ดี';
                    color = '#00ffff';
                } else {
                    text = '💪 แข็งแกร่ง';
                    color = '#00ff88';
                }
            }

            strengthBar.style.width = strength + '%';
            strengthBar.style.background = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // 🎮 FORM SUBMIT
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ รหัสผ่านไม่ตรงกัน!');
                return;
            }

            const btn = document.getElementById('submitBtn');
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });

        // 🌐 RESIZE CANVAS
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>