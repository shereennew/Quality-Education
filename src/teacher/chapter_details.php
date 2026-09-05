<?php
// src/teacher/chapter_details.php
session_start();
require_once __DIR__ . '/../config/db.php';

$chapter_name = isset($_GET['chapter']) ? trim(urldecode($_GET['chapter'])) : '';

if (empty($chapter_name)) {
    die("Chapter not specified.");
}

$error_msg = '';
$success_msg = '';

// Handle adding new material (Topic or Subtopic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_material') {
    $subtopic_name = trim($_POST['subtopic_name'] ?? '');
    $subtopic_name = ($subtopic_name === '') ? null : $subtopic_name;
    $title = trim($_POST['material_title'] ?? '');
    $file_path = null;

    if (!empty($title)) {
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

        $stmt = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$chapter_name, $subtopic_name, $title, $file_path]);
        $success_msg = "New material added successfully!";
    } else {
        $error_msg = "Material title is required.";
    }
}

// Handle editing existing material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_material') {
    $material_id = intval($_POST['material_id'] ?? 0);
    $subtopic_name = trim($_POST['subtopic_name'] ?? '');
    $subtopic_name = ($subtopic_name === '') ? null : $subtopic_name;
    $title = trim($_POST['material_title'] ?? '');

    if ($material_id > 0 && !empty($title)) {
        // Check if a new file was uploaded
        $file_sql_part = "";
        $params = [$subtopic_name, $title];

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_edit_' . basename($_FILES['material_file']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
                $file_path = 'uploads/' . $file_name;
                $file_sql_part = ", file_path = ?";
                $params[] = $file_path;
            }
        }

        $params[] = $material_id;
        $stmt = $pdo->prepare("UPDATE chapter_materials SET subtopic_name = ?, title = ? {$file_sql_part} WHERE id = ?");
        $stmt->execute($params);
        $success_msg = "Material updated successfully!";
    } else {
        $error_msg = "Valid material ID and title are required for editing.";
    }
}

// Handle adding new quiz question (Topic or Subtopic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_quiz') {
    $subtopic_name = trim($_POST['subtopic_name'] ?? '');
    $subtopic_name = ($subtopic_name === '') ? null : $subtopic_name;
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_option = trim($_POST['correct_option'] ?? '');

    if (!empty($question) && !empty($option_a) && !empty($option_b) && !empty($option_c) && !empty($option_d) && !empty($correct_option)) {
        $stmt = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$chapter_name, $subtopic_name, $question, $option_a, $option_b, $option_c, $option_d, $correct_option]);
        $success_msg = "New quiz question added successfully!";
    } else {
        $error_msg = "All fields are required for adding a quiz question.";
    }
}

// Handle editing existing quiz question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_quiz') {
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    $subtopic_name = trim($_POST['subtopic_name'] ?? '');
    $subtopic_name = ($subtopic_name === '') ? null : $subtopic_name;
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_option = trim($_POST['correct_option'] ?? '');

    if ($quiz_id > 0 && !empty($question) && !empty($option_a) && !empty($option_b) && !empty($option_c) && !empty($option_d) && !empty($correct_option)) {
        $stmt = $pdo->prepare("UPDATE chapter_quizzes SET subtopic_name = ?, question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ?");
        $stmt->execute([$subtopic_name, $question, $option_a, $option_b, $option_c, $option_d, $correct_option, $quiz_id]);
        $success_msg = "Quiz question updated successfully!";
    } else {
        $error_msg = "All fields are required for editing a quiz question.";
    }
}

// Fetch all unique subtopics for dropdown selectors
$stmt_sub = $pdo->prepare("SELECT DISTINCT subtopic_name FROM chapter_materials WHERE chapter_name = ? AND subtopic_name IS NOT NULL UNION SELECT DISTINCT subtopic_name FROM chapter_quizzes WHERE chapter_name = ? AND subtopic_name IS NOT NULL");
$stmt_sub->execute([$chapter_name, $chapter_name]);
$subtopics_list = $stmt_sub->fetchAll(PDO::FETCH_COLUMN);

