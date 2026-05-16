<?php
session_start();
include 'koneksi.php';

// Cek login
if(!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Menu aktif
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// ============================================
// PROSES EDIT PROFILE
// ============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $id_user = $_SESSION['id_user'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    
    $foto_baru = '';
    if(isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['foto_profil']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $foto_baru = time() . '_profil_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
            $target = 'uploads/profil/' . $foto_baru;
            
            if(!file_exists('uploads/profil')) {
                mkdir('uploads/profil', 0777, true);
            }
            
            move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target);
            
            $cek_foto = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_profil FROM users WHERE id_user=$id_user"));
            if($cek_foto['foto_profil'] && file_exists('uploads/profil/' . $cek_foto['foto_profil'])) {
                unlink('uploads/profil/' . $cek_foto['foto_profil']);
            }
            
            mysqli_query($conn, "UPDATE users SET foto_profil='$foto_baru' WHERE id_user=$id_user");
            $_SESSION['foto_profil'] = $foto_baru;
        }
    }
    
    mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama_lengkap', email='$email', no_telepon='$no_telepon' WHERE id_user=$id_user");
    $_SESSION['nama_lengkap'] = $nama_lengkap;
    
    header("Location: kasir.php?page=profile");
    exit;
}

// Proses ganti password
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ganti_password'])) {
    $id_user = $_SESSION['id_user'];
    $password_lama = md5($_POST['password_lama']);
    $password_baru = md5($_POST['password_baru']);
    $konfirmasi = md5($_POST['konfirmasi_password']);
    
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE id_user=$id_user AND password='$password_lama'");
    if(mysqli_num_rows($cek) == 0) {
        $_SESSION['error_password'] = "Password lama salah!";
    } elseif($_POST['password_baru'] !== $_POST['konfirmasi_password']) {
        $_SESSION['error_password'] = "Password baru tidak cocok!";
    } elseif(strlen($_POST['password_baru']) < 6) {
        $_SESSION['error_password'] = "Password minimal 6 karakter!";
    } else {
        mysqli_query($conn, "UPDATE users SET password='$password_baru' WHERE id_user=$id_user");
        $_SESSION['success_password'] = "Password berhasil diubah!";
    }
    header("Location: kasir.php?page=profile");
    exit;
}

// ============================================
// PROSES KATEGORI
// ============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    mysqli_query($conn, "INSERT INTO kategori (nama_kategori, icon) VALUES ('$nama_kategori', '$icon')");
    header("Location: kasir.php?page=menu");
    exit;
}

if(isset($_GET['hapus_kategori'])) {
    $id = (int)$_GET['hapus_kategori'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id_kategori=$id");
    header("Location: kasir.php?page=menu");
    exit;
}

// ============================================
// PROSES MENU
// ============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_menu'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_item']);
    $id_kategori = (int)$_POST['id_kategori'];
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    
    $gambar_baru = '';
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $gambar_baru = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
            $target = 'uploads/' . $gambar_baru;
            if(!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['gambar']['tmp_name'], $target);
        }
    }
    
    if(isset($_POST['id_menu']) && $_POST['id_menu'] > 0) {
        $id = (int)$_POST['id_menu'];
        if($gambar_baru) {
            if($gambar_lama && file_exists('uploads/' . $gambar_lama)) {
                unlink('uploads/' . $gambar_lama);
            }
            mysqli_query($conn, "UPDATE menu SET nama_item='$nama', id_kategori=$id_kategori, harga=$harga, stok=$stok, gambar='$gambar_baru' WHERE id_menu=$id");
        } else {
            mysqli_query($conn, "UPDATE menu SET nama_item='$nama', id_kategori=$id_kategori, harga=$harga, stok=$stok WHERE id_menu=$id");
        }
    } else {
        if($gambar_baru) {
            mysqli_query($conn, "INSERT INTO menu (nama_item, id_kategori, harga, stok, gambar) VALUES ('$nama', $id_kategori, $harga, $stok, '$gambar_baru')");
        } else {
            mysqli_query($conn, "INSERT INTO menu (nama_item, id_kategori, harga, stok) VALUES ('$nama', $id_kategori, $harga, $stok)");
        }
    }
    header("Location: kasir.php?page=menu");
    exit;
}

// Hapus menu
if(isset($_GET['hapus_menu'])) {
    $id = (int)$_GET['hapus_menu'];
    $cek_gambar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM menu WHERE id_menu=$id"));
    if($cek_gambar['gambar'] && file_exists('uploads/' . $cek_gambar['gambar'])) {
        unlink('uploads/' . $cek_gambar['gambar']);
    }
    mysqli_query($conn, "DELETE FROM menu WHERE id_menu=$id");
    header("Location: kasir.php?page=menu");
    exit;
}

// Update stok
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    $id = (int)$_POST['id_menu'];
    $stok = (int)$_POST['stok'];
    mysqli_query($conn, "UPDATE menu SET stok=$stok WHERE id_menu=$id");
    header("Location: kasir.php?page=stock");
    exit;
}

// Hapus dari stock
if(isset($_GET['hapus_dari_stock'])) {
    $id = (int)$_GET['hapus_dari_stock'];
    $cek_gambar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM menu WHERE id_menu=$id"));
    if($cek_gambar['gambar'] && file_exists('uploads/' . $cek_gambar['gambar'])) {
        unlink('uploads/' . $cek_gambar['gambar']);
    }
    mysqli_query($conn, "DELETE FROM menu WHERE id_menu=$id");
    header("Location: kasir.php?page=stock");
    exit;
}

// ============================================
// PROSES VARIAN MENU
// ============================================
// Simpan varian
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_varian'])) {
    $id_menu = (int)$_POST['id_menu'];
    $nama_varian = mysqli_real_escape_string($conn, $_POST['nama_varian']);
    $harga_tambahan = (int)$_POST['harga_tambahan'];
    
    if(isset($_POST['id_varian']) && $_POST['id_varian'] > 0) {
        $id = (int)$_POST['id_varian'];
        mysqli_query($conn, "UPDATE varian_menu SET nama_varian='$nama_varian', harga_tambahan=$harga_tambahan WHERE id_varian=$id");
    } else {
        mysqli_query($conn, "INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) VALUES ($id_menu, '$nama_varian', $harga_tambahan)");
    }
    header("Location: kasir.php?page=varian");
    exit;
}

