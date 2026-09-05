<?php
// module.php - WITH DYNAMIC DATABASE-DRIVEN ADDITIONAL RESOURCES
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
    // Fallback mock student data
    $student = [
        'name' => 'Aina',
        'level' => 4,
        'xp' => 320
    ];
}

// Get classroom ID from student record or default
$classroom_id = $student['classroom_id'] ?? 1;

// 1. Fetch only chapters unlocked for this specific classroom
$chapters = [];
$db_chapters = [];

try {
    // Attempt to fetch from a classroom-chapters mapping table
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
        // Fallback: Check if chapter_materials has classroom/unlock flags
        $stmt = $pdo->prepare("SELECT DISTINCT chapter_name FROM chapter_materials WHERE classroom_id = ? AND is_unlocked = 1 ORDER BY chapter_name ASC");
        $stmt->execute([$classroom_id]);
        $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $ex) {
        // Fallback to all materials if classroom mapping tables don't exist yet
        $stmt = $pdo->query("SELECT DISTINCT chapter_name FROM chapter_materials ORDER BY chapter_name ASC");
        $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// If no chapters in database, USE MOCK DATA (including optional remedial resources)
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
                        'overview' => 'Understanding the position of a digit in a number determines its value (e.g., thousands, ten thousands).',
                        'points' => [
                            'Identify the position from right to left: Ones, Tens, Hundreds, Thousands.',
                            'Digit value = Digit × Place value.',
                            'Write numbers in word form and numeric form accurately.'
                        ],
                        'example' => 'What is the value of <b>7</b> in 4<b>7</b>2,150? <br><span class="text-pastel-primary font-bold">Answer: 70,000</span>'
                    ],
                    'questions' => [
                        ['id' => 101, 'diff' => 'Easy', 'title' => 'Identify the place value of 5 in 352,410'],
                        ['id' => 102, 'diff' => 'Medium', 'title' => 'Compare numbers up to six digits using symbols']
                    ],
                    'additional_resources' => [
                        ['title' => 'Catch-up Guide: Simplified Place Value Breakdown', 'url' => '#', 'type' => 'Remedial Guide']
                    ]
                ],
                '1.2' => [
                    'title' => 'Addition and Subtraction Operations',
                    'badge_color' => 'bg-blue-100 text-blue-700',
                    'status' => 'In Progress',
                    'notes' => [
                        'overview' => 'Adding and subtracting large numbers by aligning digits according to their place values.',
                        'points' => [
                            'Always align numbers vertically by place value before calculating.',
                            'Carry over numbers carefully during addition.',
                            'Borrow correctly from neighboring columns during subtraction.'
                        ],
                        'example' => 'Calculate: 45,210 + 12,345 <br><span class="text-pastel-primary font-bold">Answer: 57,555</span>'
                    ],
                    'questions' => [
                        ['id' => 103, 'diff' => 'Easy', 'title' => 'Solve 5-digit addition with regrouping'],
                        ['id' => 104, 'diff' => 'Hard', 'title' => 'Word problem involving mixed addition and subtraction']
                    ],
                    'additional_resources' => []
                ]
            ]
        ]
    ];
} else {
    /* 
      EXPECTED DATABASE TABLE STRUCTURE FOR ADDITIONAL RESOURCES:
      CREATE TABLE IF NOT EXISTS additional_resources (
          id INT AUTO_INCREMENT PRIMARY KEY,
          chapter_name VARCHAR(255) NOT NULL,
          subtopic_index INT NOT NULL,
          title VARCHAR(255) NOT NULL,
          url VARCHAR(500) NOT NULL,
          type VARCHAR(50) DEFAULT 'Remedial'
      );
    */

foreach ($db_chapters as $index => $chapter_name) {
        // Fetch materials
        $stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC");
        $stmt_materials->execute([$chapter_name]);
        $materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);

        // Fetch optional remedial / additional resources for this chapter from DB
        $db_resources = [];
        try {
            $stmt_res = $pdo->prepare("SELECT * FROM additional_resources WHERE chapter_name = ?");
            $stmt_res->execute([$chapter_name]);
            $db_resources = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_resources = [];
        }

        // Fetch quizzes for this chapter from DB
        $db_quizzes = [];
        try {
            $stmt_quiz = $pdo->prepare("SELECT id, question, score FROM chapter_quizzes WHERE chapter_name = ?");
            $stmt_quiz->execute([$chapter_name]);
            $db_quizzes = $stmt_quiz->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_quizzes = [];
        }

        $subtopics = [];

        foreach ($materials as $material_index => $material) {
            $material_title = $material['title'] ?? $material['file_name'] ?? 'Teacher Material';
            $file_path = $material['file_path'] ?? null;
            $current_subtopic_idx = $material_index + 1;

            if (!$file_path && !empty($material['file_name'])) {
                $file_path = '../uploads/' . rawurlencode($material['file_name']);
            } elseif (strpos((string)$file_path, 'uploads/') === 0) {
                $file_path = '../' . $file_path;
            }

            // Filter resources specifically meant for this subtopic/material index
            $subtopic_resources = [];
            foreach ($db_resources as $res) {
                if (isset($res['subtopic_index']) && (int)$res['subtopic_index'] === $current_subtopic_idx) {
                    $subtopic_resources[] = [
                        'title' => $res['title'],
                        'url' => $res['url'],
                        'type' => $res['type'] ?? 'Remedial'
                    ];
                }
            }

            // Map chapter quizzes to subtopics (distribute or assign)
            $subtopic_questions = [];
            foreach ($db_quizzes as $qIndex => $q) {
                // Assign quizzes across subtopics evenly or attach them
                if ($qIndex % count($materials) === $material_index) {
                    $subtopic_questions[] = [
                        'id' => $q['id'],
                        'diff' => 'Medium',
                        'title' => $q['question']
                    ];
                }
            }
            // Fallback if distribution leaves a subtopic empty but quizzes exist
            if (empty($subtopic_questions) && !empty($db_quizzes) && $material_index === 0) {
                foreach ($db_quizzes as $q) {
                    $subtopic_questions[] = [
                        'id' => $q['id'],
                        'diff' => 'Medium',
                        'title' => $q['question']
                    ];
                }
            }

            $subtopics[(string)$current_subtopic_idx] = [
                'title' => $material_title,
                'badge_color' => 'bg-blue-100 text-blue-700',
                'status' => 'Available',
                'notes' => [
                    'overview' => 'Learning material provided by your teacher for this chapter.',
                    'points' => ['Read the material carefully and review the chapter quiz when ready.'],
                    'example' => $file_path ? '<a class="text-pastel-primary font-bold underline" href="' . htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8') . '" target="_blank">Open teacher material</a>' : 'No file attached.'
                ],
                'questions' => $subtopic_questions,
                'additional_resources' => $subtopic_resources
            ];
        }

        if (empty($subtopics)) {
            $subtopics['1'] = [
                'title' => $chapter_name . ' - Main Content',
                'badge_color' => 'bg-blue-100 text-blue-700',
                'status' => 'Available',
                'notes' => [
                    'overview' => 'Chapter content from your teacher.',
                    'points' => ['Check the chapter materials for this topic.'],
                    'example' => 'No material file provided yet.'
                ],
                'questions' => [],
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

// Handle chapter selection
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
            <a href="quiz.php?chapter=<?= $selected_chap_id ?>" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-sm">
                Take Chapter Test
            </a>
        </div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Subtopics -->
            <div class="lg:col-span-4 bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm h-fit">
                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-3 mb-3">Subtopics</h2>
                <div class="space-y-2.5">
                    <?php foreach ($active_chapter['subtopics'] as $key => $subtopic): ?>
                        <button onclick="selectSubtopic('<?= $key ?>')" id="subtopic-btn-<?= str_replace('.', '_', $key) ?>" class="subtopic-btn w-full text-left p-4 rounded-xl border transition <?= $key === $first_subtopic_key ? 'border-pastel-primary bg-blue-50/70 shadow-sm' : 'border-slate-100 hover:border-blue-200' ?>">
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
                    <button onclick="switchTab('notes')" id="tab-btn-notes" class="pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap">
                        📖 Notes
                    </button>
                    <button onclick="switchTab('lessons')" id="tab-btn-lessons" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        ✏️ Lessons & Questions
                    </button>
                    <button onclick="switchTab('resources')" id="tab-btn-resources" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        🔗 Additional Resources <span id="resource-badge-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 font-bold hidden"></span>
                    </button>
                </div>

                <h2 id="active-title" class="text-2xl font-bold text-pastel-text mb-5">--</h2>

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

                <!-- Lessons View -->
                <div id="view-lessons" class="hidden space-y-4">
                    <p class="text-base text-slate-500 mb-2">Click on any lesson question below to jump directly into practice mode:</p>
                    <div id="questions-list" class="space-y-3"></div>
                </div>

                <!-- Additional Resources View (Optional Remedial Material) -->
                <div id="view-resources" class="hidden space-y-4">
                    <p class="text-base text-slate-500 mb-2">Optional supplementary materials provided by your teacher to help reinforce core concepts:</p>
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
                btn.className = "pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap";
            });

            ['view-notes', 'view-lessons', 'view-resources'].forEach(viewId => {
                const el = document.getElementById(viewId);
                el.classList.add('hidden');
                el.classList.remove('block');
            });

            if (tab === 'notes') {
                notesBtn.className = "pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-notes').classList.add('block');
                document.getElementById('view-notes').classList.remove('hidden');
            } else if (tab === 'lessons') {
                lessonsBtn.className = "pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-lessons').classList.add('block');
                document.getElementById('view-lessons').classList.remove('hidden');
            } else if (tab === 'resources') {
                resourcesBtn.className = "pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-resources').classList.add('block');
                document.getElementById('view-resources').classList.remove('hidden');
            }
        }

        function renderContent() {
            const data = subtopicData[selectedKey];
            if (!data) return;
            document.getElementById('active-title').innerText = data.title;
            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            data.notes.points.forEach(point => {
                const li = document.createElement('li');
                li.innerHTML = point;
                pointsList.appendChild(li);
            });
            document.getElementById('note-example').innerHTML = data.notes.example;

            // Render Questions List
            const questionsList = document.getElementById('questions-list');
            questionsList.innerHTML = '';
            if (data.questions && data.questions.length > 0) {
                data.questions.forEach(q => {
                    const qAnchor = document.createElement('a');
                    qAnchor.className = "block p-4 rounded-xl border border-blue-50 bg-pastel-bg hover:border-pastel-primary transition";
                    qAnchor.href = `quiz.php?q=${q.id}`;
                    qAnchor.innerHTML = `
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-pastel-primary">${q.diff}</span>
                        </div>
                        <p class="font-semibold text-sm text-pastel-text">${q.title}</p>
                    `;
                    questionsList.appendChild(qAnchor);
                });
            } else {
                questionsList.innerHTML = '<p class="text-sm text-slate-500">No questions available for this subtopic yet.</p>';
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

        // Initial render
        renderContent();
    </script>
</body>
</html>