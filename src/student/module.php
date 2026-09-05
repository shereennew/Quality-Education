<?php
$student_name = "Alex Tan";
$active_tab = $_GET['page'] ?? 'module';

// Nav Bar Items (Identical to student_dashboard.php)
$nav_items = [
    'home'        => ['label' => 'Home',        'url' => 'student_dashboard.php'],
    'module'      => ['label' => 'Modules',     'url' => 'module.php'],
    'quiz'        => ['label' => 'Quizzes',     'url' => 'quiz.php'],
    'math_helper' => ['label' => 'Math Helper', 'url' => 'math_helper.php']
];

// All available chapters data structure
$chapters = [
    3 => [
        'title' => 'Chapter 3: Fraction Mountain',
        'topic' => 'Equivalent Fractions & Simplifying',
        'subtopics' => [
            '3.1' => [
                'title' => '3.1 Equivalent Fractions',
                'status' => 'Completed',
                'badge_color' => 'bg-emerald-100 text-emerald-700',
                'notes' => [
                    'overview' => 'Fractions that have different numerators and denominators but equal value.',
                    'points' => [
                        'Multiply or divide top and bottom by the same non-zero number.',
                        'The value of the fraction remains unchanged.'
                    ],
                    'example' => '$$\frac{1}{2} = \frac{2}{4} = \frac{4}{8}$$'
                ],
                'questions' => [
                    ['id' => 301, 'title' => 'Q1: Find equivalent fraction for 3/4 with denominator 8', 'diff' => 'Foundation'],
                    ['id' => 302, 'title' => 'Q2: Are 2/5 and 6/15 equivalent?', 'diff' => 'Intermediate']
                ]
            ],
            '3.2' => [
                'title' => '3.2 Simplifying Fractions',
                'status' => 'Completed',
                'badge_color' => 'bg-emerald-100 text-emerald-700',
                'notes' => [
                    'overview' => 'Divide both numerator and denominator by their Highest Common Factor (HCF).',
                    'points' => [
                        'Find the largest number that divides both numbers evenly.',
                        'A fraction is in simplest form when HCF is 1.'
                    ],
                    'example' => '$$\frac{6}{12} = \frac{6 \div 6}{12 \div 6} = \frac{1}{2}$$'
                ],
                'questions' => [
                    ['id' => 303, 'title' => 'Q1: Simplify 8/12', 'diff' => 'Foundation'],
                    ['id' => 304, 'title' => 'Q2: Simplify 15/45 to lowest terms', 'diff' => 'Intermediate']
                ]
            ]
        ]
    ],
    4 => [
        'title' => 'Chapter 4: Waterfall Forest',
        'topic' => 'Adding & Subtracting Unlike Fractions',
        'subtopics' => [
            '4.1' => [
                'title' => '4.1 Finding Common Denominators',
                'status' => 'Completed',
                'badge_color' => 'bg-emerald-100 text-emerald-700',
                'notes' => [
                    'overview' => 'Before adding or subtracting unlike fractions, find a common denominator using Least Common Multiples (LCM).',
                    'points' => [
                        'List multiples for both denominators until you find a match.',
                        'Multiply both top and bottom by the same factor.'
                    ],
                    'example' => '$$\frac{1}{2} = \frac{1 \times 3}{2 \times 3} = \frac{3}{6}$$'
                ],
                'questions' => [
                    ['id' => 401, 'title' => 'Q1: Find LCM of 3 and 4', 'diff' => 'Foundation'],
                    ['id' => 402, 'title' => 'Q2: Convert 2/3 to denominator 12', 'diff' => 'Intermediate']
                ]
            ],
            '4.2' => [
                'title' => '4.2 Adding Unlike Fractions',
                'status' => 'In Progress',
                'badge_color' => 'bg-blue-100 text-blue-700',
                'notes' => [
                    'overview' => 'Convert denominators to be identical, add only the numerators, and simplify the fraction.',
                    'points' => [
                        'Keep the common denominator the same.',
                        'Add top numbers together: $a/c + b/c = (a+b)/c$.'
                    ],
                    'example' => '$$\frac{1}{3} + \frac{1}{6} = \frac{2}{6} + \frac{1}{6} = \frac{3}{6} = \frac{1}{2}$$'
                ],
                'questions' => [
                    ['id' => 403, 'title' => 'Q1: Solve 1/4 + 1/2', 'diff' => 'Foundation'],
                    ['id' => 404, 'title' => 'Q2: Solve 2/5 + 1/3', 'diff' => 'Intermediate'],
                    ['id' => 405, 'title' => 'Q3: Solve 3/8 + 1/4 and simplify', 'diff' => 'Master']
                ]
            ],
            '4.3' => [
                'title' => '4.3 Subtracting Unlike Fractions',
                'status' => 'Locked',
                'badge_color' => 'bg-slate-100 text-slate-500',
                'notes' => [
                    'overview' => 'Equalize denominators first, then subtract the numerators.',
                    'points' => [
                        'Subtract top numbers only.',
                        'Simplify the resulting fraction if needed.'
                    ],
                    'example' => '$$\frac{3}{4} - \frac{1}{2} = \frac{3}{4} - \frac{2}{4} = \frac{1}{4}$$'
                ],
                'questions' => [
                    ['id' => 406, 'title' => 'Q1: Solve 3/4 - 1/4', 'diff' => 'Foundation'],
                    ['id' => 407, 'title' => 'Q2: Solve 5/6 - 1/3', 'diff' => 'Intermediate']
                ]
            ]
        ]
    ],
    5 => [
        'title' => 'Chapter 5: Mixed Number Valley',
        'topic' => 'Multiplying Fractions & Mixed Numbers',
        'subtopics' => [
            '5.1' => [
                'title' => '5.1 Converting Improper Fractions',
                'status' => 'Locked',
                'badge_color' => 'bg-slate-100 text-slate-500',
                'notes' => [
                    'overview' => 'Convert mixed numbers into improper fractions before multiplying.',
                    'points' => [
                        'Multiply whole number by denominator.',
                        'Add numerator to the result.'
                    ],
                    'example' => '$$2 \frac{1}{3} = \frac{(2 \times 3) + 1}{3} = \frac{7}{3}$$'
                ],
                'questions' => [
                    ['id' => 501, 'title' => 'Q1: Convert 1 3/4 to improper fraction', 'diff' => 'Foundation'],
                    ['id' => 502, 'title' => 'Q2: Convert 3 2/5 to improper fraction', 'diff' => 'Intermediate']
                ]
            ],
            '5.2' => [
                'title' => '5.2 Multiplying Fractions',
                'status' => 'Locked',
                'badge_color' => 'bg-slate-100 text-slate-500',
                'notes' => [
                    'overview' => 'Multiply top numbers together and bottom numbers together.',
                    'points' => [
                        'Numerator x Numerator = New Numerator.',
                        'Denominator x Denominator = New Denominator.'
                    ],
                    'example' => '$$\frac{2}{3} \times \frac{4}{5} = \frac{8}{15}$$'
                ],
                'questions' => [
                    ['id' => 503, 'title' => 'Q1: Solve 1/2 x 3/5', 'diff' => 'Foundation'],
                    ['id' => 504, 'title' => 'Q2: Solve 2/3 x 9/10', 'diff' => 'Intermediate']
                ]
            ]
        ]
    ]
];

