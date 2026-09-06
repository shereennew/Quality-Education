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
// The map has 7 visual pin positions. These positions are REUSED for every
// group of 7 teacher-created chapters, so the teacher can add unlimited
// chapters without changing this file.
$map_positions = [
    1 => ['x' => 44, 'y' => 84],
    2 => ['x' => 30, 'y' => 48],
    3 => ['x' => 50, 'y' => 48],
    4 => ['x' => 70, 'y' => 64],
    5 => ['x' => 70, 'y' => 32],
    6 => ['x' => 54, 'y' => 12],
    7 => ['x' => 32, 'y' => 20],
];

$chapters_per_world = 7;

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
// 4. Get ALL Classroom Chapters + Current Map World
// -------------------------------------------------------------------------
// classroom_chapters is the source of truth. Chapters are ordered by DB ID
// and numbered Chapter 1, Chapter 2, Chapter 3, ...
$chapter_rows = [];

if ($classroom_id) {
    // IMPORTANT: use the exact same chapter ordering as teacher/classroom.php.
    // This prevents "teacher locks Chapter 4, student Chapter 3 locks".
    $stmt_chapters = $pdo->prepare("
        SELECT
            ordered.chapter_name,
            ordered.chapter_number,
            COALESCE(cc.is_unlocked, CASE WHEN ordered.chapter_number = 1 THEN 1 ELSE 0 END) AS is_unlocked
        FROM (
            SELECT DISTINCT chapter_name, chapter_number
            FROM chapter_materials

            UNION

            SELECT DISTINCT chapter_name, chapter_number
            FROM chapter_quizzes
        ) AS ordered
        LEFT JOIN classroom_chapters cc
            ON cc.classroom_id = ?
           AND cc.chapter_name = ordered.chapter_name
        ORDER BY ordered.chapter_number ASC
    ");

    $stmt_chapters->execute([$classroom_id]);
    $chapter_rows = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);
}

$total_chapters = count($chapter_rows);
$total_worlds = max(1, (int) ceil($total_chapters / $chapters_per_world));

$current_world = isset($_GET['world']) ? (int) $_GET['world'] : 1;
$current_world = max(1, min($current_world, $total_worlds));

$world_offset = ($current_world - 1) * $chapters_per_world;
$current_world_chapters = array_slice($chapter_rows, $world_offset, $chapters_per_world);

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
// 6. Build Island Data For Current World
// -------------------------------------------------------------------------
$islands = [];

