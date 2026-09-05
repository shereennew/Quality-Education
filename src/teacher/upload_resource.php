<?php
// upload_resource.php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';

$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['module_file']['tmp_name'];
        $fileName = $_FILES['module_file']['name'];
        $fileSize = $_FILES['module_file']['size'];
        $fileType = $_FILES['module_file']['type'];

        $destPath = $uploadDir . basename($fileName);

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $message = "File '" . htmlspecialchars($fileName) . "' uploaded successfully to local storage.";
        } else {
            $error = "Error moving the uploaded file.";
        }
    } else {
        $error = "Please select a valid file to upload.";
    }
}

// Fetch already uploaded materials from the directory
$uploadedFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            if (is_file($filePath)) {
                $uploadedFiles[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'time' => filemtime($filePath)
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Module - EduPulse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pastel: {
                            bg: '#f0f4f9',       // Very soft pastel blue background
                            card: '#ffffff',     // Card background
                            nav: '#e1e9f5',      // Soft blue for navbar
                            primary: '#7da0ca',  // Main pastel blue accent
                            hover: '#688dbb',    // Darker pastel hover state
                            text: '#2c3e50',     // Deep slate text for contrast
                            light: '#f8fafc',    // Ultra light container
                            badge: '#cbe0f5'     // Light pastel highlight tag
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="teacher_home.php"
                    class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr;
                    Back</a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-12 flex-1 w-full space-y-8">
        <!-- Upload Form Card -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8">
            <h2 class="text-lg font-bold text-pastel-text mb-1">Upload Learning Materials</h2>

            <?php if (!empty($message)): ?>
                <div
                    class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-medium">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-medium">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="upload_resource.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-xs font-semibold text-pastel-text uppercase tracking-wider mb-2">Select
                        Package / File</label>
                    <input type="file" name="module_file" required
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover hover:file:bg-blue-200 border border-blue-100 rounded-xl cursor-pointer bg-pastel-bg">
                </div>

                <div class="flex justify-center">
                    <button type="submit"
                        class="bg-pastel-primary hover:bg-pastel-hover text-white font-medium px-6 py-2.5 rounded-xl text-center transition shadow-sm text-sm">
                        Upload File
                    </button>
                </div>
            </form>
        </div>

        <!-- Uploaded Materials List Card -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8">
            <h3 class="text-base font-bold text-pastel-text mb-4">Currently Uploaded Materials</h3>
            <?php if (empty($uploadedFiles)): ?>
                <p class="text-xs text-slate-400">No files have been uploaded to local mesh storage yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                                <th class="py-3 px-4">File Name</th>
                                <th class="py-3 px-4">Size</th>
                                <th class="py-3 px-4">Upload Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50 text-xs">
                            <?php foreach ($uploadedFiles as $file): ?>
                                <tr class="hover:bg-pastel-bg/50 transition">
                                    <td class="py-3 px-4 font-medium text-pastel-text">
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500"><?php echo round($file['size'] / 1024, 2); ?> KB</td>
                                    <td class="py-3 px-4 text-slate-500"><?php echo date("Y-m-d H:i:s", $file['time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>