<?php
// src/teacher/chapter_setup.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure tables exist with support for both topic and subtopic levels
$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    subtopic_name TEXT DEFAULT NULL,
    title TEXT NOT NULL,
    file_path TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS chapter_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    subtopic_name TEXT DEFAULT NULL,
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
    $chapter_name = trim($_POST['chapter_name'] ?? '');

    if (!empty($chapter_name)) {
        $upload_dir = __DIR__ . '/../../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 1. Save Main Topic Material (if provided)
        $main_material_title = trim($_POST['main_material_title'] ?? '');
        if (!empty($main_material_title)) {
            $file_path = null;
            if (isset($_FILES['main_material_file']) && $_FILES['main_material_file']['error'] === UPLOAD_ERR_OK) {
                $file_name = time() . '_main_' . basename($_FILES['main_material_file']['name']);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['main_material_file']['tmp_name'], $target_file)) {
                    $file_path = 'uploads/' . $file_name;
                }
            }
            $stmt_mat = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, NULL, ?, ?)");
            $stmt_mat->execute([$chapter_name, $main_material_title, $file_path]);
        }

        // 2. Save Main Topic Quizzes (if provided)
        if (!empty($_POST['main_questions']) && is_array($_POST['main_questions'])) {
            $stmt_quiz = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['main_questions'] as $q) {
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

        // 3. Save Subtopics (Materials & Quizzes)
        if (!empty($_POST['subtopics']) && is_array($_POST['subtopics'])) {
            $stmt_sub_mat = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, ?, ?, ?)");
            $stmt_sub_quiz = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($_POST['subtopics'] as $sub_key => $sub) {
                $subtopic_name = trim($sub['subtopic_name'] ?? '');
                if (empty($subtopic_name)) continue;

                // Subtopic Material
                $sub_mat_title = trim($sub['material_title'] ?? '');
                $sub_file_path = null;
                if (isset($_FILES['subtopics']['name'][$sub_key]['material_file']) && $_FILES['subtopics']['error'][$sub_key]['material_file'] === UPLOAD_ERR_OK) {
                    $sub_file_name = time() . '_sub_' . basename($_FILES['subtopics']['name'][$sub_key]['material_file']);
                    $sub_target_file = $upload_dir . $sub_file_name;
                    if (move_uploaded_file($_FILES['subtopics']['tmp_name'][$sub_key]['material_file'], $sub_target_file)) {
                        $sub_file_path = 'uploads/' . $sub_file_name;
                    }
                }

                if (!empty($sub_mat_title)) {
                    $stmt_sub_mat->execute([$chapter_name, $subtopic_name, $sub_mat_title, $sub_file_path]);
                }

                // Subtopic Quizzes
                if (!empty($sub['questions']) && is_array($sub['questions'])) {
                    foreach ($sub['questions'] as $sq) {
                        $s_question = trim($sq['question'] ?? '');
                        $s_opt_a = trim($sq['option_a'] ?? '');
                        $s_opt_b = trim($sq['option_b'] ?? '');
                        $s_opt_c = trim($sq['option_c'] ?? '');
                        $s_opt_d = trim($sq['option_d'] ?? '');
                        $s_correct = trim($sq['correct_option'] ?? '');

                        if (!empty($s_question) && !empty($s_opt_a) && !empty($s_opt_b) && !empty($s_opt_c) && !empty($s_opt_d) && !empty($s_correct)) {
                            $stmt_sub_quiz->execute([$chapter_name, $subtopic_name, $s_question, $s_opt_a, $s_opt_b, $s_opt_c, $s_opt_d, $s_correct]);
                        }
                    }
                }
            }
        }

        $success_msg = "Chapter, topic quizzes, and subtopics created successfully!";
        header("refresh:1.5;url=chapter_details.php?chapter=" . urlencode($chapter_name));
    } else {
        $error_msg = "Chapter name is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Chapter, Topics & Subtopics - EduPulse</title>
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

        let mainQuizCount = 1;
        function addMainQuestionField() {
            mainQuizCount++;
            const container = document.getElementById('main-quiz-container');
            const html = `
                <div class="quiz-block border border-blue-100 p-4 rounded-xl space-y-3 bg-pastel-bg/20 relative">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-3 right-3 text-rose-500 hover:text-rose-700 text-xs font-semibold">Remove</button>
                    <h4 class="text-xs font-bold text-pastel-text">Topic Quiz Question #${mainQuizCount}</h4>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                        <input type="text" name="main_questions[${mainQuizCount}][question]" required placeholder="Enter question" class="w-full text-xs px-4 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input type="text" name="main_questions[${mainQuizCount}][option_a]" required placeholder="Option A" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        <input type="text" name="main_questions[${mainQuizCount}][option_b]" required placeholder="Option B" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        <input type="text" name="main_questions[${mainQuizCount}][option_c]" required placeholder="Option C" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        <input type="text" name="main_questions[${mainQuizCount}][option_d]" required placeholder="Option D" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                        <select name="main_questions[${mainQuizCount}][correct_option]" required class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
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

        let subtopicCount = 0;
        function addSubtopicSection() {
            subtopicCount++;
            const container = document.getElementById('subtopics-container');
            const html = `
                <div class="subtopic-block border-2 border-blue-200 p-5 rounded-2xl space-y-4 bg-white relative" id="subtopic-block-${subtopicCount}">
                    <button type="button" onclick="document.getElementById('subtopic-block-${subtopicCount}').remove()" class="absolute top-4 right-4 text-rose-500 hover:text-rose-700 text-xs font-bold">Remove Subtopic</button>
                    <h3 class="text-sm font-bold text-pastel-primary">Subtopic #${subtopicCount}</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic Name</label>
                        <input type="text" name="subtopics[${subtopicCount}][subtopic_name]" required placeholder="e.g., Subtopic 1.1: Basics" class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic Material Title</label>
                            <input type="text" name="subtopics[${subtopicCount}][material_title]" placeholder="Material title" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic Material File</label>
                            <input type="file" name="subtopics[${subtopicCount}][material_file]" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover">
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-blue-50">
                        <div class="flex justify-between items-center">
                            <h4 class="text-xs font-bold text-slate-600 uppercase">Subtopic Quiz Questions</h4>
                            <button type="button" onclick="addSubtopicQuestion(${subtopicCount})" class="text-xs bg-pastel-badge text-pastel-hover px-2.5 py-1 rounded-lg font-semibold">+ Add Question</button>
                        </div>
                        <div id="sub-questions-${subtopicCount}" class="space-y-3">
                            <div class="border border-blue-100 p-3 rounded-xl space-y-2 bg-pastel-bg/20">
                                <input type="text" name="subtopics[${subtopicCount}][questions][1][question]" placeholder="Subtopic Question" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" name="subtopics[${subtopicCount}][questions][1][option_a]" placeholder="Option A" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    <input type="text" name="subtopics[${subtopicCount}][questions][1][option_b]" placeholder="Option B" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    <input type="text" name="subtopics[${subtopicCount}][questions][1][option_c]" placeholder="Option C" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    <input type="text" name="subtopics[${subtopicCount}][questions][1][option_d]" placeholder="Option D" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                </div>
                                <select name="subtopics[${subtopicCount}][questions][1][correct_option]" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    <option value="A">Correct: Option A</option>
                                    <option value="B">Correct: Option B</option>
                                    <option value="C">Correct: Option C</option>
                                    <option value="D">Correct: Option D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function addSubtopicQuestion(subIndex) {
            const container = document.getElementById(`sub-questions-${subIndex}`);
            const qCount = container.children.length + 1;
            const html = `
                <div class="border border-blue-100 p-3 rounded-xl space-y-2 bg-pastel-bg/20 relative">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-rose-500 text-[10px] font-bold">Remove</button>
                    <input type="text" name="subtopics[${subIndex}][questions][${qCount}][question]" placeholder="Subtopic Question #${qCount}" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="subtopics[${subIndex}][questions][${qCount}][option_a]" placeholder="Option A" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="subtopics[${subIndex}][questions][${qCount}][option_b]" placeholder="Option B" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="subtopics[${subIndex}][questions][${qCount}][option_c]" placeholder="Option C" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <input type="text" name="subtopics[${subIndex}][questions][${qCount}][option_d]" placeholder="Option D" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                    </div>
                    <select name="subtopics[${subIndex}][questions][${qCount}][correct_option]" class="text-xs px-2 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40">
                        <option value="A">Correct: Option A</option>
                        <option value="B">Correct: Option B</option>
                        <option value="C">Correct: Option C</option>
                        <option value="D">Correct: Option D</option>
                    </select>
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
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10 flex-1 w-full">
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8">
            <h2 class="text-lg font-bold text-pastel-text mb-1">Create Chapter Structure</h2>

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

            <form action="chapter_setup.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <!-- Chapter Name -->
                <div class="bg-pastel-bg/30 p-5 rounded-2xl border border-blue-100 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">1. Chapter Information</h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Chapter Name (Topic)</label>
                        <input type="text" name="chapter_name" required placeholder="e.g., Geometry (Ch 4)" class="w-full text-xs px-4 py-3 rounded-xl border border-blue-100 bg-white focus:outline-none focus:border-pastel-primary">
                    </div>
                </div>

                <!-- Main Topic Materials & Quizzes -->
                <div class="bg-pastel-bg/30 p-5 rounded-2xl border border-blue-100 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">2. Topic-Level Materials & Quizzes (Optional)</h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Topic Material Title</label>
                        <input type="text" name="main_material_title" placeholder="e.g., Chapter Overview Slides" class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-white focus:outline-none focus:border-pastel-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Topic Material File</label>
                        <input type="file" name="main_material_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover">
                    </div>

                    <div class="space-y-3 pt-3 border-t border-blue-100">
                        <div class="flex justify-between items-center">
                            <h4 class="text-xs font-bold text-slate-600">Topic Quiz Questions</h4>
                            <button type="button" onclick="addMainQuestionField()" class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1.5 rounded-xl transition">
                                + Add Topic Quiz Question
                            </button>
                        </div>
                        <div id="main-quiz-container" class="space-y-4">
                            <div class="quiz-block border border-blue-100 p-4 rounded-xl space-y-3 bg-white">
                                <h4 class="text-xs font-bold text-pastel-text">Topic Quiz Question #1</h4>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                                    <input type="text" name="main_questions[1][question]" placeholder="Enter question" class="w-full text-xs px-4 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <input type="text" name="main_questions[1][option_a]" placeholder="Option A" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                    <input type="text" name="main_questions[1][option_b]" placeholder="Option B" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                    <input type="text" name="main_questions[1][option_c]" placeholder="Option C" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                    <input type="text" name="main_questions[1][option_d]" placeholder="Option D" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                                    <select name="main_questions[1][correct_option]" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                                        <option value="A">Option A</option>
                                        <option value="B">Option B</option>
                                        <option value="C">Option C</option>
                                        <option value="D">Option D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subtopics Section -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-bold text-pastel-text">3. Subtopics (Materials & Quizzes)</h3>
                        </div>
                        <button type="button" onclick="addSubtopicSection()" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                            + Add Subtopic
                        </button>
                    </div>

                    <div id="subtopics-container" class="space-y-6">
                        <!-- Dynamic subtopics will be added here -->
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-6 py-3 rounded-xl transition shadow-sm">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>