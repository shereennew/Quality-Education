<?php
// classroom.php
session_start();
require_once __DIR__ . '/../config/db.php';


$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;



// Handle global chapter unlock action for the whole class
if (isset($_GET['action']) && $_GET['action'] === 'toggle_chapter' && isset($_GET['chapter'])) {
    $target_chapter = urldecode($_GET['chapter']);

    // Check current state
    $stmt_chk = $pdo->prepare("SELECT is_unlocked FROM classroom_chapters WHERE classroom_id = ? AND chapter_name = ?");
    $stmt_chk->execute([$class_id, $target_chapter]);
    $current_state = $stmt_chk->fetchColumn();

    $new_state = ($current_state !== false && $current_state == 1) ? 0 : 1;

    if ($current_state !== false) {
        $stmt_upd = $pdo->prepare("UPDATE classroom_chapters SET is_unlocked = ? WHERE classroom_id = ? AND chapter_name = ?");
        $stmt_upd->execute([$new_state, $class_id, $target_chapter]);
    } else {
        $stmt_ins = $pdo->prepare("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES (?, ?, ?)");
        $stmt_ins->execute([$class_id, $target_chapter, $new_state]);
    }

    header("Location: classroom.php?id=" . $class_id);
    exit;
}

// Fetch classroom details
$stmt_class = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmt_class->execute([$class_id]);
$current_class = $stmt_class->fetch(PDO::FETCH_ASSOC);

if (!$current_class) {
    die("Classroom not found.");
}

// Fetch dynamic chapters sorted properly or by their natural occurrence
$stmt_all_chapters = $pdo->query("
    SELECT chapter_name FROM (
        SELECT DISTINCT chapter_name FROM chapter_materials 
        UNION 
        SELECT DISTINCT chapter_name FROM chapter_quizzes
    ) ORDER BY CAST(SUBSTR(chapter_name, INSTR(chapter_name, 'Ch ') + 3) AS INTEGER) ASC
");
$db_chapters = $stmt_all_chapters->fetchAll(PDO::FETCH_COLUMN);
$chapters = !empty($db_chapters) ? $db_chapters : ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];

// Fetch global chapter unlock statuses for this classroom
$unlocked_chapters = [];
foreach ($chapters as $index => $ch_name) {
    $stmt_uc = $pdo->prepare("SELECT is_unlocked FROM classroom_chapters WHERE classroom_id = ? AND chapter_name = ?");
    $stmt_uc->execute([$class_id, $ch_name]);
    $val = $stmt_uc->fetchColumn();
    $unlocked_chapters[$ch_name] = ($val !== false) ? intval($val) : ($index === 0 ? 1 : 0);
}

