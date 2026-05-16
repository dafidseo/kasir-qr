<?php
// koneksi.php - Untuk Supabase PostgreSQL

// ============================================
// KONFIGURASI SUPABASE (GANTI DENGAN DATA ANDA)
// ============================================

// Dari Connection String Transaction Pooler:
// postgresql://postgres.ffmpkiotyrvdcmezhyxt:[YOUR-PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres

$host = 'aws-0-ap-southeast-1.pooler.supabase.com';  // Host dari connection string
$port = '6543';                                       // Port Transaction Pooler
$dbname = 'postgres';                                 // Nama database
$user = 'postgres.ffmpkiotyrvdcmezhyxt';             // User lengkap (dengan project ref)
$password = 'Dafid@123!aku';                 // GANTI dengan password Anda!

// ============================================
// KONEKSI MENGGUNAKAN PDO (PostgreSQL)
// ============================================

try {
    // Buat koneksi PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30
    ]);
    
    // Set timezone ke WIB
    $conn->exec("SET TIME ZONE 'Asia/Jakarta'");
    
    // Set charset ke UTF-8
    $conn->exec("SET NAMES 'UTF8'");
    $conn->exec("SET CLIENT_ENCODING TO 'UTF8'");
    
    // (Opsional) Hapus komentar untuk test koneksi
    // echo "✅ Koneksi ke Supabase berhasil!";
    
} catch(PDOException $e) {
    die("❌ Koneksi database gagal: " . $e->getMessage());
}

// ============================================
// FUNGSI KOMPATIBILITAS UNTUK KODE LAMA (MySQL style)
// ============================================

// Class wrapper untuk hasil query (kompatibel dengan mysqli_result)
class SupabaseResult {
    private $stmt;
    private $result = [];
    private $index = 0;
    private $numRows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($this->result);
    }
    
    public function fetch_assoc() {
        if ($this->index < $this->numRows) {
            return $this->result[$this->index++];
        }
        return null;
    }
    
    public function fetch_array() {
        return $this->fetch_assoc();
    }
    
    public function fetch_object() {
        $data = $this->fetch_assoc();
        return $data ? (object)$data : null;
    }
    
    public function num_rows() {
        return $this->numRows;
    }
}

// Fungsi query utama (pengganti mysqli_query)
function mysqli_query($conn, $query) {
    try {
        // Konversi fungsi MySQL ke PostgreSQL
        $query = str_ireplace('NOW()', 'CURRENT_TIMESTAMP', $query);
        $query = str_ireplace('CURDATE()', 'CURRENT_DATE', $query);
        $query = str_ireplace('UNIX_TIMESTAMP()', 'EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)', $query);
        
        // Handle LIMIT dengan offset
        if (preg_match('/LIMIT\s+(\d+)\s*,\s*(\d+)/i', $query, $matches)) {
            $query = preg_replace('/LIMIT\s+\d+\s*,\s*\d+/i', "LIMIT {$matches[2]} OFFSET {$matches[1]}", $query);
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        // Cek apakah ini query SELECT
        if (stripos(trim($query), 'SELECT') === 0 || 
            stripos(trim($query), 'SHOW') === 0 ||
            stripos(trim($query), 'DESCRIBE') === 0) {
            return new SupabaseResult($stmt);
        }
        
        // Untuk INSERT, UPDATE, DELETE
        return true;
        
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . "\nQuery: " . $query);
        die("Query Error: " . $e->getMessage() . "<br><small>Query: " . htmlspecialchars($query) . "</small>");
    }
}

// Fungsi helper lainnya (kompatibel dengan kode lama)
function mysqli_fetch_assoc($result) {
    return $result ? $result->fetch_assoc() : null;
}

function mysqli_fetch_array($result) {
    return mysqli_fetch_assoc($result);
}

function mysqli_fetch_object($result) {
    return $result ? $result->fetch_object() : null;
}

function mysqli_num_rows($result) {
    return $result ? $result->num_rows() : 0;
}

function mysqli_insert_id($conn) {
    return $conn->lastInsertId();
}

function mysqli_real_escape_string($conn, $string) {
    return addslashes($string);
}

function mysqli_begin_transaction($conn) {
    return $conn->beginTransaction();
}

function mysqli_commit($conn) {
    return $conn->commit();
}

function mysqli_rollback($conn) {
    return $conn->rollBack();
}

function mysqli_set_charset($conn, $charset) {
    // PostgreSQL sudah support UTF-8
    return true;
}

function mysqli_error($conn) {
    return '';
}

function mysqli_errno($conn) {
    return 0;
}

// ============================================
// FUNGSI TAMBAHAN UNTUK DEBUG (Opsional)
// ============================================

function debug_query($query) {
    echo "<pre style='background:#f0f0f0; padding:10px; font-size:11px; margin:5px 0; border-left:3px solid #FF6B35; overflow:auto;'>";
    echo "<strong>Query:</strong><br>";
    echo htmlspecialchars($query);
    echo "</pre>";
}

// Uncomment untuk test koneksi
// $test = mysqli_query($conn, "SELECT NOW() as waktu");
// if($test && mysqli_num_rows($test) > 0) {
//     $row = mysqli_fetch_assoc($test);
//     echo "<p style='color:green;'>✅ Koneksi berhasil! Waktu server: " . $row['waktu'] . "</p>";
// }
?>