// Hapus varian
if(isset($_GET['hapus_varian'])) {
    $id = (int)$_GET['hapus_varian'];
    mysqli_query($conn, "DELETE FROM varian_menu WHERE id_varian=$id");
    header("Location: kasir.php?page=varian");
    exit;
}

// ============================================
// PROSES PESANAN
// ============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_detail_pesanan'])) {
    $id_detail = (int)$_POST['id_detail'];
    $jumlah_baru = (int)$_POST['jumlah'];
    $id_pesanan = (int)$_POST['id_pesanan'];
    
    $menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT m.harga FROM detail_pesanan d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_detail=$id_detail"));
    $subtotal_baru = $menu['harga'] * $jumlah_baru;
    
    if($jumlah_baru <= 0) {
        mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_detail=$id_detail");
    } else {
        mysqli_query($conn, "UPDATE detail_pesanan SET jumlah=$jumlah_baru, subtotal=$subtotal_baru WHERE id_detail=$id_detail");
    }
    
    $total_baru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(subtotal) as total FROM detail_pesanan WHERE id_pesanan=$id_pesanan"))['total'];
    mysqli_query($conn, "UPDATE pesanan SET total_harga=$total_baru WHERE id_pesanan=$id_pesanan");
    
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if(isset($_GET['hapus_item_pesanan'])) {
    $id_detail = (int)$_GET['hapus_item_pesanan'];
    $detail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_pesanan FROM detail_pesanan WHERE id_detail=$id_detail"));
    $id_pesanan = $detail['id_pesanan'];
    
    mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_detail=$id_detail");
    
    $total_baru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(subtotal) as total FROM detail_pesanan WHERE id_pesanan=$id_pesanan"))['total'];
    mysqli_query($conn, "UPDATE pesanan SET total_harga=" . ($total_baru ?? 0) . " WHERE id_pesanan=$id_pesanan");
    
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_item_pesanan'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $id_menu = (int)$_POST['id_menu'];
    $jumlah = (int)$_POST['jumlah'];
    
    $menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga FROM menu WHERE id_menu=$id_menu"));
    $subtotal = $menu['harga'] * $jumlah;
    
    mysqli_query($conn, "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, subtotal) VALUES ($id_pesanan, $id_menu, $jumlah, $subtotal)");
    
    $total_baru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(subtotal) as total FROM detail_pesanan WHERE id_pesanan=$id_pesanan"))['total'];
    mysqli_query($conn, "UPDATE pesanan SET total_harga=$total_baru WHERE id_pesanan=$id_pesanan");
    
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pesanan'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    mysqli_query($conn, "UPDATE pesanan SET nama_pelanggan='$nama_pelanggan', catatan='$catatan' WHERE id_pesanan=$id_pesanan");
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if(isset($_GET['proses'])) {
    $id = (int)$_GET['proses'];
    mysqli_query($conn, "UPDATE pesanan SET status='proses' WHERE id_pesanan=$id");
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if(isset($_GET['selesai'])) {
    $id = (int)$_GET['selesai'];
    mysqli_query($conn, "UPDATE pesanan SET status='selesai' WHERE id_pesanan=$id");
    header("Location: kasir.php?page=pesanan_masuk");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaksi'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    mysqli_query($conn, "UPDATE pesanan SET nama_pelanggan='$nama_pelanggan', catatan='$catatan' WHERE id_pesanan=$id_pesanan");
    header("Location: kasir.php?page=transaksi");
    exit;
}

// ============================================
// PROSES QR PAYMENT
// ============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_qr'])) {
    $nama_qr = mysqli_real_escape_string($conn, $_POST['nama_qr']);
    
    if(isset($_FILES['gambar_qr']) && $_FILES['gambar_qr']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['gambar_qr']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $gambar_qr = time() . '_qr_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
            $target = 'uploads/qr/' . $gambar_qr;
            
            if(!file_exists('uploads/qr')) {
                mkdir('uploads/qr', 0777, true);
            }
            
            move_uploaded_file($_FILES['gambar_qr']['tmp_name'], $target);
            mysqli_query($conn, "INSERT INTO qr_pembayaran (nama_qr, gambar_qr, status) VALUES ('$nama_qr', '$gambar_qr', 'nonaktif')");
        }
    }
    header("Location: kasir.php?page=qr_payment");
    exit;
}

if(isset($_GET['set_status'])) {
    $id = (int)$_GET['set_status'];
    $status = $_GET['status'];
    mysqli_query($conn, "UPDATE qr_pembayaran SET status='$status' WHERE id_qr=$id");
    header("Location: kasir.php?page=qr_payment");
    exit;
}

if(isset($_GET['hapus_qr'])) {
    $id = (int)$_GET['hapus_qr'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar_qr FROM qr_pembayaran WHERE id_qr=$id"));
    if($cek['gambar_qr'] && file_exists('uploads/qr/' . $cek['gambar_qr'])) {
        unlink('uploads/qr/' . $cek['gambar_qr']);
    }
    mysqli_query($conn, "DELETE FROM qr_pembayaran WHERE id_qr=$id");
    header("Location: kasir.php?page=qr_payment");
    exit;
}

// ============================================
// AMBIL DATA
// ============================================
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id_user=" . $_SESSION['id_user']));
$foto_profil = $user_data['foto_profil'] ?? '';

$total_pesanan_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status='pending'"))['total'];
$total_pesanan_proses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status='proses'"))['total'];
$total_menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu"))['total'];
$total_transaksi_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_harga), 0) as total FROM pesanan WHERE DATE(waktu_pesan) = CURDATE() AND status='selesai'"))['total'];