foreach ($current_world_chapters as $slot_index => $chapter_row) {

    // Global chapter number across ALL worlds.
    $id = $world_offset + $slot_index + 1;

    // Visual map slot is always 1-7.
    $slot = $slot_index + 1;
    $position = $map_positions[$slot];

    $chapter_name = $chapter_row['chapter_name'];
    $isUnlocked = ((int)$chapter_row['is_unlocked'] === 1);

    // -------------------------------------------------------------
    // Total number of subtopics
    // -------------------------------------------------------------
    $stmt_total = $pdo->prepare("
        SELECT COUNT(DISTINCT subtopic_name)
        FROM chapter_materials
        WHERE chapter_name = ?
          AND subtopic_name IS NOT NULL
          AND TRIM(subtopic_name) != ''
    ");
    $stmt_total->execute([$chapter_name]);
    $total_subtopics = (int)$stmt_total->fetchColumn();

    // -------------------------------------------------------------
    // Completed subtopic quizzes
    // -------------------------------------------------------------
    $completed_subtopic_numbers = [];

    $stmt_completed = $pdo->prepare("
        SELECT title
        FROM student_assessments
        WHERE student_id = ?
          AND island_id = ?
          AND type = 'Quiz'
    ");
    $stmt_completed->execute([$student_id, $id]);
    $completed_assessments = $stmt_completed->fetchAll(PDO::FETCH_ASSOC);

    foreach ($completed_assessments as $assessment) {
        $title = trim($assessment['title']);

        if (preg_match('/Subtopic\s+([\d.]+)\s+Assessment/i', $title, $match)) {
            $completed_subtopic_numbers[$match[1]] = true;
        }
    }

    $completed_subtopics = count($completed_subtopic_numbers);

    if ($total_subtopics > 0) {
        $progress_percentage = round(($completed_subtopics / $total_subtopics) * 100);
        $progress_percentage = min(100, $progress_percentage);
    } else {
        $progress_percentage = 0;
    }

    // -------------------------------------------------------------
    // Chapter mastery level
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

    // Use the teacher-created chapter name instead of a hardcoded topic.
    // If the title contains ":", show the part after it as the topic label.
    $topic_label = $chapter_name;
    if (strpos($chapter_name, ':') !== false) {
        $parts = explode(':', $chapter_name, 2);
        $topic_label = trim($parts[1]);
    }

    $islands[$id] = [
        'name' => $chapter_name,
        'topic' => $topic_label,
        'x' => $position['x'],
        'y' => $position['y'],
        'unlocked' => $isUnlocked,
        'chapter_name' => $chapter_name,
        'progress' => $progress_percentage,
        'completed_subtopics' => $completed_subtopics,
        'total_subtopics' => $total_subtopics,
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

        <style>
         .main-navbar {
    height: 96px;
    background: #e1e9f5;
    border-bottom: 1px solid rgba(125, 160, 202, .25);
    box-shadow: 0 3px 15px rgba(75, 100, 130, .10);
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    width: 100%;
    align-self: stretch;
}

.nav-inner {
    width: 100%;
    max-width: 1500px;
    margin: auto;
    padding: 0 45px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
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
    border: 1px solid rgba(125, 160, 202, .35);
    background: white;
    border-radius: 999px;
    padding: 7px 16px 7px 8px;
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
    .nav-inner {
        grid-template-columns: auto 1fr;
    }

    .nav-links {
        display: none;
    }

    .profile-area {
        justify-self: end;
    }
}

@media (max-width: 650px) {
    .brand-name {
        font-size: 23px;
    }

    .profile-name {
        display: none;
    }
}
</style>

</head>


<body
    class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-start"
>
<!-- ============================================================= -->
<!-- NAVBAR -->
<!-- ============================================================= -->
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

            <a href="index.php" class="active">
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

            <a href="history.php">
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
                    <?= htmlspecialchars($student['name']) ?>
                </span>

            </div>
        </div>

    </div>

</nav>


    <!-- ============================================================= -->
    <!-- HEADER / BANNER -->
    <!-- ============================================================= -->

<div
    class="w-full max-w-[85rem] flex justify-between items-center mt-10 mb-4 bg-pastel-card border border-pastel-nav px-6 py-3.5 rounded-2xl shadow-md"
>

        <div class="flex items-center space-x-3">

            <div
                class="w-10 h-10 rounded-xl bg-pastel-badge border border-pastel-nav flex items-center justify-center font-black text-pastel-text text-base"
            >
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>


            <div>

<h1
    class="text-xl font-black text-pastel-text flex items-center gap-1.5"
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

        <?php if (empty($islands)): ?>
            <div class="absolute inset-0 z-20 flex items-center justify-center">
                <div class="bg-white/95 border border-pastel-nav rounded-2xl px-8 py-6 shadow-xl text-center">
                    <h2 class="text-xl font-black text-pastel-text">No chapters yet</h2>
                    <p class="text-sm text-slate-500 mt-2">Your teacher has not added any chapters to this classroom yet.</p>
                </div>
            </div>
        <?php endif; ?>


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

    <!-- WORLD / CHAPTER PAGE NAVIGATION -->
    <?php if ($total_worlds > 1): ?>
        <div class="mt-4 w-full max-w-[85rem] flex items-center justify-between gap-4">
            <div>
                <?php if ($current_world > 1): ?>
                    <a href="index.php?world=<?= $current_world - 1 ?>"
                       class="inline-flex items-center px-5 py-3 rounded-xl bg-white border border-pastel-nav text-pastel-text font-black shadow-sm hover:bg-pastel-badge transition">
                        ← Previous Map
                    </a>
                <?php endif; ?>
            </div>

            <div class="bg-white border border-pastel-nav rounded-xl px-5 py-3 shadow-sm text-center">
                <div class="text-xs font-bold text-pastel-primary uppercase tracking-wider">
                    Map <?= $current_world ?> of <?= $total_worlds ?>
                </div>
                <div class="text-sm font-black text-pastel-text mt-1">
                    Chapters <?= $world_offset + 1 ?>–<?= min($world_offset + $chapters_per_world, $total_chapters) ?>
                </div>
            </div>

            <div>
                <?php if ($current_world < $total_worlds): ?>
                    <a href="index.php?world=<?= $current_world + 1 ?>"
                       class="inline-flex items-center px-5 py-3 rounded-xl bg-pastel-primary text-white font-black shadow-sm hover:bg-pastel-hover transition">
                        Next Map →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>


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