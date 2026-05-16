-- ============================================
-- DATABASE KASIR QR CODE - VERSION FINAL
-- ============================================

DROP DATABASE IF EXISTS kasir_qr;
CREATE DATABASE kasir_qr;
USE kasir_qr;

-- ============================================
-- 1. TABEL MEJA
-- ============================================
CREATE TABLE meja (
    id_meja INT PRIMARY KEY AUTO_INCREMENT,
    nomor_meja INT UNIQUE NOT NULL,
    status ENUM('tersedia','terisi') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. TABEL KATEGORI (dinamis)
-- ============================================
CREATE TABLE kategori (
    id_kategori INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-utensils',
    urutan INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 3. TABEL MENU (dengan gambar dan stok)
-- ============================================
CREATE TABLE menu (
    id_menu INT PRIMARY KEY AUTO_INCREMENT,
    nama_item VARCHAR(100) NOT NULL,
    id_kategori INT,
    kategori ENUM('makanan','minuman') DEFAULT 'makanan',
    harga INT NOT NULL,
    stok INT DEFAULT 0,
    gambar VARCHAR(255) DEFAULT NULL,
    deskripsi TEXT,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE SET NULL
);

-- ============================================
-- 4. TABEL USERS (untuk login kasir)
-- ============================================
CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    email VARCHAR(100) UNIQUE DEFAULT NULL,
    no_telepon VARCHAR(20) DEFAULT NULL,
    foto_profil VARCHAR(255) DEFAULT NULL,
    role ENUM('admin','kasir','owner') DEFAULT 'kasir',
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- ============================================
-- 5. TABEL VARIAN MENU (untuk semua menu)
-- ============================================
CREATE TABLE varian_menu (
    id_varian INT PRIMARY KEY AUTO_INCREMENT,
    id_menu INT NOT NULL,
    nama_varian VARCHAR(100) NOT NULL,
    harga_tambahan INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu) ON DELETE CASCADE
);

-- ============================================
-- 6. TABEL PESANAN
-- ============================================
CREATE TABLE pesanan (
    id_pesanan INT PRIMARY KEY AUTO_INCREMENT,
    id_meja INT,
    id_user INT NULL,
    nomor_pesanan VARCHAR(20) UNIQUE,
    nama_pelanggan VARCHAR(100),
    catatan TEXT,
    total_harga INT DEFAULT 0,
    diskon INT DEFAULT 0,
    pajak INT DEFAULT 0,
    grand_total INT DEFAULT 0,
    status ENUM('pending','proses','selesai','batal') DEFAULT 'pending',
    metode_pembayaran ENUM('tunai','qris','transfer') DEFAULT 'tunai',
    waktu_pesan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai TIMESTAMP NULL,
    FOREIGN KEY (id_meja) REFERENCES meja(id_meja) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
);

-- ============================================
-- 7. TABEL DETAIL PESANAN (dengan varian)
-- ============================================
CREATE TABLE detail_pesanan (
    id_detail INT PRIMARY KEY AUTO_INCREMENT,
    id_pesanan INT,
    id_menu INT,
    id_varian INT DEFAULT NULL,
    nama_varian VARCHAR(100) DEFAULT NULL,
    harga_varian INT DEFAULT 0,
    jumlah INT,
    harga_satuan INT,
    subtotal INT,
    catatan_item TEXT,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu) ON DELETE CASCADE,
    FOREIGN KEY (id_varian) REFERENCES varian_menu(id_varian) ON DELETE SET NULL
);

