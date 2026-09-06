<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ======================================================
// QUALITY-EDUCATION STUDENT + DYNAMIC EDUHUNT ISLANDS
// ======================================================
$student_id = 1;

// Add Eduhunt-style island metadata to the existing Quality-Education table.
// Existing databases are upgraded automatically and old chapters keep working.
$chapterColumns = $pdo->query("PRAGMA table_info(classroom_chapters)")->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($chapterColumns, 'name');
if (!in_array('chapter_order', $columnNames, true)) {
    $pdo->exec("ALTER TABLE classroom_chapters ADD COLUMN chapter_order INTEGER DEFAULT 1");
}
if (!in_array('island_theme', $columnNames, true)) {
    $pdo->exec("ALTER TABLE classroom_chapters ADD COLUMN island_theme TEXT DEFAULT 'forest'");
}
if (!in_array('is_published', $columnNames, true)) {
    $pdo->exec("ALTER TABLE classroom_chapters ADD COLUMN is_published INTEGER DEFAULT 1");
}

// Fill sensible chapter orders for older rows that all received the default value 1.
$stmtExisting = $pdo->query("SELECT id, classroom_id FROM classroom_chapters ORDER BY classroom_id, id");
$orderByClass = [];
foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $cid = (int)$row['classroom_id'];
    $orderByClass[$cid] = ($orderByClass[$cid] ?? 0) + 1;
    $pdo->prepare("UPDATE classroom_chapters SET chapter_order = ? WHERE id = ? AND (chapter_order IS NULL OR chapter_order = 1)")
        ->execute([$orderByClass[$cid], (int)$row['id']]);
}

$stmt = $pdo->prepare("SELECT id, name, classroom_id, COALESCE(score,0) AS xp FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$studentRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$studentRow) {
    die('Student not found.');
}

$student = [
    'id' => (int)$studentRow['id'],
    'full_name' => $studentRow['name'],
    'xp' => (int)$studentRow['xp'],
    'level' => max(1, (int)floor(((int)$studentRow['xp']) / 100) + 1),
    'streak' => 0,
];

