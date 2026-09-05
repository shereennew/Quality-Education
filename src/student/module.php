<?php
// module.php - SAVES QUIZ ATTEMPTS TO DB, PREVENTS RE-ATTEMPTING, AND PERSISTS ANSWERS/EXPLANATIONS ACROSS PAGE LOADS
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

$student_id = 1;

// Fetch Student Info from DB
try {
    $stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt_student->execute([$student_id]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $student = null;
}

if (!$student) {
    $student = [
        'name' => 'Aina',
        'level' => 4,
        'xp' => 320
    ];
}

$classroom_id = $student['classroom_id'] ?? 1;

// Handle AJAX submission of quiz answers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    header('Content-Type: application/json');
    
    $chapter_num = $_POST['chapter_num'] ?? 1;
    $subtopic_num = $_POST['subtopic_num'] ?? 1;
    $chapter_name = $_POST['chapter_name'] ?? '';
    $answers = $_POST['answers'] ?? []; // format: [q_id => selected_option]

    $quiz_set_id = 'sub_' . $chapter_num . '_' . str_replace('.', '_', $subtopic_num);

    // Check if already attempted
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM student_quiz_history WHERE student_id = ? AND quiz_id = ?");
        $stmt_check->execute([$student_id, $quiz_set_id]);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Quiz already submitted.']);
            exit;
        }
    } catch (Exception $e) {
        // Table might differ, attempt safe fallback or creation
    }

    // Fetch questions to evaluate score & store response
    try {
        $stmt_q = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ?");
        $stmt_q->execute([$chapter_name]);
        $all_q = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

        $subtopic_questions = [];
        foreach ($all_q as $q) {
            $q_sub = $q['subtopic_name'] ?? null;
            if ($q_sub !== null && ((string)$q_sub === (string)$subtopic_num)) {
                $subtopic_questions[] = $q;
            }
        }
        if (empty($subtopic_questions)) {
            $subtopic_questions = $all_q; // fallback
        }

        $correctCount = 0;
        $totalQuestions = count($subtopic_questions);
        $detailed_results = [];

        foreach ($subtopic_questions as $q) {
            $qId = $q['id'];
            $correctOpt = strtolower($q['correct_option'] ?? $q['answer'] ?? 'a');
            $userAns = isset($answers[$qId]) ? strtolower($answers[$qId]) : '';
            
            $is_correct = ($userAns === $correctOpt);
            if ($is_correct) {
                $correctCount++;
            }

            $detailed_results[$qId] = [
                'user_answer' => $userAns,
                'correct_answer' => $correctOpt,
                'is_correct' => $is_correct
            ];
        }

        $score = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;
        $answers_json = json_encode($detailed_results);

        // Save into student_quiz_history
        $stmt_save = $pdo->prepare("INSERT INTO student_quiz_history (student_id, quiz_id, score, status, answers_data, created_at) VALUES (?, ?, ?, 'completed', ?, NOW())");
        $stmt_save->execute([$student_id, $quiz_set_id, $score, $answers_json]);

echo json_encode([
    'status' => 'success',
    'correct_count' => $correctCount,
    'total_questions' => $totalQuestions,
    'score' => round($score),
    'answers' => $detailed_results
]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch student quiz completion history & saved answers data
$completed_quizzes_data = [];
try {
    $stmt_history = $pdo->prepare("SELECT quiz_id, answers_data, score FROM student_quiz_history WHERE student_id = ? AND status = 'completed'");
    $stmt_history->execute([$student_id]);
    $history_rows = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    foreach ($history_rows as $row) {
        $completed_quizzes_data[$row['quiz_id']] = [
            'score' => $row['score'],
            'answers' => json_decode($row['answers_data'], true)
        ];
    }
} catch (Exception $e) {
    $completed_quizzes_data = [];
}
$completed_quiz_ids = array_keys($completed_quizzes_data);

// 1. Fetch only chapters unlocked for this specific classroom
$chapters = [];
$db_chapters = [];

try {
    $stmt = $pdo->prepare("
        SELECT cm.chapter_name 
        FROM classroom_chapters cc
        JOIN chapter_materials cm ON cc.chapter_id = cm.id OR cc.chapter_name = cm.chapter_name
        WHERE cc.classroom_id = ? AND cc.is_unlocked = 1
        GROUP BY cm.chapter_name
        ORDER BY cm.chapter_name ASC
    ");
    $stmt->execute([$classroom_id]);
    $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT chapter_name FROM chapter_materials WHERE classroom_id = ? AND is_unlocked = 1 ORDER BY chapter_name ASC");
        $stmt->execute([$classroom_id]);
        $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $ex) {
        $stmt = $pdo->query("SELECT DISTINCT chapter_name FROM chapter_materials ORDER BY chapter_name ASC");
        $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (empty($db_chapters)) {
    $chapters = [
        1 => [
            'title' => 'Whole Numbers & Basic Arithmetic',
            'topic' => 'Operations up to 1,000,000',
            'subtopics' => [
                '1.1' => [
                    'title' => 'Place Value and Value of Digits',
                    'badge_color' => 'bg-emerald-100 text-emerald-700',
                    'status' => 'Completed',
                    'notes' => [
                        'overview' => 'Understanding the position of a digit in a number determines its value.',
                        'points' => ['Identify position from right to left.'],
                        'example' => 'Example text here'
                    ],
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'What is the place value of digit 5 in 456,789?',
                            'option_a' => 'Thousands',
                            'option_b' => 'Ten Thousands',
                            'option_c' => 'Hundred Thousands',
                            'option_d' => 'Hundreds',
                            'correct_option' => 'b',
                            'explanation' => 'In 456,789, the digit 5 is in the ten-thousands position.'
                        ]
                    ],
                    'additional_resources' => []
                ]
            ]
        ]
    ];
} else {
    foreach ($db_chapters as $index => $chapter_name) {
        $stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC");
        $stmt_materials->execute([$chapter_name]);
        $materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);

        $db_resources = [];
        try {
            $stmt_res = $pdo->prepare("SELECT * FROM additional_resources WHERE chapter_name = ?");
            $stmt_res->execute([$chapter_name]);
            $db_resources = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_resources = [];
        }

        // Fetch all quiz questions belonging to this chapter
        $db_quizzes = [];
        try {
            $stmt_quiz = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? ORDER BY id");
            $stmt_quiz->execute([$chapter_name]);
            $db_quizzes = $stmt_quiz->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_quizzes = [];
        }

        $subtopics = [];
        $chapter_num = $index + 1;

        foreach ($materials as $material_index => $material) {
            $material_title = $material['title'] ?? $material['file_name'] ?? 'Teacher Material';
            $file_path = $material['file_path'] ?? null;
            $current_subtopic_idx = $material_index + 1;
            
            $subtopic_num = $material['subtopic_name'] ?? $current_subtopic_idx;

            if (!$file_path && !empty($material['file_name'])) {
                $file_path = '../uploads/' . rawurlencode($material['file_name']);
            } elseif (strpos((string)$file_path, 'uploads/') === 0) {
                $file_path = '../' . $file_path;
            }

            // Filter resources specifically meant for this subtopic
            $subtopic_resources = [];
            foreach ($db_resources as $res) {
                if (isset($res['subtopic_index']) && (int)$res['subtopic_index'] === (int)$current_subtopic_idx) {
                    $subtopic_resources[] = [
                        'title' => $res['title'],
                        'url' => $res['url'],
                        'type' => $res['type'] ?? 'Remedial'
                    ];
                }
            }

            $subtopic_questions = [];
            foreach ($db_quizzes as $q) {
                $q_sub = $q['subtopic_name'] ?? null;
                if ($q_sub !== null && ((string)$q_sub === (string)$subtopic_num || (string)$q_sub === (string)$current_subtopic_idx)) {
                    $subtopic_questions[] = $q;
                }
            }

            $quiz_set_id = 'sub_' . $chapter_num . '_' . str_replace('.', '_', $subtopic_num);
            $is_completed = in_array($quiz_set_id, $completed_quiz_ids);
            $status = $is_completed ? 'Completed' : 'Available';
            $badge_color = $is_completed ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700';

            $subtopics[(string)$current_subtopic_idx] = [
                'title' => $material_title,
                'badge_color' => $badge_color,
                'status' => $status,
                'is_completed' => $is_completed,
                'saved_data' => $is_completed ? $completed_quizzes_data[$quiz_set_id] : null,
                'notes' => [
                    'overview' => 'Learning material provided by your teacher for this chapter.',
                    'points' => ['Read the material carefully and click the quizzes tab above when ready to test your knowledge.'],
                    'example' => $file_path ? '<a class="text-pastel-primary font-bold underline" href="' . htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8') . '" target="_blank">Open teacher material</a>' : 'No file attached.'
                ],
                'questions' => $subtopic_questions,
                'chapter_num' => $chapter_num,
                'subtopic_name_val' => $chapter_name,
                'subtopic_num' => $subtopic_num,
                'additional_resources' => $subtopic_resources
            ];
        }

        if (empty($subtopics)) {
            $chap_quiz_id = 'chap_' . $chapter_num;
            $is_chap_completed = in_array($chap_quiz_id, $completed_quiz_ids);

            $subtopics['1'] = [
                'title' => $chapter_name . ' - Main Content',
                'badge_color' => $is_chap_completed ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700',
                'status' => $is_chap_completed ? 'Completed' : 'Available',
                'is_completed' => $is_chap_completed,
                'saved_data' => $is_chap_completed ? $completed_quizzes_data[$chap_quiz_id] : null,
                'notes' => [
                    'overview' => 'Chapter content from your teacher.',
                    'points' => ['Check the chapter materials for this topic.'],
                    'example' => 'No material file provided yet.'
                ],
                'questions' => $db_quizzes,
                'chapter_num' => $chapter_num,
                'subtopic_name_val' => $chapter_name,
                'subtopic_num' => 1,
                'additional_resources' => []
            ];
        }

        $chapters[$index + 1] = [
            'title' => $chapter_name,
            'topic' => 'Chapter ' . ($index + 1),
            'subtopics' => $subtopics
        ];
    }
}

$selected_chap_id = isset($_GET['chap']) && isset($chapters[$_GET['chap']]) ? (int)$_GET['chap'] : 1;
$active_chapter = $chapters[$selected_chap_id];
$subtopic_keys = array_keys($active_chapter['subtopics']);
$first_subtopic_key = !empty($subtopic_keys) ? $subtopic_keys[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Modules</title>
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss5.1"></script>
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
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center p-6 pt-32">

    <!-- NAV BAR -->
    <nav class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-2xl">📖</span>
                </div>
                <span class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block">EduHunt</span>
            </a>

            <div class="hidden md:flex items-center justify-center flex-1 mx-6">
                <ul class="flex items-center gap-3 text-lg font-bold">
                    <li><a href="index.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Home</a></li>
                    <li><a href="discussion.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Discussion</a></li>
                    <li><a href="module.php" class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm">Modules</a></li>
                    <li><a href="quiz.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Quizzes</a></li>
                    <li><a href="history.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">History</a></li>
                </ul>
            </div>

            <div class="flex items-center flex-shrink-0">
                <button id="user-menu-button" data-dropdown-toggle="user-dropdown" type="button" class="flex items-center gap-3 py-2.5 px-4 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm">
                    <div class="w-10 h-10 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-lg">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-lg font-bold text-pastel-text hidden sm:block"><?= htmlspecialchars($student['name']) ?></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Chapter Selector -->
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2">
            <?php foreach ($chapters as $chap_num => $chap): ?>
                <a href="module.php?chap=<?= $chap_num ?>" class="px-6 py-3 rounded-xl text-sm font-bold transition shadow-sm whitespace-nowrap flex items-center gap-2 <?= $chap_num === $selected_chap_id ? 'bg-pastel-primary text-white' : 'bg-pastel-card text-slate-600 hover:bg-blue-50 border border-blue-100' ?>">
                    <span>Chapter <?= $chap_num ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Header -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($active_chapter['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($active_chapter['topic']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Subtopics -->
            <div class="lg:col-span-4 bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm h-fit">
                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-3 mb-3">Subtopics</h2>
                <div class="space-y-2.5">
                    <?php foreach ($active_chapter['subtopics'] as $key => $subtopic): ?>
                        <button type="button" onclick="selectSubtopic('<?= $key ?>')" id="subtopic-btn-<?= str_replace('.', '_', $key) ?>" class="subtopic-btn w-full text-left p-4 rounded-xl border transition <?= $key === $first_subtopic_key ? 'border-pastel-primary bg-blue-50/70 shadow-sm' : 'border-slate-100 hover:border-blue-200' ?>">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-md <?= $subtopic['badge_color'] ?>"><?= $subtopic['status'] ?></span>
                            </div>
                            <h3 class="font-bold text-base text-pastel-text"><?= htmlspecialchars($subtopic['title']) ?></h3>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Content -->
            <div class="lg:col-span-8 bg-pastel-card p-6 sm:p-8 rounded-2xl border border-blue-100 shadow-sm">
                
                <!-- Tabs -->
                <div class="flex border-b border-slate-100 mb-6 gap-8 overflow-x-auto">
                    <button type="button" onclick="switchTab('notes')" id="tab-btn-notes" class="pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap">
                        📖 Notes
                    </button>
                    <button type="button" onclick="switchTab('lessons')" id="tab-btn-lessons" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        ✏️ Quizzes
                    </button>
                    <button type="button" onclick="switchTab('resources')" id="tab-btn-resources" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        🔗 Additional Resources <span id="resource-badge-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 font-bold hidden"></span>
                    </button>
                </div>

                <!-- Notes View -->
                <div id="view-notes" class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Overview</h3>
                        <p id="note-overview" class="text-base text-slate-600 bg-pastel-bg p-5 rounded-xl border border-blue-50 leading-relaxed"></p>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Key Steps</h3>
                        <ul id="note-points" class="list-disc list-inside text-base text-slate-600 space-y-2"></ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Worked Example</h3>
                        <div id="note-example" class="p-5 bg-blue-50/50 border border-blue-100 rounded-xl text-center text-lg font-semibold text-pastel-text"></div>
                    </div>
                </div>

                <!-- Quizzes View -->
                <div id="view-lessons" class="hidden space-y-6">
                    <div id="quiz-form" class="space-y-6">
                        <input type="hidden" id="form-chapter" value="">
                        <input type="hidden" id="form-subtopic" value="">
                        
<div id="quiz-submit-btn-wrapper" class="flex flex-col pt-4 hidden">
    <!-- Injected via JavaScript below -->
</div>

<div id="quiz-questions-container" class="space-y-6">
    <!-- Populated dynamically via JS -->
</div>

                    </div>
                </div>

                <!-- Additional Resources View -->
                <div id="view-resources" class="hidden space-y-4">
                    <p class="text-base text-slate-500 mb-2">Optional supplementary materials provided by your teacher:</p>
                    <div id="resources-list" class="space-y-3"></div>
                </div>

            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
    <script>
        const subtopicData = <?= json_encode($active_chapter['subtopics']) ?>;
        let selectedKey = "<?= $first_subtopic_key ?>";
        let activeTab = 'notes';

        function selectSubtopic(key) {
            selectedKey = key;
            document.querySelectorAll('.subtopic-btn').forEach(btn => {
                btn.classList.remove('border-pastel-primary', 'bg-blue-50/70', 'shadow-sm');
                btn.classList.add('border-slate-100');
            });
            const formattedId = `subtopic-btn-${key.replace('.', '_')}`;
            const activeBtn = document.getElementById(formattedId);
            if (activeBtn) {
                activeBtn.classList.remove('border-slate-100');
                activeBtn.classList.add('border-pastel-primary', 'bg-blue-50/70', 'shadow-sm');
            }
            renderContent();
        }

        function switchTab(tab) {
            activeTab = tab;
            const notesBtn = document.getElementById('tab-btn-notes');
            const lessonsBtn = document.getElementById('tab-btn-lessons');
            const resourcesBtn = document.getElementById('tab-btn-resources');

            [notesBtn, lessonsBtn, resourcesBtn].forEach(btn => {
                btn.className = "pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap";
            });

            ['view-notes', 'view-lessons', 'view-resources'].forEach(viewId => {
                const el = document.getElementById(viewId);
                el.classList.add('hidden');
                el.classList.remove('block');
            });

            if (tab === 'notes') {
                notesBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-notes').classList.add('block');
                document.getElementById('view-notes').classList.remove('hidden');
            } else if (tab === 'lessons') {
                lessonsBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-lessons').classList.add('block');
                document.getElementById('view-lessons').classList.remove('hidden');
            } else if (tab === 'resources') {
                resourcesBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-resources').classList.add('block');
                document.getElementById('view-resources').classList.remove('hidden');
            }
        }
 
        function renderContent() {
            const data = subtopicData[selectedKey];
            if (!data) return;
            
            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            data.notes.points.forEach(point => {
                const li = document.createElement('li');
                li.innerHTML = point;
                pointsList.appendChild(li);
            });
            document.getElementById('note-example').innerHTML = data.notes.example;

            // Set hidden form inputs for reference
            document.getElementById('form-chapter').value = data.chapter_num || <?= $selected_chap_id ?>;
            document.getElementById('form-subtopic').value = data.subtopic_num || selectedKey;

            const questionsContainer = document.getElementById('quiz-questions-container');
            const submitBtnWrapper = document.getElementById('quiz-submit-btn-wrapper');
            questionsContainer.innerHTML = '';

            if (data.questions && data.questions.length > 0) {
                submitBtnWrapper.classList.remove('hidden');
                
                const isCompleted = data.is_completed;
                const savedData = data.saved_data || {};
                const savedAnswers = savedData.answers || {};

                if (!isCompleted) {
                    submitBtnWrapper.innerHTML = `
                        <button type="button" id="submit-quiz-btn" class="px-8 py-3.5 bg-pastel-primary hover:bg-pastel-hover text-white font-bold rounded-2xl shadow-sm transition text-base w-full">
                            Submit Quiz Answers →
                        </button>
                        <div id="quiz-result-summary" class="hidden mt-6 p-6 rounded-2xl bg-white border border-blue-100 shadow-sm text-center space-y-3"></div>
                    `;
                } else {
                    // Already completed state: render locked results summary immediately
                    let correctCount = 0;
                    let totalQ = data.questions.length;
                    data.questions.forEach((q, idx) => {
                        let qId = q.id;
                        if (savedAnswers[qId] && savedAnswers[qId].is_correct) {
                            correctCount++;
                        }
                    });
                    let scorePercent = Math.round((correctCount / totalQ) * 100);

                    submitBtnWrapper.innerHTML = `
                        <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-center text-sm font-bold">
                            🔒 Quiz already submitted. You cannot re-attempt this quiz.
                        </div>
                        <div id="quiz-result-summary" class="mt-2 p-6 rounded-2xl bg-white border border-blue-100 shadow-sm text-center space-y-3">
                            <h3 class="text-lg font-extrabold text-pastel-text">Quiz Results Summary</h3>
                            <div class="flex justify-center gap-6 my-2 text-sm font-semibold">
                                <span class="text-green-600">✅ Correct: ${correctCount}</span>
                                <span class="text-red-500">❌ Incorrect: ${totalQ - correctCount}</span>
                            </div>
                            <p class="text-sm text-slate-600">Your Score: <strong class="text-pastel-primary text-base">${scorePercent}%</strong></p>
                        </div>
                    `;
                }

                data.questions.forEach((q, index) => {
                    const qId = q.id || (index + 1);
                    const qText = q.question || q.question_text || 'Question text missing';
                    const optA = q.option_a || q.opt_a || '';
                    const optB = q.option_b || q.opt_b || '';
                    const optC = q.option_c || q.opt_c || '';
                    const optD = q.option_d || q.opt_d || '';
                    const correctOpt = (q.correct_option || q.answer || 'a').toLowerCase();
                    const explanation = q.explanation || 'No explanation provided for this question.';

                    const userRecord = savedAnswers[qId] || {};
                    const userVal = userRecord.user_answer || '';

                    const questionDiv = document.createElement('div');
                    questionDiv.className = "p-6 rounded-2xl border border-blue-50 bg-pastel-bg space-y-4 quiz-item";
                    questionDiv.innerHTML = `
                        <div>
                            <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider mb-1 block">Question ${index + 1} of ${data.questions.length}</span>
                            <h3 class="text-base font-bold text-pastel-text">${qText}</h3>
                        </div>
                        <div class="space-y-2.5 options-group">
                            ${optA ? `
                                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition option-label-a">
                                    <input type="radio" name="answers[${qId}]" value="a" data-correct="${correctOpt}" ${isCompleted ? 'disabled' : ''} ${userVal === 'a' ? 'checked' : ''} class="text-pastel-primary focus:ring-pastel-primary option-input">
                                    <span class="text-sm text-pastel-text font-medium">A. ${optA}</span>
                                </label>` : ''}
                            ${optB ? `
                                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition option-label-b">
                                    <input type="radio" name="answers[${qId}]" value="b" data-correct="${correctOpt}" ${isCompleted ? 'disabled' : ''} ${userVal === 'b' ? 'checked' : ''} class="text-pastel-primary focus:ring-pastel-primary option-input">
                                    <span class="text-sm text-pastel-text font-medium">B. ${optB}</span>
                                </label>` : ''}
                            ${optC ? `
                                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition option-label-c">
                                    <input type="radio" name="answers[${qId}]" value="c" data-correct="${correctOpt}" ${isCompleted ? 'disabled' : ''} ${userVal === 'c' ? 'checked' : ''} class="text-pastel-primary focus:ring-pastel-primary option-input">
                                    <span class="text-sm text-pastel-text font-medium">C. ${optC}</span>
                                </label>` : ''}
                            ${optD ? `
                                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition option-label-d">
                                    <input type="radio" name="answers[${qId}]" value="d" data-correct="${correctOpt}" ${isCompleted ? 'disabled' : ''} ${userVal === 'd' ? 'checked' : ''} class="text-pastel-primary focus:ring-pastel-primary option-input">
                                    <span class="text-sm text-pastel-text font-medium">D. ${optD}</span>
                                </label>` : ''}
                        </div>
                        <div class="explanation-box ${isCompleted ? '' : 'hidden'} mt-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-sm text-slate-700">
                            <span class="font-bold block mb-1">💡 Explanation:</span>
                            <p>${explanation}</p>
                        </div>
                    `;
                    questionsContainer.appendChild(questionDiv);

                    // If already completed previously, style options right away
                    if (isCompleted) {
                        const inputs = questionDiv.querySelectorAll('.option-input');
                        inputs.forEach(inp => {
                            const parentLabel = inp.closest('label');
                            if (inp.value === correctOpt) {
                                parentLabel.classList.add('border-green-500', 'bg-green-50');
                            } else if (inp.checked && inp.value !== correctOpt) {
                                parentLabel.classList.add('border-red-400', 'bg-red-50');
                            }
                        });
                    }
                });

                // Attach submit event listener if not completed
                const submitBtn = document.getElementById('submit-quiz-btn');
                if (submitBtn) {
                    submitBtn.addEventListener('click', () => {
                        let formData = new FormData();
                        formData.append('action', 'submit_quiz');
                        formData.append('chapter_num', data.chapter_num || <?= $selected_chap_id ?>);
                        formData.append('subtopic_num', data.subtopic_num || selectedKey);
                        formData.append('chapter_name', data.subtopic_name_val || '<?= addslashes($active_chapter['title']) ?>');

                        data.questions.forEach((q, index) => {
                            const qId = q.id || (index + 1);
                            const selectedInput = document.querySelector(`input[name="answers[${qId}]"]:checked`);
                            if (selectedInput) {
                                formData.append(`answers[${qId}]`, selectedInput.value);
                            }
                        });

                        submitBtn.disabled = true;
                        submitBtn.innerText = 'Submitting...';

                        fetch('module.php?chap=<?= $selected_chap_id ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
.then(res => {
    if (res.status === 'success') {

        // Mark this quiz as completed
        data.is_completed = true;

        // Save returned answers into current page
        data.saved_data = {
            score: res.score,
            answers: res.answers || {}
        };

        // Remove submit button
        submitBtn.remove();

        // Show result at the top
        const resultBox = document.getElementById('quiz-result-summary');

        if (resultBox) {
            resultBox.classList.remove('hidden');

            resultBox.innerHTML = `
                <h3 class="text-lg font-extrabold text-pastel-text">
                    Quiz Results Summary
                </h3>

                <div class="flex justify-center gap-6 my-2 text-sm font-semibold">
                    <span class="text-green-600">
                        ✅ Correct: ${res.correct_count}
                    </span>

                    <span class="text-red-500">
                        ❌ Incorrect: ${res.total_questions - res.correct_count}
                    </span>
                </div>

                <p class="text-sm text-slate-600">
                    Your Score:
                    <strong class="text-pastel-primary text-base">
                        ${res.score}%
                    </strong>
                    (${res.correct_count}/${res.total_questions})
                </p>
            `;
        }

        // Lock all answers
        data.questions.forEach((q) => {

            const qId = q.id;

            const userRecord =
                data.saved_data.answers[qId] || {};

            const userVal =
                (userRecord.user_answer || '').toLowerCase();

            const correctOpt =
                (userRecord.correct_answer ||
                    q.correct_option ||
                    'a').toLowerCase();

            const questionDiv =
                document.querySelector(
                    `input[name="answers[${qId}]"]`
                )?.closest('.quiz-item');

            if (!questionDiv) return;

            // Disable all options
            questionDiv
                .querySelectorAll('.option-input')
                .forEach(input => {

                    input.disabled = true;

                    const parentLabel =
                        input.closest('label');

                    if (input.value === correctOpt) {
                        parentLabel.classList.add(
                            'border-green-500',
                            'bg-green-50'
                        );
                    }

                    if (
                        input.checked &&
                        input.value !== correctOpt
                    ) {
                        parentLabel.classList.add(
                            'border-red-400',
                            'bg-red-50'
                        );
                    }
                });

            // Show saved explanation
            const explanationBox =
                questionDiv.querySelector('.explanation-box');

            if (explanationBox) {

                const explanation =
                    userRecord.explanation ||
                    q.explanation ||
                    'No explanation provided for this question.';

                explanationBox.querySelector('p').innerText =
                    explanation;

                explanationBox.classList.remove('hidden');
            }
        });

    } else {
                                alert(res.message || 'Error submitting quiz.');
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Submit Quiz Answers →';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('An error occurred during submission.');
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Submit Quiz Answers →';
                        });
                    });
                }

            } else {
                submitBtnWrapper.classList.add('hidden');
                questionsContainer.innerHTML = '<p class="text-sm text-slate-500 py-4">No quiz questions available for this subtopic yet.</p>';
            }

            // Render Additional Resources List & Badge Count
            const resourcesList = document.getElementById('resources-list');
            const resourceBadge = document.getElementById('resource-badge-count');
            resourcesList.innerHTML = '';

            if (data.additional_resources && data.additional_resources.length > 0) {
                resourceBadge.innerText = data.additional_resources.length;
                resourceBadge.classList.remove('hidden');

                data.additional_resources.forEach(res => {
                    const resAnchor = document.createElement('a');
                    resAnchor.className = "block p-4 rounded-xl border border-blue-50 bg-pastel-bg hover:border-pastel-primary transition flex justify-between items-center";
                    resAnchor.href = res.url;
                    resAnchor.target = "_blank";
                    resAnchor.innerHTML = `
                        <div>
                            <span class="text-xs font-bold text-pastel-primary uppercase tracking-wide">${res.type}</span>
                            <p class="font-semibold text-sm text-pastel-text mt-0.5">${res.title}</p>
                        </div>
                        <span class="text-pastel-primary text-sm font-bold">↗</span>
                    `;
                    resourcesList.appendChild(resAnchor);
                });
            } else {
                resourceBadge.classList.add('hidden');
                resourcesList.innerHTML = '<p class="text-sm text-slate-500">No additional remedial resources assigned for this subtopic.</p>';
            }
        }

        renderContent();
    </script>
</body>
</html>