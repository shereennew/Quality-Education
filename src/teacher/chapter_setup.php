<?php
// src/teacher/chapter_setup.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure chapter_materials table exists to prevent missing table errors
$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_name VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle chapter creation and material upload
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_chapter'])) {
    $chapter_name = trim($_POST['chapter_name']);
    $material_title = trim($_POST['material_title']);

    // Handle file upload if provided
    $file_path = null;
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['material_file']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            $file_path = 'uploads/' . $file_name;
        }
    }

    if (!empty($chapter_name) && !empty($material_title)) {
        $stmt = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, title, file_path) VALUES (?, ?, ?)");
        if ($stmt->execute([$chapter_name, $material_title, $file_path])) {
            $success_msg = "Chapter and material uploaded successfully!";
        } else {
            $error_msg = "Failed to upload chapter material.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter Setup & Quizzes - EduPulse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pastel: {
                            bg: '#f0f4f9',
                            card: '#ffffff',
                            nav: '#e1e9f5',
                            primary: '#7da0ca',
                            hover: '#688dbb',
                            text: '#2c3e50',
                            light: '#f8fafc',
                            badge: '#cbe0f5'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <!-- Navbar -->
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="classroom.php" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back</a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide">Chapter Setup & Quizzes</h1>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                    🛠️ Curriculum Manager
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10 flex-1 w-full space-y-6">

        <?php if (!empty($success_msg)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Add Chapter & Upload Form -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <h2 class="text-base font-bold text-pastel-text mb-2">Upload New Chapter & Material</h2>
            
            <form action="chapter_setup.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Chapter Name</label>
                    <input type="text" name="chapter_name" required placeholder="e.g., Geometry (Ch 4)" class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Initial Material / Quiz Title</label>
                    <input type="text" name="material_title" required placeholder="e.g., Introduction Quiz" class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach File (Optional)</label>
                    <input type="file" name="material_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover hover:file:bg-blue-200">
                </div>
                <div class="flex justify-end pt-4">
                    <button type="submit" name="add_chapter" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-6 py-2.5 rounded-xl transition shadow-sm">
                        Upload Chapter
                    </button>
                </div>
            </form>
        </div>

    </main>
</body>
</html>