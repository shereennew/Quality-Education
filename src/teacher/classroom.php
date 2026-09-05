<?php
// classroom.php
session_start();
require_once __DIR__ . '/../config/db.php';

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Ensure classroom_chapters table exists for global chapter locking/unlocking[cite: 4]
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
    foreach ($chapters as $ch_name) {
        $stmt_prog = $pdo->prepare("SELECT level FROM student_progress WHERE student_id = ? AND chapter_name = ?");
        $stmt_prog->execute([$s['id'], $ch_name]);
        $lvl = $stmt_prog->fetchColumn();
        $student_levels[$ch_name] = $lvl !== false ? intval($lvl) : 0;
    }

    // Calculate completion percentage based on levels
    $total_score = array_sum($student_levels);
    $max_score = count($chapters) * 3;
    $percentage = $max_score > 0 ? round(($total_score / $max_score) * 100) : 0;

    $students[] = [
        "id" => $s['id'],
        "name" => $s['name'],
        "status" => $s['status'],
        "score" => $s['score'] ?? 0,
        "progress" => $student_levels,
        "summary" => $percentage . "%"
    ];
}

// Aggregate chart data dynamically from Database
$chart_data = ['foundation' => [], 'intermediate' => [], 'advanced' => []];
foreach ($chapters as $chapter_name) {
    $stmt_ch = $pdo->prepare("
        SELECT sp.level, COUNT(*) as count 
        FROM student_progress sp 
        JOIN students s ON sp.student_id = s.id 
        WHERE s.classroom_id = ? AND sp.chapter_name = ? 
        GROUP BY sp.level
    ");
    $stmt_ch->execute([$class_id, $chapter_name]);
    $res = $stmt_ch->fetchAll(PDO::FETCH_KEY_PAIR);

    $chart_data['foundation'][] = intval($res[1] ?? 0);
    $chart_data['intermediate'][] = intval($res[2] ?? 0);
    $chart_data['advanced'][] = intval($res[3] ?? 0);
}

// Helper function to classify student quiz scores based on the Malaysian standard range
function getPerformanceTier($score) {
    if ($score >= 80) {
        return [
            'label' => 'Good',
            'badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-200'
        ];
    } elseif ($score >= 50) {
        return [
            'label' => 'Intermediate',
            'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200'
        ];
    } else {
        return [
            'label' => 'Low',
            'badge_class' => 'bg-rose-50 text-rose-800 border-rose-200'
        ];
    }
}

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <div class="flex items-center space-x-3">
                <a href="reports.php?class_id=<?php echo $class_id; ?>"
                    class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-3.5 py-1.5 rounded-xl transition shadow-sm">
                    📊 Class Reports
                </a>
                <div
                    class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                    🟢 Live Progress View
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-6 flex-1 w-full">

        <!-- Overall Summary Graph & Rankings Section -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                <!-- Chart Container (Takes up 2 columns) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-pastel-text">Class Skill Distribution Overview</h2>
                            <p class="text-xs text-slate-400">Analyzing student progression distribution per tier across
                                all chapters.</p>
                        </div>
                    </div>

                    <div class="h-72 relative">
                        <canvas id="classProgressChart"></canvas>
                    </div>
                </div>

                <!-- Student Rank / Leaderboard Card (Weak Performers Focus) -->
                <div class="bg-pastel-bg/40 rounded-2xl border border-blue-50 p-5 flex flex-col justify-between h-full">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-bold text-pastel-text">Student Rankings</h3>
                            <span
                                class="bg-rose-50 text-rose-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-rose-100">Needs
                                Attention</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-4">Students with lowest scores requiring immediate academic
                            intervention.</p>

                        <div class="space-y-2.5">
                            <?php
                            // Fetch weak performers for this specific classroom, ordered by lowest score first
                            try {
                                $stmt_rank = $pdo->prepare("SELECT name, score FROM students WHERE classroom_id = ? ORDER BY score ASC LIMIT 5");
                                $stmt_rank->execute([$class_id]);
                                $rankings = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {
                                $rankings = [];
                            }
                            ?>

                            <?php if (count($rankings) > 0): ?>
                                <?php foreach ($rankings as $index => $student): 
                                    $tier = getPerformanceTier($student['score']);
                                ?>
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-blue-50 text-xs shadow-2xs">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="font-bold text-rose-500 w-5">#<?php echo $index + 1; ?></span>
                                            <span
                                                class="font-semibold text-pastel-text"><?php echo htmlspecialchars($student['name']); ?></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-slate-500 font-medium"><?php echo $student['score']; ?> pts</span>
                                            <span class="font-bold px-2 py-0.5 rounded-lg border text-[10px] <?php echo $tier['badge_class']; ?>">
                                                <?php echo $tier['label']; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Mock items if database table isn't populated yet -->
                                <?php 
                                    $mock_weak = [
                                        ['name' => 'Elias Thorne', 'score' => 30],
                                        ['name' => 'Carlos Mendez', 'score' => 45],
                                        ['name' => 'Gabe Logan', 'score' => 50]
                                    ];
                                    foreach ($mock_weak as $index => $mw):
                                        $tier = getPerformanceTier($mw['score']);
                                ?>
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-blue-50 text-xs shadow-2xs">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="font-bold text-rose-500 w-5">#<?php echo $index + 1; ?></span>
                                            <span class="font-semibold text-pastel-text"><?php echo $mw['name']; ?></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-slate-500 font-medium"><?php echo $mw['score']; ?> pts</span>
                                            <span class="font-bold px-2 py-0.5 rounded-lg border text-[10px] <?php echo $tier['badge_class']; ?>">
                                                <?php echo $tier['label']; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-blue-100/60 text-center">
                        <a href="students_overview.php"
                            class="text-xs font-semibold text-pastel-hover hover:underline">View All Students &rarr;</a>
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
            <div class="p-6 border-b border-blue-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Student Progress Matrix</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time breakdown of student advancement per chapter.</p>
                </div>
                <div class="flex items-center space-x-4 text-xs font-medium text-slate-600">
                    <span class="flex items-center"><span
                            class="w-2.5 h-2.5 bg-rose-500 rounded-sm inline-block mr-1.5"></span> Low</span>
                    <span class="flex items-center"><span
                            class="w-2.5 h-2.5 bg-amber-400 rounded-sm inline-block mr-1.5"></span> Half</span>
                    <span class="flex items-center"><span
                            class="w-2.5 h-2.5 bg-emerald-500 rounded-sm inline-block mr-1.5"></span> Finished</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-pastel-bg text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-100">
                            <th class="py-3.5 px-6">Student Name</th>
                            <th class="py-3.5 px-6">Status</th>
                            <?php foreach ($chapters as $index => $ch_name): ?>
                                <th class="py-3.5 px-6 text-center">Chapter <?php echo $index + 1; ?></th>
                            <?php endforeach; ?>
                            <th class="py-3.5 px-6 text-center">Summary</th>
                            <th class="py-3.5 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 text-sm">
                        <?php foreach ($students as $student): ?>
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
                                <?php
                                $step = 1;
                                foreach ($chapters as $ch_name):
                                    $lvl = $student['progress'][$ch_name] ?? 0;
                                    $isGlobalActive = $unlocked_chapters[$ch_name];
                                    ?>
                                    <td class="py-4 px-6 text-center">
                                        <?php echo renderChapterBadge($lvl, $step, $isGlobalActive); ?>
                                    </td>
                                    <?php
                                    $step++;
                                endforeach;
                                ?>
                                <td class="py-4 px-6 text-center font-bold text-pastel-text">
                                    <?php echo $student['summary']; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <a href="upload_resource.php?student_id=<?php echo htmlspecialchars($student['id']); ?>"
                                        title="Upload Extra Resource"
                                        class="w-8 h-8 mx-auto bg-white hover:bg-pastel-badge text-pastel-text hover:text-pastel-hover border border-blue-100 rounded-lg flex items-center justify-center transition shadow-sm font-bold text-base">
                                        +
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <script>
        const chartLabels = <?php echo json_encode($chapters); ?>;
        const foundationData = <?php echo json_encode($chart_data['foundation']); ?>;
        const intermediateData = <?php echo json_encode($chart_data['intermediate']); ?>;
        const advancedData = <?php echo json_encode($chart_data['advanced']); ?>;

        const ctx = document.getElementById('classProgressChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Low Progress (1)',
                        data: foundationData,
                        backgroundColor: '#f43f5e',
                        borderRadius: 4
                    },
                    {
                        label: 'Half Done (2)',
                        data: intermediateData,
                        backgroundColor: '#fbbf24',
                        borderRadius: 4
                    },
                    {
                        label: 'Advanced/Finished (3)',
                        data: advancedData,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } }
                }
            }
        });
    </script>
</body>

</html>