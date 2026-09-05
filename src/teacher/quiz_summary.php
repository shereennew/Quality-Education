<?php
// quiz_summary.php
session_start();
require_once __DIR__ . '/../config/db.php';

$class_id = isset($_GET['classroom_id']) ? intval($_GET['classroom_id']) : 1;
$student_filter = isset($_GET['student_filter']) ? intval($_GET['student_filter']) : 0;

// New filters to target specific clicked topics/quizzes
$quiz_id_filter = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$chapter_filter = isset($_GET['chapter']) ? trim($_GET['chapter']) : '';
$subtopic_filter = isset($_GET['subtopic']) ? trim($_GET['subtopic']) : '';

// Fetch classroom details
$stmt_class = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
$stmt_class->execute([$class_id]);
$current_class = $stmt_class->fetch(PDO::FETCH_ASSOC);

// Fetch students in this classroom for the dropdown filter
$stmt_students = $pdo->prepare("SELECT * FROM students WHERE classroom_id = ?");
$stmt_students->execute([$class_id]);
$students_raw = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Build dynamic query based on clicked topic/subtopic/quiz
$query = "SELECT q.*, sqa.answer_status, sqa.score AS awarded_score, s.name as student_name, s.id as student_id 
          FROM chapter_quizzes q
          JOIN students s ON s.classroom_id = ?
          LEFT JOIN student_quiz_answers sqa ON sqa.quiz_id = q.id AND sqa.student_id = s.id
          WHERE 1=1";

$params = [$class_id];

if ($quiz_id_filter > 0) {
    $query .= " AND q.id = ?";
    $params[] = $quiz_id_filter;
}

if (!empty($chapter_filter)) {
    $query .= " AND q.chapter_name = ?";
    $params[] = $chapter_filter;
}

if ($subtopic_filter === 'main') {
    $query .= " AND (q.subtopic_name IS NULL OR q.subtopic_name = '')";
} elseif (!empty($subtopic_filter)) {
    $query .= " AND q.subtopic_name = ?";
    $params[] = $subtopic_filter;
}

if ($student_filter > 0) {
    $query .= " AND s.id = ?";
    $params[] = $student_filter;
}

$query .= " ORDER BY q.id ASC, s.name ASC";

$stmt_q_ans = $pdo->prepare($query);
$stmt_q_ans->execute($params);
$rows = $stmt_q_ans->fetchAll(PDO::FETCH_ASSOC);

