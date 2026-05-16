<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Resto Kasir - Sistem Pemesanan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            overflow-x: hidden;
        }
        
        /* Background dengan pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        /* Container utama */
        .container-custom {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        /* Card utama */
        .main-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 40px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .main-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 35px 60px rgba(0,0,0,0.3);
        }
        
        /* Logo area */
        .logo-area {
            position: relative;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 4.5em;
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.08);
            }
        }
        
        .badge-new {
            position: absolute;
            top: -10px;
            right: -20px;
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.7em;
            font-weight: 600;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }
        
        h1 {
            font-size: 2em;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 30px;
        }
        
        /* Button styles */
        .btn-menu {
            padding: 15px 20px;
            border-radius: 20px;
            font-weight: 700;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-menu::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-menu:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-menu:active {
            transform: translateY(2px);
        }
        
        .btn-kasir {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
        }
        
        .btn-pesan {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-qr {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
        }
        
        .btn-outline-custom {
            background: transparent;
            border: 2px solid #FF6B35;
            color: #FF6B35;
        }
        
        .btn-outline-custom:hover {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            border-color: transparent;
        }
        
        /* Info stats */
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: 800;
            color: #FF6B35;
        }
        
        .stat-label {
            font-size: 0.7em;
            color: #666;
            margin-top: 5px;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            font-size: 0.75em;
            color: #999;
        }
        
        .footer a {
            color: #FF6B35;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: #FF8C42;
            text-decoration: underline;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-icons a {
            color: #999;
            font-size: 1.2em;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            color: #FF6B35;
            transform: translateY(-3px);
        }
        
        /* Floating elements */
        .floating-element {
            position: fixed;
            pointer-events: none;
            z-index: 0;
        }
        
        .floating-1 {
            top: 10%;
            left: 5%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-2 {
            bottom: 15%;
            right: 5%;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }
        
        /* Responsive untuk HP */
        @media (max-width: 768px) {
            .container-custom {
                padding: 15px;
                align-items: flex-start;
                padding-top: 50px;
            }
            
            .main-card {
                padding: 30px 25px;
                border-radius: 30px;
            }
            
            h1 {
                font-size: 1.6em;
            }
            
            .logo-icon {
                font-size: 3.5em;
            }
            
            .btn-menu {
                padding: 12px 16px;
                font-size: 0.95em;
            }
            
            .stats {
                margin: 20px 0;
                padding: 12px;
            }
            
            .stat-number {
                font-size: 1.2em;
            }
            
            .badge-new {
                top: -5px;
                right: -10px;
                font-size: 0.6em;
                padding: 3px 8px;
            }
            
            .floating-element {
                display: none;
            }
        }
        
        /* Untuk tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            .main-card {
                max-width: 550px;
                padding: 45px;
            }
            
            .btn-menu {
                padding: 14px 18px;
            }
        }
        
        /* Untuk desktop besar */
        @media (min-width: 1200px) {
            .main-card {
                max-width: 550px;
                padding: 55px;
            }
            
            .btn-menu {
                padding: 16px 24px;
                font-size: 1.05em;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .main-card {
                background: rgba(30, 30, 40, 0.98);
            }
            
            h1, .logo-icon {
                background: linear-gradient(135deg, #FF8C42, #FFB366);
                -webkit-background-clip: text;
                background-clip: text;
            }
            
            .subtitle {
                color: #aaa;
            }
            
            .stats {
                background: linear-gradient(135deg, #2a2a35, #252530);
            }
            
            .stat-label {
                color: #bbb;
            }
            
            .footer {
                border-top-color: #333;
                color: #777;
            }
        }
        
        /* Loading animation untuk button */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Background pattern -->
    <div class="bg-pattern"></div>
    
    <!-- Floating decorative elements -->
    <div class="floating-element floating-1">
        <i class="fas fa-utensils" style="font-size: 80px; opacity: 0.1; color: white;"></i>
    </div>
    <div class="floating-element floating-2">
        <i class="fas fa-qrcode" style="font-size: 60px; opacity: 0.1; color: white;"></i>
    </div>
    
    <div class="container-custom">
        <div class="main-card">
            <div class="text-center">
                <div class="logo-area">
                    <div class="logo-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="badge-new">
                        <i class="fas fa-star"></i> HOT
                    </span>
                </div>
                
                <h1>Resto Kasir</h1>
                <p class="subtitle">
                    <i class="fas fa-check-circle" style="color: #28a745; font-size: 0.8em;"></i> 
                    Sistem Manajemen & Pemesanan Restoran Terpercaya
                </p>
                
                <!-- Statistik sederhana (bisa diambil dari database) -->
                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-number">10+</div>
                        <div class="stat-label">Menu Favorit</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Layanan</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Pelanggan</div>
                    </div>
                </div>
                
                <!-- Menu Buttons dengan grid system -->
                <div class="d-grid gap-3 mt-4">
                    <?php if(isset($_SESSION['login']) && $_SESSION['login'] === true): ?>
                        <!-- Jika sudah login -->
                        <a href="kasir.php" class="btn-menu btn-kasir">
                            <i class="fas fa-tachometer-alt fa-fw"></i> 
                            Dashboard Kasir
                            <i class="fas fa-arrow-right fa-fw"></i>
                        </a>
                    <?php else: ?>
                        <!-- Jika belum login -->
                        <a href="login.php" class="btn-menu btn-kasir">
                            <i class="fas fa-sign-in-alt fa-fw"></i> 
                            Login Kasir
                            <i class="fas fa-lock fa-fw"></i>
                        </a>
                    <?php endif; ?>
                    
                    <a href="order.php?meja=1" class="btn-menu btn-pesan">
                        <i class="fas fa-utensils fa-fw"></i> 
                        Pesan Makanan
                        <i class="fas fa-shopping-cart fa-fw"></i>
                    </a>
                    
                    <a href="generate_qr.php" class="btn-menu btn-qr">
                        <i class="fas fa-qrcode fa-fw"></i> 
                        Cetak QR Meja
                        <i class="fas fa-print fa-fw"></i>
                    </a>
                    
                    <!-- Tombol tambahan untuk demo/promo -->
                    <a href="menu.php" class="btn-menu btn-outline-custom">
                        <i class="fas fa-book-open fa-fw"></i> 
                        Lihat Menu Lengkap
                    </a>
                </div>
                
                <!-- Informasi tambahan -->
                <div class="alert alert-info mt-4 mb-0" style="border-radius: 15px; background: #e8f4f8; border: none; font-size: 0.8em;">
                    <i class="fas fa-lightbulb"></i> 
                    <strong>Tips:</strong> Tempelkan QR Code di setiap meja. Pelanggan cukup scan → pesan langsung dari HP!
                </div>
                
                <div class="footer">
                    <div class="d-flex flex-column align-items-center gap-2">
                        <div>
                            <i class="fas fa-info-circle"></i> 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#helpModal">Bantuan</a> | 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">Tentang</a>
                        </div>
                        
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-whatsapp"></i></a>
                            <a href="#"><i class="fab fa-tiktok"></i></a>
                        </div>
                        
                        <small class="text-muted">
                            &copy; 2026 Resto Kasir | Version 2.0
                        </small>
                        
                        <?php if(!isset($_SESSION['login'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-question-circle"></i> 
                                Belum punya akun? <a href="register.php">Daftar di sini</a>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Bantuan -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="border-bottom: 2px solid #FF6B35;">
                    <h5 class="modal-title">
                        <i class="fas fa-question-circle" style="color: #FF6B35;"></i> 
                        Panduan Penggunaan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="accordion" id="helpAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    📱 Untuk Pelanggan
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    1. Scan QR Code di meja Anda<br>
                                    2. Isi nama dan pilih menu<br>
                                    3. Tambahkan ke keranjang<br>
                                    4. Klik "Pesan Sekarang"<br>
                                    5. Tunggu pesanan diantar
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    👨‍💼 Untuk Kasir
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    1. Login dengan akun kasir<br>
                                    2. Lihat daftar pesanan masuk<br>
                                    3. Update status pesanan<br>
                                    4. Proses pembayaran<br>
                                    5. Cetak struk
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    🖨️ Cetak QR Meja
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    1. Klik "Cetak QR Meja"<br>
                                    2. Pilih nomor meja<br>
                                    3. Print atau download QR<br>
                                    4. Tempelkan di meja
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="order.php?meja=1" class="btn btn-primary" style="background: #FF6B35; border: none;">Coba Pesan →</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Tentang -->
    <div class="modal fade" id="aboutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="border-bottom: 2px solid #FF6B35;">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle" style="color: #FF6B35;"></i> 
                        Tentang Resto Kasir
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="logo-icon mb-3" style="font-size: 3em;">
                        <i class="fas fa-store"></i>
                    </div>
                    <h6>Resto Kasir v2.0</h6>
                    <p class="text-muted small">
                        Sistem manajemen restoran dengan fitur pemesanan via QR Code.<br>
                        Dibangun dengan teknologi modern untuk pengalaman terbaik.
                    </p>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <small><strong>Fitur:</strong></small>
                            <ul class="text-start small">
                                <li>✅ QR Code Order</li>
                                <li>✅ Manajemen Menu</li>
                                <li>✅ Laporan Real-time</li>
                            </ul>
                        </div>
                        <div class="col-6">
                            <small><strong>Teknologi:</strong></small>
                            <ul class="text-start small">
                                <li>🚀 PHP 8+</li>
                                <li>💾 MySQL</li>
                                <li>🎨 Bootstrap 5</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Tambahan script untuk efek interaktif -->
    <script>
        // Efek loading saat klik button
        document.querySelectorAll('.btn-menu').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if(!this.classList.contains('btn-outline-custom') || 
                   this.getAttribute('href') !== '#') {
                    this.classList.add('btn-loading');
                    setTimeout(() => {
                        this.classList.remove('btn-loading');
                    }, 1000);
                }
            });
        });
        
        // Efek hover smooth
        const card = document.querySelector('.main-card');
        if(card) {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
            });
        }
        
        // Cek apakah sudah login
        <?php if(isset($_SESSION['login']) && $_SESSION['login'] === true): ?>
            console.log('✅ User sudah login sebagai: <?= $_SESSION['username'] ?? '' ?>');
        <?php endif; ?>
    </script>
</body>
</html>