-- ============================================
-- 8. TABEL QR PEMBAYARAN
-- ============================================
CREATE TABLE qr_pembayaran (
    id_qr INT PRIMARY KEY AUTO_INCREMENT,
    nama_qr VARCHAR(100),
    gambar_qr VARCHAR(255),
    kode_qr TEXT,
    status ENUM('aktif','nonaktif') DEFAULT 'nonaktif',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 9. TABEL LOG AKTIVITAS (untuk audit)
-- ============================================
CREATE TABLE log_aktivitas (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT,
    aktivitas VARCHAR(255),
    detail TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
);

-- ============================================
-- 10. INSERT KATEGORI DEFAULT
-- ============================================
INSERT INTO kategori (nama_kategori, icon, urutan) VALUES 
('Makanan', 'fa-hamburger', 1),
('Minuman', 'fa-coffee', 2),
('Snack', 'fa-cookie-bite', 3),
('Dessert', 'fa-ice-cream', 4),
('Cemilan', 'fa-bread-slice', 5);

-- ============================================
-- 11. DATA MEJA
-- ============================================
INSERT INTO meja (nomor_meja) VALUES 
(1), (2), (3), (4), (5), (6), (7), (8), (9), (10);

-- ============================================
-- 12. DATA MENU
-- ============================================
INSERT INTO menu (nama_item, id_kategori, kategori, harga, stok, gambar, deskripsi) VALUES
-- Makanan (id_kategori = 1)
('Nasi Goreng Spesial', 1, 'makanan', 28000, 50, NULL, 'Nasi goreng dengan telur, ayam, dan kerupuk'),
('Nasi Goreng Seafood', 1, 'makanan', 32000, 45, NULL, 'Nasi goreng dengan udang, cumi, dan seafood lainnya'),
('Mie Goreng Jawa', 1, 'makanan', 25000, 50, NULL, 'Mie goreng dengan rasa manis gurih khas Jawa'),
('Mie Kuah Spesial', 1, 'makanan', 27000, 40, NULL, 'Mie kuah dengan bakso dan pangsit'),
('Ayam Geprek Sambal', 1, 'makanan', 22000, 50, NULL, 'Ayam geprek crispy dengan sambal bawang'),
('Ayam Bakar Madu', 1, 'makanan', 30000, 35, NULL, 'Ayam bakar dengan saus madu'),
('Sate Ayam', 1, 'makanan', 30000, 50, NULL, '10 tusuk sate ayam dengan bumbu kacang'),
('Sate Kambing', 1, 'makanan', 45000, 30, NULL, '10 tusuk sate kambing dengan bumbu kacang'),
('Bakso Urat', 1, 'makanan', 20000, 50, NULL, 'Bakso urat dengan kuah hangat'),
('Rawon Daging', 1, 'makanan', 25000, 40, NULL, 'Rawon dengan daging sapi dan keluak'),

-- Minuman (id_kategori = 2)
('Es Teh Manis', 2, 'minuman', 5000, 100, NULL, 'Es teh segar dengan gula aren'),
('Es Jeruk Fresh', 2, 'minuman', 12000, 100, NULL, 'Jus jeruk peras asli tanpa pengawet'),
('Kopi Hitam', 2, 'minuman', 8000, 100, NULL, 'Kopi hitam robusta pilihan'),
('Kopi Susu', 2, 'minuman', 12000, 90, NULL, 'Kopi hitam dengan susu kental manis'),
('Es Kelapa Muda', 2, 'minuman', 15000, 80, NULL, 'Es kelapa muda dengan daging kelapa'),
('Milkshake Coklat', 2, 'minuman', 18000, 70, NULL, 'Milkshake coklat dengan whipped cream'),
('Milkshake Stroberi', 2, 'minuman', 18000, 70, NULL, 'Milkshake stroberi dengan whipped cream'),
('Air Mineral', 2, 'minuman', 5000, 200, NULL, 'Air mineral 600ml'),

-- Snack (id_kategori = 3)
('Kentang Goreng', 3, 'makanan', 15000, 60, NULL, 'Kentang goreng crispy dengan saus'),
('Pisang Goreng', 3, 'makanan', 12000, 60, NULL, 'Pisang goreng dengan topping keju'),
('Tahu Crispy', 3, 'makanan', 10000, 70, NULL, 'Tahu crispy dengan sambal'),
('Tempe Mendoan', 3, 'makanan', 10000, 70, NULL, 'Tempe mendoan dengan sambal kecap'),

-- Dessert (id_kategori = 4)
('Es Krim Vanilla', 4, 'makanan', 10000, 50, NULL, 'Es krim vanilla dengan topping'),
('Puding Coklat', 4, 'makanan', 12000, 45, NULL, 'Puding coklat dengan vla'),
('Fruit Salad', 4, 'makanan', 15000, 40, NULL, 'Salad buah segar dengan mayonaise');

-- ============================================
-- 13. DATA VARIAN MENU
-- ============================================

-- Dapatkan ID menu (gunakan subquery)
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Teh Manis Dingin', 0 FROM menu WHERE nama_item = 'Es Teh Manis' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Teh Manis Panas', 0 FROM menu WHERE nama_item = 'Es Teh Manis' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Jus Jeruk Biasa', 0 FROM menu WHERE nama_item = 'Es Jeruk Fresh' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Jus Jeruk Dingin', 0 FROM menu WHERE nama_item = 'Es Jeruk Fresh' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Kopi Panas', 0 FROM menu WHERE nama_item = 'Kopi Hitam' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Kopi Dingin', 0 FROM menu WHERE nama_item = 'Kopi Hitam' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Nasi Goreng Biasa', 0 FROM menu WHERE nama_item = 'Nasi Goreng Spesial' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Nasi Goreng Pedas', 0 FROM menu WHERE nama_item = 'Nasi Goreng Spesial' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Nasi Goreng Extra Pedas', 2000 FROM menu WHERE nama_item = 'Nasi Goreng Spesial' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Mie Goreng Biasa', 0 FROM menu WHERE nama_item = 'Mie Goreng Jawa' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Mie Goreng Pedas', 0 FROM menu WHERE nama_item = 'Mie Goreng Jawa' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Ayam Geprek Level 1 (Mild)', 0 FROM menu WHERE nama_item = 'Ayam Geprek Sambal' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Ayam Geprek Level 2 (Sedang)', 0 FROM menu WHERE nama_item = 'Ayam Geprek Sambal' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Ayam Geprek Level 3 (Pedas)', 1000 FROM menu WHERE nama_item = 'Ayam Geprek Sambal' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Ayam Geprek Level 4 (Extra Pedas)', 2000 FROM menu WHERE nama_item = 'Ayam Geprek Sambal' LIMIT 1;

INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Sate Bumbu Kacang', 0 FROM menu WHERE nama_item = 'Sate Ayam' LIMIT 1;
INSERT INTO varian_menu (id_menu, nama_varian, harga_tambahan) 
SELECT id_menu, 'Sate Bumbu Kecap', 0 FROM menu WHERE nama_item = 'Sate Ayam' LIMIT 1;

-- ============================================
-- 14. DATA USERS (Akun Login)
-- ============================================
INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role, foto_profil) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin@resto.com', '081234567890', 'admin', NULL),
('kasir', MD5('kasir123'), 'Kasir Resto', 'kasir@resto.com', '081234567891', 'kasir', NULL),
('owner', MD5('owner123'), 'Pemilik Resto', 'owner@resto.com', '081234567892', 'owner', NULL);

