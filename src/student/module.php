<?php
require_once __DIR__ . '/../config/db.php';


// 1. Fetch active student details (Defaults to Amina Yusuf from setup.php)
$student_id = 1;
$stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    // Fallback if db isn't seeded yet
    $student = ['name' => 'Amina Yusuf', 'score' => 92, 'status' => 'Advancing'];
}

// 2. Fetch all chapters & their unlock status for current student
$stmt_chapters = $pdo->prepare("
    SELECT c.id, c.chapter_name, c.is_unlocked, 
           COALESCE(sp.status, 'Locked') AS progress_status
    FROM classroom_chapters c
    LEFT JOIN student_progress sp 
        ON c.chapter_name = sp.chapter_name AND sp.student_id = ?
    ORDER BY c.id ASC
");
$stmt_chapters->execute([$student_id]);
$raw_chapters = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);

// Map database schema to rich structured content for the UI
$chapter_knowledge_base = [
    1 => [
        'title' => 'Ancient Pyramid: Fundamentals',
        'topic' => 'Basic Fraction Addition & Subtraction',
        'subtopics' => [
            '1.1' => [
                'title' => 'Adding Like Fractions',
                'badge_color' => 'bg-emerald-100 text-emerald-700',
                'status' => 'Completed',
                'notes' => [
                    'overview' => 'Like fractions share the exact same denominator. Adding them involves summing the numerators directly while keeping the common denominator unchanged.',
                    'points' => [
                        'Verify that both denominators are identical.',
                        'Add the top numbers (numerators) together.',
                        'Keep the bottom number (denominator) the same.',
                        'Simplify the resulting fraction if possible.'
                    ],
                    'example' => 'Calculate: $\\frac{1}{5} + \\frac{2}{5}$ <br><span class="text-pastel-primary font-bold">Answer: $\\frac{3}{5}$</span>'
                ],
                'questions' => [
                    ['id' => 1, 'diff' => 'Easy', 'title' => 'What is $\\frac{1}{5} + \\frac{2}{5}$?'],
                    ['id' => 2, 'diff' => 'Medium', 'title' => 'What is $\\frac{3}{4} + \\frac{2}{4}$?']
                ]
            ],
            '1.2' => [
                'title' => 'Subtracting Like Fractions',
                'badge_color' => 'bg-blue-100 text-blue-700',
                'status' => 'Completed',
                'notes' => [
                    'overview' => 'Subtracting like fractions requires taking away the second numerator from the first while maintaining the original denominator.',
                    'points' => [
                        'Ensure denominators match.',
                        'Subtract the numerators: $a/c - b/c = (a-b)/c$.',
                        'Simplify to lowest terms.'
                    ],
                    'example' => 'Calculate: $\\frac{7}{10} - \\frac{3}{10}$ <br><span class="text-pastel-primary font-bold">Answer: $\\frac{4}{10} = \\frac{2}{5}$</span>'
                ],
                'questions' => [
                    ['id' => 3, 'diff' => 'Easy', 'title' => 'What is $\\frac{7}{10} - \\frac{3}{10}$?'],
                    ['id' => 4, 'diff' => 'Medium', 'title' => 'What is $\\frac{9}{12} - \\frac{4}{12}$?']
                ]
            ]
        ]
    ],
    2 => [
        'title' => 'Cherry Blossom: Multiplications',
        'topic' => 'Equivalent Fractions & Simplification',
        'subtopics' => [
            '2.1' => [
                'title' => 'Simplifying Fractions',
                'badge_color' => 'bg-amber-100 text-amber-700',
                'status' => 'Completed',
                'notes' => [
                    'overview' => 'Simplifying or reducing a fraction means dividing both the numerator and denominator by their Greatest Common Divisor (GCD).',
                    'points' => [
                        'Find common factors for both numerator and denominator.',
                        'Divide both numbers by their highest common factor.',
                        'A fraction is in simplest form when its GCD is 1.'
                    ],
                    'example' => 'Simplify $\\frac{6}{8}$ to lowest terms. <br><span class="text-pastel-primary font-bold">Answer: $\\frac{3}{4}$ (Divided by 2)</span>'
                ],
                'questions' => [
                    ['id' => 5, 'diff' => 'Easy', 'title' => 'Simplify $\\frac{6}{8}$ to lowest terms.'],
                    ['id' => 6, 'diff' => 'Medium', 'title' => 'Simplify $\\frac{10}{15}$ to lowest terms.']
                ]
            ]
        ]
    ],
    3 => [
        'title' => 'Volcanic Jungle: Fractions & Decimals',
        'topic' => 'Mixed Numbers & Basic Operations',
        'subtopics' => [
            '3.1' => [
                'title' => 'Improper Fractions & Mixed Numbers',
                'badge_color' => 'bg-purple-100 text-purple-700',
                'status' => 'Completed',
                'notes' => [
                    'overview' => 'An improper fraction has a numerator greater than or equal to its denominator. A mixed number combines a whole number and a proper fraction.',
                    'points' => [
                        'To convert improper to mixed: Divide numerator by denominator.',
                        'The quotient becomes the whole number, the remainder becomes the new numerator.',
                        'To convert mixed to improper: $\\text{Whole} \\times \\text{Denominator} + \\text{Numerator}$.'
                    ],
                    'example' => 'Convert $\\frac{7}{3}$ to a mixed number. <br><span class="text-pastel-primary font-bold">Answer: $2\\frac{1}{3}$</span>'
                ],
                'questions' => [
                    ['id' => 7, 'diff' => 'Medium', 'title' => 'Convert $\\frac{7}{3}$ to a mixed number.'],
                    ['id' => 8, 'diff' => 'Hard', 'title' => 'Convert $3\\frac{1}{2}$ to an improper fraction.']
                ]
            ]
        ]
    ]
];

