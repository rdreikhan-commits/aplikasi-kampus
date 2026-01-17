<?php
// --- 1. KONFIGURASI DATABASE ---
// Ganti dengan detail koneksi database Anda
$host = 'localhost';
$dbname = 'db_penggajian';
$user = 'root';
$pass = ''; // Biasanya kosong jika menggunakan XAMPP default

// Membuat koneksi menggunakan PDO untuk keamanan
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// --- 2. LOGIKA PENGGUNA (SIMULASI) ---
// Di aplikasi nyata, ID pengguna akan didapat dari session setelah login.
// Kita simulasikan pengguna dengan id_user = 1 sedang login.
session_start();
// Jika Anda punya sistem login, ganti baris di bawah ini dengan:
// $id_user_saat_ini = $_SESSION['id_user']; 
$id_user_saat_ini = 1; // Ganti dengan ID user yang sedang aktif

$pesan = ''; // Variabel untuk menyimpan pesan notifikasi

// --- 3. PROSES UPLOAD FOTO PROFIL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["foto_profil_baru"])) {
    
    // Cek apakah ada file yang diupload dan tidak ada error
    if (isset($_FILES["foto_profil_baru"]) && $_FILES["foto_profil_baru"]["error"] == 0) {
        $target_dir = "uploads/"; // Folder untuk menyimpan gambar
        
        // Buat folder 'uploads' jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file = $_FILES["foto_profil_baru"];
        $nama_file_asli = basename($file["name"]);
        $tipe_gambar = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));

        // Buat nama file unik untuk menghindari nama yang sama
        // Format: user-ID-timestamp.extensi
        $nama_file_unik = "user-" . $id_user_saat_ini . "-" . time() . "." . $tipe_gambar;
        $target_file = $target_dir . $nama_file_unik;

        $uploadOk = 1;

        // Validasi 1: Cek apakah file adalah gambar asli
        $check = getimagesize($file["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $pesan = "File yang diupload bukan gambar.";
            $uploadOk = 0;
        }

        // Validasi 2: Batasi ukuran file (misal: maks 2MB)
        if ($file["size"] > 2000000) {
            $pesan = "Maaf, ukuran file terlalu besar. Maksimal 2MB.";
            $uploadOk = 0;
        }

        // Validasi 3: Izinkan hanya format tertentu
        if ($tipe_gambar != "jpg" && $tipe_gambar != "png" && $tipe_gambar != "jpeg" && $tipe_gambar != "gif") {
            $pesan = "Maaf, hanya format JPG, JPEG, PNG & GIF yang diizinkan.";
            $uploadOk = 0;
        }

        // Jika semua validasi lolos, coba upload file
        if ($uploadOk == 1) {
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                // Jika upload berhasil, update database
                try {
                    $sql = "UPDATE users SET foto_profil = :foto_profil WHERE id_user = :id_user";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['foto_profil' => $nama_file_unik, 'id_user' => $id_user_saat_ini]);
                    $pesan = "Foto profil berhasil diperbarui!";
                } catch (PDOException $e) {
                    $pesan = "Gagal memperbarui database: " . $e->getMessage();
                }
            } else {
                $pesan = "Maaf, terjadi error saat mengupload file Anda.";
            }
        }
    } else {
        $pesan = "Tidak ada file yang dipilih atau terjadi error.";
    }
}

// --- 4. AMBIL DATA PENGGUNA DARI DATABASE ---
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user_saat_ini]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Tidak bisa mengambil data user: " . $e->getMessage());
}

// Tentukan path gambar profil
$path_foto_profil = 'https://placehold.co/150x150/EFEFEF/AAAAAA?text=No+Image'; // Gambar default
if (!empty($user_data['foto_profil']) && file_exists('uploads/' . $user_data['foto_profil'])) {
    $path_foto_profil = 'uploads/' . $user_data['foto_profil'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Profil Saya</h1>
        <p class="text-center text-gray-500 mb-6">Kelola informasi profil Anda di sini.</p>

        <?php if (!empty($pesan)): ?>
            <div class="mb-4 p-4 text-sm rounded-lg <?php echo (strpos($pesan, 'berhasil') !== false) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col items-center">
            <!-- Tampilkan foto profil saat ini -->
            <img id="preview" src="<?php echo htmlspecialchars($path_foto_profil); ?>" alt="Foto Profil" class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 mb-4">

            <h2 class="text-xl font-semibold text-gray-700"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></h2>
            <p class="text-gray-500"><?php echo htmlspecialchars($user_data['username']); ?></p>

            <div class="w-full mt-8">
                <!-- Form untuk upload foto baru -->
                <form action="profil.php" method="post" enctype="multipart/form-data">
                    <label for="foto_profil_baru" class="block mb-2 text-sm font-medium text-gray-900">Ganti Foto Profil</label>
                    <input type="file" name="foto_profil_baru" id="foto_profil_baru" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" onchange="previewImage(event)">
                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG, atau GIF (Maks. 2MB).</p>

                    <button type="submit" class="w-full mt-4 bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-300">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk menampilkan preview gambar sebelum diupload
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('preview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
