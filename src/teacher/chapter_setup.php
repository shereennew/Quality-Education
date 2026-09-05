<?php
// src/teacher/chapter_setup.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    title TEXT NOT NULL,
    file_path TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option TEXT NOT NULL
)");

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter_name = trim($_POST['chapter_name']);
    $material_title = trim($_POST['material_title']);

    if (!empty($chapter_name) && !empty($material_title)) {
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

        // Save initial material
        $stmt_ins = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, title, file_path) VALUES (?, ?, ?)");
        $stmt_ins->execute([$chapter_name, $material_title, $file_path]);

        // Save multiple quiz questions if submitted
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            $stmt_quiz = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['questions'] as $q) {
                $question = trim($q['question'] ?? '');
                $option_a = trim($q['option_a'] ?? '');
                $option_b = trim($q['option_b'] ?? '');
                $option_c = trim($q['option_c'] ?? '');
                $option_d = trim($q['option_d'] ?? '');
                $correct_option = trim($q['correct_option'] ?? '');

                if (!empty($question) && !empty($option_a) && !empty($option_b) && !empty($option_c) && !empty($option_d) && !empty($correct_option)) {
                    $stmt_quiz->execute([$chapter_name, $question, $option_a, $option_b, $option_c, $option_d, $correct_option]);
                }
            }
        }

        $success_msg = "Chapter and quiz questions created successfully!";
        header("refresh:1.5;url=chapter_details.php?chapter=" . urlencode($chapter_name));
    } else {
        $error_msg = "Chapter name and initial material title are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Chapter - EduPulse</title>
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

        let questionIndex = 1;
        function addQuestionField() {
            const container = document.getElementById('quiz-questions-container');
            const html = `
                <div class="quiz-block border border-blue-100 p-4 rounded-xl space-y-3 bg-pastel-bg/20 relative">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-3 right-3 text-rose-500 hover:text-rose-700 text-xs font-semibold">Remove</button>
                    <h4 class="text-xs font-bold text-pastel-text">Question #${++questionIndex}</h4>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                        <input type="text" name="questions[${questionIndex}][question]" required placeholder="Enter question" class="w-full text-xs px-4 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input type="text" name="questions[${questionIndex}][option_a]" required placeholder="Option A" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="questions[${questionIndex}][option_b]" required placeholder="Option B" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="questions[${questionIndex}][option_c]" required placeholder="Option C" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="questions[${questionIndex}][option_d]" required placeholder="Option D" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                        <select name="questions[${questionIndex}][correct_option]" required class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-3xl mx-auto px-6 py-3.5 flex justify-between items-center w-full">
            <a href="classroom.php" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back to Classroom</a>
            <h1 class="text-base font-bold text-pastel-text tracking-wide">Chapter Setup & Quiz Builder</h1>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10 flex-1 w-full">
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8">
            <h2 class="text-lg font-bold text-pastel-text mb-1">Create Chapter & Add Multiple Quizzes</h2>
            <p class="text-xs text-slate-400 mb-6">Set up your chapter title, attach initial files, and add as many quiz questions as needed.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-xl">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form action="chapter_setup.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Chapter Info -->
                <div class="space-y-4 pb-6 border-b border-blue-50">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Chapter Name</label>
                        <input type="text" name="chapter_name" required placeholder="e.g., Geometry (Ch 4)" class="w-full text-xs px-4 py-3 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Initial Material Title</label>
                        <input type="text" name="material_title" required placeholder="e.g., Introduction Slides" class="w-full text-xs px-4 py-3 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach File (Optional)</label>
                        <input type="file" name="material_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover hover:file:bg-blue-200">
                    </div>
                </div>

                <!-- Multiple Quiz Questions Section -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-bold text-pastel-text">Quiz Questions</h3>
                            <p class="text-xs text-slate-400">Add multiple questions for this chapter.</p>
                        </div>
                        <button type="button" onclick="addQuestionField()" class="bg-pastel-badge text-pastel-hover hover:bg-blue-200 text-xs font-semibold px-3 py-1.5 rounded-xl transition">
                            + Add Another Question
                        </button>
                    </div>

                    <div id="quiz-questions-container" class="space-y-4">
                        <div class="quiz-block border border-blue-100 p-4 rounded-xl space-y-3 bg-pastel-bg/20">
                            <h4 class="text-xs font-bold text-pastel-text">Question #1</h4>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                                <input type="text" name="questions[1][question]" required placeholder="Enter question" class="w-full text-xs px-4 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <input type="text" name="questions[1][option_a]" required placeholder="Option A" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                <input type="text" name="questions[1][option_b]" required placeholder="Option B" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                <input type="text" name="questions[1][option_c]" required placeholder="Option C" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                <input type="text" name="questions[1][option_d]" required placeholder="Option D" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                                <select name="questions[1][correct_option]" required class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-6 py-3 rounded-xl transition shadow-sm">
                        Save Chapter & Quizzes
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>