<?php
include 'koneksi.php';

// Ambil semua meja
$meja = mysqli_query($conn, "SELECT * FROM meja ORDER BY nomor_meja");

// Fungsi generate QR Code menggunakan library PHP QR Code
// Atau fallback ke API jika tidak ada library

// Deteksi base URL secara dinamis
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Cek apakah ini localhost atau domain
    if ($host == 'localhost' || strpos($host, '192.168.') === 0 || strpos($host, '10.') === 0) {
        // Untuk local network, gunakan IP server
        $server_ip = $_SERVER['SERVER_ADDR'];
        if ($server_ip != '::1' && $server_ip != '127.0.0.1') {
            $host = $server_ip;
        }
    }
    
    // Hapus port jika ada untuk URL bersih
    $host = explode(':', $host)[0];
    
    return $protocol . $host;
}

$base_url = getBaseUrl();
$base_url_with_port = $base_url . ':' . $_SERVER['SERVER_PORT'];
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Gunakan URL yang benar
$order_page_url = $base_url_with_port . $base_path . '/order.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Meja - Resto Kasir</title>
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
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header h1 i {
            font-size: 1.2em;
        }
        
        .header-subtitle {
            font-size: 0.85em;
            opacity: 0.9;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: white;
            color: #FF6B35;
        }
        
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: scale(1.02);
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        /* QR Grid */
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .qr-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
        }
        
        .qr-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .meja-nomor {
            font-size: 1.4em;
            font-weight: 700;
            color: #FF6B35;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .meja-nomor i {
            font-size: 1.2em;
        }
        
        .qr-image {
            width: 180px;
            height: 180px;
            margin: 15px auto;
            background: white;
            padding: 10px;
            border-radius: 15px;
        }
        
        .qr-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .url-text {
            font-size: 0.7em;
            color: #666;
            word-break: break-all;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
        }
        
        .card-footer {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-card {
            padding: 8px 16px;
            font-size: 0.85em;
        }
        
        /* Loading */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: #FF6B35;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Print Styles */
        @media print {
            .header, .btn-group, .card-footer, .btn-card, .no-print {
                display: none !important;
            }
            
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .container {
                max-width: 100%;
                padding: 10px;
            }
            
            .qr-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .qr-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
                padding: 15px;
            }
            
            .qr-image {
                width: 150px;
                height: 150px;
            }
            
            .url-text {
                font-size: 0.6em;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            
            .qr-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .qr-card {
                padding: 15px;
            }
            
            .qr-image {
                width: 150px;
                height: 150px;
            }
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            display: none;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<div class="loading" id="loading">
    <div class="spinner"></div>
    <div style="color: white;">Memuat QR Code...</div>
</div>

<div class="toast" id="toast"></div>

<div class="container">
    <div class="header">
        <div>
            <h1>
                <i class="fas fa-qrcode"></i> 
                QR Code Meja Restoran
            </h1>
            <div class="header-subtitle">
                <i class="fas fa-info-circle"></i> 
                Tempelkan QR di setiap meja. Pelanggan scan → pesan langsung!
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="cetakSemua()">
                <i class="fas fa-print"></i> Cetak Semua
            </button>
            <button class="btn btn-success" onclick="cetakPerMeja()" id="btnCetakPerMeja" style="display:none;">
                <i class="fas fa-print"></i> Cetak Meja Ini
            </button>
            <button class="btn btn-info" onclick="downloadAllQR()">
                <i class="fas fa-download"></i> Download Semua
            </button>
        </div>
    </div>
    
    <div class="qr-grid" id="qrGrid">
        <?php while($row = mysqli_fetch_assoc($meja)): 
            $url = $order_page_url . "?meja=" . $row['id_meja'];
            // Gunakan library QR Code lokal jika ada, fallback ke API
            $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
        ?>
        <div class="qr-card" data-meja="<?= $row['nomor_meja'] ?>" data-url="<?= htmlspecialchars($url) ?>">
            <div class="meja-nomor">
                <i class="fas fa-chair"></i>
                Meja <?= $row['nomor_meja'] ?>
            </div>
            <div class="qr-image">
                <img src="<?= $qr_api_url ?>" 
                     alt="QR Code Meja <?= $row['nomor_meja'] ?>"
                     loading="lazy"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\' viewBox=\'0 0 200 200\'%3E%3Crect width=\'200\' height=\'200\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-family=\'Arial\' font-size=\'12\'%3EQR Code%3C/text%3E%3C/svg%3E'">
            </div>
            <div class="url-text">
                <i class="fas fa-link"></i> <?= $url ?>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary btn-card" onclick="cetakSatu(this)">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <button class="btn btn-info btn-card" onclick="downloadQR(this)">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Template untuk print per meja (hidden) -->
<div id="printTemplate" style="display: none;">
    <div style="text-align: center; padding: 20px; font-family: 'Poppins', sans-serif;">
        <div style="font-size: 24px; font-weight: bold; color: #FF6B35; margin-bottom: 10px;">
            🍽️ Dafid SEO
        </div>
        <div style="font-size: 18px; margin-bottom: 20px;">
            Scan QR untuk memesan
        </div>
        <div id="printQRImage" style="margin: 20px auto;">
            <img id="printQRImg" src="" style="width: 200px; height: 200px;">
        </div>
        <div id="printMejaNumber" style="font-size: 20px; font-weight: bold; margin: 10px 0;"></div>
        <div id="printUrl" style="font-size: 10px; color: #666; word-break: break-all; margin-top: 10px;"></div>
        <div style="margin-top: 20px; font-size: 10px; color: #999;">
            Tempelkan QR ini di meja | Scan dengan HP untuk pesan
        </div>
    </div>
</div>

<script>
// Tampilkan loading saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    showLoading(false);
});

