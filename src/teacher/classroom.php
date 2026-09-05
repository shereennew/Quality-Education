<?php
// classroom.php
session_start();
require_once __DIR__ . '/../config/db.php';

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Ensure classroom_chapters table exists for global chapter locking/unlocking
$pdo->exec("CREATE TABLE IF NOT EXISTS classroom_chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    chapter_name VARCHAR(100) NOT NULL,
    is_unlocked TINYINT DEFAULT 0
)");

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

// Fetch dynamic chapters from chapter_materials if available, fallback to default chapters
$stmt_all_chapters = $pdo->query("SELECT DISTINCT chapter_name FROM chapter_materials");
$db_chapters = $stmt_all_chapters->fetchAll(PDO::FETCH_COLUMN);
$chapters = !empty($db_chapters) ? $db_chapters : ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];

// Fetch global chapter unlock statuses for this classroom
$unlocked_chapters = [];
foreach ($chapters as $index => $ch_name) {
    $stmt_uc = $pdo->prepare("SELECT is_unlocked FROM classroom_chapters WHERE classroom_id = ? AND chapter_name = ?");
    $stmt_uc->execute([$class_id, $ch_name]);
    $val = $stmt_uc->fetchColumn();
    // Default: First chapter unlocked by default, others locked
    $unlocked_chapters[$ch_name] = ($val !== false) ? intval($val) : ($index === 0 ? 1 : 0);
}

// Fetch students for this classroom
$stmt_students = $pdo->prepare("SELECT * FROM students WHERE classroom_id = ?");
$stmt_students->execute([$class_id]);
$students_raw = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

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

        // Check if this chapter is globally unlocked by the teacher
        $isUnlocked = $unlocked_chapters[$ch_name] ?? 0;

        // Only include unlocked chapters in the summary calculation
        if ($isUnlocked) {
            $total_earned_score += $student_levels[$ch_name];

            // Get max possible levels/quizzes for this specific chapter
            $stmt_max = $pdo->prepare("SELECT COUNT(*) FROM chapter_quizzes WHERE chapter_name = ?");
            $stmt_max->execute([$ch_name]);
            $ch_max = max(1, $stmt_max->fetchColumn());

            $max_possible_score += $ch_max;
        }
    }

    // Calculate completion percentage based strictly on unlocked chapters
    $percentage = $max_possible_score > 0 ? round(($total_earned_score / $max_possible_score) * 100) : 0;

    $students[] = [
        "id" => $s['id'],
        "name" => $s['name'],
        "status" => $s['status'],
        "score" => $s['score'] ?? 0,
        "progress" => $student_levels,
        "summary" => $percentage . "%"
    ];
}

// Selected chapter for overview circle tracker graph
$overview_chapter = $_GET['overview_chapter'] ?? $chapters[0];

