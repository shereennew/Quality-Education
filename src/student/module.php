<?php
<<<<<<<<< Temporary merge branch 1
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
];

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
=========
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
>>>>>>>>> Temporary merge branch 2
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
=========
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
>>>>>>>>> Temporary merge branch 2
                ]
            ]
        ]
    ]
];

<<<<<<<<< Temporary merge branch 1
// Handle chapter selection from URL query parameters safely
$selected_chap_id = isset($_GET['chap']) && isset($chapters[$_GET['chap']]) ? (int)$_GET['chap'] : 1;
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
<<<<<<<<< Temporary merge branch 1
    <title>Eduhunt - Modules</title>
    <script src="https://cdn.tailwindcss.com"></script>
=========
    <title>Module View - <?= htmlspecialchars($student_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- MathJax for rendering equations correctly -->
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
>>>>>>>>> Temporary merge branch 2
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
<<<<<<<<< Temporary merge branch 1
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

        <!-- MOBILE MENU -->
        <div class="hidden w-full md:hidden px-5 pb-5 absolute top-24 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg" id="navbar-user">
            <ul class="flex flex-col gap-2 text-lg font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm">
                <li><a href="index.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Home</a></li>
                <li><a href="discussion.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Discussion</a></li>
                <li><a href="module.php" class="block py-4 px-5 text-white bg-pastel-primary rounded-xl shadow-sm">Modules</a></li>
                <li><a href="quiz.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Quizzes</a></li>
            </ul>
=========
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
>>>>>>>>> Temporary merge branch 2
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

    <!-- JS State Management -->
<<<<<<<<< Temporary merge branch 1
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

            // Trigger MathJax re-render if loaded
            if (window.MathJax) {
                MathJax.typeset();
            }
        }

        // Initial render on load
=========
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
>>>>>>>>> Temporary merge branch 2
        renderContent();
    </script>
</body>
</html>