// Fetch materials for this chapter
$stmt_m = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ?");
$stmt_m->execute([$chapter_name]);
$materials = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

// Fetch Main Topic Quizzes
$stmt_main = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? AND (subtopic_name IS NULL OR subtopic_name = '')");
$stmt_main->execute([$chapter_name]);
$main_quizzes = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

// Fetch Subtopic Quizzes
$stmt_sub = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? AND subtopic_name IS NOT NULL AND subtopic_name != ''");
$stmt_sub->execute([$chapter_name]);
$sub_quizzes = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter_name); ?> - Chapter Details</title>
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

        function toggleEditModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    </script>
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-5xl mx-auto px-6 py-3.5 flex justify-between items-center w-full">
            <a href="classroom.php"
                class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr;
                Back to Classroom</a>
            <h1 class="text-base font-bold text-pastel-text tracking-wide">
                <?php echo htmlspecialchars($chapter_name); ?>
            </h1>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-8 space-y-8 flex-1 w-full">
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

        <!-- Section 1: Chapter Materials -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Chapter Materials & Subtopics</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (count($materials) > 0): ?>
                    <?php foreach ($materials as $mat): ?>
                        <div
                            class="border border-blue-100 rounded-xl p-4 bg-pastel-bg/20 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex justify-between items-start">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        <?php echo !empty($mat['subtopic_name']) ? htmlspecialchars($mat['subtopic_name']) : 'Main Topic Material'; ?>
                                    </span>
                                    <button onclick="toggleEditModal('edit-material-<?php echo $mat['id']; ?>')"
                                        class="text-xs text-pastel-primary hover:text-pastel-hover font-semibold">Edit</button>
                                </div>
                                <h3 class="text-sm font-bold text-pastel-text mt-0.5">
                                    <?php echo htmlspecialchars($mat['title']); ?>
                                </h3>
                            </div>
                            <?php if (!empty($mat['file_path'])): ?>
                                <div>
                                    <a href="../../<?php echo htmlspecialchars($mat['file_path']); ?>" target="_blank"
                                        class="inline-block bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                        View/Download File &rarr;
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400 italic">No file attached</span>
                            <?php endif; ?>
                        </div>

                        <!-- Edit Material Modal Popup -->
                        <div id="edit-material-<?php echo $mat['id']; ?>"
                            class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                            <div class="bg-white rounded-2xl p-6 max-w-md w-full space-y-4 border border-blue-100 shadow-lg">
                                <h3 class="text-sm font-bold text-pastel-text">Edit Material</h3>
                                <form action="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>" method="POST"
                                    enctype="multipart/form-data" class="space-y-3">
                                    <input type="hidden" name="action" value="edit_material">
                                    <input type="hidden" name="material_id" value="<?php echo $mat['id']; ?>">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Assign to Subtopic
                                            (Optional)</label>
                                        <select name="subtopic_name"
                                            class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                            <option value="">-- Main Topic Level --</option>
                                            <?php foreach ($subtopics_list as $sname): ?>
                                                <option value="<?php echo htmlspecialchars($sname); ?>" <?php echo ($mat['subtopic_name'] === $sname) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sname); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                                        <input type="text" name="material_title" required
                                            value="<?php echo htmlspecialchars($mat['title']); ?>"
                                            class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Replace File
                                            (Optional)</label>
                                        <input type="file" name="material_file" class="w-full text-xs text-slate-500">
                                    </div>
                                    <div class="flex justify-end space-x-2 pt-2">
                                        <button type="button"
                                            onclick="toggleEditModal('edit-material-<?php echo $mat['id']; ?>')"
                                            class="px-3 py-1.5 text-xs bg-slate-100 rounded-xl">Cancel</button>
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-pastel-primary text-white rounded-xl">Save
                                            Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-slate-400 col-span-2">No material items found for this chapter yet.</p>
                <?php endif; ?>
            </div>

            <!-- Add Material Form -->
            <div class="border-t border-blue-100 pt-6 mt-6">
                <h3 class="text-sm font-bold text-pastel-text mb-3">Add New Material</h3>
                <form action="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>" method="POST"
                    enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_material">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic
                                (Optional)</label>
                            <input type="text" name="subtopic_name" placeholder="Leave empty for Main Topic"
                                class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Material Title</label>
                            <input type="text" name="material_title" required placeholder="e.g., Practice Notes"
                                class="w-full text-xs px-4 py-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach File</label>
                            <input type="file" name="material_file"
                                class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pastel-badge file:text-pastel-hover">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                            + Add Material
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Chapter Quizzes -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 space-y-6">
            <!-- Main Topic Quizzes Section -->
            <div class="mb-10">
                <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span>Main Topic Quizzes</span>
                    <span
                        class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full"><?php echo count($main_quizzes); ?></span>
                </h2>

                <?php if (empty($main_quizzes)): ?>
                    <p class="text-sm text-slate-500 italic bg-white p-4 rounded-xl border border-slate-100">No main topic
                        quizzes available.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($main_quizzes as $index => $q): ?>
                            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                <div class="flex justify-between items-center mb-3">
                                    <span
                                        class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg">Question
                                        #<?php echo $index + 1; ?></span>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg">Correct:
                                            Option <?php echo $q['correct_option']; ?></span>
                                        <button type="button" onclick="toggleEditModal('edit-quiz-<?php echo $q['id']; ?>')"
                                            class="text-xs font-semibold text-slate-400 hover:text-pastel-primary transition">Edit</button>
                                    </div>
                                </div>
                                <p class="text-slate-800 font-medium mb-4"><?php echo htmlspecialchars($q['question']); ?></p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">A:
                                        <?php echo htmlspecialchars($q['option_a']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">B:
                                        <?php echo htmlspecialchars($q['option_b']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">C:
                                        <?php echo htmlspecialchars($q['option_c']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">D:
                                        <?php echo htmlspecialchars($q['option_d']); ?></div>
                                </div>
                            </div>

                            <!-- Edit Quiz Modal Popup -->
                            <div id="edit-quiz-<?php echo $q['id']; ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                                <div class="bg-white rounded-2xl p-6 max-w-lg w-full space-y-4 border border-blue-100 shadow-lg">
                                    <h3 class="text-sm font-bold text-pastel-text">Edit Quiz Question</h3>
                                    <form action="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>" method="POST" class="space-y-3">
                                        <input type="hidden" name="action" value="edit_quiz">
                                        <input type="hidden" name="quiz_id" value="<?php echo $q['id']; ?>">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic (Optional)</label>
                                            <input type="text" name="subtopic_name" value="<?php echo htmlspecialchars($q['subtopic_name'] ?? ''); ?>" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                                            <input type="text" name="question" required value="<?php echo htmlspecialchars($q['question']); ?>" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="option_a" required value="<?php echo htmlspecialchars($q['option_a']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option A">
                                            <input type="text" name="option_b" required value="<?php echo htmlspecialchars($q['option_b']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option B">
                                            <input type="text" name="option_c" required value="<?php echo htmlspecialchars($q['option_c']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option C">
                                            <input type="text" name="option_d" required value="<?php echo htmlspecialchars($q['option_d']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option D">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                                            <select name="correct_option" required class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                                <option value="A" <?php echo ($q['correct_option'] === 'A') ? 'selected' : ''; ?>>Option A</option>
                                                <option value="B" <?php echo ($q['correct_option'] === 'B') ? 'selected' : ''; ?>>Option B</option>
                                                <option value="C" <?php echo ($q['correct_option'] === 'C') ? 'selected' : ''; ?>>Option C</option>
                                                <option value="D" <?php echo ($q['correct_option'] === 'D') ? 'selected' : ''; ?>>Option D</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end space-x-2 pt-2">
                                            <button type="button" onclick="toggleEditModal('edit-quiz-<?php echo $q['id']; ?>')" class="px-3 py-1.5 text-xs bg-slate-100 rounded-xl">Cancel</button>
                                            <button type="submit" class="px-3 py-1.5 text-xs bg-pastel-primary text-white rounded-xl">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Subtopic Quizzes Section -->
            <div>
                <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span>Subtopic Quizzes</span>
                    <span
                        class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full"><?php echo count($sub_quizzes); ?></span>
                </h2>

                <?php if (empty($sub_quizzes)): ?>
                    <p class="text-sm text-slate-500 italic bg-white p-4 rounded-xl border border-slate-100">No subtopic
                        quizzes available.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($sub_quizzes as $index => $q): ?>
                            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                <div class="flex justify-between items-center mb-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg">Question
                                            #<?php echo $index + 1; ?></span>
                                        <span
                                            class="text-xs font-semibold px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg uppercase"><?php echo htmlspecialchars($q['subtopic_name']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-xs font-semibold px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg">Correct:
                                            Option <?php echo $q['correct_option']; ?></span>
                                        <button type="button" onclick="toggleEditModal('edit-quiz-<?php echo $q['id']; ?>')"
                                            class="text-xs font-semibold text-slate-400 hover:text-pastel-primary transition">Edit</button>
                                    </div>
                                </div>
                                <p class="text-slate-800 font-medium mb-4"><?php echo htmlspecialchars($q['question']); ?></p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">A:
                                        <?php echo htmlspecialchars($q['option_a']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">B:
                                        <?php echo htmlspecialchars($q['option_b']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">C:
                                        <?php echo htmlspecialchars($q['option_c']); ?></div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50">D:
                                        <?php echo htmlspecialchars($q['option_d']); ?></div>
                                </div>
                            </div>

                            <!-- Edit Quiz Modal Popup -->
                            <div id="edit-quiz-<?php echo $q['id']; ?>" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                                <div class="bg-white rounded-2xl p-6 max-w-lg w-full space-y-4 border border-blue-100 shadow-lg">
                                    <h3 class="text-sm font-bold text-pastel-text">Edit Quiz Question</h3>
                                    <form action="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>" method="POST" class="space-y-3">
                                        <input type="hidden" name="action" value="edit_quiz">
                                        <input type="hidden" name="quiz_id" value="<?php echo $q['id']; ?>">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Subtopic (Optional)</label>
                                            <input type="text" name="subtopic_name" value="<?php echo htmlspecialchars($q['subtopic_name'] ?? ''); ?>" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Question</label>
                                            <input type="text" name="question" required value="<?php echo htmlspecialchars($q['question']); ?>" class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="option_a" required value="<?php echo htmlspecialchars($q['option_a']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option A">
                                            <input type="text" name="option_b" required value="<?php echo htmlspecialchars($q['option_b']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option B">
                                            <input type="text" name="option_c" required value="<?php echo htmlspecialchars($q['option_c']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option C">
                                            <input type="text" name="option_d" required value="<?php echo htmlspecialchars($q['option_d']); ?>" class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40" placeholder="Option D">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correct Option</label>
                                            <select name="correct_option" required class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40">
                                                <option value="A" <?php echo ($q['correct_option'] === 'A') ? 'selected' : ''; ?>>Option A</option>
                                                <option value="B" <?php echo ($q['correct_option'] === 'B') ? 'selected' : ''; ?>>Option B</option>
                                                <option value="C" <?php echo ($q['correct_option'] === 'C') ? 'selected' : ''; ?>>Option C</option>
                                                <option value="D" <?php echo ($q['correct_option'] === 'D') ? 'selected' : ''; ?>>Option D</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end space-x-2 pt-2">
                                            <button type="button" onclick="toggleEditModal('edit-quiz-<?php echo $q['id']; ?>')" class="px-3 py-1.5 text-xs bg-slate-100 rounded-xl">Cancel</button>
                                            <button type="submit" class="px-3 py-1.5 text-xs bg-pastel-primary text-white rounded-xl">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>

</html>