$classroom_id = (int)($studentRow['classroom_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id, name FROM classrooms WHERE id = ?");
$stmt->execute([$classroom_id]);
$classroomRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$classroomRow) {
    die('No classroom found.');
}
$classroom = [
    'id' => (int)$classroomRow['id'],
    'class_name' => $classroomRow['name'],
];

// Every classroom_chapters row is one island. Adding a chapter adds an island automatically.
$stmt = $pdo->prepare("
    SELECT
        cc.id,
        cc.chapter_name AS title,
        '' AS description,
        COALESCE(cc.chapter_order, cc.id) AS chapter_order,
        COALESCE(NULLIF(cc.island_theme,''), 'forest') AS island_theme,
        COALESCE(cc.is_unlocked,0) AS is_unlocked,
        COALESCE(sp.level,0) AS progress_level,
        COALESCE(sp.status,'Not Started') AS progress_status
    FROM classroom_chapters cc
    LEFT JOIN student_progress sp
      ON sp.student_id = ?
     AND sp.chapter_name = cc.chapter_name
    WHERE cc.classroom_id = ?
      AND COALESCE(cc.is_published,1) = 1
    ORDER BY COALESCE(cc.chapter_order, cc.id), cc.id
");
$stmt->execute([$student_id, $classroom_id]);
$chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Read the REAL Chapter Test score from student_assessments.
// The score is stored as text such as "4/8". The dashboard must display
// the actual percentage, not a fixed percentage based on Beginner/Intermediate/Master.
$stmt = $pdo->prepare("
    SELECT island_id, score, status
    FROM student_assessments
    WHERE student_id = ?
      AND type = 'Chapter Test'
    ORDER BY id ASC
");
$stmt->execute([$student_id]);
$chapterTestRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chapterTestByIsland = [];
foreach ($chapterTestRows as $testRow) {
    // Later rows overwrite earlier rows, so this always keeps the latest attempt.
    $chapterTestByIsland[(int)$testRow['island_id']] = $testRow;
}

foreach ($chapters as &$chapter) {
    $chapterNumber = (int)$chapter['chapter_order'];
    $test = $chapterTestByIsland[$chapterNumber] ?? null;

    $chapter['chapter_test_completed'] = false;
    $chapter['mastery_percentage'] = 0;

    if ($test && strcasecmp((string)($test['status'] ?? ''), 'Completed') === 0) {
        $chapter['chapter_test_completed'] = true;

        $scoreText = trim((string)($test['score'] ?? ''));
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)\s*$/', $scoreText, $matches)) {
            $correct = (float)$matches[1];
            $total = (float)$matches[2];
            $chapter['mastery_percentage'] = $total > 0
                ? round(($correct / $total) * 100)
                : 0;
        } elseif (is_numeric($scoreText)) {
            // Safe fallback in case a future version stores the percentage directly.
            $chapter['mastery_percentage'] = max(0, min(100, round((float)$scoreText)));
        }
    }
}
unset($chapter);

$totalChapters = count($chapters);
$mastered = 0;
$totalMastery = 0;
foreach ($chapters as $chapter) {
    $mastery = (float)$chapter['mastery_percentage'];
    $totalMastery += $mastery;
    if ($mastery >= 80) $mastered++;
}
$overallMastery = $totalChapters > 0 ? round($totalMastery / $totalChapters) : 0;

function getIslandPosition($chapterId, $chapterOrder, $totalChapters) {
    $islandsPerRow = 4;
    $row = floor(($chapterOrder - 1) / $islandsPerRow);
    $positionInRow = ($chapterOrder - 1) % $islandsPerRow;
    $horizontalSlots = [16, 39, 62, 85];
    $left = $horizontalSlots[$positionInRow];
    $seed = ($chapterId * 17) + ($chapterOrder * 29) + ($totalChapters * 11);
    $left += ($seed % 7) - 3;
    $left = max(13, min(87, $left));
    $top = 210 + ($row * 330) + ((($seed % 5) - 2) * 12);
    return ['left' => $left, 'top' => $top];
}

$rows = max(1, ceil($totalChapters / 4));
$worldHeight = 430 + (($rows - 1) * 330);
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduHunt - Learning Adventure</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* =====================================================
   GENERAL
===================================================== */

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {

            margin: 0;

            background: #f0f4f9;

            color: #2c3e50;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }


        /* =====================================================
   NAVBAR
===================================================== */

        .main-navbar {

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



        /* =====================================================
   PAGE
===================================================== */

        .page {

            max-width: 1500px;

            margin: auto;

            padding:
                30px 30px 60px;
        }


        /* =====================================================
   STUDENT PANEL
===================================================== */

        .student-panel {

            background: white;

            border:
                1px solid #dfe8f2;

            border-radius: 24px;

            padding:
                22px 28px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            box-shadow:
                0 8px 25px rgba(80, 110, 140, .08);
        }


        .welcome {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .big-avatar {

            width: 58px;
            height: 58px;

            background: #cbe0f5;

            border-radius: 17px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 900;
        }


        .welcome h1 {

            margin: 0 0 5px;

            font-size: 24px;

            font-weight: 900;
        }


        .welcome p {

            margin: 0;

            color: #7da0ca;

            font-weight: 700;
        }


        .student-stats {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;
        }


        .stat-pill {

            background: #f0f4f9;

            border:
                1px solid #e1e9f5;

            border-radius: 14px;

            padding:
                11px 16px;

            font-size: 15px;

            font-weight: 800;
        }


        /* =====================================================
   HEADING
===================================================== */

        .map-heading {

            text-align: center;

            margin:
                30px 0 18px;
        }


        .map-heading h2 {

            margin: 0;

            font-size: 31px;

            font-weight: 900;
        }


        .map-heading p {

            color: #688dbb;

            font-size: 16px;

            font-weight: 700;

            margin-top: 7px;
        }


        /* =====================================================
   WORLD
===================================================== */

        .adventure-world {

            position: relative;

            width: 100%;

            height: <?= (int)$worldHeight ?>px;

            overflow: hidden;

            border-radius: 34px;

            border: 7px solid white;

            background:

                radial-gradient(circle at 13% 18%,
                    rgba(255, 255, 255, .18),
                    transparent 20%),

                radial-gradient(circle at 88% 78%,
                    rgba(255, 255, 255, .12),
                    transparent 18%),

                linear-gradient(180deg,
                    #a9dfeb 0%,
                    #88cedf 55%,
                    #78c3d7 100%);

            box-shadow:
                0 14px 35px rgba(70, 110, 140, .17);
        }


        .water-lines {

            position: absolute;

            inset: 0;

            opacity: .15;

            background-image:
                radial-gradient(ellipse,
                    transparent 48%,
                    white 50%,
                    transparent 53%);

            background-size:
                160px 48px;

            pointer-events: none;
        }


        /* =====================================================
   CLOUDS
===================================================== */

        .cloud {

            position: absolute;

            width: 100px;
            height: 28px;

            background:
                rgba(255, 255, 255, .38);

            border-radius: 999px;

            z-index: 1;
        }


        .cloud::before,
        .cloud::after {

            content: "";

            position: absolute;

            background: inherit;

            border-radius: 50%;
        }


        .cloud::before {

            width: 42px;
            height: 42px;

            left: 17px;
            top: -18px;
        }


        .cloud::after {

            width: 52px;
            height: 52px;

            right: 10px;
            top: -26px;
        }


        /* =====================================================
   ISLAND
===================================================== */

        .island {

            position: absolute;

            width: 255px;
            height: 200px;

            transform:
                translate(-50%, -50%);

            z-index: 10;
        }


        .island-shadow {

            position: absolute;

            width: 205px;
            height: 52px;

            left: 25px;
            bottom: 5px;

            background:
                rgba(52, 110, 132, .18);

            border-radius: 50%;

            filter: blur(6px);

            z-index: 1;
        }


        /* =====================================================
   ROCK
===================================================== */

        .island-rock {

            position: absolute;

            width: 205px;
            height: 105px;

            left: 25px;
            top: 75px;

            background:
                linear-gradient(160deg,
                    #c7a185,
                    #9b7963 55%,
                    #765c4d);

            clip-path:
                polygon(4% 0,
                    96% 0,
                    89% 45%,
                    75% 77%,
                    58% 98%,
                    42% 98%,
                    25% 78%,
                    11% 45%);

            filter:
                drop-shadow(0 8px 5px rgba(60, 80, 90, .18));

            z-index: 2;
        }


        .island-rock::after {

            content: "";

            position: absolute;

            width: 15px;
            height: 28px;

            left: 52px;
            top: 48px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, .13);

            transform:
                rotate(25deg);

            box-shadow:
                70px 10px 0 rgba(255, 255, 255, .08),
                105px -6px 0 rgba(255, 255, 255, .09);
        }


        /* =====================================================
   LAND
===================================================== */

        .island-land {

            position: absolute;

            width: 230px;
            height: 108px;

            left: 12px;
            top: 42px;

            border-radius: 50%;

            background:
                radial-gradient(circle at 35% 28%,
                    rgba(255, 255, 255, .25),
                    transparent 22%),
                linear-gradient(145deg,
                    #a9dda0,
                    #78bf75);

            border:
                7px solid #c7e8b6;

            box-shadow:
                inset 0 -11px 0 rgba(68, 129, 70, .12),
                0 5px 10px rgba(45, 90, 75, .10);

            z-index: 3;
        }


        .island-land::after {

            content: "";

            position: absolute;

            width: 11px;
            height: 6px;

            background:
                rgba(255, 255, 255, .25);

            border-radius: 50%;

            left: 36px;
            top: 28px;

            box-shadow:
                28px 39px 0 rgba(255, 255, 255, .18),
                93px 7px 0 rgba(255, 255, 255, .18),
                132px 43px 0 rgba(255, 255, 255, .15),
                155px 15px 0 rgba(255, 255, 255, .15);
        }


        /* =====================================================
   CHAPTER BUTTON
   BACK IN THE MIDDLE
===================================================== */

        .chapter-button {

            position: absolute;

            left: 50%;

            top: 62px;

            transform:
                translateX(-50%);

            z-index: 60;

            width: 70px;
            height: 70px;

            border-radius: 50%;

            border:
                6px solid rgba(255, 255, 255, .95);

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 27px;

            font-weight: 900;

            box-shadow:
                0 7px 0 rgba(66, 85, 100, .14),
                0 12px 20px rgba(55, 85, 105, .20);

            animation:
                levelBounce 2.1s ease-in-out infinite;

            transition:
                filter .2s,
                box-shadow .2s;
        }


        @keyframes levelBounce {

            0%,
            100% {

                transform:
                    translateX(-50%) translateY(0);
            }

            50% {

                transform:
                    translateX(-50%) translateY(-8px);
            }
        }


        .chapter-button:hover {

            animation-play-state: paused;

            transform:
                translateX(-50%) translateY(-8px) scale(1.08);

            filter:
                brightness(1.04);

            box-shadow:
                0 8px 0 rgba(66, 85, 100, .13),
                0 16px 26px rgba(55, 85, 105, .25);
        }


        /* =====================================================
   PASTEL STATUS COLORS
===================================================== */

        .status-red {

            background:
                linear-gradient(145deg,
                    #f5a5aa,
                    #e9868f);

            color: white;
        }


        .status-yellow {

            background:
                linear-gradient(145deg,
                    #f8db91,
                    #efc96f);

            color: #6d5724;
        }


        .status-green {

            background:
                linear-gradient(145deg,
                    #a2ddb5,
                    #76c596);

            color: white;
        }


        /* =====================================================
   TREES
===================================================== */

        .tree {

            position: absolute;

            width: 12px;
            height: 36px;

            background: #8b694e;

            border-radius: 5px;

            z-index: 15;
        }


        .tree::before {

            content: "";

            position: absolute;

            width: 38px;
            height: 38px;

            left: -13px;
            top: -24px;

            border-radius: 50%;

            background: #70b77c;

            box-shadow:
                13px 4px 0 #8bc996,
                -9px 6px 0 #60a96d,
                3px -8px 0 #9ad3a3;
        }


        .tree-one {

            left: 45px;
            top: 73px;

            transform:
                scale(.78);
        }


        .tree-two {

            right: 43px;
            top: 79px;

            transform:
                scale(.63);
        }


        .tree-three {

            left: 75px;
            top: 96px;

            transform:
                scale(.45);
        }


        /* =====================================================
   FLOWERS
===================================================== */

        .flower {

            position: absolute;

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #fff3a8;

            left: 80px;
            top: 103px;

            z-index: 16;

            box-shadow:
                18px -9px 0 #f6aac8,
                37px 3px 0 #ffffff,
                73px -14px 0 #f8b1c9,
                91px 3px 0 #ffe79a,
                109px -10px 0 #d9b5ef,
                126px 5px 0 #ffffff;
        }


        /* =====================================================
   BUSHES
===================================================== */

        .bush {

            position: absolute;

            width: 29px;
            height: 18px;

            border-radius: 50%;

            background: #6dae78;

            z-index: 14;
        }


        .bush::before,
        .bush::after {

            content: "";

            position: absolute;

            width: 21px;
            height: 20px;

            border-radius: 50%;

            background: inherit;
        }


        .bush::before {

            left: -9px;
            top: 3px;
        }


        .bush::after {

            right: -8px;
            top: 4px;
        }


        .bush-one {

            right: 54px;
            top: 105px;
        }


        .bush-two {

            left: 105px;
            top: 112px;

            transform:
                scale(.65);
        }


        /* =====================================================
   MUSHROOM
===================================================== */

        .mushroom {

            position: absolute;

            width: 9px;
            height: 14px;

            background: #f4eadb;

            left: 91px;
            top: 115px;

            z-index: 18;
        }


        .mushroom::before {

            content: "";

            position: absolute;

            width: 24px;
            height: 13px;

            left: -7px;
            top: -7px;

            border-radius:
                50% 50% 35% 35%;

            background: #e98c91;
        }


        /* =====================================================
   PEBBLES
===================================================== */

        .pebble {

            position: absolute;

            width: 15px;
            height: 9px;

            background: #b7b5ae;

            border-radius: 50%;

            z-index: 13;
        }


        .pebble-one {

            left: 54px;
            top: 119px;

            transform:
                rotate(-10deg);
        }


        .pebble-two {

            right: 72px;
            top: 120px;

            transform:
                rotate(15deg) scale(.75);
        }


        /* =====================================================
   CHERRY THEME
===================================================== */

        .theme-cherry .island-land {

            background:
                linear-gradient(145deg,
                    #b8dfa7,
                    #83c88a);

            border-color:
                #f4c7db;
        }


        .theme-cherry .tree::before {

            background: #efb1cd;

            box-shadow:
                13px 4px 0 #f5c5da,
                -9px 6px 0 #e79ebe,
                3px -8px 0 #f9d4e3;
        }


        /* =====================================================
   OCEAN THEME
===================================================== */

        .theme-ocean .island-land {

            background:
                linear-gradient(145deg,
                    #9be2bc,
                    #64c7a0);

            border-color:
                #f7e2a2;
        }


        .theme-ocean .tree {

            display: none;
        }


        .theme-ocean .palm {

            display: block;
        }


        /* =====================================================
   PALM
===================================================== */

        .palm {

            display: none;

            position: absolute;

            width: 12px;
            height: 50px;

            background: #96724c;

            left: 49px;
            top: 72px;

            border-radius: 5px;

            z-index: 18;

            transform:
                rotate(-7deg);
        }


        .palm::before {

            content: "";

            position: absolute;

            width: 55px;
            height: 17px;

            left: -22px;
            top: -10px;

            border-radius:
                100% 10% 100% 10%;

            background: #70bf8a;

            transform:
                rotate(20deg);

            box-shadow:
                4px 4px 0 #86cd99;
        }


        .palm::after {

            content: "";

            position: absolute;

            width: 55px;
            height: 17px;

            left: -17px;
            top: -7px;

            border-radius:
                10% 100% 10% 100%;

            background: #61ae7b;

            transform:
                rotate(-28deg);
        }


        /* =====================================================
   DESERT
===================================================== */

        .theme-desert .island-land {

            background:
                linear-gradient(145deg,
                    #f5dea3,
                    #e9c56f);

            border-color:
                #ffeab4;
        }


        .theme-desert .island-rock {

            background:
                linear-gradient(160deg,
                    #d29a72,
                    #a8725a);
        }


        .theme-desert .tree,
        .theme-desert .bush,
        .theme-desert .mushroom {

            display: none;
        }


        .theme-desert .cactus {

            display: block;
        }


        /* =====================================================
   CACTUS
===================================================== */

        .cactus {

            display: none;

            position: absolute;

            width: 14px;
            height: 43px;

            left: 55px;
            top: 80px;

            background: #7bb887;

            border-radius: 8px;

            z-index: 18;
        }


        .cactus::before {

            content: "";

            position: absolute;

            width: 17px;
            height: 12px;

            left: -11px;
            top: 15px;

            border-left:
                6px solid #7bb887;

            border-bottom:
                6px solid #7bb887;

            border-radius:
                0 0 0 10px;
        }


        .cactus::after {

            content: "";

            position: absolute;

            width: 17px;
            height: 12px;

            right: -11px;
            top: 8px;

            border-right:
                6px solid #7bb887;

            border-bottom:
                6px solid #7bb887;

            border-radius:
                0 0 10px 0;
        }


        /* =====================================================
   FOREST
===================================================== */

        .theme-forest .island-land {

            background:
                linear-gradient(145deg,
                    #93cf8b,
                    #60ad6d);

            border-color:
                #b7dfa5;
        }


        .theme-forest .tree::before {

            background: #579965;

            box-shadow:
                13px 4px 0 #70ad79,
                -9px 6px 0 #4a8857,
                3px -8px 0 #82ba88;
        }


        /* =====================================================
   SNOW
===================================================== */

        .theme-snow .island-land {

            background:
                linear-gradient(145deg,
                    #eefbfc,
                    #cbeaf0);

            border-color:
                white;
        }


        .theme-snow .island-rock {

            background:
                linear-gradient(160deg,
                    #c0cbd3,
                    #8fa4b1);
        }


        .theme-snow .tree::before {

            background: #f7ffff;

            box-shadow:
                13px 4px 0 #e3f3f5,
                -9px 6px 0 #ffffff,
                3px -8px 0 #d3edf1;
        }


        .theme-snow .snowman {

            display: block;
        }


        /* =====================================================
   SNOWMAN
===================================================== */

        .snowman {

            display: none;

            position: absolute;

            width: 26px;
            height: 26px;

            right: 52px;
            top: 102px;

            background: white;

            border-radius: 50%;

            z-index: 20;

            box-shadow:
                0 -17px 0 -5px white;
        }


        /* =====================================================
   CANDY
===================================================== */

        .theme-candy .island-land {

            background:
                linear-gradient(145deg,
                    #dcb8ed,
                    #bd8edb);

            border-color:
                #f7d1ea;
        }


        .theme-candy .island-rock {

            background:
                linear-gradient(160deg,
                    #d7b0c8,
                    #a780a1);
        }


        .theme-candy .tree::before {

            background: #f0b4d1;

            box-shadow:
                13px 4px 0 #f6c7de,
                -9px 6px 0 #daa5e7,
                3px -8px 0 #ffd8e8;
        }


        /* =====================================================
   SUNSET
===================================================== */

        .theme-sunset .island-land {

            background:
                linear-gradient(145deg,
                    #f7c28c,
                    #e9948b);

            border-color:
                #f9d9af;
        }


        .theme-sunset .tree::before {

            background: #e8a7b6;

            box-shadow:
                13px 4px 0 #f4b9c2,
                -9px 6px 0 #d895ae,
                3px -8px 0 #f3cfaa;
        }


        /* =====================================================
   VOLCANO
===================================================== */

        .theme-volcano .island-land {

            background:
                linear-gradient(145deg,
                    #a3ad82,
                    #75835e);

            border-color:
                #e7b07e;
        }


        .theme-volcano .island-rock {

            background:
                linear-gradient(160deg,
                    #8a6f67,
                    #5e514e);
        }


        .theme-volcano .tree {

            display: none;
        }


        .theme-volcano .volcano {

            display: block;
        }


        .volcano {

            display: none;

            position: absolute;

            width: 67px;
            height: 51px;

            left: 38px;
            top: 91px;

            z-index: 17;

            background:
                linear-gradient(150deg,
                    #80645c,
                    #504544);

            clip-path:
                polygon(50% 0,
                    100% 100%,
                    0 100%);
        }


        .volcano::before {

            content: "";

            position: absolute;

            width: 24px;
            height: 8px;

            left: 21px;
            top: 6px;

            background: #ef9a79;

            border-radius: 50%;

            box-shadow:
                0 -5px 9px rgba(240, 142, 107, .55);
        }


        /* =====================================================
   ISLAND INFO
===================================================== */

        .island-info {

            position: absolute;

            left: 50%;

            top: 159px;

            transform:
                translateX(-50%);

            width: 210px;

            padding:
                8px 11px;

            background:
                rgba(255, 255, 255, .95);

            border:
                2px solid rgba(255, 255, 255, .9);

            border-radius: 15px;

            text-align: center;

            box-shadow:
                0 7px 16px rgba(55, 85, 100, .13);

            z-index: 50;
        }


        .island-info strong {

            display: block;

            font-size: 14px;

            line-height: 1.2;

            font-weight: 900;
        }


        .island-info span {

            display: block;

            margin-top: 3px;

            color: #7893b3;

            font-size: 12px;

            font-weight: 800;
        }


        /* =====================================================
   LEGEND
===================================================== */

        .legend {

            margin-top: 20px;

            background: white;

            border:
                1px solid #e1e9f5;

            border-radius: 20px;

            padding:
                17px 24px;

            display: flex;

            justify-content: center;

            align-items: center;

            flex-wrap: wrap;

            gap: 28px;

            box-shadow:
                0 6px 18px rgba(80, 110, 140, .07);
        }


        .legend-title {

            font-weight: 900;
        }


        .legend-item {

            display: flex;

            align-items: center;

            gap: 8px;

            font-weight: 800;

            font-size: 14px;
        }


        .legend-dot {

            width: 17px;
            height: 17px;

            border-radius: 50%;
        }


        .legend-red {

            background: #f5a5aa;
        }


        .legend-yellow {

            background: #f8db91;
        }


        .legend-green {

            background: #a2ddb5;
        }


        /* =====================================================
   RESPONSIVE
===================================================== */

        @media (max-width: 1000px) {

            .nav-inner {

                grid-template-columns:
                    auto 1fr;
            }

            .nav-links {

                display: none;
            }

            .profile-area {

                justify-self: end;
            }

            .student-panel {

                flex-direction: column;

                align-items: flex-start;
            }

            .island {

                transform:
                    translate(-50%, -50%) scale(.80);
            }
        }


        @media (max-width: 650px) {

            .page {

                padding:
                    20px 10px 45px;
            }

            .brand-name {

                font-size: 23px;
            }

            .profile-name {

                display: none;
            }

            .island {

                transform:
                    translate(-50%, -50%) scale(.68);
            }
        }
    </style>

</head>


<body>


    <!-- ======================================================
     NAVBAR
====================================================== -->

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

                <a href="quiz.php">
                    Quizzes
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
                                    $student['full_name'],
                                    0,
                                    1
                                )
                            )
                        ) ?>
                    </div>

                    <span class="profile-name">
                        <?= htmlspecialchars(
                            $student['full_name']
                        ) ?>
                    </span>
                </div>
            </div>

        </div>

    </nav>


    <!-- ======================================================
     PAGE
====================================================== -->

    <main class="page">


        <section class="student-panel">

            <div class="welcome">

                <div class="big-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $student['full_name'],
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <div>

                    <h1>

                        Welcome back,
                        <?= htmlspecialchars(
                            explode(
                                ' ',
                                $student['full_name']
                            )[0]
                        ) ?>!

                    </h1>

                    <p>

                        <?= htmlspecialchars(
                            $classroom['class_name']
                        ) ?>

                    </p>

                </div>

            </div>


            <div class="student-stats">

                <div class="stat-pill">

                    <?= $mastered ?>
                    /
                    <?= $totalChapters ?>
                    Mastered

                </div>

                <div class="stat-pill">

                    <?= $overallMastery ?>%
                    Mastery

                </div>

            </div>

        </section>


        <div class="map-heading">

            <h2>
                Your Learning Adventure
            </h2>

            <p>
                Explore every island and master each chapter.
            </p>

        </div>


        <!-- ======================================================
     ADVENTURE WORLD
====================================================== -->

        <div class="adventure-world">

            <div class="water-lines"></div>


            <div class="cloud" style="
            left:5%;
            top:50px;
            transform:scale(.65);
        "></div>


            <div class="cloud" style="
            right:7%;
            top:55px;
            transform:scale(.55);
        "></div>


            <?php if (!$chapters): ?>

                <div style="
                position:absolute;
                left:50%;
                top:50%;
                transform:translate(-50%,-50%);
                background:white;
                padding:28px;
                border-radius:20px;
                text-align:center;
                font-weight:900;
            ">

                    Your teacher has not added any chapters yet.

                </div>

            <?php endif; ?>


            <?php foreach (
                $chapters as $index => $chapter
            ): ?>


                <?php

                $chapterNumber =
                    $index + 1;


                $mastery =
                    round(
                        (float) $chapter[
                            'mastery_percentage'
                        ]
                    );


                // ===============================================
                // STATUS
                // ===============================================
            
                if (empty($chapter['chapter_test_completed'])) {

                    // Chapter Test has not been completed yet.
                    $statusClass =
                        'status-red';

                    $statusText =
                        'Not Done';

                } elseif ($mastery >= 80) {

                    // 80% and above = green.
                    $statusClass =
                        'status-green';

                    $statusText =
                        'Mastered';

                } elseif ($mastery <= 40) {

                    // 40% and below = red.
                    $statusClass =
                        'status-red';

                    $statusText =
                        'Needs Practice';

                } else {

                    // 41% to 79% = yellow.
                    $statusClass =
                        'status-yellow';

                    $statusText =
                        'Needs Practice';
                }


                // ===============================================
                // POSITION
                // ===============================================
            
                $position =
                    getIslandPosition(
                        $chapter['id'],
                        $chapterNumber,
                        $totalChapters
                    );


                // ===============================================
                // THEMES
                // ===============================================
            
                $allowedThemes = [
                    'cherry',
                    'ocean',
                    'desert',
                    'forest',
                    'snow',
                    'volcano',
                    'candy',
                    'sunset'
                ];


                $theme =
                    strtolower(
                        $chapter['island_theme']
                        ?? ''
                    );


                /*
                 * If the database theme is missing,
                 * automatically cycle through the
                 * different island designs.
                 */

                if (
                    !in_array(
                        $theme,
                        $allowedThemes
                    )
                ) {

                    $theme =
                        $allowedThemes[
                            $index
                            %
                            count($allowedThemes)
                        ];
                }

                ?>


                <div class="
                island
                theme-<?= htmlspecialchars($theme) ?>
            " style="
                left:
                    <?= $position['left'] ?>%;

                top:
                    <?= $position['top'] ?>px;
            ">


                    <!-- ISLAND SHADOW -->

                    <div class="island-shadow"></div>


                    <!-- ROCK -->

                    <div class="island-rock"></div>


                    <!-- LAND -->

                    <div class="island-land"></div>


                    <!-- =================================================
                 DECORATIONS
            ================================================== -->

                    <div class="tree tree-one"></div>

                    <div class="tree tree-two"></div>

                    <div class="tree tree-three"></div>


                    <div class="flower"></div>


                    <div class="bush bush-one"></div>

                    <div class="bush bush-two"></div>


                    <div class="mushroom"></div>


                    <div class="pebble pebble-one"></div>

                    <div class="pebble pebble-two"></div>


                    <div class="palm"></div>


                    <div class="cactus"></div>


                    <div class="snowman"></div>


                    <div class="volcano"></div>


                    <!-- =================================================
                 CHAPTER BUTTON
            ================================================== -->

                    <a href="module.php?chap=<?= (int) $chapterNumber ?>" class="
                    chapter-button
                    <?= $statusClass ?>
                " title="<?= htmlspecialchars(
                    $chapter['title']
                ) ?>">

                        <?= $chapterNumber ?>

                    </a>


                    <!-- =================================================
                 CHAPTER LABEL
            ================================================== -->

                    <div class="island-info">

                        <strong>

                            Chapter
                            <?= $chapterNumber ?>:

                            <?= htmlspecialchars(
                                $chapter['title']
                            ) ?>

                        </strong>


                        <span>

                            <?= $mastery ?>%

                            ·

                            <?= $statusText ?>

                        </span>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


        <!-- ======================================================
     LEGEND
====================================================== -->

        <div class="legend">

            <div class="legend-title">

                Island Progress

            </div>


            <div class="legend-item">

                <span class="
                legend-dot
                legend-red
            "></span>

                Not Done

            </div>


            <div class="legend-item">

                <span class="
                legend-dot
                legend-yellow
            "></span>

                Completed

            </div>


            <div class="legend-item">

                <span class="
                legend-dot
                legend-green
            "></span>

                Mastered

            </div>

        </div>


    </main>


    


</body>

</html>