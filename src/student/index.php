<?php
require_once __DIR__ . '/../config/db.php';

// -------------------------------------------------------------------------
// 1. Dynamic Student Data Fetching
// -------------------------------------------------------------------------
$student_id = $_SESSION['student_id'] ?? 1; 

$stmt_student = $pdo->prepare("
    SELECT s.name, s.score AS xp, c.name AS classroom_name
    FROM students s
    LEFT JOIN classrooms c ON s.classroom_id = c.id
    WHERE s.id = ?
");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

// Default fallback values if student record does not exist
if (!$student) {
    $student = [
        'name' => 'Amina Yusuf',
        'xp' => 0,
        'level' => 1
    ];
} else {
    // Calculate level based on XP/Score dynamically (1 Level per 100 XP, minimum Level 1)
    $student['level'] = max(1, (int) floor($student['xp'] / 100));
}

// -------------------------------------------------------------------------
// 2. Dynamic Island & Progress Data Fetching
// -------------------------------------------------------------------------
$island_config = [
    1 => ['name' => 'Ancient Pyramid', 'topic' => 'Arithmetic', 'x' => 44, 'y' => 84],
    2 => ['name' => 'Cherry Blossom Valley', 'topic' => 'Multiplication', 'x' => 30, 'y' => 48],
    3 => ['name' => 'Volcanic Jungle', 'topic' => 'Fractions', 'x' => 50, 'y' => 48],
    4 => ['name' => 'Hidden Cove', 'topic' => 'Geometry', 'x' => 70, 'y' => 64],
    5 => ['name' => 'Waterfall Cliffs', 'topic' => 'Measurement', 'x' => 70, 'y' => 32],
    6 => ['name' => 'Frozen Igloo', 'topic' => 'Data Handling', 'x' => 54, 'y' => 12],
    7 => ['name' => 'Desert Treasure', 'topic' => 'Word Problems', 'x' => 32, 'y' => 20],
];

// Fetch active student progress records from DB
$stmt_progress = $pdo->prepare("
    SELECT island_id, status 
    FROM student_progress 
    WHERE student_id = ?
");
$stmt_progress->execute([$student_id]);
$progress_records = $stmt_progress->fetchAll(PDO::FETCH_KEY_PAIR); // [island_id => status]

// Build dynamic $islands array
$islands = [];
foreach ($island_config as $id => $config) {
    $db_status = $progress_records[$id] ?? 'locked';
    
    $ui_status = match (strtolower($db_status)) {
        'completed', 'mastered' => 'mastered',
        'intermediate'          => 'intermediate',
        'pending'               => 'pending',
        'in progress', 'beginner' => 'beginner',
        default                 => 'locked',
    };

    $islands[$id] = [
        'name'   => $config['name'],
        'topic'  => $config['topic'],
        'x'      => $config['x'],
        'y'      => $config['y'],
        'status' => $ui_status
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Island Math Adventure!</title>
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

<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-center p-4 pt-28">

    <!-- NAVBAR -->
    <nav class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between">

            <!-- LOGO -->
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-2xl">📖</span>
                </div>
                <span class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block">
                    EduHunt
                </span>
            </a>

            <!-- NAVIGATION -->
            <div class="hidden md:flex items-center justify-center flex-1 mx-6">
                <ul class="flex items-center gap-3 text-lg font-bold">
                    <li>
                        <a href="index.php" class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5">
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="discussion.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Discussion</span>
                        </a>
                    </li>
                    <li>
                        <a href="module.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Modules</span>
                        </a>
                    </li>
                    <li>
                        <a href="quiz.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Quizzes</span>
                        </a>
                    </li>
                    <li>
                        <a href="history.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>History</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- PROFILE -->
            <div class="flex items-center flex-shrink-0">
                <button id="user-menu-button" data-dropdown-toggle="user-dropdown" type="button" class="flex items-center gap-3 py-2.5 px-4 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200">
                    <div class="w-10 h-10 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-lg">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-lg font-bold text-pastel-text hidden sm:block">
                        <?= htmlspecialchars($student['name']) ?>
                    </span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>

                <!-- PROFILE DROPDOWN -->
                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-56 mt-2" id="user-dropdown">
                    <div class="px-5 py-3.5 border-b-2 border-pastel-nav">
                        <span class="block text-base font-bold text-pastel-text">
                            <?= htmlspecialchars($student['name']) ?>
                        </span>
                        <span class="block text-xs text-pastel-primary font-semibold mt-0.5">
                            Level <?= $student['level'] ?>
                        </span>
                    </div>
                    <ul class="p-2 text-base text-pastel-text font-medium" aria-labelledby="user-menu-button">
                        <li><a href="#" class="block w-full px-4 py-2.5 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Profile</a></li>
                        <li><a href="#" class="block w-full px-4 py-2.5 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Settings</a></li>
                        <li><a href="#" class="block w-full px-4 py-2.5 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Sign out</a></li>
                    </ul>
                </div>

                <!-- MOBILE MENU BUTTON -->
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2.5 ml-3 w-12 h-12 justify-center text-pastel-primary rounded-2xl bg-pastel-card border-2 border-pastel-primary/20 md:hidden hover:bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary/30" aria-controls="navbar-user" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 7h14M5 12h14M5 17h14" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- MOBILE MENU -->
        <div class="hidden w-full md:hidden px-5 pb-4 absolute top-20 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg" id="navbar-user">
            <ul class="flex flex-col gap-2 text-base font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm">
                <li><a href="index.php" class="block py-3 px-4 text-white bg-pastel-primary rounded-xl shadow-sm">Home</a></li>
                <li><a href="discussion.php" class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Discussion</a></li>
                <li><a href="module.php" class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Modules</a></li>
                <li><a href="quiz.php" class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Quizzes</a></li>
                <li><a href="history.php" class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">History</a></li>
            </ul>
        </div>
    </nav>

    <!-- HEADER / BANNER -->
    <div class="w-full max-w-[85rem] flex justify-between items-center mb-4 bg-pastel-card border border-pastel-nav px-6 py-3.5 rounded-2xl shadow-md">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-pastel-badge border border-pastel-nav flex items-center justify-center font-black text-pastel-text text-base">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-base font-black text-pastel-text flex items-center gap-1.5">
                    Ahoy, <?= htmlspecialchars($student['name']) ?>! <span class="text-2xl leading-none">🏴‍☠️</span>
                </h1>
                <p class="text-xs font-semibold text-pastel-primary">Ready to conquer your Year 4 Math Islands?</p>
            </div>
        </div>
        <div class="flex items-center space-x-3 text-sm font-bold">
            <div class="bg-pastel-bg text-pastel-text px-3 py-1.5 rounded-xl border border-pastel-nav flex items-center gap-1.5">
                ⭐ Level <?= $student['level'] ?>
            </div>
            <div class="bg-pastel-bg text-pastel-text px-3 py-1.5 rounded-xl border border-pastel-nav flex items-center gap-1.5">
                🪙 <?= $student['xp'] ?> XP
            </div>
        </div>
    </div>

    <!-- MAP CONTAINER -->
    <div class="relative w-full max-w-[85rem] aspect-[16/8.5] rounded-2xl overflow-hidden border-4 border-pastel-card shadow-xl bg-pastel-bg">

        <img src="map.jpeg" alt="EduHunt Map" class="absolute inset-0 w-full h-full object-cover select-none z-0">

        <?php foreach ($islands as $id => $island):
            $status = $island['status'];

            $pinColor = "bg-slate-200 border-white text-slate-400 cursor-not-allowed shadow-md";
            $pulse = "";
            $statusLabel = "Locked 🔒";

            if ($status === 'beginner') {
                $pinColor = "bg-rose-100 border-rose-300 text-rose-700 hover:bg-rose-200 cursor-pointer hover:scale-110 shadow-rose-200/50 shadow-lg";
                $pulse = "animate-bounce";
                $statusLabel = "Beginner 🌱";
            } elseif ($status === 'intermediate') {
                $pinColor = "bg-sky-100 border-sky-300 text-sky-700 hover:bg-sky-200 cursor-pointer hover:scale-110 shadow-sky-200/50 shadow-lg";
                $pulse = "animate-pulse";
                $statusLabel = "Intermediate 🌿";
            } elseif ($status === 'pending') {
                $pinColor = "bg-amber-100 border-amber-300 text-amber-700 hover:bg-amber-200 cursor-pointer hover:scale-110 shadow-amber-200/50 shadow-lg";
                $pulse = "animate-bounce";
                $statusLabel = "Waiting for Teacher! ⏳";
            } elseif ($status === 'mastered') {
                $pinColor = "bg-emerald-500 border-white text-white hover:bg-emerald-400 cursor-pointer hover:scale-110 shadow-emerald-500/50 shadow-lg";
                $statusLabel = "Mastered! ✨";
            }
        ?>
            <div class="absolute transform -translate-x-1/2 -translate-y-1/2 z-30 <?= $pulse ?>" style="left: <?= $island['x'] ?>%; top: <?= $island['y'] ?>%;">

                <?php if ($status !== 'locked'): ?>
                    <a href="history.php?island_id=<?= $id ?>" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-2xl border-2 flex items-center justify-center font-black text-lg transition-all duration-200 <?= $pinColor ?>">
                            <?php if ($status === 'mastered'): ?>
                                ✓
                            <?php elseif ($status === 'pending'): ?>
                                👩‍🏫
                            <?php else: ?>
                                <?= $id ?>
                            <?php endif; ?>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/90 text-white text-xs font-bold px-3 py-1.5 rounded-xl whitespace-nowrap mt-2 border border-slate-700 shadow-xl z-40 pointer-events-none">
                            Island <?= $id ?>: <?= htmlspecialchars($island['name']) ?> (<span class="text-pastel-primary"><?= htmlspecialchars($island['topic']) ?></span>) — <span class="text-amber-300"><?= $statusLabel ?></span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="flex flex-col items-center group cursor-not-allowed">
                        <div class="w-11 h-11 rounded-2xl border-2 flex items-center justify-center font-bold text-xs <?= $pinColor ?>">
                            🔒
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>

    <!-- LEGEND FOOTER -->
    <div class="mt-4 w-full max-w-[85rem] flex flex-col sm:flex-row justify-between items-center bg-pastel-card border border-pastel-nav px-6 py-3 rounded-xl shadow-sm text-xs font-semibold text-pastel-text gap-3">
        <div class="text-pastel-text font-bold">🗺️ Island Adventure Legend:</div>
        <div class="flex items-center space-x-5">
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-200 border border-rose-300"></span> Beginner</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-sky-200 border border-sky-300"></span> Intermediate</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-200 border border-amber-300"></span> Pending Review</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Mastered</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-slate-200"></span> Locked</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>

</html>