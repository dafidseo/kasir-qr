<?php
session_start();
include 'koneksi.php';

if(!isset($_GET['id'])) {
    die("ID Pesanan tidak ditemukan!");
}

$id_pesanan = (int)$_GET['id'];
$pesanan = mysqli_query($conn, "SELECT p.*, m.nomor_meja FROM pesanan p 
                                JOIN meja m ON p.id_meja = m.id_meja 
                                WHERE p.id_pesanan = $id_pesanan");
if(mysqli_num_rows($pesanan) == 0) {
    die("Pesanan tidak ditemukan!");
}
$p = mysqli_fetch_assoc($pesanan);

$detail = mysqli_query($conn, "SELECT d.*, m.nama_item, m.harga FROM detail_pesanan d 
                               JOIN menu m ON d.id_menu = m.id_menu 
                               WHERE d.id_pesanan = $id_pesanan");

$qr_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar_qr FROM qr_pembayaran WHERE status='aktif' ORDER BY id_qr DESC LIMIT 1"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .struk {
            width: 350px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .header { text-align: center; border-bottom: 1px dashed #333; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { font-size: 1.2em; margin-bottom: 5px; }
        .header p { font-size: 0.7em; color: #666; }
        .info { margin-bottom: 15px; font-size: 0.8em; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .items { border-top: 1px dashed #333; border-bottom: 1px dashed #333; padding: 10px 0; margin: 10px 0; }
        .item { display: flex; justify-content: space-between; font-size: 0.8em; margin-bottom: 5px; }
        .total { display: flex; justify-content: space-between; font-weight: bold; font-size: 1em; margin-top: 10px; padding-top: 10px; border-top: 1px solid #333; }
        .qr-area { text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #333; }
        .qr-area img { width: 120px; height: 120px; margin-top: 10px; }
        .footer { text-align: center; margin-top: 15px; font-size: 0.7em; color: #666; border-top: 1px dashed #333; padding-top: 10px; }
        .btn-print { background: #FF6B35; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 1em; margin-top: 20px; width: 100%; }
        @media print { body { background: white; } .btn-print { display: none; } .struk { box-shadow: none; padding: 0; } }
    </style>
</head>
<body>
    <div class="struk">
        <div class="header">
            <h2>🍽️ RESTO NUSANTARA</h2>
            <p>Jl. Makan Enak No. 123<br>Telp: 0812-3456-7890</p>
        </div>
        
        <div class="info">
            <div class="info-row"><span>No. Order</span><span>#<?= str_pad($p['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></span></div>
            <div class="info-row"><span>Tanggal</span><span><?= date('d/m/Y H:i:s', strtotime($p['waktu_pesan'])) ?></span></div>
            <div class="info-row"><span>Meja</span><span><?= $p['nomor_meja'] ?></span></div>
            <div class="info-row"><span>Pelanggan</span><span><?= htmlspecialchars($p['nama_pelanggan']) ?></span></div>
            <?php if(!empty($p['catatan'])): ?>
                <div class="info-row"><span>Catatan</span><span><?= htmlspecialchars($p['catatan']) ?></span></div>
            <?php endif; ?>
        </div>
        
        <div class="items">
            <?php while($d = mysqli_fetch_assoc($detail)): ?>
            <div class="item"><span><?= $d['nama_item'] ?> x<?= $d['jumlah'] ?></span><span>Rp <?= number_format($d['subtotal'],0,',','.') ?></span></div>
            <?php endwhile; ?>
        </div>
        
        <div class="total"><span>TOTAL</span><span>Rp <?= number_format($p['total_harga'],0,',','.') ?></span></div>
        
        <div class="qr-area">
            <p>Scan QR untuk Pembayaran</p>
            <?php if($qr_aktif && $qr_aktif['gambar_qr'] && file_exists('uploads/qr/' . $qr_aktif['gambar_qr'])): ?>
                <img src="uploads/qr/<?= $qr_aktif['gambar_qr'] ?>" alt="QR Payment">
            <?php else: ?>
                <div style="width:120px;height:120px;background:#f0f0f0;margin:0 auto;display:flex;align-items:center;justify-content:center;border-radius:10px;"><span>QR Code</span></div>
            <?php endif; ?>
            <p style="font-size:0.7em; margin-top:5px;">QRIS / Scan to Pay</p>
        </div>
        
        <div class="footer"><p>Terima Kasih!<br>Silakan scan QR untuk pembayaran</p></div>
    </div>
    
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
    <script>setTimeout(() => { window.print(); }, 500);</script>
</body>
</html>