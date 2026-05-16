<?php
session_start();
include 'koneksi.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if(empty($token)) {
    die("Token tidak valid!");
}

// Cek token
$cek = mysqli_query($conn, "SELECT * FROM users WHERE reset_token='$token' AND reset_expires > NOW()");
if(mysqli_num_rows($cek) == 0) {
    die("Token tidak valid atau sudah kadaluarsa! <a href='login.php'>Kembali ke Login</a>");
}

$user = mysqli_fetch_assoc($cek);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_baru = md5($_POST['password']);
    $konfirmasi = md5($_POST['konfirmasi']);
    
    if($_POST['password'] !== $_POST['konfirmasi']) {
        $error = "Password tidak cocok!";
    } elseif(strlen($_POST['password']) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        mysqli_query($conn, "UPDATE users SET password='$password_baru', reset_token=NULL, reset_expires=NULL WHERE id_user=" . $user['id_user']);
        $success = "Password berhasil direset! Silakan <a href='login.php'>login</a> dengan password baru.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Resto Kasir</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            text-align: center;
        }
        .logo i { font-size: 4em; color: #FF6B35; }
        h2 { margin: 20px 0; color: #333; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
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
        .input-group i { padding: 12px 15px; color: #999; }
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
        }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .success a { color: #155724; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h2>Reset Password</h2>
            <p>Untuk akun: <strong><?= htmlspecialchars($user['username']) ?></strong></p>
        </div>
        
        <?php if($error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="konfirmasi" placeholder="Ulangi password" required>
                    </div>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>