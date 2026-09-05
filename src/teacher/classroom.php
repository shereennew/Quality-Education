<?php
// classroom.php
session_start();
require_once __DIR__ . '/../config/db.php';

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch classroom details
$stmt_class = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmt_class->execute([$class_id]);
$current_class = $stmt_class->fetch(PDO::FETCH_ASSOC);

if (!$current_class) {
    die("Classroom not found.");
}

// Fetch students for this classroom
$stmt_students = $pdo->prepare("SELECT * FROM students WHERE classroom_id = ?");
$stmt_students->execute([$class_id]);
$students_raw = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

$chapters = ["Foundation (Ch 1)", "Intermediate (Ch 2)", "Advanced (Ch 3)"];
$students = [];

foreach ($students_raw as $s) {
    $stmt_prog = $pdo->prepare("SELECT level FROM student_progress WHERE student_id = ? ORDER BY id ASC");
    $stmt_prog->execute([$s['id']]);
    $levels = $stmt_prog->fetchAll(PDO::FETCH_COLUMN);

    // Calculate completion percentage based on levels
    $total_score = array_sum(array_map('intval', $levels));
    $max_score = count($chapters) * 3;
    $percentage = $max_score > 0 ? round(($total_score / $max_score) * 100) : 0;

    $students[] = [
        "id" => $s['id'],
        "name" => $s['name'],
        "status" => $s['status'],
        "progress" => array_map('intval', $levels),
        "summary" => $percentage . "%"
    ];
}

// Aggregate chart data dynamically from Database
$chart_data = [];
$chapter_names_db = ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];
foreach ($chapter_names_db as $chapter_name) {
    $stmt_ch = $pdo->prepare("
        SELECT sp.level, COUNT(*) as count 
        FROM student_progress sp 
        JOIN students s ON sp.student_id = s.id 
        WHERE s.classroom_id = ? AND sp.chapter_name = ? 
        GROUP BY sp.level
    ");
    $stmt_ch->execute([$class_id, $chapter_name]);
    $res = $stmt_ch->fetchAll(PDO::FETCH_KEY_PAIR); // level => count

    $chart_data['foundation'][] = intval($res[1] ?? 0);
    $chart_data['intermediate'][] = intval($res[2] ?? 0);
    $chart_data['advanced'][] = intval($res[3] ?? 0);
}

// Level Badge matching LMS color guidelines
function getLevelBadge($level, $step_num)
{
    switch ($level) {
        case 3: // Finished (Green)
            return '<div class="w-8 h-8 mx-auto bg-emerald-500 text-white font-bold text-xs rounded-lg flex items-center justify-center shadow-sm">' . $step_num . '</div>';
        case 2: // Half done (Yellow)
            return '<div class="w-8 h-8 mx-auto bg-amber-400 text-slate-900 font-bold text-xs rounded-lg flex items-center justify-center shadow-sm">' . $step_num . '</div>';
        case 1: // Low progress (Red)
            return '<div class="w-8 h-8 mx-auto bg-rose-500 text-white font-bold text-xs rounded-lg flex items-center justify-center shadow-sm">' . $step_num . '</div>';
        default: // Locked / Not started (Grey)
            return '<div class="w-8 h-8 mx-auto bg-slate-100 text-slate-400 font-bold text-xs rounded-lg flex items-center justify-center border border-slate-200">🔒</div>';
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
                            bg: '#f0f4f9',       // Very soft pastel blue background
                            card: '#ffffff',     // Card background
                            nav: '#e1e9f5',      // Soft blue for navbar
                            primary: '#7da0ca',  // Main pastel blue accent
                            hover: '#688dbb',    // Darker pastel hover state
                            text: '#2c3e50',     // Deep slate text for contrast
                            light: '#f8fafc',    // Ultra light container
                            badge: '#cbe0f5'     // Light pastel highlight tag
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <!-- Clean Pastel Navbar -->
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
            <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                📊 Live Progress View
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-6 flex-1 w-full">

        <!-- Overall Summary Graph Section -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold text-pastel-text">Class Skill Distribution Overview</h2>
                <span class="text-xs text-slate-400">Real-time database analytics</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2 h-64">
                    <canvas id="classProgressChart"></canvas>
                </div>
                <div class="space-y-4 border-l pl-6 border-blue-100">
                    <div class="bg-rose-50 border-l-4 border-rose-400 p-3.5 rounded-r-xl">
                        <p class="text-xs font-bold text-rose-700 uppercase">Class Bottleneck</p>
                        <p class="text-xs text-rose-900 mt-1 leading-relaxed">Check chapter breakdowns below to spot
                            struggling students instantly and adapt instruction.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Student Progress Table Section -->
        <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="p-6 border-b border-blue-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-pastel-text">Progress Tracker Matrix</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Color codes: Red (Low), Yellow (Half), Green (Finished)</p>
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
                            <?php foreach ($chapters as $index => $ch): ?>
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
                                <?php foreach ($student['progress'] as $i => $level): ?>
                                    <td class="py-4 px-6 text-center">
                                        <?php echo getLevelBadge($level, $i + 1); ?>
                                    </td>
                                <?php endforeach; ?>
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

    <!-- Chart Configuration Script -->
    <script>
        const ctx = document.getElementById('classProgressChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Fractions (Ch 1)', 'Decimals (Ch 2)', 'Percentages (Ch 3)'],
                datasets: [
                    {
                        label: 'Low Progress (1)',
                        data: <?php echo json_encode($chart_data['foundation']); ?>,
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    },
                    {
                        label: 'Half Done (2)',
                        data: <?php echo json_encode($chart_data['intermediate']); ?>,
                        backgroundColor: '#fbbf24',
                        borderRadius: 4,
                    },
                    {
                        label: 'Advanced/Finished (3)',
                        data: <?php echo json_encode($chart_data['advanced']); ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    </script>
</body>

</html>