$data_menu = mysqli_query($conn, "SELECT m.*, k.nama_kategori, k.icon FROM menu m LEFT JOIN kategori k ON m.id_kategori = k.id_kategori ORDER BY k.nama_kategori, m.nama_item");
$kategori_list = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori");
$transaksi = mysqli_query($conn, "SELECT p.*, m.nomor_meja FROM pesanan p JOIN meja m ON p.id_meja = m.id_meja WHERE p.status='selesai' ORDER BY p.waktu_pesan DESC LIMIT 50");
$pesanan_masuk = mysqli_query($conn, "SELECT p.*, m.nomor_meja FROM pesanan p JOIN meja m ON p.id_meja = m.id_meja WHERE p.status IN ('pending','proses') ORDER BY FIELD(p.status, 'pending', 'proses'), p.waktu_pesan ASC");
$semua_menu = mysqli_query($conn, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_item");
$varian_list = mysqli_query($conn, "SELECT v.*, m.nama_item FROM varian_menu v JOIN menu m ON v.id_menu = m.id_menu ORDER BY m.nama_item, v.nama_varian");
$all_menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_item");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Kasir Resto - Manajemen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fb; overflow-x: hidden; }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; width: 280px; height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white; transition: all 0.3s; z-index: 1000; overflow-y: auto;
            transform: translateX(0);
        }
        .sidebar.closed { transform: translateX(-100%); }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #FF6B35; }
        .sidebar-header h3 { font-size: 1.1em; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item {
            padding: 12px 25px; display: flex; align-items: center; gap: 12px;
            color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s;
        }
        .menu-item:hover, .menu-item.active { background: rgba(255,107,53,0.2); color: #FF6B35; }
        .menu-item.active { border-left: 3px solid #FF6B35; }
        .menu-item .badge-count { margin-left: auto; background: #FF6B35; padding: 2px 8px; border-radius: 20px; font-size: 0.7em; }

        /* Main Content */
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; transition: all 0.3s; }
        .main-content.expanded { margin-left: 0; }

        /* Top Bar */
        .top-bar {
            background: white; border-radius: 20px; padding: 15px 25px; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .menu-toggle-btn { background: #FF6B35; border: none; width: 45px; height: 45px; border-radius: 12px; color: white; cursor: pointer; display: none; }
        .page-title { font-size: 1.3em; font-weight: 700; color: #333; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #FF6B35; }
        .btn-order-quick {
            background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 10px 20px;
            border-radius: 40px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            font-weight: 600; font-size: 0.9em;
        }
        .user-dropdown { position: relative; cursor: pointer; }
        .user-dropdown-btn { display: flex; align-items: center; gap: 12px; background: none; border: none; cursor: pointer; padding: 8px 15px; border-radius: 50px; }
        .user-dropdown-btn:hover { background: #f5f5f5; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #FF6B35; }
        .user-name { font-weight: 600; color: #333; }
        .dropdown-menu {
            position: absolute; top: 60px; right: 0; background: white; min-width: 280px;
            border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); display: none; overflow: hidden;
            z-index: 1001;
        }
        .dropdown-menu.show { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .dropdown-header { padding: 20px; background: linear-gradient(135deg, #FF6B35, #FF8C42); color: white; text-align: center; }
        .dropdown-header .avatar { width: 60px; height: 60px; border-radius: 50%; border: 2px solid white; }
        .dropdown-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: #333; border-bottom: 1px solid #eee; }
        .dropdown-item:hover { background: #f8f9fa; color: #FF6B35; }
        .dropdown-item i { width: 25px; color: #FF6B35; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #FF6B35, #FF8C42); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6em; color: white; }
        .stat-info h3 { font-size: 1.8em; font-weight: 800; color: #FF6B35; }
        .stat-info p { color: #666; font-size: 0.85em; }

        /* Orders */
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
        .order-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7em; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-proses { background: #d0f0ff; color: #17a2b8; }
        .badge-success { background: #d4edda; color: #155724; }
        .detail-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }

        /* Table */
        .table-container { background: white; border-radius: 20px; padding: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .menu-gambar { width: 50px; height: 50px; object-fit: cover; border-radius: 12px; }

        /* Buttons */
        .btn { padding: 8px 16px; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: all 0.2s; }
        .btn-sm { padding: 6px 12px; font-size: 0.8em; }
        .btn-primary { background: #FF6B35; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 24px; padding: 25px; max-width: 550px; width: 90%; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #FFE0D0; }
        .modal-header h3 { color: #FF6B35; }
        .close-modal { background: none; border: none; font-size: 1.8em; cursor: pointer; color: #999; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85em; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 12px; font-family: 'Inter', sans-serif; }
        .form-group input:focus { outline: none; border-color: #FF6B35; }

        /* Fab Mobile */
        .fab-menu { position: fixed; bottom: 20px; right: 20px; display: none; z-index: 99; }
        .fab-button { width: 56px; height: 56px; background: linear-gradient(135deg, #FF6B35, #FF8C42); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5em; cursor: pointer; box-shadow: 0 4px 15px rgba(255,107,53,0.4); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 15px; }
            .menu-toggle-btn { display: flex; align-items: center; justify-content: center; }
            .top-bar { flex-direction: column; align-items: stretch; }
            .top-bar-right { display: flex; justify-content: space-between; align-items: center; }
            .user-name { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-card { padding: 15px; }
            .stat-icon { width: 45px; height: 45px; font-size: 1.2em; }
            .stat-info h3 { font-size: 1.3em; }
            .orders-grid { grid-template-columns: 1fr; }
            .fab-menu { display: block; }
        }
        @media (min-width: 769px) and (max-width: 1024px) {
            .sidebar { width: 250px; }
            .main-content { margin-left: 250px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-2 { gap: 8px; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 10px; }
        .mb-2 { margin-bottom: 10px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <?php if($foto_profil && file_exists('uploads/profil/' . $foto_profil)): ?>
            <img src="uploads/profil/<?= $foto_profil ?>" class="avatar">
        <?php else: ?>
            <i class="fas fa-user-circle" style="font-size: 4.5em; color: #FF6B35;"></i>
        <?php endif; ?>
        <h3><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></h3>
        <small><?= $_SESSION['role'] == 'admin' ? 'Administrator' : 'Kasir' ?></small>
    </div>
    <div class="sidebar-menu">
        <a href="?page=dashboard" class="menu-item <?= $page == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?page=pesanan_masuk" class="menu-item <?= $page == 'pesanan_masuk' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Pesanan Masuk
            <?php if($total_pesanan_pending > 0): ?>
                <span class="badge-count"><?= $total_pesanan_pending ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=stock" class="menu-item <?= $page == 'stock' ? 'active' : '' ?>"><i class="fas fa-boxes"></i> Stock Barang</a>
        <a href="?page=menu" class="menu-item <?= $page == 'menu' ? 'active' : '' ?>"><i class="fas fa-utensils"></i> Menu</a>
        <a href="?page=varian" class="menu-item <?= $page == 'varian' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Varian Menu</a>
        <a href="?page=transaksi" class="menu-item <?= $page == 'transaksi' ? 'active' : '' ?>"><i class="fas fa-receipt"></i> Transaksi</a>
        <a href="?page=qr_payment" class="menu-item <?= $page == 'qr_payment' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> QR Payment</a>
        <div style="margin: 20px 0; height: 1px; background: rgba(255,255,255,0.1);"></div>
        <a href="order.php?meja=1" class="menu-item" target="_blank"><i class="fas fa-mobile-alt"></i> Halaman Order</a>
        <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <div class="top-bar">
        <div class="d-flex align-center gap-2">
            <button class="menu-toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="page-title">
                <i class="fas <?= $page == 'dashboard' ? 'fa-tachometer-alt' : ($page == 'pesanan_masuk' ? 'fa-bell' : ($page == 'stock' ? 'fa-boxes' : ($page == 'menu' ? 'fa-utensils' : ($page == 'varian' ? 'fa-layer-group' : ($page == 'transaksi' ? 'fa-receipt' : 'fa-qrcode'))))) ?>"></i>
                <?php
                switch($page) {
                    case 'dashboard': echo 'Dashboard'; break;
                    case 'pesanan_masuk': echo 'Pesanan Masuk'; break;
                    case 'stock': echo 'Manajemen Stock'; break;
                    case 'menu': echo 'Manajemen Menu'; break;
                    case 'varian': echo 'Manajemen Varian Menu'; break;
                    case 'transaksi': echo 'Riwayat Transaksi'; break;
                    case 'qr_payment': echo 'QR Payment'; break;
                    default: echo 'Dashboard';
                }
                ?>
            </div>
        </div>
        <div class="top-bar-right">
            <div class="user-dropdown">
                <div class="user-dropdown-btn" onclick="toggleDropdown()">
                    <?php if($foto_profil && file_exists('uploads/profil/' . $foto_profil)): ?>
                        <img src="uploads/profil/<?= $foto_profil ?>" class="user-avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 2.5em; color: #FF6B35;"></i>
                    <?php endif; ?>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-header">
                        <?php if($foto_profil && file_exists('uploads/profil/' . $foto_profil)): ?>
                            <img src="uploads/profil/<?= $foto_profil ?>" class="avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 3em;"></i>
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></h4>
                        <p>@<?= htmlspecialchars($_SESSION['username']) ?></p>
                    </div>
                    <a href="#" class="dropdown-item" onclick="openProfileModal(); return false;"><i class="fas fa-user-edit"></i> Edit Profile</a>
                    <a href="#" class="dropdown-item" onclick="openPasswordModal(); return false;"><i class="fas fa-key"></i> Ganti Password</a>
                    <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- DASHBOARD -->
    <?php if($page == 'dashboard'): ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-bell"></i></div><div class="stat-info"><h3><?= $total_pesanan_pending ?></h3><p>Pesanan Pending</p></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-spinner"></i></div><div class="stat-info"><h3><?= $total_pesanan_proses ?></h3><p>Pesanan Diproses</p></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-utensils"></i></div><div class="stat-info"><h3><?= $total_menu ?></h3><p>Menu Tersedia</p></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-info"><h3>Rp <?= number_format($total_transaksi_hari_ini,0,',','.') ?></h3><p>Omset Hari Ini</p></div></div>
    </div>
    <?php endif; ?>

    <!-- PESANAN MASUK -->
    <?php if($page == 'pesanan_masuk'): ?>
    <div class="orders-grid">
        <?php if(mysqli_num_rows($pesanan_masuk) == 0): ?>
            <div class="order-card text-center"><i class="fas fa-check-circle" style="font-size: 3em; color: #28a745;"></i><p class="mt-2">✨ Tidak ada pesanan masuk ✨</p></div>
        <?php endif; ?>
        <?php while($p = mysqli_fetch_assoc($pesanan_masuk)): 
            $detail = mysqli_query($conn, "SELECT d.*, m.nama_item FROM detail_pesanan d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_pesanan = {$p['id_pesanan']}");
        ?>
        <div class="order-card">
            <div class="d-flex justify-between align-center">
                <strong><i class="fas fa-chair"></i> Meja <?= $p['nomor_meja'] ?> | #<?= str_pad($p['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></strong>
                <span class="badge <?= $p['status'] == 'pending' ? 'badge-pending' : 'badge-proses' ?>"><?= strtoupper($p['status']) ?></span>
            </div>
            <p class="mt-2"><i class="fas fa-user"></i> <strong><?= htmlspecialchars($p['nama_pelanggan']) ?></strong></p>
            <?php if(!empty($p['catatan'])): ?>
                <p style="background:#f8f9fa; padding:10px; border-radius:12px; font-size:0.85em; margin:10px 0;"><i class="fas fa-pencil-alt"></i> <?= htmlspecialchars($p['catatan']) ?></p>
            <?php endif; ?>
            <div>
                <strong><i class="fas fa-shopping-basket"></i> Item Pesanan:</strong>
                <?php while($d = mysqli_fetch_assoc($detail)): ?>
                <div class="detail-item">
                    <span><?= $d['nama_item'] ?> <strong>x<?= $d['jumlah'] ?></strong></span>
                    <div class="d-flex gap-2">
                        <span style="color:#FF6B35;">Rp <?= number_format($d['subtotal'],0,',','.') ?></span>
                        <button class="btn btn-warning btn-sm" onclick="openEditItemModal(<?= $d['id_detail'] ?>, <?= $p['id_pesanan'] ?>, '<?= addslashes($d['nama_item']) ?>', <?= $d['jumlah'] ?>)"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus item ini?')) location.href='?page=pesanan_masuk&hapus_item_pesanan=<?= $d['id_detail'] ?>'"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="d-flex justify-between align-center" style="margin: 15px 0; padding-top: 10px; border-top: 1px solid #eee;">
                <strong>TOTAL</strong><strong style="color:#FF6B35;">Rp <?= number_format($p['total_harga'],0,',','.') ?></strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if($p['status'] == 'pending'): ?>
                    <button class="btn btn-info" onclick="location.href='?page=pesanan_masuk&proses=<?= $p['id_pesanan'] ?>'"><i class="fas fa-spinner"></i> Proses</button>
                <?php endif; ?>
                <button class="btn btn-success" onclick="location.href='?page=pesanan_masuk&selesai=<?= $p['id_pesanan'] ?>'"><i class="fas fa-check"></i> Selesai</button>
                <button class="btn btn-warning" onclick="openEditPesananModal(<?= $p['id_pesanan'] ?>, '<?= addslashes($p['nama_pelanggan']) ?>', '<?= addslashes($p['catatan']) ?>')"><i class="fas fa-user-edit"></i> Edit</button>
                <button class="btn btn-primary" onclick="openTambahItemModal(<?= $p['id_pesanan'] ?>)"><i class="fas fa-plus"></i> Tambah Item</button>
                <button class="btn btn-info" onclick="window.open('cetak_struk.php?id=<?= $p['id_pesanan'] ?>', '_blank')"><i class="fas fa-print"></i> Cetak</button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- STOCK BARANG -->
    <?php if($page == 'stock'): ?>
    <div class="table-container">
        <h3><i class="fas fa-boxes"></i> Stock Barang</h3>
        <table>
            <thead><tr><th>Gambar</th><th>Menu</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php mysqli_data_seek($data_menu, 0); while($row = mysqli_fetch_assoc($data_menu)): ?>
                <tr>
                    <td><?php if(!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])): ?><img src="uploads/<?= $row['gambar'] ?>" class="menu-gambar"><?php else: ?><div style="width:50px;height:50px;background:#f0f0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas <?= $row['icon'] ?? 'fa-utensils' ?>"></i></div><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_item']) ?></strong></td>
                    <td><?= $row['nama_kategori'] ?? 'Lainnya' ?></td>
                    <td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
                    <td><span class="badge" style="background:<?= ($row['stok'] ?? 0) > 10 ? '#d4edda' : '#f8d7da' ?>;"><?= $row['stok'] ?? 0 ?> pcs</span></td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm" onclick="openStockModal(<?= $row['id_menu'] ?>, '<?= addslashes($row['nama_item']) ?>', <?= $row['stok'] ?? 0 ?>)"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus menu?')) location.href='?page=stock&hapus_dari_stock=<?= $row['id_menu'] ?>'"><i class="fas fa-trash"></i> Hapus</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- MANAJEMEN MENU -->
    <?php if($page == 'menu'): ?>
    <div class="table-container">
        <div class="d-flex justify-between align-center flex-wrap gap-2" style="margin-bottom:20px;">
            <h3><i class="fas fa-utensils"></i> Daftar Menu</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="openMenuModal()"><i class="fas fa-plus"></i> Tambah Menu</button>
                <button class="btn btn-info" onclick="openKategoriModal()"><i class="fas fa-tags"></i> Kelola Kategori</button>
            </div>
        </div>
        <table>
            <thead><tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php mysqli_data_seek($data_menu, 0); while($row = mysqli_fetch_assoc($data_menu)): ?>
                <tr>
                    <td><?php if(!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])): ?><img src="uploads/<?= $row['gambar'] ?>" class="menu-gambar"><?php else: ?><div style="width:50px;height:50px;background:#f0f0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas <?= $row['icon'] ?? 'fa-utensils' ?>"></i></div><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_item']) ?></strong></td>
                    <td><?= $row['nama_kategori'] ?? 'Lainnya' ?></td>
                    <td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
                    <td><?= $row['stok'] ?? 0 ?></td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm" onclick="editMenu(<?= $row['id_menu'] ?>, '<?= addslashes($row['nama_item']) ?>', <?= $row['id_kategori'] ?? 1 ?>, <?= $row['harga'] ?>, <?= $row['stok'] ?? 0 ?>, '<?= $row['gambar'] ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus menu?')) location.href='?page=menu&hapus_menu=<?= $row['id_menu'] ?>'"><i class="fas fa-trash"></i> Hapus</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- MANAJEMEN VARIAN MENU -->
    <?php if($page == 'varian'): ?>
    <div class="table-container">
        <div class="d-flex justify-between align-center flex-wrap gap-2" style="margin-bottom:20px;">
            <h3><i class="fas fa-layer-group"></i> Manajemen Varian Menu</h3>
            <button class="btn btn-primary" onclick="openVarianModal()"><i class="fas fa-plus"></i> Tambah Varian</button>
        </div>
        
        <table>
            <thead>
                <tr><th>Menu</th><th>Nama Varian</th><th>Harga Tambahan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php 
                $query_varian = "SELECT v.*, m.nama_item FROM varian_menu v JOIN menu m ON v.id_menu = m.id_menu ORDER BY m.nama_item, v.nama_varian";
                $varian_list = mysqli_query($conn, $query_varian);
                while($v = mysqli_fetch_assoc($varian_list)):
                ?>
                <tr>
                    <td><?= htmlspecialchars($v['nama_item']) ?></td>
                    <td><?= htmlspecialchars($v['nama_varian']) ?></td>
                    <td>Rp <?= number_format($v['harga_tambahan'],0,',','.') ?></td>
                    <td><span class="badge <?= $v['status'] == 'aktif' ? 'badge-success' : 'badge-pending' ?>" style="background:<?= $v['status'] == 'aktif' ? '#d4edda' : '#f8d7da' ?>; color:<?= $v['status'] == 'aktif' ? '#155724' : '#856404' ?>"><?= $v['status'] == 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm" onclick="editVarian(<?= $v['id_varian'] ?>, <?= $v['id_menu'] ?>, '<?= addslashes($v['nama_varian']) ?>', <?= $v['harga_tambahan'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus varian ini?')) location.href='?page=varian&hapus_varian=<?= $v['id_varian'] ?>'"><i class="fas fa-trash"></i> Hapus</button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($varian_list) == 0): ?>
                <tr>
                    <td colspan="5" class="text-center">Belum ada varian. Silakan tambah varian.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah/Edit Varian -->
    <div id="varianModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 id="varianModalTitle">Tambah Varian</h3><button class="close-modal" onclick="closeVarianModal()">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="id_varian" id="varian_id">
                <div class="form-group">
                    <label>Pilih Menu</label>
                    <select name="id_menu" id="varian_id_menu" required>
                        <option value="">-- Pilih Menu --</option>
                        <?php 
                        $all_menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_item");
                        while($m = mysqli_fetch_assoc($all_menu)): 
                        ?>
                            <option value="<?= $m['id_menu'] ?>"><?= htmlspecialchars($m['nama_item']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Nama Varian</label><input type="text" name="nama_varian" id="varian_nama" placeholder="Contoh: Pedas Level 1, Dingin, Panas" required></div>
                <div class="form-group"><label>Harga Tambahan</label><input type="number" name="harga_tambahan" id="varian_harga" value="0" placeholder="0 = tidak ada tambahan"></div>
                <button type="submit" name="simpan_varian" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- TRANSAKSI -->
    <?php if($page == 'transaksi'): ?>
    <div class="table-container">
        <h3><i class="fas fa-receipt"></i> Riwayat Transaksi</h3>
        <table>
            <thead><tr><th>ID</th><th>Waktu</th><th>Meja</th><th>Pelanggan</th><th>Total</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($transaksi)): ?>
                <tr>
                    <td>#<?= str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_pesan'])) ?></td>
                    <td>Meja <?= $row['nomor_meja'] ?></td>
                    <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                    <td style="color:#FF6B35; font-weight:600;">Rp <?= number_format($row['total_harga'],0,',','.') ?></td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm" onclick="openEditTransaksiModal(<?= $row['id_pesanan'] ?>, '<?= addslashes($row['nama_pelanggan']) ?>', '<?= addslashes($row['catatan']) ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-info btn-sm" onclick="window.open('cetak_struk.php?id=<?= $row['id_pesanan'] ?>', '_blank')"><i class="fas fa-print"></i> Cetak</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- QR PAYMENT -->
    <?php if($page == 'qr_payment'): ?>
    <div class="table-container">
        <div class="d-flex justify-between align-center" style="margin-bottom:20px;">
            <h3><i class="fas fa-qrcode"></i> QR Code Pembayaran</h3>
            <button class="btn btn-primary" onclick="openQRModal()"><i class="fas fa-upload"></i> Upload QR</button>
        </div>
        <div class="orders-grid">
            <?php
            $qr_list = mysqli_query($conn, "SELECT * FROM qr_pembayaran ORDER BY id_qr DESC");
            while($qr = mysqli_fetch_assoc($qr_list)): 
            ?>
            <div class="order-card text-center">
                <?php if($qr['gambar_qr'] && file_exists('uploads/qr/' . $qr['gambar_qr'])): ?>
                    <img src="uploads/qr/<?= $qr['gambar_qr'] ?>" style="width: 150px; height: 150px; object-fit: contain; margin: 0 auto;">
                <?php else: ?>
                    <div style="width:150px;height:150px;background:#f0f0f0;margin:0 auto;display:flex;align-items:center;justify-content:center;border-radius:15px;"><i class="fas fa-qrcode" style="font-size:3em; color:#999;"></i></div>
                <?php endif; ?>
                <h4 class="mt-2"><?= htmlspecialchars($qr['nama_qr']) ?></h4>
                <p class="badge" style="background:<?= $qr['status'] == 'aktif' ? '#d4edda' : '#f8d7da' ?>;"><?= $qr['status'] == 'aktif' ? 'Aktif' : 'Nonaktif' ?></p>
                <div class="d-flex flex-wrap gap-2 justify-center mt-2">
                    <?php if($qr['status'] == 'aktif'): ?>
                        <button class="btn btn-warning btn-sm" onclick="location.href='?page=qr_payment&set_status=<?= $qr['id_qr'] ?>&status=nonaktif'">Nonaktifkan</button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm" onclick="location.href='?page=qr_payment&set_status=<?= $qr['id_qr'] ?>&status=aktif'">Aktifkan</button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus QR?')) location.href='?page=qr_payment&hapus_qr=<?= $qr['id_qr'] ?>'">Hapus</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Fab Mobile -->
<div class="fab-menu"><div class="fab-button" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div></div>

<!-- MODAL STOCK -->
<div id="stockModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Update Stok</h3><button class="close-modal" onclick="closeStockModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id_menu" id="stock_id_menu">
            <div class="form-group"><label>Nama Menu</label><input type="text" id="stock_nama_menu" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Stok Baru</label><input type="number" name="stok" id="stock_stok" required></div>
            <button type="submit" name="update_stok" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL MENU -->
<div id="menuModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="menuModalTitle">Tambah Menu</h3><button class="close-modal" onclick="closeMenuModal()">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_menu" id="menu_id">
            <input type="hidden" name="gambar_lama" id="gambar_lama">
            <div class="form-group"><label>Nama Menu</label><input type="text" name="nama_item" id="menu_nama" required></div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="id_kategori" id="menu_kategori" required>
                    <?php mysqli_data_seek($kategori_list, 0); while($kat = mysqli_fetch_assoc($kategori_list)): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label>Harga</label><input type="number" name="harga" id="menu_harga" required></div>
            <div class="form-group"><label>Stok</label><input type="number" name="stok" id="menu_stok" value="0"></div>
            <div class="form-group"><label>Gambar</label><input type="file" name="gambar" id="menu_gambar" accept="image/*"><div id="gambarPreview"></div></div>
            <button type="submit" name="simpan_menu" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL KATEGORI -->
<div id="kategoriModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Kelola Kategori</h3><button class="close-modal" onclick="closeKategoriModal()">&times;</button></div>
        <h4>Daftar Kategori</h4>
        <table style="width:100%; margin-bottom:20px;">
            <thead><tr><th>Kategori</th><th>Icon</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php mysqli_data_seek($kategori_list, 0); while($kat = mysqli_fetch_assoc($kategori_list)): ?>
                <tr>
                    <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
                    <td><i class="fas <?= $kat['icon'] ?>"></i> <?= $kat['icon'] ?></td>
                    <td><button class="btn btn-danger btn-sm" onclick="if(confirm('Hapus kategori?')) location.href='?page=menu&hapus_kategori=<?= $kat['id_kategori'] ?>'">Hapus</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <h4>Tambah Kategori Baru</h4>
        <form method="POST">
            <div class="form-group"><label>Nama Kategori</label><input type="text" name="nama_kategori" required></div>
            <div class="form-group">
                <label>Icon</label>
                <select name="icon">
                    <option value="fa-hamburger">🍔 fa-hamburger</option>
                    <option value="fa-coffee">☕ fa-coffee</option>
                    <option value="fa-cookie-bite">🍪 fa-cookie-bite</option>
                    <option value="fa-ice-cream">🍦 fa-ice-cream</option>
                </select>
            </div>
            <button type="submit" name="tambah_kategori" class="btn btn-primary">Tambah</button>
        </form>
    </div>
</div>

<!-- MODAL EDIT PESANAN -->
<div id="editPesananModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Pesanan</h3><button class="close-modal" onclick="closeEditPesananModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id_pesanan" id="edit_id_pesanan">
            <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="nama_pelanggan" id="edit_nama_pelanggan" required></div>
            <div class="form-group"><label>Catatan</label><textarea name="catatan" id="edit_catatan" rows="3"></textarea></div>
            <button type="submit" name="edit_pesanan" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL EDIT ITEM -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Item Pesanan</h3><button class="close-modal" onclick="closeEditItemModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id_detail" id="edit_id_detail">
            <input type="hidden" name="id_pesanan" id="edit_item_id_pesanan">
            <div class="form-group"><label>Menu</label><input type="text" id="edit_nama_item" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Jumlah</label><input type="number" name="jumlah" id="edit_jumlah" min="0" required></div>
            <button type="submit" name="update_detail_pesanan" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH ITEM -->
<div id="tambahItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Tambah Item ke Pesanan</h3><button class="close-modal" onclick="closeTambahItemModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id_pesanan" id="tambah_id_pesanan">
            <div class="form-group">
                <label>Pilih Menu</label>
                <select name="id_menu" id="tambah_id_menu" required>
                    <option value="">-- Pilih Menu --</option>
                    <?php mysqli_data_seek($semua_menu, 0); while($menu_item = mysqli_fetch_assoc($semua_menu)): ?>
                        <option value="<?= $menu_item['id_menu'] ?>"><?= htmlspecialchars($menu_item['nama_item']) ?> - Rp <?= number_format($menu_item['harga'],0,',','.') ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label>Jumlah</label><input type="number" name="jumlah" id="tambah_jumlah" value="1" min="1" required></div>
            <button type="submit" name="tambah_item_pesanan" class="btn btn-primary">Tambah</button>
        </form>
    </div>
</div>

<!-- MODAL EDIT TRANSAKSI -->
<div id="editTransaksiModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Transaksi</h3><button class="close-modal" onclick="closeEditTransaksiModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id_pesanan" id="edit_transaksi_id">
            <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="nama_pelanggan" id="edit_transaksi_nama" required></div>
            <div class="form-group"><label>Catatan</label><textarea name="catatan" id="edit_transaksi_catatan" rows="3"></textarea></div>
            <button type="submit" name="edit_transaksi" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL PROFILE -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Profile</h3><button class="close-modal" onclick="closeProfileModal()">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user_data['nama_lengkap']) ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>"></div>
            <div class="form-group"><label>No Telepon</label><input type="text" name="no_telepon" value="<?= htmlspecialchars($user_data['no_telepon']) ?>"></div>
            <div class="form-group"><label>Foto Profil</label><input type="file" name="foto_profil" accept="image/*"></div>
            <button type="submit" name="update_profile" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<!-- MODAL PASSWORD -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Ganti Password</h3><button class="close-modal" onclick="closePasswordModal()">&times;</button></div>
        <form method="POST">
            <div class="form-group"><label>Password Lama</label><input type="password" name="password_lama" required></div>
            <div class="form-group"><label>Password Baru (min 6 karakter)</label><input type="password" name="password_baru" required></div>
            <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="konfirmasi_password" required></div>
            <button type="submit" name="ganti_password" class="btn btn-primary">Ganti Password</button>
        </form>
    </div>
</div>

<!-- MODAL QR -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Upload QR Payment</h3><button class="close-modal" onclick="closeQRModal()">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Nama QR</label><input type="text" name="nama_qr" placeholder="Contoh: QRIS BCA" required></div>
            <div class="form-group"><label>Gambar QR</label><input type="file" name="gambar_qr" accept="image/*" required></div>
            <button type="submit" name="upload_qr" class="btn btn-primary">Upload</button>
        </form>
    </div>
</div>

<script>
// Toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Toggle dropdown
function toggleDropdown() {
    document.getElementById('dropdownMenu').classList.toggle('show');
}

// Close dropdown klik di luar
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.user-dropdown');
    const menu = document.getElementById('dropdownMenu');
    if (dropdown && !dropdown.contains(event.target)) {
        menu.classList.remove('show');
    }
});

// Profile Modal
function openProfileModal() { document.getElementById('profileModal').classList.add('show'); document.getElementById('dropdownMenu').classList.remove('show'); }
function closeProfileModal() { document.getElementById('profileModal').classList.remove('show'); }

// Password Modal
function openPasswordModal() { document.getElementById('passwordModal').classList.add('show'); document.getElementById('dropdownMenu').classList.remove('show'); }
function closePasswordModal() { document.getElementById('passwordModal').classList.remove('show'); }

// QR Modal
function openQRModal() { document.getElementById('qrModal').classList.add('show'); }
function closeQRModal() { document.getElementById('qrModal').classList.remove('show'); }

// Stock Modal
function openStockModal(id, nama, stok) {
    document.getElementById('stock_id_menu').value = id;
    document.getElementById('stock_nama_menu').value = nama;
    document.getElementById('stock_stok').value = stok;
    document.getElementById('stockModal').classList.add('show');
}
function closeStockModal() { document.getElementById('stockModal').classList.remove('show'); }

// Menu Modal
function openMenuModal() {
    document.getElementById('menuModalTitle').innerText = 'Tambah Menu';
    document.getElementById('menu_id').value = '';
    document.getElementById('menu_nama').value = '';
    document.getElementById('menu_kategori').value = '1';
    document.getElementById('menu_harga').value = '';
    document.getElementById('menu_stok').value = '0';
    document.getElementById('gambar_lama').value = '';
    document.getElementById('gambarPreview').innerHTML = '';
    document.getElementById('menu_gambar').value = '';
    document.getElementById('menuModal').classList.add('show');
}
function editMenu(id, nama, id_kategori, harga, stok, gambar) {
    document.getElementById('menuModalTitle').innerText = 'Edit Menu';
    document.getElementById('menu_id').value = id;
    document.getElementById('menu_nama').value = nama;
    document.getElementById('menu_kategori').value = id_kategori;
    document.getElementById('menu_harga').value = harga;
    document.getElementById('menu_stok').value = stok;
    document.getElementById('gambar_lama').value = gambar || '';
    if(gambar && gambar !== 'null' && gambar !== '') {
        document.getElementById('gambarPreview').innerHTML = '<img src="uploads/'+gambar+'" style="width:100px;height:100px;object-fit:cover;border-radius:12px;margin-top:10px;"><br><small>Gambar saat ini</small>';
    } else {
        document.getElementById('gambarPreview').innerHTML = '';
    }
    document.getElementById('menuModal').classList.add('show');
}
function closeMenuModal() { document.getElementById('menuModal').classList.remove('show'); }

// Kategori Modal
function openKategoriModal() { document.getElementById('kategoriModal').classList.add('show'); }
function closeKategoriModal() { document.getElementById('kategoriModal').classList.remove('show'); }

// Varian Modal
function openVarianModal() {
    document.getElementById('varianModalTitle').innerText = 'Tambah Varian';
    document.getElementById('varian_id').value = '';
    document.getElementById('varian_id_menu').value = '';
    document.getElementById('varian_nama').value = '';
    document.getElementById('varian_harga').value = '0';
    document.getElementById('varianModal').classList.add('show');
}
function editVarian(id, id_menu, nama, harga) {
    document.getElementById('varianModalTitle').innerText = 'Edit Varian';
    document.getElementById('varian_id').value = id;
    document.getElementById('varian_id_menu').value = id_menu;
    document.getElementById('varian_nama').value = nama;
    document.getElementById('varian_harga').value = harga;
    document.getElementById('varianModal').classList.add('show');
}
function closeVarianModal() { document.getElementById('varianModal').classList.remove('show'); }

// Edit Pesanan Modal
function openEditPesananModal(id, nama, catatan) {
    document.getElementById('edit_id_pesanan').value = id;
    document.getElementById('edit_nama_pelanggan').value = nama;
    document.getElementById('edit_catatan').value = catatan;
    document.getElementById('editPesananModal').classList.add('show');
}
function closeEditPesananModal() { document.getElementById('editPesananModal').classList.remove('show'); }

// Edit Item Modal
function openEditItemModal(id_detail, id_pesanan, nama_item, jumlah) {
    document.getElementById('edit_id_detail').value = id_detail;
    document.getElementById('edit_item_id_pesanan').value = id_pesanan;
    document.getElementById('edit_nama_item').value = nama_item;
    document.getElementById('edit_jumlah').value = jumlah;
    document.getElementById('editItemModal').classList.add('show');
}
function closeEditItemModal() { document.getElementById('editItemModal').classList.remove('show'); }

// Tambah Item Modal
function openTambahItemModal(id_pesanan) {
    document.getElementById('tambah_id_pesanan').value = id_pesanan;
    document.getElementById('tambah_id_menu').value = '';
    document.getElementById('tambah_jumlah').value = '1';
    document.getElementById('tambahItemModal').classList.add('show');
}
function closeTambahItemModal() { document.getElementById('tambahItemModal').classList.remove('show'); }

// Edit Transaksi Modal
function openEditTransaksiModal(id, nama, catatan) {
    document.getElementById('edit_transaksi_id').value = id;
    document.getElementById('edit_transaksi_nama').value = nama;
    document.getElementById('edit_transaksi_catatan').value = catatan;
    document.getElementById('editTransaksiModal').classList.add('show');
}
function closeEditTransaksiModal() { document.getElementById('editTransaksiModal').classList.remove('show'); }

// Preview gambar
document.getElementById('menu_gambar')?.addEventListener('change', function(e) {
    const preview = document.getElementById('gambarPreview');
    if(e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(loadEvent) {
            preview.innerHTML = '<img src="'+loadEvent.target.result+'" style="width:100px;height:100px;object-fit:cover;border-radius:12px;margin-top:10px;"><br><small>Gambar baru</small>';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>
</body>
</html>