<?php
require_once __DIR__ . '/../config/db.php';

$student_id = 1;

// Fetch Student Info
$stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

// Fetch all 3 Islands from progress
$stmt_islands = $pdo->prepare("
    SELECT * FROM student_progress 
    WHERE student_id = ? 
    ORDER BY island_id ASC
");
$stmt_islands->execute([$student_id]);
$islands = $stmt_islands->fetchAll(PDO::FETCH_ASSOC);

// Currently selected island ID
$selectedIsland = isset($_GET['island_id']) ? (int)$_GET['island_id'] : 1;

// Find current island lesson status
$currentIslandProgress = null;
foreach ($islands as $isl) {
    if ((int)$isl['island_id'] === $selectedIsland) {
        $currentIslandProgress = $isl;
        break;
    }
}

// Fetch Quiz/Test completed summaries for the selected island
$stmt_assessments = $pdo->prepare("
    SELECT * FROM student_assessments 
    WHERE student_id = ? AND island_id = ? 
    ORDER BY submitted_at DESC
");
$stmt_assessments->execute([$student_id, $selectedIsland]);
$assessments = $stmt_assessments->fetchAll(PDO::FETCH_ASSOC);

// Fetch ONLY incorrect questions for review for this island
$stmt_incorrect = $pdo->prepare("
    SELECT sa.*, a.title as quiz_title 
    FROM student_answers sa
    JOIN student_assessments a ON sa.assessment_id = a.id
    WHERE a.student_id = ? AND a.island_id = ? AND sa.is_correct = 0
    ORDER BY sa.id DESC
");
$stmt_incorrect->execute([$student_id, $selectedIsland]);
$incorrectAnswers = $stmt_incorrect->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Island History</title>
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
                            badge: '#cbe0f5'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-start p-4 pt-28">

    <!-- NAVBAR -->
    <nav class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-2xl">📖</span>
                </div>
                <span class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block">EduHunt</span>
            </a>

            <div class="hidden md:flex items-center justify-center flex-1 mx-6">
                <ul class="flex items-center gap-3 text-lg font-bold">
                    <li><a href="index.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Home</a></li>
                    <li><a href="discussion.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Discussion</a></li>
                    <li><a href="module.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Modules</a></li>
                    <li><a href="quiz.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Quizzes</a></li>
                    <li><a href="history.php" class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm">History</a></li>
                </ul>
            </div>

            <div class="flex items-center flex-shrink-0">
                <button id="user-menu-button" data-dropdown-toggle="user-dropdown" type="button" class="flex items-center gap-3 py-2.5 px-4 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm">
                    <div class="w-10 h-10 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-lg">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-lg font-bold text-pastel-text hidden sm:block"><?= htmlspecialchars($student['name']) ?></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTAINER -->
    <main class="w-full max-w-[85rem] grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT SIDEBAR: CHAPTER / ISLAND LIST -->
        <section class="lg:col-span-4 bg-pastel-card border border-pastel-nav p-5 rounded-2xl shadow-md h-fit">
            <h2 class="text-lg font-black text-pastel-text mb-4 flex items-center gap-2">
                <span>🏝️</span> Chapter Islands
            </h2>
            
            <div class="flex flex-col gap-3">
                <?php foreach ($islands as $isl): ?>
                    <?php $isActive = ((int)$isl['island_id'] === $selectedIsland); ?>
                    <a href="history.php?island_id=<?= $isl['island_id'] ?>" 
                       class="p-4 rounded-xl border-2 transition-all duration-200 flex items-center justify-between <?= $isActive ? 'bg-pastel-badge border-pastel-primary shadow-sm' : 'bg-pastel-bg border-transparent hover:border-pastel-primary/40' ?>">
                        <div>
                            <span class="text-xs font-bold text-pastel-primary uppercase tracking-wide">Island <?= $isl['island_id'] ?></span>
                            <h3 class="font-bold text-pastel-text text-base leading-snug"><?= htmlspecialchars($isl['chapter_name']) ?></h3>
                            <span class="inline-block text-[11px] font-bold px-2 py-0.5 mt-1 rounded <?= $isl['status'] === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                Lesson: <?= htmlspecialchars($isl['status']) ?>
                            </span>
                        </div>
                        <span class="text-xl"><?= $isActive ? '➔' : '📑' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- RIGHT DISPLAY: SELECTED ISLAND HISTORY -->
        <section class="lg:col-span-8 flex flex-col gap-6">
            
            <!-- LESSON & ISLAND STATUS HEADER -->
            <div class="bg-pastel-card border border-pastel-nav p-6 rounded-2xl shadow-md flex flex-wrap justify-between items-center gap-4">
                <div>
                    <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Island <?= $selectedIsland ?> Status</span>
                    <h1 class="text-2xl font-black text-pastel-text mt-1"><?= htmlspecialchars($currentIslandProgress['chapter_name'] ?? 'Island Details') ?></h1>
                </div>
                
                <div class="bg-pastel-bg px-4 py-2.5 rounded-xl border border-pastel-nav text-center">
                    <span class="text-xs font-bold text-slate-400 uppercase block">Lesson Review Status</span>
                    <span class="text-sm font-black <?= ($currentIslandProgress['status'] ?? '') === 'Completed' ? 'text-emerald-600' : 'text-amber-600' ?>">
                        <?= htmlspecialchars($currentIslandProgress['status'] ?? 'In Progress') ?>
                    </span>
                </div>
            </div>

            <!-- COMPLETED QUIZZES & TESTS (CLICKABLE SUMMARY ONLY) -->
            <div class="bg-pastel-card border border-pastel-nav p-6 rounded-2xl shadow-md">
                <h2 class="text-lg font-black text-pastel-text mb-4 flex items-center gap-2">
                    <span>🎯</span> Completed Quizzes & Tests
                </h2>

                <?php if (empty($assessments)): ?>
                    <p class="text-sm text-slate-500">No quizzes or tests taken on this island yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($assessments as $item): ?>
                            <a href="view_quiz.php?assessment_id=<?= $item['id'] ?>" 
                               class="block border-2 border-pastel-nav hover:border-pastel-primary bg-pastel-bg hover:bg-white p-4 rounded-xl transition-all duration-200 shadow-sm">
                                <div class="flex justify-between items-start">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-pastel-badge text-pastel-primary uppercase"><?= htmlspecialchars($item['type']) ?></span>
                                    <span class="text-xs text-slate-400"><?= date('M d, Y', strtotime($item['submitted_at'])) ?></span>
                                </div>
                                <h3 class="font-bold text-pastel-text text-base mt-2"><?= htmlspecialchars($item['title']) ?></h3>
                                <div class="mt-3 flex justify-between items-center border-t border-slate-200/60 pt-2">
                                    <span class="text-base font-black text-pastel-primary"><?= htmlspecialchars($item['score']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEPARATE SECTION: MISSED QUESTIONS / FOCUS AREAS -->
            <div class="bg-pastel-card border border-pastel-nav p-6 rounded-2xl shadow-md">
                <h2 class="text-lg font-black text-pastel-text mb-1 flex items-center gap-2">
                    <span>⚠️</span> Focus Areas (Incorrect Questions)
                </h2>
                <p class="text-xs text-slate-400 mb-4">Only incorrect items from your quizzes and tests are highlighted here for quick review.</p>

                <?php if (empty($incorrectAnswers)): ?>
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
                        <span>🎉</span> No incorrect questions recorded for this island!
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($incorrectAnswers as $q): ?>
                            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-950">
                                <div class="flex justify-between items-start gap-2">
                                    <h4 class="font-bold text-sm text-slate-800"><?= htmlspecialchars($q['question_text']) ?></h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-200 text-rose-800 uppercase flex-shrink-0"><?= htmlspecialchars($q['quiz_title']) ?></span>
                                </div>
                                <div class="mt-2 text-xs grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div class="bg-white/80 p-2 rounded border border-rose-200">
                                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Your Answer</span>
                                        <span class="font-bold text-rose-700"><?= htmlspecialchars($q['student_answer']) ?></span>
                                    </div>
                                    <div class="bg-white/80 p-2 rounded border border-rose-200">
                                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Correct Answer</span>
                                        <span class="font-bold text-emerald-700"><?= htmlspecialchars($q['correct_answer']) ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($q['explanation'])): ?>
                                    <p class="mt-2 text-xs italic text-slate-600 border-t border-rose-200/60 pt-2">
                                        💡 <strong>Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>