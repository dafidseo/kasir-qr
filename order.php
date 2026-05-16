<?php
// order.php - Handle AJAX request untuk simpan pesanan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'koneksi.php';
    
    $items = json_decode($_POST['items'], true);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $nomor_meja = (int)$_POST['nomor_meja'];
    $catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
    $total = 0;
    
    foreach ($items as $item) { 
        $total += $item['harga'] * $item['jumlah']; 
    }
    
    // Cari atau buat meja
    $cari_meja = mysqli_query($conn, "SELECT id_meja FROM meja WHERE nomor_meja = $nomor_meja");
    if (mysqli_num_rows($cari_meja) == 0) {
        mysqli_query($conn, "INSERT INTO meja (nomor_meja) VALUES ($nomor_meja)");
        $id_meja_baru = mysqli_insert_id($conn);
    } else {
        $id_meja_baru = mysqli_fetch_assoc($cari_meja)['id_meja'];
    }
    
    // Insert pesanan
    $query = "INSERT INTO pesanan (id_meja, nama_pelanggan, catatan, total_harga, status) 
              VALUES ($id_meja_baru, '$nama', '$catatan', $total, 'pending')";
    mysqli_query($conn, $query);
    $id_pesanan = mysqli_insert_id($conn);
    
    // Insert detail pesanan dengan varian
    foreach ($items as $item) {
        $harga_item = $item['harga'];
        $nama_varian_display = '';
        $harga_varian_tambahan = 0;
        
        if(isset($item['id_varian']) && $item['id_varian'] > 0) {
            $varian = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_varian, harga_tambahan FROM varian_menu WHERE id_varian = {$item['id_varian']}"));
            if($varian) {
                $harga_item = $item['harga'] + $varian['harga_tambahan'];
                $nama_varian_display = $varian['nama_varian'];
                $harga_varian_tambahan = $varian['harga_tambahan'];
            }
        }
        $subtotal = $harga_item * $item['jumlah'];
        
        $id_varian = $item['id_varian'] ?? 0;
        $query = "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, subtotal, id_varian, nama_varian, harga_varian) 
                  VALUES ($id_pesanan, {$item['id']}, {$item['jumlah']}, $harga_item, $subtotal, $id_varian, '$nama_varian_display', $harga_varian_tambahan)";
        mysqli_query($conn, $query);
    }
    
    echo json_encode(['status' => 'success', 'id_pesanan' => $id_pesanan]);
    exit;
}

// ============================================
// TAMPILAN HALAMAN ORDER
// ============================================

require_once 'koneksi.php';

// Validasi id_meja
$id_meja = isset($_GET['meja']) ? (int)$_GET['meja'] : 0;
$nomor_meja_otomatis = '';

if ($id_meja > 0) {
    $meja_data = mysqli_query($conn, "SELECT nomor_meja FROM meja WHERE id_meja = $id_meja");
    if ($meja_data && mysqli_num_rows($meja_data) > 0) {
        $nomor_meja_otomatis = mysqli_fetch_assoc($meja_data)['nomor_meja'];
    }
}

// ============================================
// AMBIL MENU DENGAN VARIAN
// ============================================

// Ambil semua menu yang aktif
$query_menu = "SELECT m.*, k.nama_kategori, k.icon, k.urutan 
               FROM menu m 
               LEFT JOIN kategori k ON m.id_kategori = k.id_kategori 
               WHERE m.status = 'aktif' AND m.stok > 0 
               ORDER BY k.urutan ASC, m.nama_item ASC";

$semua_menu = mysqli_query($conn, $query_menu);

// Ambil semua varian yang aktif
$query_varian = "SELECT * FROM varian_menu WHERE status = 'aktif' ORDER BY id_menu";
$semua_varian = mysqli_query($conn, $query_varian);

// Kelompokkan varian berdasarkan id_menu
$varian_by_menu = [];
while($varian = mysqli_fetch_assoc($semua_varian)) {
    if(!isset($varian_by_menu[$varian['id_menu']])) {
        $varian_by_menu[$varian['id_menu']] = [];
    }
    $varian_by_menu[$varian['id_menu']][] = $varian;
}

