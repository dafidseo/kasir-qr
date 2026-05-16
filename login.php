<?php
session_start();
include 'koneksi.php';

// Jika sudah login, redirect ke kasir
if(isset($_SESSION['login'])) {
    header("Location: kasir.php");
    exit;
}

$error = '';
$success = '';

// Proses Login
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    if(mysqli_num_rows($query) == 1) {
        $user = mysqli_fetch_assoc($query);
        $_SESSION['login'] = true;
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        header("Location: kasir.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

// Proses Lupa Password - Kirim Link Reset
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lupa_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    
    if(mysqli_num_rows($cek) == 1) {
        $user = mysqli_fetch_assoc($cek);
        $token = md5(uniqid() . time());
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        mysqli_query($conn, "UPDATE users SET reset_token='$token', reset_expires='$expires' WHERE id_user=" . $user['id_user']);
        
        // Link reset password (harus diakses via browser)
        $reset_link = "http://localhost/kasir_qr/reset_password.php?token=" . $token;
        
        $success = "Link reset password telah dibuat. <br> 
                    <small>Karena email tidak dikirim otomatis, silakan gunakan link berikut:</small>
                    <br><a href='$reset_link' target='_blank'>$reset_link</a>";
    } else {
        $error = "Email tidak terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir Resto</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 30px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 4em;
            color: #FF6B35;
        }
        
        .logo h1 {
            font-size: 1.8em;
            color: #333;
            margin-top: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 0.85em;
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
        }
        
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            color: #999;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #FF6B35;
            border-bottom: 2px solid #FF6B35;
            margin-bottom: -2px;
        }
        
        .form-panel {
            display: none;
        }
        
        .form-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .input-group {
            display: flex;
            align-items: center;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .input-group:focus-within {
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
        }
        
        .input-group i {
            padding: 12px 15px;
            color: #999;
        }
        
        .input-group input {
            flex: 1;
            padding: 12px 15px 12px 0;
            border: none;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.02);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.85em;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.85em;
        }
        
        .info-akun {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.75em;
            color: #888;
            text-align: center;
        }
        
        .info-akun p {
            margin: 5px 0;
        }
        
        .link-lupapassword {
            text-align: right;
            margin-top: 10px;
        }
        
        .link-lupapassword a {
            color: #FF6B35;
            text-decoration: none;
            font-size: 0.8em;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #FF6B35;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-store"></i>
            <h1>Resto Kasir</h1>
            <p>Sistem Manajemen Restoran</p>
        </div>
        
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="showPanel('login')">Login</button>
            <button class="tab-btn" onclick="showPanel('register')">Daftar</button>
            <button class="tab-btn" onclick="showPanel('forgot')">Lupa Password</button>
        </div>
        
        <!-- PANEL LOGIN -->
        <div id="loginPanel" class="form-panel active">
            <?php if($error && !isset($_POST['lupa_password']) && !isset($_POST['register'])): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
        
        <!-- PANEL REGISTER -->
        <div id="registerPanel" class="form-panel">
            <?php 
            $reg_error = '';
            $reg_success = '';
            
            if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
                $username = mysqli_real_escape_string($conn, $_POST['reg_username']);
                $password = md5($_POST['reg_password']);
                $nama_lengkap = mysqli_real_escape_string($conn, $_POST['reg_nama']);
                $email = mysqli_real_escape_string($conn, $_POST['reg_email']);
                $no_telepon = mysqli_real_escape_string($conn, $_POST['reg_telepon']);
                
                // Cek username sudah ada
                $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
                if(mysqli_num_rows($cek) > 0) {
                    $reg_error = "Username sudah terdaftar!";
                } else {
                    $query = "INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role) 
                              VALUES ('$username', '$password', '$nama_lengkap', '$email', '$no_telepon', 'kasir')";
                    if(mysqli_query($conn, $query)) {
                        $reg_success = "Pendaftaran berhasil! Silakan login.";
                    } else {
                        $reg_error = "Pendaftaran gagal, coba lagi!";
                    }
                }
            }
            ?>
            
            <?php if($reg_error): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= $reg_error ?></div>
            <?php endif; ?>
            <?php if($reg_success): ?>
                <div class="success"><i class="fas fa-check-circle"></i> <?= $reg_success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nama Lengkap</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="reg_nama" placeholder="Nama lengkap" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user-circle"></i> Username</label>
                    <div class="input-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" name="reg_username" placeholder="Username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="reg_email" placeholder="email@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> No. Telepon</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="reg_telepon" placeholder="081234567890" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="reg_password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
            </form>
        </div>
        
        <!-- PANEL LUPA PASSWORD -->
        <div id="forgotPanel" class="form-panel">
            <?php if($error && isset($_POST['lupa_password'])): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Terdaftar</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Masukkan email Anda" required>
                    </div>
                </div>
                
                <button type="submit" name="lupa_password" class="btn">
                    <i class="fas fa-paper-plane"></i> Kirim Link Reset
                </button>
            </form>
            
            <div class="back-to-login">
                <a href="#" onclick="showPanel('login')">← Kembali ke Login</a>
            </div>
        </div>
        
        <div class="info-akun">
            <p><strong>Akun Demo:</strong></p>
            <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
            <p>Kasir: <strong>kasir</strong> / <strong>kasir123</strong></p>
        </div>
    </div>
    
    <script>
        function showPanel(panel) {
            // Sembunyikan semua panel
            document.getElementById('loginPanel').classList.remove('active');
            document.getElementById('registerPanel').classList.remove('active');
            document.getElementById('forgotPanel').classList.remove('active');
            
            // Tampilkan panel yang dipilih
            if(panel === 'login') {
                document.getElementById('loginPanel').classList.add('active');
            } else if(panel === 'register') {
                document.getElementById('registerPanel').classList.add('active');
            } else if(panel === 'forgot') {
                document.getElementById('forgotPanel').classList.add('active');
            }
            
            // Update active tab
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach((tab, index) => {
                if((panel === 'login' && index === 0) ||
                   (panel === 'register' && index === 1) ||
                   (panel === 'forgot' && index === 2)) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>