-- ============================================
-- 15. DATA QR PEMBAYARAN
-- ============================================
INSERT INTO qr_pembayaran (nama_qr, gambar_qr, kode_qr, status) VALUES
('QRIS BCA', NULL, NULL, 'aktif'),
('QRIS Mandiri', NULL, NULL, 'nonaktif'),
('QRIS BRI', NULL, NULL, 'nonaktif'),
('DANA', NULL, NULL, 'nonaktif'),
('OVO', NULL, NULL, 'nonaktif');

-- ============================================
-- 16. VERIFIKASI DATA
-- ============================================
SELECT '=========================================' AS '';
SELECT '✅ DATABASE BERHASIL DIBUAT!' AS Status;
SELECT '=========================================' AS '';

SELECT '📊 Jumlah Meja: ' AS Info, COUNT(*) AS Jumlah FROM meja
UNION ALL
SELECT '🍽️ Jumlah Menu: ', COUNT(*) FROM menu
UNION ALL
SELECT '🎨 Jumlah Varian: ', COUNT(*) FROM varian_menu
UNION ALL
SELECT '📑 Jumlah Kategori: ', COUNT(*) FROM kategori
UNION ALL
SELECT '👤 Jumlah User: ', COUNT(*) FROM users
UNION ALL
SELECT '💳 QR Pembayaran: ', COUNT(*) FROM qr_pembayaran;

SELECT '=========================================' AS '';
SELECT '📋 DATA USERS:' AS '';
SELECT id_user, username, nama_lengkap, email, role, is_active FROM users;

SELECT '=========================================' AS '';
SELECT '📁 DATA KATEGORI:' AS '';
SELECT id_kategori, nama_kategori, icon, status FROM kategori;

SELECT '=========================================' AS '';
SELECT '🍕 SAMPLE MENU DENGAN VARIAN:' AS '';
SELECT 
    m.nama_item,
    k.nama_kategori,
    m.harga,
    (SELECT GROUP_CONCAT(nama_varian SEPARATOR ', ') FROM varian_menu WHERE id_menu = m.id_menu AND status = 'aktif') AS varian_tersedia
FROM menu m
LEFT JOIN kategori k ON m.id_kategori = k.id_kategori
WHERE m.status = 'aktif'
GROUP BY m.id_menu, m.nama_item, k.nama_kategori, m.harga
LIMIT 10;

-- ============================================
-- SELESAI
-- ============================================
SELECT '=========================================' AS '';
SELECT '✅ Selesai! Database siap digunakan.' AS '';
SELECT '=========================================' AS '';