function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'flex' : 'none';
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.backgroundColor = isError ? '#dc3545' : '#28a745';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Cetak semua QR
function cetakSemua() {
    window.print();
}

// Cetak satu QR saja
function cetakSatu(button) {
    const card = button.closest('.qr-card');
    const meja = card.querySelector('.meja-nomor').innerText;
    const qrImg = card.querySelector('.qr-image img').src;
    const url = card.querySelector('.url-text').innerText.replace('🔗 ', '');
    
    // Buat window baru untuk print
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak QR Meja ${meja}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background: white;
                }
                .qr-container {
                    text-align: center;
                    padding: 30px;
                    border: 1px solid #ddd;
                    border-radius: 20px;
                    max-width: 400px;
                    margin: 20px auto;
                }
                .resto-name {
                    font-size: 24px;
                    font-weight: bold;
                    color: #FF6B35;
                    margin-bottom: 10px;
                }
                .subtitle {
                    font-size: 14px;
                    color: #666;
                    margin-bottom: 20px;
                }
                .qr-image {
                    margin: 20px auto;
                }
                .qr-image img {
                    width: 250px;
                    height: 250px;
                    object-fit: contain;
                }
                .meja-number {
                    font-size: 22px;
                    font-weight: bold;
                    margin: 15px 0;
                    color: #FF6B35;
                }
                .url {
                    font-size: 10px;
                    color: #999;
                    word-break: break-all;
                    margin-top: 15px;
                }
                .footer {
                    margin-top: 20px;
                    font-size: 10px;
                    color: #ccc;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    .qr-container { border: none; }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <div class="resto-name">🍽️ Dafid SEO</div>
                <div class="subtitle">Scan QR untuk memesan</div>
                <div class="qr-image">
                    <img src="${qrImg}" alt="QR Code">
                </div>
                <div class="meja-number">${meja}</div>
                <div class="url">${url}</div>
                <div class="footer">Tempelkan di meja | Scan dengan HP</div>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(() => { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Cetak per meja (untuk cetak banyak sekaligus tapi per kartu)
let currentMejaToPrint = null;

function cetakPerMeja() {
    if (currentMejaToPrint) {
        cetakSatu(currentMejaToPrint);
        currentMejaToPrint = null;
        document.getElementById('btnCetakPerMeja').style.display = 'none';
    }
}

// Download QR Code
async function downloadQR(button) {
    const card = button.closest('.qr-card');
    const meja = card.querySelector('.meja-nomor').innerText;
    const qrImg = card.querySelector('.qr-image img');
    
    try {
        showLoading(true);
        
        // Fetch gambar
        const response = await fetch(qrImg.src);
        const blob = await response.blob();
        
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qr_meja_${meja.replace('Meja ', '')}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showToast(`✅ QR ${meja} berhasil didownload`);
    } catch (error) {
        console.error('Download gagal:', error);
        showToast('❌ Gagal download QR', true);
    } finally {
        showLoading(false);
    }
}

// Download semua QR (membuat zip)
async function downloadAllQR() {
    const cards = document.querySelectorAll('.qr-card');
    const qrImages = [];
    
    showLoading(true);
    
    for (let card of cards) {
        const meja = card.querySelector('.meja-nomor').innerText;
        const qrImg = card.querySelector('.qr-image img');
        
        try {
            const response = await fetch(qrImg.src);
            const blob = await response.blob();
            qrImages.push({
                name: `qr_meja_${meja.replace('Meja ', '')}.png`,
                blob: blob
            });
        } catch (error) {
            console.error('Gagal download:', error);
        }
    }
    
    // Untuk download semua, kita buat zip (perlu library JSZip)
    // Atau download satu per satu dengan delay
    if (qrImages.length > 0) {
        let i = 0;
        function downloadNext() {
            if (i < qrImages.length) {
                const url = window.URL.createObjectURL(qrImages[i].blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = qrImages[i].name;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                i++;
                setTimeout(downloadNext, 500);
            } else {
                showToast(`✅ ${qrImages.length} QR berhasil didownload`);
                showLoading(false);
            }
        }
        downloadNext();
    } else {
        showLoading(false);
        showToast('❌ Gagal download QR', true);
    }
}

// Fungsi untuk memilih meja yang akan dicetak
document.querySelectorAll('.qr-card').forEach(card => {
    card.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        const meja = this.querySelector('.meja-nomor').innerText;
        if (confirm(`Cetak ${meja}?`)) {
            cetakSatu(this);
        }
    });
});

// Tooltip untuk right-click
console.log('💡 Tips: Klik kanan pada kartu QR untuk cetak cepat');
</script>

</body>
</html>