<?php
session_start();

require_once __DIR__ . '/../config/db.php';

// -------------------------------------------------------------------------
// 1. Dynamic Student Data Fetching
// -------------------------------------------------------------------------
$student_id = 3;

$stmt_student = $pdo->prepare("
    SELECT s.id, s.name, s.score AS xp, c.name AS classroom_name
    FROM students s
    LEFT JOIN classrooms c ON s.classroom_id = c.id
    WHERE s.id = ?
");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

// Default fallback values
if (!$student) {
    $student = [
        'id' => $student_id,
        'name' => 'Student',
        'xp' => 0,
        'classroom_name' => 'Mathematics'
    ];
}

// XP Level is separate from Chapter Mastery Level
$student['xp_level'] = max(1, (int) floor(((int)$student['xp']) / 100));


// -------------------------------------------------------------------------
// 2. Island / Map Configuration
// -------------------------------------------------------------------------
// These positions match the existing map design.
// The database controls the actual chapter unlock and mastery information.
$island_config = [
    1 => [
        'name' => 'Ancient Pyramid',
        'topic' => 'Arithmetic',
        'x' => 44,
        'y' => 84
    ],
    2 => [
        'name' => 'Cherry Blossom Valley',
        'topic' => 'Multiplication',
        'x' => 30,
        'y' => 48
    ],
    3 => [
        'name' => 'Volcanic Jungle',
        'topic' => 'Fractions',
        'x' => 50,
        'y' => 48
    ],
    4 => [
        'name' => 'Hidden Cove',
        'topic' => 'Geometry',
        'x' => 70,
        'y' => 64
    ],
    5 => [
        'name' => 'Waterfall Cliffs',
        'topic' => 'Measurement',
        'x' => 70,
        'y' => 32
    ],
    6 => [
        'name' => 'Frozen Igloo',
        'topic' => 'Data Handling',
        'x' => 54,
        'y' => 12
    ],
    7 => [
        'name' => 'Desert Treasure',
        'topic' => 'Word Problems',
        'x' => 32,
        'y' => 20
    ],
];


// -------------------------------------------------------------------------
// 3. Get Student Classroom
// -------------------------------------------------------------------------
$stmt_classroom = $pdo->prepare("
    SELECT classroom_id
    FROM students
    WHERE id = ?
");
$stmt_classroom->execute([$student_id]);
$classroom_id = $stmt_classroom->fetchColumn();


// -------------------------------------------------------------------------
// 4. Get Classroom Chapter Unlock Information
// -------------------------------------------------------------------------
// classroom_chapters is the source of truth for chapter unlocking.
//
// The rows are ordered by their database ID and mapped to Chapter 1, 2, 3...
// because the current system does not have a separate chapter number column.
$chapter_unlock = [];

if ($classroom_id) {

    $stmt_chapters = $pdo->prepare("
        SELECT id, chapter_name, is_unlocked
        FROM classroom_chapters
        WHERE classroom_id = ?
        ORDER BY id ASC
    ");

    $stmt_chapters->execute([$classroom_id]);

    $chapter_rows = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);

    foreach ($chapter_rows as $index => $row) {

        $chapter_num = $index + 1;

        $chapter_unlock[$chapter_num] = [
            'chapter_name' => $row['chapter_name'],
            'is_unlocked' => (int)$row['is_unlocked']
        ];
    }
}


// -------------------------------------------------------------------------
// 5. Get Student Chapter Mastery Level
// -------------------------------------------------------------------------
// student_progress.level:
//
// 0 = Not Assessed
// 1 = Beginner
// 2 = Intermediate
// 3 = Master
//
// IMPORTANT:
// This level is determined by the Chapter Test,
// NOT by the number of completed subtopic quizzes.
$chapter_levels = [];

$stmt_level = $pdo->prepare("
    SELECT island_id, level, status
    FROM student_progress
    WHERE student_id = ?
");

$stmt_level->execute([$student_id]);

$level_rows = $stmt_level->fetchAll(PDO::FETCH_ASSOC);

foreach ($level_rows as $row) {

    $island_id = (int)$row['island_id'];

    $chapter_levels[$island_id] = [
        'level' => (int)$row['level'],
        'status' => $row['status']
    ];
}


// -------------------------------------------------------------------------
// 6. Build Island Data
// -------------------------------------------------------------------------
$islands = [];

foreach ($island_config as $id => $config) {

    // -------------------------------------------------------------
    // Chapter unlock
    // -------------------------------------------------------------
    $isUnlocked =
        isset($chapter_unlock[$id]) &&
        $chapter_unlock[$id]['is_unlocked'] === 1;

    $chapter_name = $chapter_unlock[$id]['chapter_name'] ?? null;


    // -------------------------------------------------------------
    // Total number of subtopics
    // -------------------------------------------------------------
    $total_subtopics = 0;

    if ($chapter_name) {

        $stmt_total = $pdo->prepare("
            SELECT COUNT(DISTINCT subtopic_name)
            FROM chapter_materials
            WHERE chapter_name = ?
              AND subtopic_name IS NOT NULL
              AND TRIM(subtopic_name) != ''
        ");

        $stmt_total->execute([$chapter_name]);

        $total_subtopics = (int)$stmt_total->fetchColumn();
    }


    // -------------------------------------------------------------
    // Completed subtopic quizzes
    // -------------------------------------------------------------
    //
    // A subtopic is considered completed when its Quiz assessment
    // exists in student_assessments.
    //
    // Example:
    // Subtopic 1 Assessment
    // Subtopic 2 Assessment
    //
    // These count as:
    // 2 completed subtopics
    //
    // Chapter Test is NOT included here.
    // -------------------------------------------------------------
$completed_subtopics = 0;
$completed_subtopic_numbers = [];

if ($chapter_name) {

    $stmt_completed = $pdo->prepare("
        SELECT title
        FROM student_assessments
        WHERE student_id = ?
          AND island_id = ?
          AND type = 'Quiz'
    ");

    $stmt_completed->execute([
        $student_id,
        $id
    ]);

    $completed_assessments = $stmt_completed->fetchAll(PDO::FETCH_ASSOC);

    foreach ($completed_assessments as $assessment) {

        $title = trim($assessment['title']);

        if (
preg_match(
    '/Subtopic\s+([\d.]+)\s+Assessment/i',
    $title,
    $match
)

        ) {
            $subtopic_number = $match[1];

            $completed_subtopic_numbers[$subtopic_number] = true;
        }
    }

    $completed_subtopics = count($completed_subtopic_numbers);
}

    // -------------------------------------------------------------
    // Calculate Subtopic Progress
    // -------------------------------------------------------------
    if ($total_subtopics > 0) {

        $progress_percentage = round(
            ($completed_subtopics / $total_subtopics) * 100
        );

        // Make sure it never exceeds 100
        $progress_percentage = min(100, $progress_percentage);

    } else {

        $progress_percentage = 0;
    }


    // -------------------------------------------------------------
    // Get Chapter Mastery Level
    // -------------------------------------------------------------
    $level = $chapter_levels[$id]['level'] ?? 0;


    if ($level === 1) {

        $level_name = 'Beginner';
        $level_description = 'Needs more foundational practice';
        $level_color = 'red';

    } elseif ($level === 2) {

        $level_name = 'Intermediate';
        $level_description = 'Developing understanding';
        $level_color = 'orange';

    } elseif ($level === 3) {

        $level_name = 'Master';
        $level_description = 'Strong understanding';
        $level_color = 'green';

    } else {

        $level_name = 'Not Assessed';
        $level_description = 'Chapter Test not completed';
        $level_color = 'gray';
    }


    // -------------------------------------------------------------
    // Build Final Island Object
    // -------------------------------------------------------------
    $islands[$id] = [
        'name' => $config['name'],
        'topic' => $config['topic'],
        'x' => $config['x'],
        'y' => $config['y'],

        // Unlock information
        'unlocked' => $isUnlocked,
        'chapter_name' => $chapter_name,

        // Subtopic progress
        'progress' => $progress_percentage,
        'completed_subtopics' => $completed_subtopics,
        'total_subtopics' => $total_subtopics,

        // Chapter mastery
        'level' => $level,
        'level_name' => $level_name,
        'level_description' => $level_description,
        'level_color' => $level_color
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

    <link
        href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css"
        rel="stylesheet"
    />

</head>


<body
    class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-center p-4 pt-28"
>


    <!-- ============================================================= -->
    <!-- NAVBAR -->
    <!-- ============================================================= -->

    <nav
        class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center"
    >

        <div
            class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between"
        >

            <!-- LOGO -->

            <a
                href="index.php"
                class="flex items-center gap-3 flex-shrink-0"
            >

                <div
                    class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm"
                >

                    <span class="text-2xl">📖</span>

                </div>

                <span
                    class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block"
                >
                    EduHunt
                </span>

            </a>


            <!-- NAVIGATION -->

            <div
                class="hidden md:flex items-center justify-center flex-1 mx-6"
            >

                <ul
                    class="flex items-center gap-3 text-lg font-bold"
                >

                    <li>

                        <a
                            href="index.php"
                            class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5"
                        >
                            <span>Home</span>
                        </a>

                    </li>


                    <li>

                        <a
                            href="/Quality-Education/src/student/discussion.php"
                            class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"
                        >
                            <span>Discussion</span>
                        </a>

                    </li>


                    <li>

                        <a
                            href="/Quality-Education/src/student/module.php"
                            class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"
                        >
                            <span>Modules</span>
                        </a>

                    </li>


                    <li>

                        <a
                            href="/Quality-Education/src/student/quiz.php"
                            class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"
                        >
                            <span>Quizzes</span>
                        </a>

                    </li>


                    <li>

                        <a
                            href="/Quality-Education/src/student/history.php"
                            class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"
                        >
                            <span>History</span>
                        </a>

                    </li>

                </ul>

            </div>


            <!-- PROFILE -->

            <div class="flex items-center flex-shrink-0">

                <div
                    class="flex items-center gap-3 py-2.5 px-4 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm"
                >

                    <div
                        class="w-10 h-10 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-lg"
                    >
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>


                    <span
                        class="text-lg font-bold text-pastel-text hidden sm:block"
                    >
                        <?= htmlspecialchars($student['name']) ?>
                    </span>

                </div>


                <!-- MOBILE MENU BUTTON -->

                <button
                    data-collapse-toggle="navbar-user"
                    type="button"
                    class="inline-flex items-center p-2.5 ml-3 w-12 h-12 justify-center text-pastel-primary rounded-2xl bg-pastel-card border-2 border-pastel-primary/20 md:hidden hover:bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary/30"
                    aria-controls="navbar-user"
                    aria-expanded="false"
                >

                    <span class="sr-only">
                        Open main menu
                    </span>

                    <svg
                        class="w-7 h-7"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M5 7h14M5 12h14M5 17h14"
                        />

                    </svg>

                </button>

            </div>

        </div>


        <!-- MOBILE MENU -->

        <div
            class="hidden w-full md:hidden px-5 pb-4 absolute top-20 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg"
            id="navbar-user"
        >

            <ul
                class="flex flex-col gap-2 text-base font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm"
            >

                <li>

                    <a
                        href="index.php"
                        class="block py-3 px-4 text-white bg-pastel-primary rounded-xl shadow-sm"
                    >
                        Home
                    </a>

                </li>


                <li>

                    <a
                        href="discussion.php"
                        class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors"
                    >
                        Discussion
                    </a>

                </li>


                <li>

                    <a
                        href="module.php"
                        class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors"
                    >
                        Modules
                    </a>

                </li>


                <li>

                    <a
                        href="quiz.php"
                        class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors"
                    >
                        Quizzes
                    </a>

                </li>


                <li>

                    <a
                        href="history.php"
                        class="block py-3 px-4 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors"
                    >
                        History
                    </a>

                </li>

            </ul>

        </div>

    </nav>


    <!-- ============================================================= -->
    <!-- HEADER / BANNER -->
    <!-- ============================================================= -->

    <div
        class="w-full max-w-[85rem] flex justify-between items-center mb-4 bg-pastel-card border border-pastel-nav px-6 py-3.5 rounded-2xl shadow-md"
    >

        <div class="flex items-center space-x-3">

            <div
                class="w-10 h-10 rounded-xl bg-pastel-badge border border-pastel-nav flex items-center justify-center font-black text-pastel-text text-base"
            >
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>


            <div>

                <h1
                    class="text-base font-black text-pastel-text flex items-center gap-1.5"
                >
                    Ahoy,
                    <?= htmlspecialchars($student['name']) ?>!
                    <span class="text-2xl leading-none">🏴‍☠️</span>
                </h1>


                <p class="text-xs font-semibold text-pastel-primary">
                    Ready to conquer your Year 4 Math Islands?
                </p>

            </div>

        </div>


        <div
            class="flex items-center space-x-3 text-sm font-bold"
        >

            <!-- XP LEVEL -->

            <div
                class="bg-pastel-bg text-pastel-text px-3 py-1.5 rounded-xl border border-pastel-nav flex items-center gap-1.5"
            >
                ⭐ XP Level <?= $student['xp_level'] ?>
            </div>


            <!-- XP -->

            <div
                class="bg-pastel-bg text-pastel-text px-3 py-1.5 rounded-xl border border-pastel-nav flex items-center gap-1.5"
            >
                🪙 <?= (int)$student['xp'] ?> XP
            </div>

        </div>

    </div>


    <!-- ============================================================= -->
    <!-- MAP CONTAINER -->
    <!-- ============================================================= -->

    <div
        class="relative w-full max-w-[85rem] aspect-[16/8.5] rounded-2xl overflow-hidden border-4 border-pastel-card shadow-xl bg-pastel-bg"
    >

        <img
            src="/Quality-Education/src/student/map.jpeg"
            alt="EduHunt Map"
            class="absolute inset-0 w-full h-full object-cover select-none z-0"
        >


        <?php foreach ($islands as $id => $island): ?>

            <?php

            $isUnlocked = $island['unlocked'];

            $progress = $island['progress'];

            $levelName = $island['level_name'];

            $levelDescription = $island['level_description'];

            $levelColor = $island['level_color'];


            // ---------------------------------------------------------
            // Pin style based on mastery level
            // ---------------------------------------------------------

            if (!$isUnlocked) {

                $pinColor =
                    "bg-slate-200 border-white text-slate-400 cursor-not-allowed shadow-md";

            } elseif ($levelColor === 'red') {

                $pinColor =
                    "bg-rose-200 border-rose-400 text-rose-700 hover:bg-rose-200 cursor-pointer hover:scale-110 shadow-rose-200/50 shadow-lg";

            } elseif ($levelColor === 'orange') {

                $pinColor =
                    "bg-orange-200 border-orange-400 text-orange-700 hover:bg-orange-200 cursor-pointer hover:scale-110 shadow-orange-200/50 shadow-lg";

            } elseif ($levelColor === 'green') {

                $pinColor =
                    "bg-emerald-500 border-white text-white hover:bg-emerald-400 cursor-pointer hover:scale-110 shadow-emerald-500/50 shadow-lg";

            } else {

                // Not Assessed
                $pinColor =
                    "bg-slate-200 border-slate-300 text-slate-600 hover:bg-slate-200 cursor-pointer hover:scale-110 shadow-slate-200/50 shadow-lg";
            }

            ?>


            <!-- ===================================================== -->
            <!-- ISLAND PIN -->
            <!-- ===================================================== -->

            <div
                class="absolute transform -translate-x-1/2 -translate-y-1/2 z-30"
                style="
                    left: <?= $island['x'] ?>%;
                    top: <?= $island['y'] ?>%;
                "
            >


                <?php if ($isUnlocked): ?>


                    <!-- CLICKABLE CHAPTER -->

                    <a
                        href="module.php?chap=<?= $id ?>"
                        class="flex flex-col items-center group"
                    >


                        <!-- ------------------------------------------------ -->
                        <!-- Circular Progress -->
                        <!-- ------------------------------------------------ -->

                        <div
                            class="w-16 h-16 rounded-full p-[4px] shadow-lg transition-all duration-200 group-hover:scale-110"
style="
    background: conic-gradient(
        <?= $levelColor === 'red'
            ? '#f43f5e'
            : ($levelColor === 'orange'
                ? '#f97316'
                : ($levelColor === 'green'
                    ? '#10b981'
                    : '#94a3b8'))
        ?>
        <?= $progress ?>%,
        #e2e8f0 <?= $progress ?>%
    );
"
                        >

                            <div
class="w-full h-full rounded-full flex flex-col items-center justify-center border-2 border-white
    <?= $levelColor === 'red'
        ? 'bg-rose-200'
        : ($levelColor === 'orange'
            ? 'bg-orange-200'
            : ($levelColor === 'green'
                ? 'bg-emerald-500'
                : 'bg-slate-200'))
    ?>"
                                >

                                <span
                                    class="text-lg font-black text-pastel-text leading-none"
                                >
                                    <?= $id ?>
                                </span>

                                <span
                                    class="text-[9px] font-bold text-slate-500 leading-none mt-1"
                                >
                                    <?= $progress ?>%
                                </span>

                            </div>

                        </div>


                        <!-- ------------------------------------------------ -->
                        <!-- Hover Information -->
                        <!-- ------------------------------------------------ -->

                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/95 text-white text-xs font-bold px-4 py-3 rounded-xl whitespace-nowrap mt-2 border border-slate-700 shadow-xl z-40 pointer-events-none"
                        >

                            <div class="text-sm">
                                Chapter <?= $id ?>:
                                <?= htmlspecialchars($island['name']) ?>
                            </div>


                            <div class="mt-1 text-slate-300">
                                <?= htmlspecialchars($island['topic']) ?>
                            </div>


                            <div class="mt-2">

                                Progress:
                                <span class="text-white">
                                    <?= $island['completed_subtopics'] ?>
                                    /
                                    <?= $island['total_subtopics'] ?>
                                    Subtopics
                                </span>

                                <span class="text-slate-400">
                                    (<?= $progress ?>%)
                                </span>

                            </div>


                            <div class="mt-1">

                                Level:

                                <?php if ($levelColor === 'red'): ?>

                                    <span class="text-rose-400">
                                        🔴 Beginner
                                    </span>

                                <?php elseif ($levelColor === 'orange'): ?>

                                    <span class="text-orange-400">
                                        🟠 Intermediate
                                    </span>

                                <?php elseif ($levelColor === 'green'): ?>

                                    <span class="text-emerald-400">
                                        🟢 Master
                                    </span>

                                <?php else: ?>

                                    <span class="text-slate-300">
                                        ⚪ Not Assessed
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div
                                class="mt-1 text-slate-400 text-[11px]"
                            >
                                <?= htmlspecialchars($levelDescription) ?>
                            </div>

                        </div>

                    </a>


                <?php else: ?>


                    <!-- ================================================= -->
                    <!-- LOCKED CHAPTER -->
                    <!-- ================================================= -->

                    <div
                        class="flex flex-col items-center group cursor-not-allowed"
                    >

                        <div
                            class="w-14 h-14 rounded-2xl border-2 flex items-center justify-center font-black text-lg bg-slate-200 border-white text-slate-400 shadow-md"
                        >

                            🔒

                        </div>


                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/95 text-white text-xs font-bold px-3 py-2 rounded-xl whitespace-nowrap mt-2 border border-slate-700 shadow-xl pointer-events-none"
                        >

                            Chapter <?= $id ?>: Locked

                        </div>

                    </div>


                <?php endif; ?>


            </div>

        <?php endforeach; ?>


    </div>


    <!-- ============================================================= -->
    <!-- LEGEND -->
    <!-- ============================================================= -->

    <div
        class="mt-4 w-full max-w-[85rem] bg-pastel-card border border-pastel-nav px-6 py-4 rounded-xl shadow-sm"
    >


        <div
            class="flex flex-col lg:flex-row justify-between items-center gap-4"
        >


            <!-- TITLE -->

            <div class="text-pastel-text font-bold text-sm">
                🗺️ Island Adventure Legend
            </div>


            <!-- LEVEL LEGEND -->

            <div
                class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs font-semibold"
            >

                <!-- Beginner -->

                <div class="flex items-center gap-1.5">

                    <span
                        class="w-3 h-3 rounded-full bg-rose-400"
                    ></span>

                    <span>
                        Beginner
                    </span>

                </div>


                <!-- Intermediate -->

                <div class="flex items-center gap-1.5">

                    <span
                        class="w-3 h-3 rounded-full bg-orange-400"
                    ></span>

                    <span>
                        Intermediate
                    </span>

                </div>


                <!-- Master -->

                <div class="flex items-center gap-1.5">

                    <span
                        class="w-3 h-3 rounded-full bg-emerald-500"
                    ></span>

                    <span>
                        Master
                    </span>

                </div>


                <!-- Not Assessed -->

                <div class="flex items-center gap-1.5">

                    <span
                        class="w-3 h-3 rounded-full bg-slate-300 border border-slate-400"
                    ></span>

                    <span>
                        Not Assessed
                    </span>

                </div>


                <!-- Locked -->

                <div class="flex items-center gap-1.5">

                    <span
                        class="w-3 h-3 rounded-full bg-slate-500"
                    ></span>

                    <span>
                        Locked
                    </span>

                </div>

            </div>

        </div>


        <!-- EXPLANATION -->

        <div
            class="mt-3 pt-3 border-t border-pastel-nav text-center text-[11px] text-slate-500"
        >

            <span class="font-semibold">
                Progress
            </span>
            shows completed subtopic quizzes.

            <span class="mx-1">•</span>

            <span class="font-semibold">
                Level
            </span>
            is determined by the Chapter Test.

        </div>

    </div>


    <!-- FLOWBITE -->

    <script
        src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"
    ></script>

</body>

</html>