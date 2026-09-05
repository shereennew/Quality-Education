<?php
// src/teacher/chapter_details.php
session_start();
require_once __DIR__ . '/../config/db.php';

$chapter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$chapter_name = isset($_GET['chapter']) ? trim(urldecode($_GET['chapter'])) : '';

// Ensure chapter_materials table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_name VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Fetch specific chapter info by ID or Chapter Name
if ($chapter_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM chapter_materials WHERE id = ?");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (!empty($chapter_name)) {
    $stmt = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$chapter_name]);
    $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback if no materials are uploaded yet for this chapter
    if (!$chapter) {
        $chapter = [
            'id' => 0,
            'chapter_name' => $chapter_name,
            'title' => 'General Chapter View',
            'file_path' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

if (!$chapter) {
    die("Chapter not found.");
}

// Handle material or resource upload for this chapter[cite: 3]
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $material_title = trim($_POST['material_title']);
    
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

    if (!empty($material_title)) {
        $stmt_ins = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, title, file_path) VALUES (?, ?, ?)");
        if ($stmt_ins->execute([$chapter['chapter_name'], $material_title, $file_path])) {
            $success_msg = "Material added successfully!";
            // Refresh chapter data
            if ($chapter_id > 0) {
                $stmt->execute([$chapter_id]);
                $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $error_msg = "Failed to add material.";
        }
    } else {
        $error_msg = "Material title is required.";
    }
}

// Fetch all resources/materials sharing this chapter name[cite: 3]
$stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id DESC");
$stmt_materials->execute([$chapter['chapter_name']]);
$materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter['chapter_name']); ?> - EduPulse</title>
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
                <a href="classroom.php" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back </a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide"><?php echo htmlspecialchars($chapter['chapter_name']); ?></h1>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                    📖 Chapter Details & Materials
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-1 w-full space-y-8">

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

        <!-- Chapter Overview Card -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Curriculum Chapter</span>
                <h2 class="text-xl font-bold text-pastel-text mt-1"><?php echo htmlspecialchars($chapter['chapter_name']); ?></h2>
                <p class="text-xs text-slate-500 mt-1">Manage quizzes, notes, and downloadable files associated with this chapter.</p>
            </div>
            <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-4 py-2 rounded-xl border border-blue-100">
                Total Materials: <?php echo count($materials); ?>
            </div>
        </div>

        <!-- Add Material / Quiz Form -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <h3 class="text-base font-bold text-pastel-text mb-4">Add Material or Quiz to this Chapter</h3>
            <form action="chapter_details.php?id=<?php echo $chapter_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Material / Quiz Title</label>
                        <input type="text" name="material_title" required placeholder="e.g., Practice Worksheet 1" class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach File (Optional)</label>
                        <input type="file" name="material_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover hover:file:bg-blue-200">
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" name="upload_material" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-5 py-2.5 rounded-xl transition shadow-sm">
                        Upload Material
                    </button>
                </div>
            </form>
        </div>

        <!-- Materials List -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="p-6 border-b border-blue-100">
                <h3 class="text-base font-bold text-pastel-text">Chapter Resources & Quizzes</h3>
                <p class="text-xs text-slate-400 mt-0.5">All files and quizzes tied to <?php echo htmlspecialchars($chapter['chapter_name']); ?>.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Title</th>
                            <th class="py-3.5 px-6">File Attachment</th>
                            <th class="py-3.5 px-6">Added At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 text-sm">
                        <?php if (count($materials) > 0): ?>
                            <?php foreach ($materials as $mat): ?>
                                <tr class="hover:bg-pastel-bg/50 transition">
                                    <td class="py-4 px-6 text-xs text-slate-500 font-semibold">#<?php echo $mat['id']; ?></td>
                                    <td class="py-4 px-6 font-medium text-pastel-text"><?php echo htmlspecialchars($mat['title']); ?></td>
                                    <td class="py-4 px-6 text-xs">
                                        <?php if (!empty($mat['file_path'])): ?>
                                            <a href="../../<?php echo htmlspecialchars($mat['file_path']); ?>" target="_blank" class="text-pastel-hover font-semibold underline hover:text-blue-700">Download File</a>
                                        <?php else: ?>
                                            <span class="text-slate-400">No file attached</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-400"><?php echo $mat['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-xs text-slate-400">No materials uploaded for this chapter yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>