// Fetch class average progress level for the selected overview chapter
$stmt_avg = $pdo->prepare("
    SELECT AVG(sp.level) as avg_lvl 
    FROM student_progress sp 
    JOIN students s ON sp.student_id = s.id 
    WHERE sp.chapter_name = ? AND s.classroom_id = ?
");
$stmt_avg->execute([$overview_chapter, $class_id]);
$avg_level = $stmt_avg->fetchColumn() ?: 0;

// Dynamically fetch max quizzes/levels for the selected overview chapter to calculate circle percentage accurately
$stmt_max_overview = $pdo->prepare("SELECT COUNT(*) FROM chapter_quizzes WHERE chapter_name = ?");
$stmt_max_overview->execute([$overview_chapter]);
$overview_max_scale = max(1, $stmt_max_overview->fetchColumn());
$overview_percentage = min(100, round(($avg_level / $overview_max_scale) * 100));

// Render badge depending on whether chapter is globally unlocked and student progress
function renderChapterBadge($level, $step_num, $is_globally_unlocked)
{
    if (!$is_globally_unlocked) {
        return '<div class="w-8 h-8 mx-auto bg-slate-100 text-slate-400 font-bold text-xs rounded-lg flex items-center justify-center border border-slate-200" title="Chapter Locked by Teacher">🔒</div>';
    }

    switch ($level) {
        case 3:
            return '<div class="w-8 h-8 mx-auto bg-emerald-500 text-white font-bold text-xs rounded-lg flex items-center justify-center shadow-sm" title="Finished">' . $step_num . '</div>';
        case 2:
            return '<div class="w-8 h-8 mx-auto bg-amber-400 text-slate-900 font-bold text-xs rounded-lg flex items-center justify-center shadow-sm" title="Half Done">' . $step_num . '</div>';
        case 1:
            return '<div class="w-8 h-8 mx-auto bg-rose-500 text-white font-bold text-xs rounded-lg flex items-center justify-center shadow-sm" title="In Progress">' . $step_num . '</div>';
        default:
            return '<div class="w-8 h-8 mx-auto bg-white text-pastel-primary font-bold text-xs rounded-lg flex items-center justify-center border border-blue-200 shadow-sm" title="Unlocked - Not Started">0</div>';
    }
}
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

                <!-- Circular Progress Visualizer Container (Scaled Down) -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-pastel-text">Class Skill Distribution Overview</h2>
                        </div>

                        <form method="GET" class="w-full sm:w-auto">
                            <input type="hidden" name="id" value="<?php echo $class_id; ?>">
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
                        <!-- SVG Circular Ring (Smaller: w-40 h-40, radius 68) -->
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
                                    class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Done</span>
                            </div>
                        </div>

                        <!-- Metric Details Breakdown -->
                        <div class="space-y-2 text-center sm:text-left">
                            <h3 class="text-xs font-bold text-pastel-text">
                                <?php echo htmlspecialchars($overview_chapter); ?>
                            </h3>
                            <p class="text-[11px] text-slate-500 max-w-xs">
                                Average class progress score stands at <span
                                    class="font-bold text-pastel-text"><?php echo round($avg_level, 1); ?> /
                                    <?php echo $overview_max_scale; ?></span> levels completed.
                            </p>
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

                <!-- Student Rank / Leaderboard Card (Compact) -->
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
                                $stmt_rank = $pdo->prepare("SELECT name, score FROM students WHERE classroom_id = ? ORDER BY score ASC LIMIT 5");
                                $stmt_rank->execute([$class_id]);
                                $rankings = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);
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
                                            class="bg-rose-50 text-rose-700 font-bold px-1.5 py-0.5 rounded border border-rose-100"><?php echo intval($student['score']); ?>
                                            pts</span>
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

                    <div class="mt-3 pt-2 border-t border-blue-100/60 text-center">
                        <a href="students_overview.php"
                            class="text-[11px] font-semibold text-pastel-hover hover:underline">View All Students
                            &rarr;</a>
                    </div>
                </div>

            </div>
        </section>

        <!-- Global Chapter Control Panel -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Classroom Chapter Access Control</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Click any chapter to view its details and
                        materials.</p>
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
                                <?php echo $isUnlocked ? '🔓 Unlocked' : '🔒 Locked'; ?>
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

        <!-- Student Progress Table Section -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <?php
            // Selected chapter for the student progress table dropdown
            $table_chapter = $_GET['table_chapter'] ?? $chapters[0];

            // Dynamically fetch all quizzes/steps belonging to this selected chapter
            $stmt_quizzes = $pdo->prepare("SELECT id FROM chapter_quizzes WHERE chapter_name = ? ORDER BY id ASC");
            $stmt_quizzes->execute([$table_chapter]);
            $chapter_quizzes = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);
            $total_quizzes = count($chapter_quizzes);
            ?>
            <div
                class="p-6 border-b border-blue-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Student Progress Matrix</h2>
                </div>

                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <!-- Chapter Dropdown Selector -->
                    <form method="GET" class="flex items-center space-x-2 w-full sm:w-auto">
                        <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                        <?php if (isset($_GET['overview_chapter'])): ?>
                            <input type="hidden" name="overview_chapter"
                                value="<?php echo htmlspecialchars($_GET['overview_chapter']); ?>">
                        <?php endif; ?>
                        <label for="table_chapter"
                            class="text-xs font-semibold text-slate-500 shrink-0">Chapter:</label>
                        <select name="table_chapter" id="table_chapter" onchange="this.form.submit()"
                            class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">
                            <?php foreach ($chapters as $ch): ?>
                                <option value="<?php echo htmlspecialchars($ch); ?>" <?php echo ($table_chapter === $ch) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ch); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- AI Summary Trigger Button -->
                    <button onclick="openGlobalAiSummaryModal()"
                        class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-white hover:bg-pastel-badge font-semibold text-pastel-text transition shadow-sm flex items-center space-x-1.5 shrink-0">
                        <span>✨ AI Summary</span>
                    </button>

                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">Student Name</th>
                            <th class="py-3.5 px-6">Status</th>
                            <?php if ($total_quizzes > 0): ?>
                                <?php foreach ($chapter_quizzes as $index => $quiz): ?>
                                    <th class="py-3.5 px-6 text-center">Quiz <?php echo $index + 1; ?></th>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <th class="py-3.5 px-6 text-center">Quiz Data</th>
                            <?php endif; ?>
                            <th class="py-3.5 px-6 text-center">Chapter Progress</th>
                            <th class="py-3.5 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 text-sm">
                        <?php foreach ($students as $student): ?>
                            <?php
                            // Check if chapter is globally unlocked
                            $isUnlocked = $unlocked_chapters[$table_chapter] ?? 0;

                            // Fetch individual student quiz answers/status for this specific chapter from database
                            $student_quiz_answers = [];
                            $completed_quiz_count = 0;

                            if ($isUnlocked && $total_quizzes > 0) {
                                foreach ($chapter_quizzes as $q) {
                                    $stmt_ans = $pdo->prepare("SELECT answer_status, score FROM student_quiz_answers WHERE student_id = ? AND quiz_id = ?");
                                    $stmt_ans->execute([$student['id'], $q['id']]);
                                    $ans_data = $stmt_ans->fetch(PDO::FETCH_ASSOC);

                                    $student_quiz_answers[$q['id']] = $ans_data ? $ans_data['answer_status'] : 'Not Attempted';
                                    if ($ans_data && ($ans_data['answer_status'] === 'Correct' || $ans_data['answer_status'] === 'Completed')) {
                                        $completed_quiz_count++;
                                    }
                                }
                            }

                            // Calculate chapter-specific progress percentage
                            $chapter_percentage = ($total_quizzes > 0) ? round(($completed_quiz_count / $total_quizzes) * 100) : 0;
                            ?>
                            <tr class="hover:bg-pastel-bg/50 transition">
                                <td class="py-4 px-6 font-medium text-pastel-text">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <span
                                        class="text-xs font-medium px-3 py-1 rounded-full <?php echo in_array($student['status'], ['Struggling', 'Needs Help']) ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-pastel-badge text-pastel-hover'; ?>">
                                        <?php echo $student['status']; ?>
                                    </span>
                                </td>

                                <?php if (!$isUnlocked): ?>
                                    <!-- If chapter is locked, span across quiz columns -->
                                    <td colspan="<?php echo max(1, $total_quizzes); ?>"
                                        class="py-4 px-6 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                        🔒 Chapter Locked by Teacher
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-slate-400">
                                        0%
                                    </td>
                                <?php elseif ($total_quizzes === 0): ?>
                                    <td class="py-4 px-6 text-center text-slate-400 text-xs italic">
                                        No quizzes configured for this chapter.
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-pastel-text">
                                        0%
                                    </td>
                                <?php else: ?>
                                    <?php foreach ($chapter_quizzes as $q): ?>
                                        <?php $status = $student_quiz_answers[$q['id']] ?? 'Pending'; ?>
                                        <td class="py-4 px-6 text-center">
                                            <div class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl text-xs font-semibold shadow-2xs
                                            <?php
                                            if ($status === 'Correct' || $status === 'Completed') {
                                                echo 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                            } elseif ($status === 'Incorrect' || $status === 'Failed') {
                                                echo 'bg-rose-100 text-rose-700 border border-rose-200';
                                            } else {
                                                echo 'bg-slate-100 text-slate-500 border border-slate-200';
                                            }
                                            ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>

                                    <!-- Chapter Progress Column -->
                                    <td class="py-4 px-6 text-center">
                                        <span
                                            class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            <?php echo $chapter_percentage; ?>%
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center space-x-2">

                                        <!-- Quick Feedback Trigger Button -->
                                        <button type="button"
                                            onclick="openFeedbackModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>')"
                                            title="Give Feedback"
                                            class="px-2.5 h-8 bg-white hover:bg-pastel-badge text-pastel-text hover:text-pastel-hover border border-blue-100 rounded-lg flex items-center justify-center transition shadow-sm text-xs font-semibold">
                                            💬 Feedback
                                        </button>

                                        <!-- Extra Resource Upload Button -->
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

    <!-- Global AI Summary Modal Container -->
    <div id="globalAiSummaryModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
        <div
            class="bg-white rounded-2xl shadow-xl border border-blue-100 max-w-lg w-full overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="p-5 border-b border-blue-100 flex justify-between items-center bg-pastel-bg/50">
                <div>
                    <h3 class="text-sm font-bold text-pastel-text">AI Performance Summary</h3>
                </div>
                <button onclick="closeGlobalAiSummaryModal()"
                    class="text-slate-400 hover:text-slate-600 font-bold text-base px-2 py-1 rounded-lg">
                    &times;
                </button>
            </div>

            <!-- Modal Body / Parameters Form -->
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Target Selector -->
                    <div>
                        <label for="aiSummaryStudent" class="block text-xs font-semibold text-slate-500 mb-1">Target
                            Student</label>
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

                    <!-- Date Selector -->
                    <div>
                        <label for="aiSummaryDate" class="block text-xs font-semibold text-slate-500 mb-1">Benchmark
                            Date</label>
                        <input type="date" id="aiSummaryDate" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary">
                    </div>
                </div>

                <!-- Loading Spinner Container -->
                <div id="aiSummaryLoading" class="hidden text-center py-6 text-xs text-slate-400">
                    Analyzing records and generating summary metrics...
                </div>

                <!-- Result Box -->
                <div id="aiSummaryResultContainer"
                    class="hidden text-xs text-slate-600 leading-relaxed space-y-3 bg-pastel-bg/30 p-4 rounded-xl border border-blue-50 max-h-60 overflow-y-auto">
                    <!-- Dynamic summary text injected via JS -->
                </div>
            </div>

            <!-- Modal Footer -->
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

            // Fetch dynamically from our separated backend handler
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

    <!-- Pop-up Feedback Container -->
    <div id="quickFeedbackModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
        <div
            class="bg-white rounded-2xl shadow-xl border border-blue-100 max-w-md w-full overflow-hidden transform transition-all">
            <div class="p-4 border-b border-blue-100 flex justify-between items-center bg-pastel-bg/50">
                <h3 class="text-sm font-bold text-pastel-text">Feedback for <span id="feedbackStudentName"></span></h3>
                <button type="button" onclick="closeFeedbackModal()"
                    class="text-slate-400 hover:text-slate-600 font-bold text-base px-2 py-1 rounded-lg">&times;</button>
            </div>
            <form method="POST" action="../tools/save_feedback.php" class="p-5 space-y-4">
                <input type="hidden" name="student_id" id="feedbackStudentId">
                <input type="hidden" name="classroom_id" value="<?php echo $class_id; ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Teacher Remarks / Advice</label>
                    <textarea name="teacher_feedback" rows="3" required placeholder="Write personalized feedback..."
                        class="w-full text-xs p-2.5 rounded-xl border border-blue-100 bg-pastel-bg/40 focus:outline-none focus:border-pastel-primary"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeFeedbackModal()"
                        class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Cancel</button>
                    <button type="submit"
                        class="text-xs font-semibold px-4 py-2 rounded-xl bg-pastel-primary text-white hover:bg-pastel-hover transition">Save
                        Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openFeedbackModal(studentId, studentName) {
            document.getElementById('feedbackStudentId').value = studentId;
            document.getElementById('feedbackStudentName').innerText = studentName;
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