$pdo->prepare("
    UPDATE students 
    SET score = (
        SELECT COALESCE(SUM(sq.is_correct), 0) 
        FROM student_quiz_answers sq 
        JOIN student_assessments sa ON sq.assessment_id = sa.id
        WHERE sa.student_id = students.id
    )
    WHERE classroom_id = ?
")->execute([$class_id]);

// Fetch students for this classroom
$stmt_students = $pdo->prepare("SELECT * FROM students WHERE classroom_id = ?");
$stmt_students->execute([$class_id]);
$students_raw = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

$table_chapter = $_GET['table_chapter'] ?? $chapters[0];

$students = [];

foreach ($students_raw as $s) {
    $student_levels = [];
    $total_earned_score = 0;
    $max_possible_score = 0;

    foreach ($chapters as $ch_name) {
        $stmt_prog = $pdo->prepare("SELECT level FROM student_progress WHERE student_id = ? AND chapter_name = ?");
        $stmt_prog->execute([$s['id'], $ch_name]);
        $lvl = $stmt_prog->fetchColumn();
        $student_levels[$ch_name] = $lvl !== false ? intval($lvl) : 0;

        $isUnlocked = $unlocked_chapters[$ch_name] ?? 0;

        if ($isUnlocked) {
            $total_earned_score += $student_levels[$ch_name];
            $stmt_max = $pdo->prepare("SELECT COUNT(*) FROM chapter_quizzes WHERE chapter_name = ?");
            $stmt_max->execute([$ch_name]);
            $ch_max = max(1, $stmt_max->fetchColumn());
            $max_possible_score += $ch_max;
        }
    }

$percentage = $max_possible_score > 0
    ? round(($total_earned_score / $max_possible_score) * 100)
    : 0;


// ---------------------------------------------------------
// Calculate student status based on Quiz performance
// for the selected table chapter
// ---------------------------------------------------------

// Total quiz questions in this chapter
$stmt_status_total = $pdo->prepare("
    SELECT COUNT(*)
    FROM chapter_quizzes
    WHERE chapter_name = ?
");
$stmt_status_total->execute([$table_chapter]);

$status_total_questions = (int)$stmt_status_total->fetchColumn();


// Questions the student has actually attempted
$stmt_status_attempted = $pdo->prepare("
    SELECT COUNT(*)
    FROM student_quiz_answers sqa
    JOIN student_assessments sa
        ON sqa.assessment_id = sa.id
    JOIN chapter_quizzes cq
        ON sqa.quiz_id = cq.id
    WHERE sa.student_id = ?
      AND sa.type = 'Quiz'
      AND cq.chapter_name = ?
");
$stmt_status_attempted->execute([
    $s['id'],
    $table_chapter
]);

$status_attempted_questions = (int)$stmt_status_attempted->fetchColumn();


// Correct answers
$stmt_status_correct = $pdo->prepare("
    SELECT COUNT(*)
    FROM student_quiz_answers sqa
    JOIN student_assessments sa
        ON sqa.assessment_id = sa.id
    JOIN chapter_quizzes cq
        ON sqa.quiz_id = cq.id
    WHERE sa.student_id = ?
      AND sa.type = 'Quiz'
      AND cq.chapter_name = ?
      AND sqa.is_correct = 1
");
$stmt_status_correct->execute([
    $s['id'],
    $table_chapter
]);

$status_correct = (int)$stmt_status_correct->fetchColumn();


// Calculate quiz percentage
$status_percentage = $status_total_questions > 0
    ? round(($status_correct / $status_total_questions) * 100)
    : 0;


// Determine student status
if ($status_attempted_questions == 0) {
    $student_status = '-';
} elseif ($status_percentage >= 80) {
    $student_status = 'Mastering';
} elseif ($status_percentage >= 50) {
    $student_status = 'On Track';
} else {
    $student_status = 'Struggling';
}


$stmt_live_score = $pdo->prepare("

        SELECT COALESCE(SUM(sq.is_correct), 0) 
        FROM student_quiz_answers sq 
        JOIN student_assessments sa ON sq.assessment_id = sa.id 
        WHERE sa.student_id = ?
    ");
    $stmt_live_score->execute([$s['id']]);
    $live_score = $stmt_live_score->fetchColumn();
$students[] = [
    "id" => $s['id'],
    "name" => $s['name'],
    "status" => $student_status,
    "score" => intval($live_score),
    "progress" => $student_levels,
    "summary" => $percentage . "%"
];

}


// Selected chapter for overview circle tracker graph
$overview_chapter = $_GET['overview_chapter'] ?? $chapters[0];

// Total questions in selected chapter
$stmt_overview_total = $pdo->prepare("
    SELECT COUNT(*)
    FROM chapter_quizzes
    WHERE chapter_name = ?
");
$stmt_overview_total->execute([$overview_chapter]);

$overview_total_questions = (int)$stmt_overview_total->fetchColumn();


$student_progress_percentages = [];

if ($overview_total_questions > 0) {

    foreach ($students_raw as $overview_student) {

        // Count correct answers for this student in this chapter
        $stmt_overview_correct = $pdo->prepare("
            SELECT COUNT(*)
            FROM student_quiz_answers sqa
            JOIN student_assessments sa
                ON sqa.assessment_id = sa.id
            JOIN chapter_quizzes cq
                ON sqa.quiz_id = cq.id
            WHERE sa.student_id = ?
              AND sa.type = 'Quiz'
              AND cq.chapter_name = ?
              AND sqa.is_correct = 1
        ");

        $stmt_overview_correct->execute([
            $overview_student['id'],
            $overview_chapter
        ]);

        $overview_correct = (int)$stmt_overview_correct->fetchColumn();

        // Student's chapter progress
        $student_percentage = round(
            ($overview_correct / $overview_total_questions) * 100
        );

        $student_progress_percentages[] = $student_percentage;
    }
}

// Average of ALL students
$overview_percentage = count($student_progress_percentages) > 0
    ? round(
        array_sum($student_progress_percentages)
        / count($student_progress_percentages)
    )
    : 0;

// Filter criteria for table section
$status_filter = $_GET['status_filter'] ?? [];
if (!is_array($status_filter)) {
    $status_filter = $status_filter !== '' ? [$status_filter] : [];
}
$quiz_filter = $_GET['quiz_filter'] ?? '';

$active_filter_count = count($status_filter) + (!empty($quiz_filter) ? 1 : 0) + (!empty($table_chapter) && $table_chapter !== $chapters[0] ? 1 : 0);

$filtered_students = array_filter($students, function($student) use ($status_filter) {
    if (!empty($status_filter) && !in_array($student['status'], $status_filter)) {
        return false;
    }
    return true;
});

// Fetch Main Topic Quizzes for the table section
if (!empty($quiz_filter)) {
    $stmt_main_q = $pdo->prepare("
        SELECT id, question, subtopic_name
        FROM chapter_quizzes
        WHERE chapter_name = ?
          AND (subtopic_name IS NULL OR subtopic_name = '')
    ");
    $stmt_main_q->execute([$table_chapter]);
} else {
    $stmt_main_q = $pdo->prepare("
        SELECT id, question, subtopic_name
        FROM chapter_quizzes
        WHERE chapter_name = ?
          AND (subtopic_name IS NULL OR subtopic_name = '')
        ORDER BY id ASC
    ");
    $stmt_main_q->execute([$table_chapter]);
}
$table_main_quizzes = $stmt_main_q->fetchAll(PDO::FETCH_ASSOC);
// Fetch Subtopic Quizzes for the table section
if (!empty($quiz_filter)) {

    // quiz_filter contains subtopic name, e.g. 1.1 or 1.2
    $stmt_sub_q = $pdo->prepare("
        SELECT id, question, subtopic_name
        FROM chapter_quizzes
        WHERE chapter_name = ?
          AND subtopic_name = ?
          AND subtopic_name IS NOT NULL
          AND subtopic_name != ''
        ORDER BY id ASC
    ");

    $stmt_sub_q->execute([
        $table_chapter,
        $quiz_filter
    ]);

} else {

    // Show all subtopics under the selected chapter
    $stmt_sub_q = $pdo->prepare("
        SELECT id, question, subtopic_name
        FROM chapter_quizzes
        WHERE chapter_name = ?
          AND subtopic_name IS NOT NULL
          AND subtopic_name != ''
        ORDER BY subtopic_name ASC, id ASC
    ");

    $stmt_sub_q->execute([
        $table_chapter
    ]);
}

$table_sub_quizzes_raw = $stmt_sub_q->fetchAll(PDO::FETCH_ASSOC);

$table_grouped_sub_quizzes = [];

foreach ($table_sub_quizzes_raw as $q) {
    $table_grouped_sub_quizzes[$q['subtopic_name']][] = $q;
}

$total_displayed_quizzes = count($table_main_quizzes) + count($table_sub_quizzes_raw);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_class['name']); ?> - EduPulse</title>
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
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="teacher_home.php"
                    class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr;
                    Back</a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide">
                    <?php echo htmlspecialchars($current_class['name']); ?>
                </h1>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-6 flex-1 w-full">

        <!-- Overall Summary Circular Tracker & Rankings Section -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                <!-- Circular Progress Visualizer Container -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-pastel-text">Class Skill Distribution Overview</h2>
                        </div>

                        <form method="GET" class="w-full sm:w-auto">
                            <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                            <?php if (!empty($table_chapter)): ?>
                                <input type="hidden" name="table_chapter" value="<?php echo htmlspecialchars($table_chapter); ?>">
                            <?php endif; ?>
                            <?php foreach ($status_filter as $sf): ?>
                                <input type="hidden" name="status_filter[]" value="<?php echo htmlspecialchars($sf); ?>">
                            <?php endforeach; ?>
                            <?php if (!empty($quiz_filter)): ?>
                                <input type="hidden" name="quiz_filter" value="<?php echo htmlspecialchars($quiz_filter); ?>">
                            <?php endif; ?>
                            <select name="overview_chapter" onchange="this.form.submit()"
                                class="w-full sm:w-auto text-xs px-2.5 py-1.5 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">
                                <?php foreach ($chapters as $ch): ?>
                                    <option value="<?php echo htmlspecialchars($ch); ?>" <?php echo ($overview_chapter === $ch) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ch); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row items-center justify-center gap-6 py-5 bg-pastel-bg/20 rounded-xl border border-blue-100">
                        <div class="relative flex items-center justify-center">
                            <svg class="w-64 h-64 transform -rotate-90">
                                <circle cx="128" cy="128" r="108" stroke="currentColor" stroke-width="12"
                                    class="text-blue-100 fill-none"></circle>
                                <circle cx="128" cy="128" r="108" stroke="currentColor" stroke-width="12" class="fill-none transition-all duration-500 ease-out 
                                    <?php
                                    if ($overview_percentage >= 80) {
                                        echo 'text-emerald-500';
                                    } elseif ($overview_percentage >= 50) {
                                        echo 'text-amber-500';
                                    } else {
                                        echo 'text-rose-500';
                                    }
                                    ?>" stroke-dasharray="678.58"
                                    stroke-dashoffset="<?php echo 678.58 - (678.58 * $overview_percentage / 100); ?>"
                                    stroke-linecap="round">
                                </circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span
                                    class="text-2xl font-bold text-pastel-text"><?php echo $overview_percentage; ?>%</span>
                                <span
                                    class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Mastery</span>
                            </div>
                        </div>

                        <div class="space-y-2 text-center sm:text-left">
                            <h3 class="text-xs font-bold text-pastel-text">
                                <?php echo htmlspecialchars($overview_chapter); ?>
                            </h3>
                            <div class="flex flex-wrap gap-1.5 justify-center sm:justify-start pt-1">
                                <span
                                    class="text-[9px] font-semibold px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-lg">Target:
                                    >80%</span>
                                <span
                                    class="text-[9px] font-semibold px-2 py-0.5 bg-blue-100 text-blue-700 rounded-lg">Active
                                    Tracking</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Rank / Leaderboard Card -->
                <div class="bg-pastel-bg/40 rounded-xl border border-blue-50 p-4 flex flex-col justify-between h-full">
                    <div>
                        <div class="flex justify-between items-center mb-2.5">
                            <h3 class="text-xs font-bold text-pastel-text">Student Rankings</h3>
                            <span
                                class="bg-rose-50 text-rose-700 text-[10px] font-semibold px-2 py-0.5 rounded-full border border-rose-100">Needs
                                Attention</span>
                        </div>

                        <div class="space-y-2">
<?php
try {
    $stmt_rank = $pdo->prepare("
        SELECT
            s.id,
            s.name,
            COUNT(DISTINCT cq.id) AS total_questions,
            COALESCE(SUM(
                CASE
                    WHEN sa.type = 'Quiz' AND sq.is_correct = 1
                    THEN 1
                    ELSE 0
                END
            ), 0) AS correct_answers
        FROM students s
        LEFT JOIN student_quiz_answers sq
            ON s.id = sq.student_id
        LEFT JOIN student_assessments sa
            ON sq.assessment_id = sa.id
        LEFT JOIN chapter_quizzes cq
            ON sq.quiz_id = cq.id
        WHERE s.classroom_id = ?
          AND (sa.type = 'Quiz' OR sa.type IS NULL)
        GROUP BY s.id, s.name
    ");

    $stmt_rank->execute([$class_id]);
    $ranking_rows = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);

    $rankings = [];

    foreach ($ranking_rows as $row) {
        $total_questions = (int)$row['total_questions'];
        $correct_answers = (int)$row['correct_answers'];

        $percentage = $total_questions > 0
            ? round(($correct_answers / $total_questions) * 100)
            : 0;

        $rankings[] = [
            'name' => $row['name'],
            'percentage' => $percentage
        ];
    }

    usort($rankings, function ($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
    });

    $rankings = array_slice($rankings, 0, 5);

} catch (Exception $e) {
    $rankings = [];
}
?>

                            <?php if (count($rankings) > 0): ?>
                                <?php foreach ($rankings as $index => $student): ?>
                                    <div
                                        class="flex items-center justify-between p-2 rounded-lg bg-white border border-blue-50 text-[11px] shadow-2xs">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-bold text-rose-500 w-4">#<?php echo $index + 1; ?></span>
                                            <span
                                                class="font-semibold text-pastel-text truncate max-w-[110px]"><?php echo htmlspecialchars($student['name']); ?></span>
                                        </div>
<span
    class="bg-rose-50 text-rose-700 font-bold px-1.5 py-0.5 rounded border border-rose-100">
    <?php echo intval($student['percentage']); ?>%
</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div
                                    class="text-center py-4 text-[11px] text-slate-400 bg-white rounded-lg border border-blue-50">
                                    No records found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Global Chapter Control Panel -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Classroom Chapter Access Control</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Click any chapter to view its details and materials.</p>
                </div>
                <a href="chapter_setup.php"
                    class="bg-pastel-primary hover:bg-pastel-hover text-white font-semibold text-xs px-5 py-2.5 rounded-xl transition shadow-sm inline-flex items-center space-x-2 shrink-0">
                    <span>+ Add Chapter</span>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($chapters as $index => $ch_name):
                    $isUnlocked = $unlocked_chapters[$ch_name];
                    ?>
                    <div onclick="window.location.href='chapter_details.php?chapter=<?php echo urlencode($ch_name); ?>'"
                        class="border border-blue-100 rounded-xl p-4 flex items-center justify-between bg-pastel-bg/40 hover:border-pastel-primary hover:bg-white transition cursor-pointer relative group">
                        <div class="flex-1 pr-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Chapter
                                <?php echo $index + 1; ?></span>
                            <h3 class="text-sm font-bold text-pastel-text mt-0.5 hover:text-pastel-hover">
                                <?php echo htmlspecialchars($ch_name); ?>
                            </h3>
                            <span
                                class="inline-block mt-2 text-xs font-semibold px-2.5 py-0.5 rounded-full <?php echo $isUnlocked ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'; ?>">
                                <?php echo $isUnlocked ? 'Unlocked' : 'Locked'; ?>
                            </span>
                        </div>
                        <a href="classroom.php?id=<?php echo $class_id; ?>&action=toggle_chapter&chapter=<?php echo urlencode($ch_name); ?>"
                            onclick="event.stopPropagation();"
                            class="px-4 py-2 rounded-xl text-xs font-semibold transition shadow-sm z-10 <?php echo $isUnlocked ? 'bg-rose-100 hover:bg-rose-200 text-rose-700' : 'bg-pastel-primary hover:bg-pastel-hover text-white'; ?>">
                            <?php echo $isUnlocked ? 'Lock' : 'Unlock'; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Student Progress Table Section with Popup Filter Modal -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div
                class="p-6 border-b border-blue-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <h2 class="text-base font-bold text-pastel-text flex flex-wrap items-center gap-2">
                        <span>Student Progress Table</span>
                        <span class="bg-pastel-badge text-pastel-text text-xs font-extrabold px-3 py-1 rounded-xl border border-blue-200 shadow-2xs">
                            📖 Current Chapter: <?php echo htmlspecialchars($table_chapter); ?>
                        </span>
                    </h2>
                </div>

                <div
                class="p-6 border-b border-blue-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">

                <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
                    <button type="button" onclick="openFilterModal()"
                        class="text-xs px-3.5 py-2 rounded-xl border border-blue-100 bg-white hover:bg-pastel-badge font-semibold text-pastel-text transition shadow-sm flex items-center space-x-1.5 shrink-0">
                        <span>🔍 Filter <?php echo $active_filter_count > 0 ? '(' . $active_filter_count . ')' : ''; ?></span>
                    </button>

                    <button type="button" onclick="openGlobalAiSummaryModal()"
                        class="text-xs px-3.5 py-2 rounded-xl border border-blue-100 bg-white hover:bg-pastel-badge font-semibold text-pastel-text transition shadow-sm flex items-center space-x-1.5 shrink-0">
                        <span>✨ AI Summary</span>
                    </button>
                </div>
            </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">Student Name</th>
                            <th class="py-3.5 px-6">Status</th>
                            
                            <?php if (count($table_main_quizzes) > 0): ?>
                                <?php foreach ($table_main_quizzes as $mq): ?>
                                    <th class="py-3.5 px-6 text-center">
                                        <a href="quiz_summary.php?quiz_id=<?php echo $mq['id']; ?>&classroom_id=<?php echo $class_id; ?>" 
                                           class="hover:text-pastel-hover underline decoration-pastel-primary/50 underline-offset-4 transition">
                                            Main Topic Quiz (Q#<?php echo $mq['id']; ?>)
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                            <?php endif; ?>

<?php foreach ($table_grouped_sub_quizzes as $sub_name => $sub_qs): ?>
    <th class="py-3.5 px-6 text-center">
        <a href="quiz_summary.php?chapter=<?php echo urlencode($table_chapter); ?>&subtopic=<?php echo urlencode($sub_name); ?>&classroom_id=<?php echo $class_id; ?>"
           class="hover:text-pastel-hover underline decoration-pastel-primary/50 underline-offset-4 transition">
            <?php echo htmlspecialchars($sub_name); ?> Quiz
        </a>
    </th>
<?php endforeach; ?>


                            <th class="py-3.5 px-6 text-center">Chapter Progress</th>
                            <th class="py-3.5 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 text-sm">
                        <?php foreach ($filtered_students as $student): ?>
                            <?php
                            $isUnlocked = $unlocked_chapters[$table_chapter] ?? 0;
                            $student_quiz_answers = [];

                            $stmt_max_q_count = $pdo->prepare("SELECT COUNT(*) FROM chapter_quizzes WHERE chapter_name = ?");
                            $stmt_max_q_count->execute([$table_chapter]);
                            $chapter_total_quiz_count = max(1, $stmt_max_q_count->fetchColumn());

                            if ($isUnlocked) {
    foreach ($table_main_quizzes as $q) {
        $stmt_ans = $pdo->prepare("
            SELECT sa.status 
            FROM student_quiz_answers sqa
            JOIN student_assessments sa ON sqa.assessment_id = sa.id
            WHERE sa.student_id = ? AND sqa.quiz_id = ?
            LIMIT 1
        ");
        $stmt_ans->execute([$student['id'], $q['id']]);
        $ans_data = $stmt_ans->fetch(PDO::FETCH_ASSOC);
        $student_quiz_answers[$q['id']] = $ans_data ? $ans_data['status'] : 'Not Attempted';
    }
    foreach ($table_sub_quizzes_raw as $q) {
        $stmt_ans = $pdo->prepare("
            SELECT sa.status 
            FROM student_quiz_answers sqa
            JOIN student_assessments sa ON sqa.assessment_id = sa.id
            WHERE sa.student_id = ? AND sqa.quiz_id = ?
            LIMIT 1
        ");
        $stmt_ans->execute([$student['id'], $q['id']]);
        $ans_data = $stmt_ans->fetch(PDO::FETCH_ASSOC);
        $student_quiz_answers[$q['id']] = $ans_data ? $ans_data['status'] : 'Not Attempted';
    }
}

$stmt_prog_cnt = $pdo->prepare("
    SELECT COUNT(*)
    FROM student_quiz_answers sq
    JOIN student_assessments sa
        ON sq.assessment_id = sa.id
    JOIN chapter_quizzes cq
        ON sq.quiz_id = cq.id
    WHERE sa.student_id = ?
      AND sa.type = 'Quiz'
      AND cq.chapter_name = ?
      AND sq.is_correct = 1
");

$stmt_prog_cnt->execute([
    $student['id'],
    $table_chapter
]);

$completed_quiz_count = (int)$stmt_prog_cnt->fetchColumn();

$chapter_percentage = $chapter_total_quiz_count > 0
    ? round(($completed_quiz_count / $chapter_total_quiz_count) * 100)
    : 0;

                            ?>
                            <tr class="hover:bg-pastel-bg/50 transition">
                                <td class="py-4 px-6 font-medium text-pastel-text">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?= htmlspecialchars($student['status']) ?>
                                </td>

                                <?php 
                                $total_quiz_cols = count($table_main_quizzes) + count($table_grouped_sub_quizzes);
                                if (!$isUnlocked): 
                                ?>
                                    <td colspan="<?php echo $total_quiz_cols + 2; ?>"
                                        class="py-4 px-6 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                        Chapter Locked by Teacher
                                    </td>
                                <?php elseif ($total_quiz_cols === 0): ?>
                                    <td colspan="1" class="py-4 px-6 text-center text-slate-400 text-xs italic">
                                        No quizzes configured for this chapter.
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-pastel-text">
                                        0%
                                    </td>
                                <?php else: ?>
                                    <?php if (count($table_main_quizzes) > 0): ?>
                                        <?php foreach ($table_main_quizzes as $q): ?>
                                            <?php $status = $student_quiz_answers[$q['id']] ?? 'Pending'; ?>
                                            <td class="py-4 px-6 text-center">
                                                <div class="inline-flex items-center justify-center px-3 py-1 rounded-xl text-xs font-semibold shadow-2xs
                                                <?php
                                                if ($status === 'Correct' || $status === 'Completed') {
                                                    echo 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                                } elseif ($status === 'Attempted') {
                                                    echo 'bg-amber-100 text-amber-700 border border-amber-200';
                                                } elseif ($status === 'Incorrect' || $status === 'Failed') {
                                                    echo 'bg-rose-100 text-rose-700 border border-rose-200';
                                                } else {
                                                    echo 'bg-slate-100 text-slate-500 border border-slate-200';
                                                }
                                                ?>">
                                                    Main Topic: <?php echo htmlspecialchars($status); ?>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php foreach ($table_grouped_sub_quizzes as $sub_name => $sub_qs): ?>
                                        <?php
                                        $subtopic_title = 'Subtopic ' . $sub_name . ' Assessment';
                                        $stmt_sub_result = $pdo->prepare("
                                            SELECT score, status
                                            FROM student_assessments
                                            WHERE student_id = ?
                                              AND title = ?
                                              AND type = 'Quiz'
                                            ORDER BY id DESC
                                            LIMIT 1
                                        ");
                                        $stmt_sub_result->execute([$student['id'], $subtopic_title]);
                                        $sub_result = $stmt_sub_result->fetch(PDO::FETCH_ASSOC);
                                        if ($sub_result) {
                                            $status = $sub_result['status'];
                                            $score_text = $sub_result['score'];
                                        } else {
                                            $status = 'Not Attempt';
                                            $score_text = '';
                                        }
                                        ?>
                                        <td class="py-4 px-6 text-center">
                                            <div class="inline-flex flex-col items-center justify-center px-3 py-2 rounded-xl text-xs font-semibold shadow-2xs
                                            <?php
                                            if ($status === 'Completed' || $status === 'Mastered') {
                                                echo 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                            } elseif ($status === 'Incorrect' || $status === 'Failed') {
                                                echo 'bg-rose-100 text-rose-700 border border-rose-200';
                                            } elseif ($status === 'Available') {
                                                echo 'bg-slate-100 text-slate-500 border border-slate-200';
                                            } else {
                                                echo 'bg-amber-100 text-amber-700 border border-amber-200';
                                            }
                                            ?>">
                                                <span><?php echo htmlspecialchars($status); ?></span>
                                                <?php if ($score_text !== ''): ?>
                                                    <span class="text-[10px] mt-0.5 opacity-80"><?php echo htmlspecialchars($score_text); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            <?php echo $chapter_percentage; ?>%
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button type="button"
                                            onclick="openFeedbackModal(
                                                <?php echo $student['id']; ?>,
                                                '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($table_chapter, ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($quiz_filter, ENT_QUOTES); ?>'
                                            )"
                                            title="Give Feedback"
                                            class="px-2.5 h-8 bg-white hover:bg-pastel-badge text-pastel-text hover:text-pastel-hover border border-blue-100 rounded-lg flex items-center justify-center transition shadow-sm text-xs font-semibold">
                                            Feedback
                                        </button>
                                        <a href="upload_resource.php?student_id=<?php echo htmlspecialchars($student['id']); ?>"
                                            title="Upload Extra Resource"
                                            class="w-8 h-8 bg-white hover:bg-pastel-badge text-pastel-text hover:text-pastel-hover border border-blue-100 rounded-lg flex items-center justify-center transition shadow-sm font-bold text-base">
                                            +
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Filter Pop-up Modal Container -->
    <div id="filterModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
        <div
            class="bg-white rounded-2xl shadow-xl border border-blue-100 max-w-md w-full overflow-hidden transform transition-all">
            <div class="p-4 border-b border-blue-100 flex justify-between items-center bg-pastel-bg/50">
                <h3 class="text-sm font-bold text-pastel-text">Filter Options</h3>
                <button type="button" onclick="closeFilterModal()"
                    class="text-slate-400 hover:text-slate-600 font-bold text-base px-2 py-1 rounded-lg">&times;</button>
            </div>
            <form method="GET" class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                <?php if (isset($_GET['overview_chapter'])): ?>
                    <input type="hidden" name="overview_chapter" value="<?php echo htmlspecialchars($_GET['overview_chapter']); ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Select Chapter:</label>
                    <div class="space-y-1.5 bg-pastel-bg/30 p-3 rounded-xl border border-blue-50">
                        <?php foreach ($chapters as $ch): 
                            $isChapterChecked = ($table_chapter === $ch) ? 'checked' : '';
                        ?>
                            <label class="flex items-center space-x-2.5 text-xs font-semibold text-pastel-text cursor-pointer">
                                <input type="radio" name="table_chapter" value="<?php echo htmlspecialchars($ch); ?>" <?php echo $isChapterChecked; ?>
                                    onchange="this.form.submit()"
                                    class="border-blue-200 text-pastel-primary focus:ring-pastel-primary w-4 h-4">
                                <span><?php echo htmlspecialchars($ch); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Select Topic / Subtopic:</label>
                    <div class="space-y-3 bg-pastel-bg/30 p-3 rounded-xl border border-blue-50 max-h-48 overflow-y-auto">
                        <label class="flex items-center space-x-2.5 text-xs font-semibold text-pastel-text cursor-pointer">
                            <input type="radio" name="quiz_filter" value="" <?php echo empty($_GET['quiz_filter']) ? 'checked' : ''; ?>
                                class="border-blue-200 text-pastel-primary focus:ring-pastel-primary w-4 h-4">
                            <span>All Topics (Default)</span>
                        </label>
<?php
$stmt_distinct_subs = $pdo->prepare("
    SELECT DISTINCT subtopic_name
    FROM chapter_quizzes
    WHERE chapter_name = ?
      AND subtopic_name IS NOT NULL
      AND subtopic_name != ''
    ORDER BY subtopic_name ASC
");
$stmt_distinct_subs->execute([$table_chapter]);
$all_subtopics = $stmt_distinct_subs->fetchAll(PDO::FETCH_COLUMN);
?>

<?php foreach ($all_subtopics as $subtopic): 
    $isQuizChecked = (
        isset($_GET['quiz_filter']) &&
        $_GET['quiz_filter'] === $subtopic
    ) ? 'checked' : '';
?>
    <label class="flex items-center space-x-2.5 text-xs font-semibold text-pastel-text cursor-pointer pt-1">
        <input
            type="radio"
            name="quiz_filter"
            value="<?php echo htmlspecialchars($subtopic); ?>"
            <?php echo $isQuizChecked; ?>
            class="border-blue-200 text-pastel-primary focus:ring-pastel-primary w-4 h-4">

        <span><?php echo htmlspecialchars($subtopic); ?></span>
    </label>
<?php endforeach; ?>

                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5">Select Statuses:</label>
                    <div class="space-y-2 bg-pastel-bg/30 p-3 rounded-xl border border-blue-50">
                        <?php 
$available_statuses = ['Mastering', 'On Track', 'Struggling', 'Not Attempted'];
                        foreach ($available_statuses as $st):
                            $isChecked = in_array($st, $status_filter) ? 'checked' : '';
                        ?>
                            <label class="flex items-center space-x-2.5 text-xs font-semibold text-pastel-text cursor-pointer">
                                <input type="checkbox" name="status_filter[]" value="<?php echo $st; ?>" <?php echo $isChecked; ?>
                                    class="rounded border-blue-200 text-pastel-primary focus:ring-pastel-primary w-4 h-4">
                                <span><?php echo $st; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <a href="classroom.php?id=<?php echo $class_id; ?>"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">
                        Clear All Filters
                    </a>
                    <div class="space-x-2">
                        <button type="button" onclick="closeFilterModal()"
                            class="text-xs font-semibold px-3.5 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Cancel</button>
                        <button type="submit"
                            class="text-xs font-semibold px-4 py-2 rounded-xl bg-pastel-primary text-white hover:bg-pastel-hover transition">Apply Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openFilterModal() {
            const modal = document.getElementById('filterModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeFilterModal() {
            const modal = document.getElementById('filterModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>

    <!-- Global AI Summary Modal Container -->
    <div id="globalAiSummaryModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
        <div
            class="bg-white rounded-2xl shadow-xl border border-blue-100 max-w-lg w-full overflow-hidden transform transition-all">
            <div class="p-5 border-b border-blue-100 flex justify-between items-center bg-pastel-bg/50">
                <div>
                    <h3 class="text-sm font-bold text-pastel-text">AI Performance Summary</h3>
                </div>
                <button onclick="closeGlobalAiSummaryModal()"
                    class="text-slate-400 hover:text-slate-600 font-bold text-base px-2 py-1 rounded-lg">
                    &times;
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="aiSummaryStudent" class="block text-xs font-semibold text-slate-500 mb-1">Target Student</label>
                        <select id="aiSummaryStudent"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">
                            <option value="all">All Students (Class Overview)</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="aiSummaryDate" class="block text-xs font-semibold text-slate-500 mb-1">Benchmark Date</label>
                        <input type="date" id="aiSummaryDate" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">
                    </div>
                </div>

                <div id="aiSummaryLoading" class="hidden text-center py-6 text-xs text-slate-400">
                    Analyzing records and generating summary metrics...
                </div>

                <div id="aiSummaryResultContainer"
                    class="hidden text-xs text-slate-600 leading-relaxed space-y-3 bg-pastel-bg/30 p-4 rounded-xl border border-blue-50 max-h-60 overflow-y-auto">
                </div>
            </div>

            <div class="p-4 border-t border-blue-100 flex justify-end space-x-2 bg-pastel-bg/20">
                <button onclick="closeGlobalAiSummaryModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button onclick="generateAiSummary()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-pastel-primary text-white hover:bg-pastel-hover transition">
                    Generate Summary
                </button>
            </div>
        </div>
    </div>

    <script>
        function openGlobalAiSummaryModal() {
            const modal = document.getElementById('globalAiSummaryModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('aiSummaryResultContainer').classList.add('hidden');
            document.getElementById('aiSummaryLoading').classList.add('hidden');
        }

        function closeGlobalAiSummaryModal() {
            const modal = document.getElementById('globalAiSummaryModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function generateAiSummary() {
            const studentSelect = document.getElementById('aiSummaryStudent');
            const selectedTarget = studentSelect.value;
            const summaryDate = document.getElementById('aiSummaryDate').value;
            const classroomId = <?php echo $class_id; ?>;

            const loadingEl = document.getElementById('aiSummaryLoading');
            const resultEl = document.getElementById('aiSummaryResultContainer');

            loadingEl.classList.remove('hidden');
            resultEl.classList.add('hidden');

            fetch(`../tools/get_ai_summary.php?student_id=${selectedTarget}&date=${summaryDate}&classroom_id=${classroomId}`)
                .then(response => response.text())
                .then(data => {
                    loadingEl.classList.add('hidden');
                    resultEl.classList.remove('hidden');
                    resultEl.innerHTML = data;
                })
                .catch(error => {
                    loadingEl.classList.add('hidden');
                    resultEl.classList.remove('hidden');
                    resultEl.innerHTML = `<p class="text-rose-600">Failed to generate AI summary. Please check your connection or API configurations.</p>`;
                });
        }
    </script>

<!-- Pop-up Quiz Feedback Container -->
<div id="quickFeedbackModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-50 p-4">

    <div
        class="bg-white rounded-2xl shadow-xl border border-blue-100 max-w-md w-full overflow-hidden transform transition-all">

        <div class="p-4 border-b border-blue-100 flex justify-between items-center bg-pastel-bg/50">
            <div>
                <h3 class="text-sm font-bold text-pastel-text">
                    Quiz Feedback
                </h3>

                <p class="text-[10px] text-slate-400 mt-0.5">
                    Give feedback for a specific quiz/subtopic
                </p>
            </div>

            <button type="button"
                onclick="closeFeedbackModal()"
                class="text-slate-400 hover:text-slate-600 font-bold text-base px-2 py-1 rounded-lg">
                &times;
            </button>
        </div>

        <form method="POST"
            action="../tools/save_quiz_feedback.php"
            class="p-5 space-y-4">
<input type="hidden"
    name="classroom_id"
    value="<?php echo $class_id; ?>">

<input type="hidden"
    name="student_id"
    id="feedbackStudentId">

<input type="hidden"
    name="chapter_name"
    id="feedbackChapter">

<!-- Student -->
<div>
    <label class="block text-xs font-semibold text-slate-500 mb-1.5">
        Student
    </label>

    <input type="text"
        id="feedbackStudentDisplay"
        readonly
        class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-slate-50 text-slate-600 font-semibold">
</div>

<!-- Chapter -->
<div>
    <label class="block text-xs font-semibold text-slate-500 mb-1.5">
        Chapter
    </label>

    <input type="text"
        id="feedbackChapterDisplay"
        readonly
        class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-slate-50 text-slate-600 font-semibold">
</div>

            <!-- Quiz / Subtopic -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">
                    Select Topic / Subtopic
                </label>

                <select name="subtopic_name"
                    id="feedbackSubtopic"
                    required
                    class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">

                    <option value="">Select a quiz</option>

                    <?php
                    $stmt_feedback_quizzes = $pdo->prepare("
                        SELECT DISTINCT subtopic_name
                        FROM chapter_quizzes
                        WHERE chapter_name = ?
                          AND subtopic_name IS NOT NULL
                          AND subtopic_name != ''
                        ORDER BY subtopic_name ASC
                    ");

                    $stmt_feedback_quizzes->execute([$table_chapter]);

                    $feedback_quizzes = $stmt_feedback_quizzes->fetchAll(PDO::FETCH_COLUMN);
                    ?>

                    <?php foreach ($feedback_quizzes as $feedback_quiz): ?>
                        <option value="<?php echo htmlspecialchars($feedback_quiz); ?>">
                            <?php echo htmlspecialchars($feedback_quiz); ?> Quiz
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <!-- Feedback -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">
                    Teacher Remarks / Advice
                </label>

                <textarea
                    name="teacher_feedback"
                    rows="4"
                    required
                    placeholder="Write feedback for this quiz/subtopic..."
                    class="w-full text-xs p-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary"></textarea>
            </div>

            <div class="flex justify-end space-x-2">

                <button type="button"
                    onclick="closeFeedbackModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                    Cancel
                </button>

                <button type="submit"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-pastel-primary text-white hover:bg-pastel-hover transition">
                    Save Feedback
                </button>

            </div>

        </form>
    </div>
</div>

<script>
function openFeedbackModal(studentId, studentName, chapterName, subtopicName) {

    document.getElementById('feedbackStudentId').value = studentId;
    document.getElementById('feedbackStudentDisplay').value = studentName;

    document.getElementById('feedbackChapter').value = chapterName;
    document.getElementById('feedbackChapterDisplay').value = chapterName;

    const subtopicSelect = document.getElementById('feedbackSubtopic');

    if (subtopicName && subtopicName.trim() !== '') {
        subtopicSelect.value = subtopicName;
    } else {
        subtopicSelect.value = '';
    }

    const modal = document.getElementById('quickFeedbackModal');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeFeedbackModal() {
    const modal = document.getElementById('quickFeedbackModal');

    modal.classList.remove('flex');
    modal.classList.add('hidden');
}
</script>

</body>
</html>