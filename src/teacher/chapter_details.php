<?php
// src/teacher/chapter_details.php
session_start();
require_once __DIR__ . '/../config/db.php';

$chapter_name = isset($_GET['chapter']) ? trim(urldecode($_GET['chapter'])) : '';

if (empty($chapter_name)) {
    die("Chapter not specified.");
}

// Handle deletions or updates if needed for view/edit control
if (isset($_GET['delete_material'])) {
    $mat_id = intval($_GET['delete_material']);
    $stmt = $pdo->prepare("DELETE FROM chapter_materials WHERE id = ? AND chapter_name = ?");
    $stmt->execute([$mat_id, $chapter_name]);
    header("Location: chapter_details.php?chapter=" . urlencode($chapter_name));
    exit;
}

if (isset($_GET['delete_quiz'])) {
    $quiz_id = intval($_GET['delete_quiz']);
    $stmt = $pdo->prepare("DELETE FROM chapter_quizzes WHERE id = ? AND chapter_name = ?");
    $stmt->execute([$quiz_id, $chapter_name]);
    header("Location: chapter_details.php?chapter=" . urlencode($chapter_name));
    exit;
}

// Fetch materials
$stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id DESC");
$stmt_materials->execute([$chapter_name]);
$materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);

// Fetch quizzes
$stmt_quizzes = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? ORDER BY id DESC");
$stmt_quizzes->execute([$chapter_name]);
$quizzes = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Chapter: <?php echo htmlspecialchars($chapter_name); ?> - EduPulse</title>
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
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center w-full">
            <div class="flex items-center space-x-4">
                <a href="classroom.php" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back to Classroom</a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide">Chapter View: <?php echo htmlspecialchars($chapter_name); ?></h1>
            </div>
            <a href="quiz.php?chapter=<?php echo urlencode($chapter_name); ?>" target="_blank" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                Preview Student Quiz
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-1 w-full space-y-8">
        <!-- Chapter Overview Card -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Curriculum Chapter View</span>
                <h2 class="text-xl font-bold text-pastel-text mt-1"><?php echo htmlspecialchars($chapter_name); ?></h2>
                <p class="text-xs text-slate-500 mt-1">Reviewing uploaded materials and quiz questions configured for this chapter.</p>
            </div>
            <div class="flex space-x-2">
                <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-4 py-2 rounded-xl border border-blue-100">
                    Materials: <?php echo count($materials); ?>
                </div>
                <div class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-4 py-2 rounded-xl border border-emerald-200">
                    Quiz Questions: <?php echo count($quizzes); ?>
                </div>
            </div>
        </div>

        <!-- Materials List -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="p-6 border-b border-blue-100">
                <h3 class="text-base font-bold text-pastel-text">Chapter Resources</h3>
                <p class="text-xs text-slate-400 mt-0.5">Files tied to <?php echo htmlspecialchars($chapter_name); ?>.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Title</th>
                            <th class="py-3.5 px-6">File Attachment</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
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
                                    <td class="py-4 px-6 text-right text-xs">
                                        <a href="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>&delete_material=<?php echo $mat['id']; ?>" onclick="return confirm('Delete this material?');" class="text-rose-600 hover:text-rose-800 font-semibold">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-xs text-slate-400">No materials found for this chapter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quizzes List -->
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="p-6 border-b border-blue-100">
                <h3 class="text-base font-bold text-pastel-text">Chapter Quiz Questions</h3>
                <p class="text-xs text-slate-400 mt-0.5">Active quiz assessments configured for <?php echo htmlspecialchars($chapter_name); ?>.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Question</th>
                            <th class="py-3.5 px-6">Options (A, B, C, D)</th>
                            <th class="py-3.5 px-6 text-center">Correct Answer</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 text-sm">
                        <?php if (count($quizzes) > 0): ?>
                            <?php foreach ($quizzes as $quiz): ?>
                                <tr class="hover:bg-pastel-bg/50 transition">
                                    <td class="py-4 px-6 text-xs text-slate-500 font-semibold">#<?php echo $quiz['id']; ?></td>
                                    <td class="py-4 px-6 font-medium text-pastel-text"><?php echo htmlspecialchars($quiz['question']); ?></td>
                                    <td class="py-4 px-6 text-xs space-y-1 text-slate-600">
                                        <div><strong class="text-slate-400">A:</strong> <?php echo htmlspecialchars($quiz['option_a']); ?></div>
                                        <div><strong class="text-slate-400">B:</strong> <?php echo htmlspecialchars($quiz['option_b']); ?></div>
                                        <div><strong class="text-slate-400">C:</strong> <?php echo htmlspecialchars($quiz['option_c']); ?></div>
                                        <div><strong class="text-slate-400">D:</strong> <?php echo htmlspecialchars($quiz['option_d']); ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-block bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full">
                                            <?php echo htmlspecialchars($quiz['correct_option']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right text-xs">
                                        <a href="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>&delete_quiz=<?php echo $quiz['id']; ?>" onclick="return confirm('Delete this question?');" class="text-rose-600 hover:text-rose-800 font-semibold">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-xs text-slate-400">No quiz questions found for this chapter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>