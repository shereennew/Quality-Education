<?php
// src/teacher/upload.php
session_start();
require_once __DIR__ . '/../config/db.php';

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$message = '';
$error = '';

// Ensure individual student resource table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Fetch student details
$stmt = $pdo->prepare("SELECT s.*, c.name as classroom_name, c.id as classroom_id FROM students s JOIN classrooms c ON s.classroom_id = c.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

$uploadDir = __DIR__ . '/../../uploads/targeted/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = trim($_POST['notes'] ?? '');
    
    if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['student_file']['tmp_name'];
        $fileName = $_FILES['student_file']['name'];
        $destPath = $uploadDir . basename($fileName);

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $stmt_ins = $pdo->prepare("INSERT INTO student_resources (student_id, file_name, notes) VALUES (?, ?, ?)");
            $stmt_ins->execute([$student_id, $fileName, $notes]);
            $message = "Targeted resource uploaded successfully for " . htmlspecialchars($student['name']) . ".";
        } else {
            $error = "Failed to move uploaded file.";
        }
    } else {
        $error = "Please select a valid file to upload.";
    }
}

// Fetch previously uploaded files for this student
$stmt_res = $pdo->prepare("SELECT * FROM student_resources WHERE student_id = ? ORDER BY id DESC");
$stmt_res->execute([$student_id]);
$resources = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Targeted Resource Upload - EduPulse</title>
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
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="classroom.php?id=<?php echo $student['classroom_id']; ?>" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back to Classroom</a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide">Targeted Student Intervention</h1>
            </div>
            <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                🎯 Individual Support
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10 flex-1 w-full space-y-8">

        <?php if (!empty($message)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-medium">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-medium">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Student Info Header -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 flex justify-between items-center">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider bg-pastel-badge text-pastel-hover px-2.5 py-1 rounded-md"><?php echo htmlspecialchars($student['classroom_name']); ?></span>
                <h2 class="text-lg font-bold text-pastel-text mt-2"><?php echo htmlspecialchars($student['name']); ?></h2>
                <p class="text-xs text-slate-500 mt-0.5">Current Status: <span class="font-semibold text-pastel-text"><?php echo htmlspecialchars($student['status']); ?></span></p>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8 space-y-6">
            <div>
                <h3 class="text-base font-bold text-pastel-text mb-1">Upload Remedial Resource</h3>
                <p class="text-xs text-slate-500">Provide customized learning files or worksheets specifically for this student.</p>
            </div>

            <form action="upload.php?student_id=<?php echo $student_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-pastel-text uppercase tracking-wider">Select File</label>
                    <input type="file" name="student_file" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover hover:file:bg-blue-200 border border-blue-100 rounded-xl cursor-pointer bg-pastel-bg">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-pastel-text uppercase tracking-wider">Teacher Notes / Instructions</label>
                    <textarea name="notes" rows="3" placeholder="Add guidance notes for the student..." class="w-full text-xs text-pastel-text border border-blue-100 rounded-xl px-4 py-3 bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary"></textarea>
                </div>

                <div class="flex justify-center pt-2">
                    <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white font-medium px-8 py-2.5 rounded-xl text-center transition shadow-sm text-sm">
                        Upload Remedial Material
                    </button>
                </div>
            </form>
        </div>

        <!-- Previous Uploads List -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 space-y-4">
            <h3 class="text-sm font-bold text-pastel-text">Previously Assigned Resources</h3>
            <?php if (empty($resources)): ?>
                <p class="text-xs text-slate-400">No targeted resources uploaded for this student yet.</p>
            <?php else: ?>
                <ul class="space-y-3 text-xs">
                    <?php foreach ($resources as $res): ?>
                        <li class="bg-pastel-bg p-4 rounded-xl border border-blue-50 space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-pastel-text"><?php echo htmlspecialchars($res['file_name']); ?></span>
                                <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($res['created_at']); ?></span>
                            </div>
                            <?php if (!empty($res['notes'])): ?>
                                <p class="text-slate-600 mt-1 italic"><?php echo htmlspecialchars($res['notes']); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>