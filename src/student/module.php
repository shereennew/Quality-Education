<?php
// module.php - WITH PROPER FALLBACK
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

// Mock student data
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
];

// Get chapters from database
$chapters = [];
$db_chapters = [];

try {
    $stmt = $pdo->query("SELECT DISTINCT chapter_name FROM chapter_materials ORDER BY chapter_name");
    $db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Database error - use mock data
    $db_chapters = [];
}

// If no chapters in database, USE MOCK DATA
if (empty($db_chapters)) {
    // Mock chapters data (your original hardcoded data)
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
                        'example' => 'Calculate: $45,210 + 12,345$ <br><span class="text-pastel-primary font-bold">Answer: 57,555</span>'
                    ],
                    'questions' => [
                        ['id' => 103, 'diff' => 'Easy', 'title' => 'Solve 5-digit addition with regrouping'],
                        ['id' => 104, 'diff' => 'Hard', 'title' => 'Word problem involving mixed addition and subtraction']
                    ]
                ]
            ]
        ],
        2 => [
            'title' => 'Fractions, Decimals & Percentages',
            'topic' => 'Conversion and Calculations',
            'subtopics' => [
                '2.1' => [
                    'title' => 'Equivalent Fractions',
                    'badge_color' => 'bg-amber-100 text-amber-700',
                    'status' => 'Locked',
                    'notes' => [
                        'overview' => 'Fractions that represent the same proportion of the whole even though their numerators and denominators differ.',
                        'points' => [
                            'Multiply or divide numerator and denominator by the same non-zero number.',
                            'Simplify fractions to their lowest terms.'
                        ],
                        'example' => 'Find an equivalent fraction for $\frac{2}{3}$ with denominator $6$. <br><span class="text-pastel-primary font-bold">Answer: $\frac{4}{6}$</span>'
                    ],
                    'questions' => [
                        ['id' => 201, 'diff' => 'Easy', 'title' => 'Simplify fractions to lowest terms']
                    ]
                ]
            ]
        ]
    ];
} else {
    // Build chapters and their materials from the teacher's database records.
    foreach ($db_chapters as $index => $chapter_name) {
        $stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC");
        $stmt_materials->execute([$chapter_name]);
        $materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);
        $subtopics = [];

        foreach ($materials as $material_index => $material) {
            $material_title = $material['title'] ?? $material['file_name'] ?? 'Teacher Material';
            $file_path = $material['file_path'] ?? null;

            if (!$file_path && !empty($material['file_name'])) {
                $file_path = '../uploads/' . rawurlencode($material['file_name']);
            } elseif (strpos((string)$file_path, 'uploads/') === 0) {
                $file_path = '../' . $file_path;
            }

            $subtopics[(string)($material_index + 1)] = [
                'title' => $material_title,
                'badge_color' => 'bg-blue-100 text-blue-700',
                'status' => 'Available',
                'notes' => [
                    'overview' => 'Learning material provided by your teacher for this chapter.',
                    'points' => ['Read the material carefully and review the chapter quiz when ready.'],
                    'example' => $file_path ? '<a class="text-pastel-primary font-bold underline" href="' . htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8') . '" target="_blank">Open teacher material</a>' : 'No file attached.'
                ],
                'questions' => []
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
                'questions' => []
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
    <title>Eduhunt - Modules</title>
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
    <nav class="bg-pastel-nav fixed w-full h-24 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[90rem] mx-auto px-10 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-4 flex-shrink-0">
                <div class="bg-pastel-badge w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-3xl">📖</span>
                </div>
                <span class="text-3xl font-black tracking-wide text-pastel-text hidden lg:block">Eduhunt</span>
            </a>
            <div class="hidden md:flex items-center justify-center flex-1 mx-8">
                <ul class="flex items-center gap-3 text-2xl font-bold">
                    <li><a href="index.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"><span>Home</span></a></li>
                    <li><a href="discussion.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"><span>Discussion</span></a></li>
                    <li><a href="module.php" class="flex items-center px-7 py-4 rounded-2xl bg-pastel-primary text-white shadow-md transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5"><span>Modules</span></a></li>
                    <li><a href="quiz.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200"><span>Quizzes</span></a></li>
                </ul>
            </div>
            <div class="flex items-center flex-shrink-0 relative">
                <button type="button" class="flex items-center gap-3 py-3 px-5 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-12 h-12 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-xl">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-xl font-bold text-pastel-text hidden sm:block"><?= $student['name'] ?></span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>
                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-60 mt-2" id="user-dropdown">
                    <div class="px-5 py-4 border-b-2 border-pastel-nav">
                        <span class="block text-lg font-bold text-pastel-text"><?= $student['name'] ?></span>
                        <span class="block text-sm text-pastel-primary font-semibold mt-1">Level <?= $student['level'] ?></span>
                    </div>
                    <ul class="p-2 text-lg text-pastel-text font-medium">
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Profile</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Settings</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Sign out</a></li>
                    </ul>
                </div>
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-3 ml-3 w-14 h-14 justify-center text-pastel-primary rounded-2xl bg-pastel-card border-2 border-pastel-primary/20 md:hidden hover:bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary/30">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 7h14M5 12h14M5 17h14" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="hidden w-full md:hidden px-5 pb-5 absolute top-24 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg" id="navbar-user">
            <ul class="flex flex-col gap-2 text-lg font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm">
                <li><a href="index.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Home</a></li>
                <li><a href="discussion.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Discussion</a></li>
                <li><a href="module.php" class="block py-4 px-5 text-white bg-pastel-primary rounded-xl shadow-sm">Modules</a></li>
                <li><a href="quiz.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Quizzes</a></li>
            </ul>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Chapter Selector -->
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2">
            <?php foreach ($chapters as $chap_num => $chap): ?>
                <a href="module.php?chap=<?= $chap_num ?>" class="px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap flex items-center gap-2 <?= $chap_num === $selected_chap_id ? 'bg-pastel-primary text-white' : 'bg-pastel-card text-slate-600 hover:bg-blue-50 border border-blue-100' ?>">
                    <span>Chapter <?= $chap_num ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Header -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Active Module</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($active_chapter['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($active_chapter['topic']) ?></p>
            </div>
            <a href="quiz.php?chapter=<?= $selected_chap_id ?>" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-sm">
                Take Chapter Test
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Subtopics -->
            <div class="lg:col-span-4 bg-pastel-card p-4 rounded-2xl border border-blue-100 shadow-sm h-fit">
                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-3 mb-3">Subtopics</h2>
                <div class="space-y-2">
                    <?php foreach ($active_chapter['subtopics'] as $key => $subtopic): ?>
                        <button onclick="selectSubtopic('<?= $key ?>')" id="subtopic-btn-<?= str_replace('.', '_', $key) ?>" class="subtopic-btn w-full text-left p-3.5 rounded-xl border transition <?= $key === $first_subtopic_key ? 'border-pastel-primary bg-blue-50/70 shadow-sm' : 'border-slate-100 hover:border-blue-200' ?>">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-md <?= $subtopic['badge_color'] ?>"><?= $subtopic['status'] ?></span>
                            </div>
                            <h3 class="font-semibold text-sm text-pastel-text"><?= htmlspecialchars($subtopic['title']) ?></h3>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Content -->
            <div class="lg:col-span-8 bg-pastel-card p-6 sm:p-8 rounded-2xl border border-blue-100 shadow-sm">
                
                <!-- Tabs -->
                <div class="flex border-b border-slate-100 mb-6 gap-6">
                    <button onclick="switchTab('notes')" id="tab-btn-notes" class="pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition">
                        📖 Notes
                    </button>
                    <button onclick="switchTab('lessons')" id="tab-btn-lessons" class="pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition">
                        ✏️ Lessons & Questions
                    </button>
                </div>

                <h2 id="active-title" class="text-xl font-bold text-pastel-text mb-4">--</h2>

                <!-- Notes View -->
                <div id="view-notes" class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Overview</h3>
                        <p id="note-overview" class="text-sm text-slate-600 bg-pastel-bg p-4 rounded-xl border border-blue-50 leading-relaxed"></p>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Key Steps</h3>
                        <ul id="note-points" class="list-disc list-inside text-sm text-slate-600 space-y-1.5"></ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Worked Example</h3>
                        <div id="note-example" class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl text-center text-lg font-semibold text-pastel-text"></div>
                    </div>
                </div>

                <!-- Lessons View -->
                <div id="view-lessons" class="hidden space-y-4">
                    <p class="text-sm text-slate-500 mb-2">Click on any lesson question below to jump directly into practice mode:</p>
                    <div id="questions-list" class="space-y-3"></div>
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
            if (tab === 'notes') {
                notesBtn.className = "pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition";
                lessonsBtn.className = "pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition";
                document.getElementById('view-notes').classList.remove('hidden');
                document.getElementById('view-lessons').classList.add('hidden');
            } else {
                lessonsBtn.className = "pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition";
                notesBtn.className = "pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition";
                document.getElementById('view-lessons').classList.remove('hidden');
                document.getElementById('view-notes').classList.add('hidden');
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
        }

        // Initial render
        renderContent();
    </script>
</body>
</html>