// Kelompokkan menu berdasarkan kategori
$menu_by_kategori = [];
while($row = mysqli_fetch_assoc($semua_menu)) {
    $kategori_id = $row['id_kategori'] ?? 0;
    $kategori_nama = $row['nama_kategori'] ?? 'Lainnya';
    $kategori_icon = $row['icon'] ?? 'fa-utensils';
    
    if(!isset($menu_by_kategori[$kategori_id])) {
        $menu_by_kategori[$kategori_id] = [
            'nama' => $kategori_nama,
            'icon' => $kategori_icon,
            'items' => []
        ];
    }
    
    // Tambahkan varian ke menu (hanya jika ada)
    $row['has_varian'] = isset($varian_by_menu[$row['id_menu']]) && count($varian_by_menu[$row['id_menu']]) > 0;
    $row['varian'] = $varian_by_menu[$row['id_menu']] ?? [];
    $menu_by_kategori[$kategori_id]['items'][] = $row;
}

// Fungsi helper
function getEmojiForItem($nama_item) {
    $nama_lower = strtolower($nama_item);
    $emoji_map = [
        'nasi' => '🍚', 'mie' => '🍜', 'ayam' => '🍗', 'sate' => '🍢',
        'bakso' => '🥣', 'ikan' => '🐟', 'tahu' => '🥟', 'tempe' => '🥟',
        'teh' => '🧋', 'jus' => '🍊', 'kopi' => '☕', 'kelapa' => '🥥',
        'coklat' => '🍫', 'susu' => '🥛', 'kentang' => '🍟', 'pisang' => '🍌'
    ];
    foreach ($emoji_map as $key => $emoji) {
        if (strpos($nama_lower, $key) !== false) return $emoji;
    }
    return '🍽️';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Pesan Makanan - Resto Nusantara</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding-bottom: 0;
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header h2 { font-size: 1.3em; margin-bottom: 8px; }
        .header p { font-size: 0.8em; opacity: 0.9; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .kategori-title {
            font-size: 1.3em;
            font-weight: 700;
            margin: 25px 0 15px 0;
            padding-left: 15px;
            border-left: 4px solid #FF6B35;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .menu-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(255,107,53,0.2); }
        .menu-card:active { transform: scale(0.97); }
        
        .menu-gambar {
            height: 140px;
            background: linear-gradient(135deg, #FFF5F0, #FFE8DF);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .menu-gambar-img { width: 100px; height: 100px; object-fit: cover; border-radius: 15px; }
        .menu-gambar-emoji { font-size: 3.5em; }
        
        .menu-info { padding: 15px; text-align: center; }
        .menu-nama { font-weight: 700; font-size: 1em; color: #333; margin-bottom: 5px; }
        .menu-harga { color: #FF6B35; font-weight: 700; font-size: 1.1em; }
        .menu-harga::before { content: "Rp "; font-size: 0.8em; }
        .badge-varian {
            display: inline-block;
            background: #FFE0D0;
            color: #FF6B35;
            font-size: 0.6em;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 5px;
        }
        .badge-menu {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #FF6B35;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.65em;
            font-weight: 600;
        }
        .badge-stok {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65em;
        }
        
        /* Modal Varian */
        .modal-varian {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-varian.show { display: flex; }
        .modal-varian-content {
            background: white;
            border-radius: 20px;
            padding: 25px;
            max-width: 400px;
            width: 90%;
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-varian-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFE0D0;
        }
        .modal-varian-header h3 { color: #FF6B35; }
        .close-varian { background: none; border: none; font-size: 1.5em; cursor: pointer; color: #999; }
        .varian-list { margin-bottom: 20px; max-height: 300px; overflow-y: auto; }
        .varian-option {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .varian-option:hover { background: #FFE8DF; transform: translateX(5px); }
        .varian-option.selected { background: #FF6B35; color: white; }
        .varian-option.selected .varian-harga { color: white; }
        .varian-nama { font-weight: 500; }
        .varian-harga { color: #FF6B35; font-size: 0.8em; }
        .btn-confirm {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 30px rgba(0,0,0,0.15);
            z-index: 1000;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .cart-sidebar.open { right: 0; }
        .cart-header {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-header h3 { margin: 0; font-size: 1.2em; }
        .close-cart { background: none; border: none; color: white; font-size: 1.5em; cursor: pointer; }
        .cart-items { flex: 1; overflow-y: auto; padding: 15px; }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item-info h4 { font-size: 0.95em; margin-bottom: 4px; }
        .cart-item-info p { color: #FF6B35; font-weight: 600; font-size: 0.85em; }
        .cart-item-controls { display: flex; align-items: center; gap: 10px; }
        .btn-qty {
            background: #f5f5f5;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            color: #FF6B35;
            transition: all 0.2s;
        }
        .btn-qty:hover { background: #FF6B35; color: white; }
        .cart-footer {
            padding: 20px;
            border-top: 2px solid #FFE0D0;
            background: #FFF9F5;
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        .btn-order {
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-order:hover { transform: scale(1.02); }
        .cart-floating {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #FF6B35, #FF8C42);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 99;
            transition: transform 0.2s;
        }
        .cart-floating:hover { transform: scale(1.05); }
        .cart-floating span {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #FFE600;
            color: #FF6B35;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.7em;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification {
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 500;
            z-index: 1001;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
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
        @keyframes spin { to { transform: rotate(360deg); } }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .overlay.active { display: block; }
        
        @media (max-width: 768px) {
            .cart-sidebar { width: 100%; right: -100%; }
            .container { padding: 15px; margin-bottom: 80px; }
            .menu-grid { gap: 12px; }
            .menu-gambar { height: 110px; }
            .menu-gambar-img { width: 70px; height: 70px; }
            .menu-gambar-emoji { font-size: 2.8em; }
        }
        @media (min-width: 769px) {
            .container { max-width: 1000px; }
            .menu-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; }
        }
    </style>
</head>
<body>

<div class="header">
    <h2><i class="fas fa-utensils"></i> Resto Nusantara</h2>
    <p><i class="fas fa-qrcode"></i> Scan QR di meja Anda untuk memesan</p>
</div>

<div class="container">
    <div class="form-card">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Nama Anda <span style="color: #FF6B35;">*</span></label>
            <input type="text" id="namaPelanggan" placeholder="Contoh: Ahmad Budi" autocomplete="off">
        </div>
        <div class="form-group">
            <label><i class="fas fa-chair"></i> Nomor Meja <span style="color: #FF6B35;">*</span></label>
            <input type="number" id="nomorMeja" placeholder="Contoh: 5" value="<?= htmlspecialchars($nomor_meja_otomatis) ?>" autocomplete="off">
        </div>
        <div class="form-group">
            <label><i class="fas fa-pencil-alt"></i> Catatan (Opsional)</label>
            <textarea id="catatanPesanan" rows="3" placeholder="Contoh: tidak pakai pedas, request sambal terpisah, tambah es batu"></textarea>
        </div>
    </div>
    
    <?php foreach($menu_by_kategori as $kategori_id => $kategori_data): 
        if(count($kategori_data['items']) > 0):
    ?>
    <div class="kategori-title">
        <i class="fas <?= $kategori_data['icon'] ?>"></i>
        <?= htmlspecialchars($kategori_data['nama']) ?>
    </div>
    <div class="menu-grid">
        <?php foreach($kategori_data['items'] as $row): ?>
        <div class="menu-card" 
             data-id="<?= $row['id_menu'] ?>"
             data-nama="<?= htmlspecialchars($row['nama_item']) ?>"
             data-harga="<?= $row['harga'] ?>"
             data-has-varian="<?= $row['has_varian'] ? 'true' : 'false' ?>"
             data-varian='<?= json_encode($row['varian']) ?>'
             onclick="handleMenuClick(this)">
            <div class="menu-gambar">
                <?php if(!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])): ?>
                    <img src="uploads/<?= $row['gambar'] ?>" class="menu-gambar-img">
                <?php else: ?>
                    <div class="menu-gambar-emoji"><?= getEmojiForItem($row['nama_item']) ?></div>
                <?php endif; ?>
            </div>
            <div class="menu-info">
                <div class="menu-nama"><?= htmlspecialchars($row['nama_item']) ?></div>
                <div class="menu-harga"><?= number_format($row['harga'],0,',','.') ?></div>
                <?php if($row['has_varian']): ?>
                    <div class="badge-varian"><i class="fas fa-layer-group"></i> Ada Varian</div>
                <?php endif; ?>
            </div>
            <div class="badge-menu"><?= strtoupper(substr($kategori_data['nama'], 0, 7)) ?></div>
            <?php if($row['stok'] <= 0): ?>
                <div class="badge-stok">Stok Habis</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; endforeach; ?>
</div>

<!-- Modal Pilihan Varian -->
<div id="varianModal" class="modal-varian">
    <div class="modal-varian-content">
        <div class="modal-varian-header">
            <h3 id="modalVarianTitle">Pilih Varian</h3>
            <button class="close-varian" onclick="closeVarianModal()">&times;</button>
        </div>
        <div id="varianList" class="varian-list"></div>
        <button class="btn-confirm" onclick="confirmAddToCart()">Tambah ke Keranjang</button>
    </div>
</div>

<!-- Floating Cart Button -->
<div class="cart-floating" onclick="openCart()">
    <i class="fas fa-shopping-cart"></i>
    <span id="floatingCartCount">0</span>
</div>

<!-- Sidebar Cart -->
<div class="overlay" id="overlay" onclick="closeCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
        <button class="close-cart" onclick="closeCart()">&times;</button>
    </div>
    <div class="cart-items" id="cartItemsList">
        <div style="text-align: center; padding: 40px; color: #999;">
            <i class="fas fa-shopping-basket" style="font-size: 3em; margin-bottom: 10px; display: block;"></i>
            Keranjang masih kosong
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total"><span>Total</span><span id="cartTotalPrice" style="color:#FF6B35;">Rp 0</span></div>
        <button class="btn-order" onclick="submitOrder()"><i class="fas fa-paper-plane"></i> Pesan Sekarang</button>
    </div>
</div>

<div id="notification" class="notification" style="display: none;"></div>
<div id="loading" class="loading"><div class="spinner"></div><div>Memproses pesanan...</div></div>

<script>
let cart = [];
let selectedMenu = null;
let selectedVarian = null;

function showNotification(msg, isError) {
    const n = document.getElementById('notification');
    n.textContent = msg;
    n.style.backgroundColor = isError ? '#dc3545' : '#28a745';
    n.style.display = 'block';
    setTimeout(() => n.style.display = 'none', 3000);
}

function openCart() {
    if(cart.length === 0) { showNotification('Keranjang kosong!', true); return; }
    document.getElementById('cartSidebar').classList.add('open');
    document.getElementById('overlay').classList.add('active');
}

function closeCart() {
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('active');
}

function handleMenuClick(element) {
    const hasVarian = element.getAttribute('data-has-varian') === 'true';
    const menuData = {
        id: parseInt(element.getAttribute('data-id')),
        nama: element.getAttribute('data-nama'),
        harga: parseInt(element.getAttribute('data-harga')),
        varian: JSON.parse(element.getAttribute('data-varian'))
    };
    
    if(hasVarian && menuData.varian && menuData.varian.length > 0) {
        // Tampilkan modal pilihan varian
        showVarianModal(menuData);
    } else {
        // Langsung tambah ke keranjang tanpa varian
        addToCartDirect(menuData);
    }
}

function showVarianModal(menu) {
    selectedMenu = menu;
    selectedVarian = null;
    
    document.getElementById('modalVarianTitle').innerHTML = `Pilih Varian - ${menu.nama}`;
    
    const varianList = document.getElementById('varianList');
    let html = '';
    menu.varian.forEach(v => {
        const harga_tambahan = v.harga_tambahan > 0 ? ` (+Rp ${v.harga_tambahan.toLocaleString('id-ID')})` : '';
        html += `
            <div class="varian-option" onclick="selectVarian(${v.id_varian}, '${v.nama_varian}', ${v.harga_tambahan}, this)">
                <span class="varian-nama">${v.nama_varian}</span>
                <span class="varian-harga">${harga_tambahan}</span>
            </div>
        `;
    });
    varianList.innerHTML = html;
    
    document.getElementById('varianModal').classList.add('show');
}

function selectVarian(id, nama, harga_tambahan, element) {
    selectedVarian = { id, nama, harga_tambahan };
    // Highlight selected
    document.querySelectorAll('.varian-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
}

function closeVarianModal() {
    document.getElementById('varianModal').classList.remove('show');
    selectedMenu = null;
    selectedVarian = null;
}

function confirmAddToCart() {
    if(!selectedMenu) return;
    
    if(selectedVarian) {
        // Tambah dengan varian
        const itemToAdd = {
            id: selectedMenu.id,
            nama: selectedMenu.nama,
            harga: selectedMenu.harga,
            id_varian: selectedVarian.id,
            nama_varian: selectedVarian.nama,
            harga_varian: selectedVarian.harga_tambahan,
            jumlah: 1,
            display_name: `${selectedMenu.nama} (${selectedVarian.nama})`
        };
        addToCart(itemToAdd);
    } else {
        // Tambah tanpa varian (jika tidak pilih, pakai yang pertama)
        const firstVarian = selectedMenu.varian[0];
        if(firstVarian) {
            const itemToAdd = {
                id: selectedMenu.id,
                nama: selectedMenu.nama,
                harga: selectedMenu.harga,
                id_varian: firstVarian.id_varian,
                nama_varian: firstVarian.nama_varian,
                harga_varian: firstVarian.harga_tambahan,
                jumlah: 1,
                display_name: `${selectedMenu.nama} (${firstVarian.nama_varian})`
            };
            addToCart(itemToAdd);
        } else {
            addToCartDirect(selectedMenu);
        }
    }
    
    closeVarianModal();
}

function addToCartDirect(menu) {
    const itemToAdd = {
        id: menu.id,
        nama: menu.nama,
        harga: menu.harga,
        id_varian: 0,
        nama_varian: '',
        harga_varian: 0,
        jumlah: 1,
        display_name: menu.nama
    };
    addToCart(itemToAdd);
}

function addToCart(item) {
    const finalHarga = item.harga + (item.harga_varian || 0);
    const existing = cart.find(i => i.id === item.id && i.id_varian === (item.id_varian || 0));
    
    if(existing) {
        existing.jumlah++;
        showNotification(`👍 ${item.display_name} (${existing.jumlah}x)`, false);
    } else {
        cart.push({
            id: item.id,
            id_varian: item.id_varian || 0,
            nama: item.nama,
            nama_varian: item.nama_varian || '',
            display_name: item.display_name,
            harga: finalHarga,
            harga_asli: item.harga,
            harga_varian: item.harga_varian || 0,
            jumlah: 1
        });
        showNotification(`✅ ${item.display_name} ditambahkan`, false);
    }
    updateCart();
}

function updateQty(id, id_varian, delta) {
    const item = cart.find(i => i.id === id && i.id_varian === id_varian);
    if(item) {
        item.jumlah += delta;
        if(item.jumlah <= 0) cart = cart.filter(i => !(i.id === id && i.id_varian === id_varian));
        updateCart();
    }
}

function updateCart() {
    const container = document.getElementById('cartItemsList');
    const totalElem = document.getElementById('cartTotalPrice');
    const countSpan = document.getElementById('floatingCartCount');
    let total = 0, totalItems = 0;
    
    if(cart.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><i class="fas fa-shopping-basket" style="font-size:3em;"></i><br>Keranjang kosong</div>';
        totalElem.innerText = 'Rp 0';
        countSpan.innerText = '0';
        return;
    }
    
    container.innerHTML = '';
    cart.forEach(item => {
        total += item.harga * item.jumlah;
        totalItems += item.jumlah;
        const displayName = item.display_name || item.nama;
        container.innerHTML += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${escapeHtml(displayName)}</h4>
                    <p>Rp ${item.harga.toLocaleString('id-ID')}</p>
                </div>
                <div class="cart-item-controls">
                    <button class="btn-qty" onclick="updateQty(${item.id}, ${item.id_varian}, -1)">−</button>
                    <span style="font-weight:600;min-width:25px;text-align:center;">${item.jumlah}</span>
                    <button class="btn-qty" onclick="updateQty(${item.id}, ${item.id_varian}, 1)">+</button>
                </div>
            </div>
        `;
    });
    
    totalElem.innerText = 'Rp ' + total.toLocaleString('id-ID');
    countSpan.innerText = totalItems;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function submitOrder() {
    const nama = document.getElementById('namaPelanggan').value.trim();
    const nomorMeja = document.getElementById('nomorMeja').value.trim();
    const catatan = document.getElementById('catatanPesanan').value.trim();
    
    if(cart.length === 0) { showNotification('Keranjang kosong!', true); return; }
    if(!nama) { showNotification('Isi nama Anda!', true); document.getElementById('namaPelanggan').focus(); return; }
    if(!nomorMeja) { showNotification('Isi nomor meja!', true); document.getElementById('nomorMeja').focus(); return; }
    
    if(!confirm(`Konfirmasi pesanan untuk ${nama} di Meja ${nomorMeja}?`)) return;
    
    document.getElementById('loading').style.display = 'flex';
    
    try {
        const res = await fetch('order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'items=' + encodeURIComponent(JSON.stringify(cart.map(i => ({
                id: i.id, 
                jumlah: i.jumlah, 
                harga: i.harga_asli,
                id_varian: i.id_varian || 0
            })))) + 
                  '&nama=' + encodeURIComponent(nama) + 
                  '&nomor_meja=' + encodeURIComponent(nomorMeja) + 
                  '&catatan=' + encodeURIComponent(catatan)
        });
        const result = await res.json();
        if(result.status === 'success') {
            showNotification('✅ Pesanan berhasil!', false);
            cart = [];
            updateCart();
            document.getElementById('namaPelanggan').value = '';
            document.getElementById('catatanPesanan').value = '';
            closeCart();
        } else {
            showNotification('Gagal memesan', true);
        }
    } catch(e) {
        console.error(e);
        showNotification('Error koneksi', true);
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

updateCart();
</script>
</body>
</html>