// Set selected chapter (defaults to Chapter 4)
$selected_chap_id = isset($_GET['chap']) && isset($chapters[(int)$_GET['chap']]) ? (int)$_GET['chap'] : 4;
$active_chapter = $chapters[$selected_chap_id];
$first_subtopic_key = array_key_first($active_chapter['subtopics']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module View - <?= htmlspecialchars($student_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- MathJax for rendering equations correctly -->
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
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
</head>
<body class="bg-pastel-bg text-pastel-text font-sans min-h-screen flex flex-col">

    <!-- Top Navigation Bar (Matches Dashboard 1:1) -->
    <nav class="bg-pastel-nav shadow-sm border-b border-blue-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Leftmost: Student Avatar & Name -->
                <div class="flex items-center space-x-3 pr-4 border-r border-blue-200">
                    <div class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <?= strtoupper(substr($student_name, 0, 1)) ?>
                    </div>
                    <span class="font-semibold text-pastel-text text-base whitespace-nowrap">
                        <?= htmlspecialchars($student_name) ?>
                    </span>
                </div>

                <!-- Center Navigation Items (Text Only) -->
                <div class="flex space-x-2">
                    <?php foreach ($nav_items as $key => $item): ?>
                        <a href="<?= $item['url'] ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $key === $active_tab ? 'bg-pastel-primary text-white shadow-sm' : 'text-pastel-text hover:bg-white/60' ?>">
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Right: Utility Tools & Grade Tag (Text Only) -->
                <div class="flex items-center space-x-3">
                    
                    <!-- Read-Aloud Tool Button -->
                    <button type="button" 
                            title="Read Aloud"
                            class="px-3 py-1.5 rounded-lg bg-white/80 hover:bg-white text-slate-600 hover:text-pastel-hover text-xs border border-blue-100 transition shadow-sm font-medium">
                        Read Aloud
                    </button>

                    <!-- Digital Scratchpad Tool Button -->
                    <button type="button" 
                            title="Scratchpad"
                            class="px-3 py-1.5 rounded-lg bg-white/80 hover:bg-white text-slate-600 hover:text-pastel-hover text-xs border border-blue-100 transition shadow-sm font-medium">
                        Scratchpad
                    </button>

                    <!-- Grade Tag -->
                    <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1.5 rounded-full whitespace-nowrap">
                        Year 4 Math
                    </span>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Section -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Chapter Selector Bar -->
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
            
            <!-- LEFT SIDE: Subtopics List (4 Columns) -->
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

                <!-- TAB CONTENT 2: LESSONS & QUESTIONS VIEW -->
                <div id="view-lessons" class="hidden space-y-4">
                    <p class="text-sm text-slate-500 mb-2">Click on any lesson question below to jump directly into practice mode:</p>
                    <div id="questions-list" class="space-y-3">
                        <!-- Questions injected dynamically -->
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- JS State Management -->
    <script>
        const subtopicData = <?= json_encode($active_chapter['subtopics']) ?>;
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
            const data = subtopicData[selectedKey];
            if (!data) return;

            document.getElementById('active-title').innerText = data.title;

            // Render Notes
            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            data.notes.points.forEach(pt => {
                const li = document.createElement('li');
                li.innerText = pt;
                pointsList.appendChild(li);
            });

            document.getElementById('note-example').innerHTML = data.notes.example;

            // Render Lessons Questions
            const qList = document.getElementById('questions-list');
            qList.innerHTML = '';
            data.questions.forEach(q => {
                const card = document.createElement('div');
                card.className = "p-4 border border-slate-100 rounded-xl bg-pastel-bg flex justify-between items-center";
                card.innerHTML = `
                    <div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-pastel-hover">${q.diff}</span>
                        <h4 class="font-semibold text-sm text-slate-700 mt-1">${q.title}</h4>
                    </div>
                    <a href="quiz.php?q=${q.id}" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
                        Jump to Question →
                    </a>
                `;
                qList.appendChild(card);
            });

            // Re-render LaTeX expressions with MathJax
            if (window.MathJax && window.MathJax.typesetPromise) {
                MathJax.typesetPromise();
            }
        }

        // Initial Load
        renderContent();
    </script>
</body>
</html>