// Group results by question so each question can show its breakdown, chart, and responses per student
$questions_data = [];
foreach ($rows as $row) {
    $q_id = $row['id'];
    if (!isset($questions_data[$q_id])) {
        $questions_data[$q_id] = [
            'id' => $row['id'],
            'chapter_name' => $row['chapter_name'],
            'subtopic_name' => $row['subtopic_name'],
            'question' => $row['question'],
            'option_a' => $row['option_a'],
            'option_b' => $row['option_b'],
            'option_c' => $row['option_c'],
            'option_d' => $row['option_d'],
            'correct_option' => $row['correct_option'],
            'responses' => [],
            'stats' => [
                'Correct' => 0,
                'Incorrect' => 0,
                'Attempted' => 0,
                'Pending' => 0
            ]
        ];
    }

    $status = $row['answer_status'] ?? 'Pending';
    if ($status === 'Completed')
        $status = 'Correct';
    if ($status === 'Failed')
        $status = 'Incorrect';

    if (isset($questions_data[$q_id]['stats'][$status])) {
        $questions_data[$q_id]['stats'][$status]++;
    } else {
        $questions_data[$q_id]['stats']['Pending']++;
    }

    $questions_data[$q_id]['responses'][] = [
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'status' => $status,
        'score' => $row['awarded_score'] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Questions Summary - EduPulse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="classroom.php?id=<?php echo $class_id; ?>"
                    class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr;
                    Back to Classroom</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-6 flex-1 w-full">
        <!-- Filter Bar -->
        <section
            class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-base font-bold text-pastel-text">Filter Responses by Student</h2>
            </div>

            <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="classroom_id" value="<?php echo $class_id; ?>">
                <select name="student_filter" onchange="this.form.submit()"
                    class="text-xs px-3 py-2 rounded-xl border border-blue-100 bg-pastel-bg/40 font-semibold text-pastel-text focus:outline-none focus:border-pastel-primary w-full sm:w-64">
                    <option value="0">All Students</option>
                    <?php foreach ($students_raw as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $student_filter === (int) $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($student_filter > 0): ?>
                    <a href="quiz_summary.php?classroom_id=<?php echo $class_id; ?>"
                        class="text-xs text-slate-400 hover:text-slate-600 underline whitespace-nowrap">Reset</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Questions List Section with Charts -->
        <div class="space-y-6">
            <?php if (count($questions_data) > 0): ?>
                <?php foreach ($questions_data as $q_id => $q): ?>
                    <section class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
                        <!-- Question Header -->
                        <div class="p-6 border-b border-blue-100 bg-pastel-bg/30">
                            <h3 class="text-base font-bold text-pastel-text mt-1">
                                <?php echo htmlspecialchars($q['question']); ?>
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 text-xs">
                                <div class="bg-white p-2 rounded-lg border border-blue-50"><b>A:</b>
                                    <?php echo htmlspecialchars($q['option_a']); ?></div>
                                <div class="bg-white p-2 rounded-lg border border-blue-50"><b>B:</b>
                                    <?php echo htmlspecialchars($q['option_b']); ?></div>
                                <div class="bg-white p-2 rounded-lg border border-blue-50"><b>C:</b>
                                    <?php echo htmlspecialchars($q['option_c']); ?></div>
                                <div class="bg-white p-2 rounded-lg border border-blue-50"><b>D:</b>
                                    <?php echo htmlspecialchars($q['option_d']); ?></div>
                            </div>
                        </div>

                        <!-- Chart & Metrics Grid for this Question -->
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 border-b border-blue-100 items-center bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Response
                                    Distribution</span>
                                <div class="w-[180px] h-[180px] relative">
                                    <canvas id="chart_<?php echo $q_id; ?>"></canvas>
                                </div>
                            </div>
                            <div class="md:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl text-center">
                                    <span class="block text-xs font-semibold text-emerald-600 uppercase">Correct</span>
                                    <span
                                        class="text-xl font-bold text-emerald-700 mt-1 inline-block"><?php echo $q['stats']['Correct']; ?></span>
                                </div>
                                <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl text-center">
                                    <span class="block text-xs font-semibold text-rose-600 uppercase">Incorrect</span>
                                    <span
                                        class="text-xl font-bold text-rose-700 mt-1 inline-block"><?php echo $q['stats']['Incorrect']; ?></span>
                                </div>
                                <div class="bg-amber-50 border border-amber-100 p-4 rounded-xl text-center">
                                    <span class="block text-xs font-semibold text-amber-600 uppercase">Attempted</span>
                                    <span
                                        class="text-xl font-bold text-amber-700 mt-1 inline-block"><?php echo $q['stats']['Attempted']; ?></span>
                                </div>
                                <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-center">
                                    <span class="block text-xs font-semibold text-slate-500 uppercase">Pending</span>
                                    <span
                                        class="text-xl font-bold text-slate-700 mt-1 inline-block"><?php echo $q['stats']['Pending']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Student Answers Sub-Table for this Question -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-blue-50 bg-pastel-bg/20">
                                        <th class="py-3 px-6">Student Name</th>
                                        <th class="py-3 px-6 text-center">Answer Status</th>
                                        <th class="py-3 px-6 text-center">Score Awarded</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-blue-50 text-sm">
                                    <?php foreach ($q['responses'] as $res): ?>
                                        <tr class="hover:bg-pastel-bg/50 transition">
                                            <td class="py-3.5 px-6 font-medium text-pastel-text">
                                                <?php echo htmlspecialchars($res['student_name']); ?>
                                            </td>
                                            <td class="py-3.5 px-6 text-center">
                                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-xl text-xs font-semibold
                                                    <?php
                                                    if ($res['status'] === 'Correct') {
                                                        echo 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                                    } elseif ($res['status'] === 'Attempted') {
                                                        echo 'bg-amber-100 text-amber-700 border border-amber-200';
                                                    } elseif ($res['status'] === 'Incorrect') {
                                                        echo 'bg-rose-100 text-rose-700 border border-rose-200';
                                                    } else {
                                                        echo 'bg-slate-100 text-slate-500 border border-slate-200';
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($res['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-6 text-center font-bold text-pastel-text">
                                                <?php echo intval($res['score']); ?> pts
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-8 text-center text-slate-400 text-sm">
                    No quiz questions or student responses found.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        <?php foreach ($questions_data as $q_id => $q): ?>
            const ctx_<?php echo $q_id; ?> = document.getElementById('chart_<?php echo $q_id; ?>').getContext('2d');
            new Chart(ctx_<?php echo $q_id; ?>, {
                type: 'doughnut',
                data: {
                    labels: ['Correct', 'Incorrect', 'Attempted', 'Pending'],
                    datasets: [{
                        data: [
                            <?php echo $q['stats']['Correct']; ?>,
                            <?php echo $q['stats']['Incorrect']; ?>,
                            <?php echo $q['stats']['Attempted']; ?>,
                            <?php echo $q['stats']['Pending']; ?>
                        ],
                        backgroundColor: ['#10b981', '#f43f5e', '#f59e0b', '#cbd5e1'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10,
                                    family: 'sans-serif'
                                }
                            }
                        }
                    }
                }
            });
        <?php endforeach; ?>
    </script>
</body>

</html>