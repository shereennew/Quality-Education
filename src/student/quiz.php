<?php
<<<<<<<<< Temporary merge branch 1
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp'    => 320
];

// Mock database / chapters matching module.php
$chapters = [
    1 => [
        'title' => 'Whole Numbers & Basic Arithmetic',
        'topic' => 'Operations up to 1,000,000'
    ],
    2 => [
        'title' => 'Fractions, Decimals & Percentages',
        'topic' => 'Conversion and Calculations'
    ]
];

// Determine selected chapter ID from URL query parameters (default to 1)
$selected_chap_id = isset($_GET['chapter']) && isset($chapters[$_GET['chapter']]) ? (int)$_GET['chapter'] : 1;
$chapter_info = $chapters[$selected_chap_id];

// Mock section quizzes and overall quiz based on chapter
$section_quizzes = [
    '1.1' => [
        'title' => 'Place Value & Digit Values',
        'desc' => 'Test your understanding of place values up to millions.',
        'questions' => [
            [
                'q' => 'What is the value of 7 in 472,150?',
                'options' => ['7,000', '70,000', '700,000', '700'],
                'ans' => 1
            ],
            [
                'q' => 'Which digit is in the ten-thousands place in 834,219?',
                'options' => ['8', '3', '4', '2'],
                'ans' => 1
            ]
        ]
    ],
    '1.2' => [
        'title' => 'Addition & Subtraction',
        'desc' => 'Practice aligning and calculating large number sums and differences.',
        'questions' => [
            [
                'q' => 'Calculate: 45,210 + 12,345',
                'options' => ['57,555', '56,555', '57,455', '58,555'],
                'ans' => 0
            ],
            [
                'q' => 'What is 80,000 - 34,200?',
                'options' => ['46,800', '45,800', '45,700', '46,200'],
                'ans' => 1
            ]
=========
$student_name = "Alex Tan";

// 顶部 Navigation 配置
$nav_items = [
    'home'     => ['label' => 'Home',     'url' => 'student_dashboard.php?page=home'],
    'notes'    => ['label' => 'Notes',    'url' => 'student_dashboard.php?page=notes'],
    'practice' => ['label' => 'Practice', 'url' => 'student_dashboard.php?page=practice'],
    'quiz'     => ['label' => 'Quiz',     'url' => 'quiz.php'],
    'module'   => ['label' => 'Module',   'url' => 'module.php']
];

// 章节信息
$chapter_info = [
    'title' => 'Chapter 4: Waterfall Forest',
    'topic' => 'Adding & Subtracting Unlike Fractions'
];

// 分小节练习题 (Section Quizzes)
$section_quizzes = [
    '4.1' => [
        'title' => '4.1 Finding Common Denominators',
        'desc' => '3 Questions • Focus on Least Common Multiples (LCM)',
        'questions' => [
            ['q' => 'What is the Lowest Common Denominator (LCD) for 1/3 and 1/4?', 'options' => ['7', '12', '6', '24'], 'ans' => 1],
            ['q' => 'Convert 2/5 to an equivalent fraction with denominator 15:', 'options' => ['4/15', '6/15', '5/15', '8/15'], 'ans' => 1],
            ['q' => 'What is the LCD for 3/8 and 1/6?', 'options' => ['48', '14', '24', '18'], 'ans' => 2]
        ]
    ],
    '4.2' => [
        'title' => '4.2 Adding Unlike Fractions',
        'desc' => '3 Questions • Focus on converting & adding numerators',
        'questions' => [
            ['q' => 'Solve: 1/2 + 1/4 =', 'options' => ['2/6', '3/4', '2/4', '3/8'], 'ans' => 1],
            ['q' => 'Solve: 2/5 + 1/3 =', 'options' => ['11/15', '3/8', '3/15', '7/15'], 'ans' => 0],
            ['q' => 'Solve: 1/6 + 2/3 =', 'options' => ['3/9', '5/6', '3/6', '4/6'], 'ans' => 1]
        ]
    ],
    '4.3' => [
        'title' => '4.3 Subtracting Unlike Fractions',
        'desc' => '3 Questions • Focus on subtracting unequal parts',
        'questions' => [
            ['q' => 'Solve: 3/4 - 1/2 =', 'options' => ['2/2', '1/4', '2/4', '1/2'], 'ans' => 1],
            ['q' => 'Solve: 5/6 - 1/3 =', 'options' => ['4/3', '1/2', '4/6', '2/3'], 'ans' => 1],
            ['q' => 'Solve: 7/10 - 2/5 =', 'options' => ['5/5', '3/10', '5/10', '1/5'], 'ans' => 1]
>>>>>>>>> Temporary merge branch 2
        ]
    ]
];

<<<<<<<<< Temporary merge branch 1
$overall_chapter_quiz = [
    'title' => 'Chapter ' . $selected_chap_id . ' Comprehensive Exam',
    'desc' => 'Full assessment covering all core concepts in this module.',
    'questions' => [
        [
            'q' => 'What is the value of 7 in 472,150?',
            'options' => ['7,000', '70,000', '700,000', '700'],
            'ans' => 1
        ],
        [
            'q' => 'Calculate: 45,210 + 12,345',
            'options' => ['57,555', '56,555', '57,455', '58,555'],
            'ans' => 0
        ],
        [
            'q' => 'Which digit is in the ten-thousands place in 834,219?',
            'options' => ['8', '3', '4', '2'],
            'ans' => 1
        ]
    ]
=========
// 整章综合测试 (Overall Chapter Quiz)
$overall_chapter_quiz = [
    'title' => 'Chapter 4 Overall Master Quiz',
    'desc' => '9 Mixed Questions • Full Chapter Mastery Test',
    'questions' => array_merge(
        $section_quizzes['4.1']['questions'],
        $section_quizzes['4.2']['questions'],
        $section_quizzes['4.3']['questions']
    )
>>>>>>>>> Temporary merge branch 2
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<<<< Temporary merge branch 1
    <title>Eduhunt - Quizzes</title>
=========
    <title>Quiz Center - <?= htmlspecialchars($student_name) ?></title>
>>>>>>>>> Temporary merge branch 2
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
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-center p-6 pt-32">

    <!-- NAV BAR -->
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

            <!-- CENTER: LARGE NAVIGATION -->
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

            <!-- RIGHT: PROFILE -->
            <div class="flex items-center flex-shrink-0 relative">
                <button type="button" class="flex items-center gap-3 py-3 px-5 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-12 h-12 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-xl">
                        <?= htmlspecialchars(strtoupper(substr($student['name'], 0, 1))) ?>
                    </div>
                    <span class="text-xl font-bold text-pastel-text hidden sm:block">
                        <?= htmlspecialchars($student['name']) ?>
                    </span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>

                <!-- PROFILE DROPDOWN -->
                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-60 mt-2" id="user-dropdown">
                    <div class="px-5 py-4 border-b-2 border-pastel-nav">
                        <span class="block text-lg font-bold text-pastel-text"><?= htmlspecialchars($student['name']) ?></span>
                        <span class="block text-sm text-pastel-primary font-semibold mt-1">Level <?= (int)$student['level'] ?></span>
                    </div>
                    <ul class="p-2 text-lg text-pastel-text font-medium" aria-labelledby="user-menu-button">
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Profile</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Settings</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Sign out</a></li>
                    </ul>
                </div>

                <!-- MOBILE MENU BUTTON -->
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-3 ml-3 w-14 h-14 justify-center text-pastel-primary rounded-2xl bg-pastel-card border-2 border-pastel-primary/20 md:hidden hover:bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary/30" aria-controls="navbar-user" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 7h14M5 12h14M5 17h14" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- MOBILE MENU -->
        <div class="hidden w-full md:hidden px-5 pb-5 absolute top-24 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg" id="navbar-user">
            <ul class="flex flex-col gap-2 text-lg font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm">
                <li><a href="index.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Home</a></li>
                <li><a href="discussion.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Discussion</a></li>
                <li><a href="module.php" class="block py-4 px-5 text-white bg-pastel-primary rounded-xl shadow-sm">Modules</a></li>
                <li><a href="quiz.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Quizzes</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Section -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Chapter Selector Bar -->
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2">
            <?php foreach ($all_chapters as $chap): ?>
                <?php $chap_num = (int)$chap['id']; ?>
                <a href="module.php?chap=<?= $chap_num ?>" class="px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap flex items-center gap-2 <?= $chap_num === $selected_chap_id ? 'bg-pastel-primary text-white' : 'bg-pastel-card text-slate-600 hover:bg-blue-50 border border-blue-100' ?>">
                    <span>Chapter <?= $chap_num ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Header -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Quiz Center</span>
<<<<<<<<< Temporary merge branch 1
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($chapter_info['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($chapter_info['topic']) ?></p>
            </div>
            <a href="module.php?chap=<?= $selected_chap_id ?>" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Module Notes
=========
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= $chapter_info['title'] ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= $chapter_info['topic'] ?></p>
            </div>
            <a href="student_dashboard.php" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Dashboard
>>>>>>>>> Temporary merge branch 2
            </a>
        </div>

        <!-- QUIZ MENU SELECTION VIEW -->
        <div id="quiz-menu" class="space-y-8">
            
            <!-- SECTION 1: Subtopic / Section Practice Quizzes -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-pastel-text">1. Section Practice Quizzes</h2>
                        <p class="text-xs text-slate-500">Practice targeted questions section by section.</p>
                    </div>
                    <span class="text-xs bg-emerald-100 text-emerald-700 font-bold px-2.5 py-1 rounded-md">Short & Focused</span>
                </div>

<<<<<<<<< Temporary merge branch 1
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
=========
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
>>>>>>>>> Temporary merge branch 2
                    <?php foreach ($section_quizzes as $code => $s_quiz): ?>
                        <div class="bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm flex flex-col justify-between hover:border-pastel-primary transition">
                            <div>
                                <span class="text-xs font-bold text-pastel-primary">Section <?= $code ?></span>
<<<<<<<<< Temporary merge branch 1
                                <h3 class="font-bold text-base text-pastel-text mt-1"><?= htmlspecialchars($s_quiz['title']) ?></h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed"><?= htmlspecialchars($s_quiz['desc']) ?></p>
=========
                                <h3 class="font-bold text-base text-pastel-text mt-1"><?= $s_quiz['title'] ?></h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed"><?= $s_quiz['desc'] ?></p>
>>>>>>>>> Temporary merge branch 2
                            </div>
                            <button onclick='startQuiz(<?= json_encode($s_quiz) ?>)' class="mt-5 w-full bg-blue-50 hover:bg-pastel-primary hover:text-white text-pastel-hover text-xs font-bold py-2.5 rounded-xl transition text-center border border-blue-100">
                                Start Section Quiz →
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT SIDE: Sub-tab Navigation for Notes & Lessons (8 Columns) -->
            <div class="lg:col-span-8 bg-pastel-card p-6 sm:p-8 rounded-2xl border border-blue-100 shadow-sm">
                
                <!-- Sub-tab Navigation (Notes vs Lessons) -->
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
                        <h2 class="text-lg font-bold text-pastel-text">2. Overall Chapter Quiz</h2>
<<<<<<<<< Temporary merge branch 1
                        <p class="text-xs text-slate-500">Comprehensive test combining all concepts from Chapter <?= $selected_chap_id ?>.</p>
=========
                        <p class="text-xs text-slate-500">Comprehensive test combining all concepts from Chapter 4.</p>
>>>>>>>>> Temporary merge branch 2
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Key Steps</h3>
                        <ul id="note-points" class="list-disc list-inside text-sm text-slate-600 space-y-1.5"></ul>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Chapter Final Test</span>
<<<<<<<<< Temporary merge branch 1
                        <h3 class="text-xl font-bold text-pastel-text mt-0.5"><?= htmlspecialchars($overall_chapter_quiz['title']) ?></h3>
                        <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($overall_chapter_quiz['desc']) ?></p>
=========
                        <h3 class="text-xl font-bold text-pastel-text mt-0.5"><?= $overall_chapter_quiz['title'] ?></h3>
                        <p class="text-xs text-slate-600 mt-1"><?= $overall_chapter_quiz['desc'] ?></p>
>>>>>>>>> Temporary merge branch 2
                    </div>
                    <button onclick='startQuiz(<?= json_encode($overall_chapter_quiz) ?>)' class="w-full sm:w-auto px-6 py-3 bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold rounded-xl shadow-sm transition whitespace-nowrap">
                        Take Overall Quiz 🏆
                    </button>
                </div>
            </div>

                <!-- TAB CONTENT 2: LESSONS & QUESTIONS VIEW -->
                <div id="view-lessons" class="hidden space-y-4">
                    <p class="text-sm text-slate-500 mb-2">Click on any lesson question below to jump directly into practice mode:</p>
                    <div id="questions-list" class="space-y-3">
                        <!-- Questions injected dynamically -->
                    </div>
                </div>

            </div>

            <div class="flex justify-center gap-3">
                <button onclick="exitQuiz()" class="px-6 py-2.5 bg-pastel-primary text-white text-xs font-bold rounded-xl hover:bg-pastel-hover transition">
                    Back to Quiz List
                </button>
<<<<<<<<< Temporary merge branch 1
                <a href="module.php?chap=<?= $selected_chap_id ?>" class="px-6 py-2.5 bg-white border border-blue-200 text-pastel-text text-xs font-bold rounded-xl hover:bg-slate-50 transition">
=========
                <a href="module.php" class="px-6 py-2.5 bg-white border border-blue-200 text-pastel-text text-xs font-bold rounded-xl hover:bg-slate-50 transition">
>>>>>>>>> Temporary merge branch 2
                    Review Module Notes
                </a>
            </div>
        </div>

    </main>

    <!-- JS State Management -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
    <script>
        const subtopicData = <?= json_encode($active_chapter['subtopics'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let selectedKey = "<?= $first_subtopic_key ?>";
        let activeTab = 'notes';

        function selectSubtopic(key) {
            selectedKey = key;

            // Highlight subtopic button
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
            if (!subtopicData || !subtopicData[selectedKey]) return;

            const data = subtopicData[selectedKey];

            document.getElementById('active-title').innerText = data.title;

            // Render Notes
            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            if (Array.isArray(data.notes.points)) {
                data.notes.points.forEach(point => {
                    const li = document.createElement('li');
                    li.innerHTML = point;
                    pointsList.appendChild(li);
                });
            }

            document.getElementById('note-example').innerHTML = data.notes.example;

            // Render Questions
            const questionsList = document.getElementById('questions-list');
            questionsList.innerHTML = '';
            if (Array.isArray(data.questions) && data.questions.length > 0) {
                data.questions.forEach(q => {
                    const qAnchor = document.createElement('a');
                    qAnchor.className = "block p-4 rounded-xl border border-blue-50 bg-pastel-bg hover:border-pastel-primary transition";
                    qAnchor.href = `quiz.php?q=${q.id}`;
                    qAnchor.innerHTML = `
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-pastel-primary">${q.diff || ''}</span>
                        </div>
                        <p class="font-semibold text-sm text-pastel-text">${q.title}</p>
                    `;
                    questionsList.appendChild(qAnchor);
                });
            } else {
                questionsList.innerHTML = '<p class="text-sm text-slate-400">No questions available for this subtopic.</p>';
            }

            // Trigger MathJax re-render if loaded
            if (window.MathJax) {
                MathJax.typeset();
            }
        }

        // Initial render on load
        renderContent();
    </script>
<<<<<<<<< Temporary merge branch 1
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
=========
>>>>>>>>> Temporary merge branch 2
</body>
</html>