<?php
require_once __DIR__ . '/../config/db.php';

$student_id = 3;

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
    SELECT 
        sa.*,
        a.title AS quiz_title,
        a.type AS quiz_type,
        a.submitted_at
    FROM student_quiz_answers sa
    JOIN student_assessments a 
        ON sa.assessment_id = a.id
    WHERE a.student_id = ?
      AND a.island_id = ?
      AND sa.is_correct = 0
    ORDER BY a.submitted_at DESC, sa.id DESC
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
    <style>


        .main-navbar {
            width: 100%;
            max-width: none;
            align-self: stretch;
            flex-shrink: 0;

            height: 96px;

            background: #e1e9f5;

            border-bottom:
                1px solid rgba(125, 160, 202, .25);

            box-shadow:
                0 3px 15px rgba(75, 100, 130, .10);

            position: sticky;

            top: 0;

            z-index: 1000;

            display: flex;

            align-items: center;
        }


        .nav-inner {

            width: 100%;

            max-width: 1500px;

            margin: auto;

            padding: 0 45px;

            display: grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items: center;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 13px;

            text-decoration: none;

            color: #2c3e50;
        }


        .brand-icon {

            width: 53px;
            height: 53px;

            border-radius: 16px;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

            font-weight: 900;
        }


        .brand-name {

            font-size: 30px;

            font-weight: 900;
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .nav-links a {

            text-decoration: none;

            color: #2c3e50;

            padding: 13px 24px;

            border-radius: 15px;

            font-size: 18px;

            font-weight: 800;

            transition: .2s;
        }


        .nav-links a:hover {

            background: #cbd9eb;
        }


        .nav-links a.active {

            background: #7da0ca;

            color: white;
        }


        .profile-area {

            display: flex;

            justify-content: flex-end;

            position: relative;
        }


        .profile-button {

            border:
                1px solid rgba(125, 160, 202, .35);

            background: white;

            border-radius: 999px;

            padding:
                7px 16px 7px 8px;

            display: flex;

            align-items: center;

            gap: 10px;

            cursor: default;

            color: #2c3e50;

            font-weight: 800;

            font-size: 16px;
        }


        .avatar {

            width: 44px;
            height: 44px;

            border-radius: 50%;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: 900;
        }



        

        @media (max-width: 1000px) {
            .nav-inner { grid-template-columns: auto 1fr; }
            .nav-links { display: none; }
            .profile-area { justify-self: end; }
        }

        @media (max-width: 650px) {
            .brand-name { font-size: 23px; }
            .profile-name { display: none; }
        }

    </style>
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-start">

    <!-- NAVBAR -->
    <nav class="main-navbar">

        <div class="nav-inner">


            <a href="index.php" class="brand">

                <div class="brand-icon">
                    E
                </div>

                <div class="brand-name">
                    EduHunt
                </div>

            </a>


            <div class="nav-links">

                <a href="index.php">
                    Home
                </a>

                <a href="discussion.php">
                    Discussion
                </a>

                <a href="module.php">
                    Modules
                </a>

                <a href="mathhelper.php">
                    Math Helper
                </a>

                <a href="history.php" class="active">
                    History
                </a>

            </div>


            <div class="profile-area">
                <div class="profile-button">
                    <div class="avatar">
                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $student['name'],
                                    0,
                                    1
                                )
                            )
                        ) ?>
                    </div>

                    <span class="profile-name">
                        <?= htmlspecialchars(
                            $student['name']
                        ) ?>
                    </span>
                </div>
            </div>

        </div>

    </nav>

    <!-- MAIN CONTAINER -->
    <main class="w-full max-w-[85rem] grid grid-cols-1 lg:grid-cols-12 gap-6 p-4 mt-4">

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
                    <span>🎯</span> Completed Quizzes & Tests & Lessons
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
                    <span>⚠️</span> Focus Areas
                </h2>
                <p class="text-xs text-slate-400 mb-4">Only incorrect items from your quizzes and tests are highlighted here for quick review.</p>
<?php if (empty($incorrectAnswers)): ?>

    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200
                text-emerald-800 text-sm font-semibold flex items-center gap-2">
        <span>🎉</span>
        Great job! No incorrect questions recorded for this chapter.
    </div>

<?php else: ?>

    <div class="space-y-4">

        <?php foreach ($incorrectAnswers as $q): ?>

            <div class="p-5 rounded-xl bg-rose-50 border border-rose-200">

                <!-- Question + Quiz Name -->
                <div class="flex justify-between items-start gap-3">

                    <div class="flex-1">

                        <span class="text-[10px] font-bold uppercase
                                     tracking-wider text-slate-400">
                            <?= htmlspecialchars($q['quiz_type']) ?>
                        </span>

                        <h4 class="font-bold text-sm text-slate-800 mt-1">
                            <?= htmlspecialchars($q['question_text']) ?>
                        </h4>

                    </div>

                    <span class="text-[10px] font-bold px-2 py-1 rounded
                                 bg-rose-200 text-rose-800 uppercase
                                 flex-shrink-0">
                        Incorrect
                    </span>

                </div>


                <!-- Answers -->
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <!-- Your Answer -->
                    <div class="bg-white p-3 rounded-lg border border-rose-200">

                        <span class="text-[10px] uppercase font-bold
                                     text-rose-500 block mb-1">
                            Your Answer
                        </span>

                        <span class="font-bold text-rose-700 text-sm">
                            <?= htmlspecialchars($q['student_answer']) ?>
                        </span>

                    </div>


                    <!-- Correct Answer -->
                    <div class="bg-white p-3 rounded-lg border border-emerald-200">

                        <span class="text-[10px] uppercase font-bold
                                     text-emerald-600 block mb-1">
                            Correct Answer
                        </span>

                        <span class="font-bold text-emerald-700 text-sm">
                            <?= htmlspecialchars($q['correct_answer']) ?>
                        </span>

                    </div>

                </div>


                <!-- Explanation -->
                <?php if (!empty($q['explanation'])): ?>

                    <div class="mt-4 pt-3 border-t border-rose-200/60">

                        <div class="flex items-start gap-2">

                            <span class="text-base">💡</span>

                            <div>

                                <span class="text-[10px] uppercase
                                             tracking-wider font-bold
                                             text-pastel-primary">
                                    Explanation
                                </span>

                                <p class="text-sm text-slate-600
                                          leading-relaxed mt-1">
                                    <?= htmlspecialchars($q['explanation']) ?>
                                </p>

                            </div>

                        </div>

                    </div>

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