// Combine DB unlocked status with knowledge base content
$chapters = [];
foreach ($raw_chapters as $index => $row) {
    $c_id = $row['id'];
    if (isset($chapter_knowledge_base[$c_id])) {
        $chapters[$c_id] = $chapter_knowledge_base[$c_id];
        $chapters[$c_id]['is_unlocked'] = (int)$row['is_unlocked'];
        $chapters[$c_id]['progress_status'] = $row['progress_status'];
    }
}

// Fallback if DB is empty
if (empty($chapters)) {
    $chapters = $chapter_knowledge_base;
    foreach ($chapters as $k => $v) {
        $chapters[$k]['is_unlocked'] = 1;
        $chapters[$k]['progress_status'] = 'Completed';
    }
}

// Selected Chapter Logic
$selected_chap_id = isset($_GET['chap']) && isset($chapters[$_GET['chap']]) ? (int)$_GET['chap'] : array_key_first($chapters);
$active_chapter = $chapters[$selected_chap_id];

// Subtopic keys
$subtopic_keys = array_keys($active_chapter['subtopics']);
$first_subtopic_key = !empty($subtopic_keys) ? $subtopic_keys[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduhunt - Learning Modules</title>
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
    <!-- Flowbite CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
    
    <!-- MathJax for rendering Mathematical Expressions -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']]
            },
            svg: { fontCache: 'global' }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-center p-6 pt-32">

    <!-- NAVIGATION BAR -->
    <nav class="bg-pastel-nav fixed w-full h-24 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[90rem] mx-auto px-10 flex items-center justify-between">
            
            <!-- LEFT: LOGO -->
            <a href="index.php" class="flex items-center gap-4 flex-shrink-0">
                <div class="bg-pastel-badge w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-3xl">📖</span>
                </div>
                <span class="text-3xl font-black tracking-wide text-pastel-text hidden lg:block">
                    Eduhunt
                </span>
            </a>

            <!-- CENTER: NAVIGATION -->
            <div class="hidden md:flex items-center justify-center flex-1 mx-8">
                <ul class="flex items-center gap-3 text-2xl font-bold">
                    <li>
                        <a href="index.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="discussion.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Discussion</span>
                        </a>
                    </li>
                    <li>
                        <a href="module.php" class="flex items-center px-7 py-4 rounded-2xl bg-pastel-primary text-white shadow-md transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5">
                            <span>Modules</span>
                        </a>
                    </li>
                    <li>
                        <a href="quiz.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Quizzes</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- RIGHT: PROFILE DROPDOWN -->
            <div class="flex items-center flex-shrink-0 relative">
                <button type="button" class="flex items-center gap-3 py-3 px-5 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-12 h-12 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-xl">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-xl font-bold text-pastel-text hidden sm:block">
                        <?= htmlspecialchars($student['name']) ?>
                    </span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>

                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-60 mt-2" id="user-dropdown">
                    <div class="px-5 py-4 border-b-2 border-pastel-nav">
                        <span class="block text-lg font-bold text-pastel-text"><?= htmlspecialchars($student['name']) ?></span>
                        <span class="block text-sm text-pastel-primary font-semibold mt-1">Status: <?= htmlspecialchars($student['status']) ?></span>
                    </div>
                    <ul class="p-2 text-lg text-pastel-text font-medium" aria-labelledby="user-menu-button">
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Profile</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Settings</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTAINER -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- SEARCH & CHAPTER SELECTOR BAR -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Chapter Selector -->
            <div class="flex gap-3 overflow-x-auto pb-2 md:pb-0">
                <?php foreach ($chapters as $chap_num => $chap): ?>
                    <a href="module.php?chap=<?= $chap_num ?>" class="px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap flex items-center gap-2 <?= $chap_num === $selected_chap_id ? 'bg-pastel-primary text-white' : 'bg-pastel-card text-slate-600 hover:bg-blue-50 border border-blue-100' ?>">
                        <span>Chapter <?= $chap_num ?></span>
                        <?php if (!$chap['is_unlocked']): ?>
                            <span class="text-xs">🔒</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Real-time Search Input -->
            <div class="relative min-w-[260px]">
                <input type="text" id="search-input" onkeyup="filterSubtopics()" placeholder="Search topics or concepts..." class="w-full bg-pastel-card border border-blue-100 text-slate-700 text-xs rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-pastel-primary shadow-sm transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- HEADER BANNER -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Active Module</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($active_chapter['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($active_chapter['topic']) ?></p>
            </div>
            <a href="quiz.php?chapter=<?= $selected_chap_id ?>" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-5 py-3 rounded-xl transition shadow-sm flex items-center gap-2">
                <span>Take Chapter Test</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- LEFT COLUMN: Subtopics Sidebar -->
            <div class="lg:col-span-4 bg-pastel-card p-4 rounded-2xl border border-blue-100 shadow-sm h-fit">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider px-3 mb-3">Subtopics</h2>
                <div id="subtopics-container" class="space-y-2">
                    <?php foreach ($active_chapter['subtopics'] as $key => $subtopic): ?>
                        <button onclick="selectSubtopic('<?= $key ?>')" id="subtopic-btn-<?= str_replace('.', '_', $key) ?>" class="subtopic-btn w-full text-left p-3.5 rounded-xl border transition <?= $key === $first_subtopic_key ? 'border-pastel-primary bg-blue-50/70 shadow-sm' : 'border-slate-100 hover:border-blue-200' ?>" data-title="<?= strtolower($subtopic['title']) ?>">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-md <?= $subtopic['badge_color'] ?>"><?= $subtopic['status'] ?></span>
                                <span class="text-xs font-semibold text-slate-400">Subtopic <?= $key ?></span>
                            </div>
                            <h3 class="font-semibold text-sm text-pastel-text"><?= htmlspecialchars($subtopic['title']) ?></h3>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: Dynamic Notes & Questions -->
            <div class="lg:col-span-8 bg-pastel-card p-6 sm:p-8 rounded-2xl border border-blue-100 shadow-sm">
                
                <!-- Sub-tab Navigation -->
                <div class="flex border-b border-slate-100 mb-6 gap-6">
                    <button onclick="switchTab('notes')" id="tab-btn-notes" class="pb-3 text-sm font-bold border-b-2 border-pastel-primary text-pastel-primary transition">
                        📖 Notes
                    </button>
                    <button onclick="switchTab('lessons')" id="tab-btn-lessons" class="pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition">
                        ✏️ Lessons & Questions
                    </button>
                </div>

                <!-- Active Subtopic Title -->
                <h2 id="active-title" class="text-xl font-bold text-pastel-text mb-4">--</h2>

                <!-- TAB CONTENT 1: NOTES VIEW -->
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

                <!-- TAB CONTENT 2: LESSONS VIEW -->
                <div id="view-lessons" class="hidden space-y-4">
                    <p class="text-sm text-slate-500 mb-2">Click on any lesson question below to jump directly into practice mode:</p>
                    <div id="questions-list" class="space-y-3">
                        <!-- Dynamically populated via JS -->
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- FLOWBITE & JS CONTROLLERS -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
    <script>
        const subtopicData = <?= json_encode($active_chapter['subtopics']) ?>;
        let selectedKey = "<?= $first_subtopic_key ?>";
        let activeTab = 'notes';

        function selectSubtopic(key) {
            selectedKey = key;

            // Update subtopic selection styles
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

            // Render Notes
            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            data.notes.points.forEach(point => {
                const li = document.createElement('li');
                li.innerHTML = point;
                pointsList.appendChild(li);
            });

            document.getElementById('note-example').innerHTML = data.notes.example;

            // Render Questions
            const questionsList = document.getElementById('questions-list');
            questionsList.innerHTML = '';
            data.questions.forEach(q => {
                const qAnchor = document.createElement('a');
                qAnchor.className = "block p-4 rounded-xl border border-blue-50 bg-pastel-bg hover:border-pastel-primary transition shadow-xs";
                qAnchor.href = `quiz.php?q=${q.id}`;
                qAnchor.innerHTML = `
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-bold text-pastel-primary">${q.diff}</span>
                        <span class="text-xs text-slate-400 font-semibold">Start Quiz →</span>
                    </div>
                    <p class="font-semibold text-sm text-pastel-text">${q.title}</p>
                `;
                questionsList.appendChild(qAnchor);
            });

            // Re-render LaTeX expressions with MathJax
            if (window.MathJax && window.MathJax.typesetPromise) {
                MathJax.typesetPromise();
            }
        }

        // Live Search Filter for Subtopics
        function filterSubtopics() {
            const query = document.getElementById('search-input').value.toLowerCase();
            const buttons = document.querySelectorAll('.subtopic-btn');

            buttons.forEach(btn => {
                const title = btn.getAttribute('data-title');
                if (title.includes(query)) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            });
        }

        // Initialize content on page load
        document.addEventListener('DOMContentLoaded', () => {
            renderContent();
        });
    </script>
</body>
</html>