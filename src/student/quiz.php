<?php
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
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
        ]
    ]
];

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
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduhunt - Quizzes</title>
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

    <!-- EXACT NAV BAR -->
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
                        <a href="module.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Modules</span>
                        </a>
                    </li>
                    <li>
                        <a href="quiz.php" class="flex items-center px-7 py-4 rounded-2xl bg-pastel-primary text-white shadow-md transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5">
                            <span>Quizzes</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- RIGHT: PROFILE -->
            <div class="flex items-center flex-shrink-0 relative">
                <button type="button" class="flex items-center gap-3 py-3 px-5 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-12 h-12 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-xl">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-xl font-bold text-pastel-text hidden sm:block">
                        <?= $student['name'] ?>
                    </span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>

                <!-- PROFILE DROPDOWN -->
                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-60 mt-2" id="user-dropdown">
                    <div class="px-5 py-4 border-b-2 border-pastel-nav">
                        <span class="block text-lg font-bold text-pastel-text"><?= $student['name'] ?></span>
                        <span class="block text-sm text-pastel-primary font-semibold mt-1">Level <?= $student['level'] ?></span>
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
                <li><a href="module.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Modules</a></li>
                <li><a href="quiz.php" class="block py-4 px-5 text-white bg-pastel-primary rounded-xl shadow-sm">Quizzes</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
        
        <!-- Header Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Quiz Center</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($chapter_info['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($chapter_info['topic']) ?></p>
            </div>
            <a href="module.php?chap=<?= $selected_chap_id ?>" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Module Notes
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($section_quizzes as $code => $s_quiz): ?>
                        <div class="bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm flex flex-col justify-between hover:border-pastel-primary transition">
                            <div>
                                <span class="text-xs font-bold text-pastel-primary">Section <?= $code ?></span>
                                <h3 class="font-bold text-base text-pastel-text mt-1"><?= htmlspecialchars($s_quiz['title']) ?></h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed"><?= htmlspecialchars($s_quiz['desc']) ?></p>
                            </div>
                            <button onclick='startQuiz(<?= json_encode($s_quiz) ?>)' class="mt-5 w-full bg-blue-50 hover:bg-pastel-primary hover:text-white text-pastel-hover text-xs font-bold py-2.5 rounded-xl transition text-center border border-blue-100">
                                Start Section Quiz →
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECTION 2: Overall Chapter Quiz -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-pastel-text">2. Overall Chapter Quiz</h2>
                        <p class="text-xs text-slate-500">Comprehensive test combining all concepts from Chapter <?= $selected_chap_id ?>.</p>
                    </div>
                    <span class="text-xs bg-purple-100 text-purple-700 font-bold px-2.5 py-1 rounded-md">Full Assessment</span>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-indigo-50/50 p-6 rounded-2xl border border-blue-200 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-sm">
                    <div>
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Chapter Final Test</span>
                        <h3 class="text-xl font-bold text-pastel-text mt-0.5"><?= htmlspecialchars($overall_chapter_quiz['title']) ?></h3>
                        <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($overall_chapter_quiz['desc']) ?></p>
                    </div>
                    <button onclick='startQuiz(<?= json_encode($overall_chapter_quiz) ?>)' class="w-full sm:w-auto px-6 py-3 bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold rounded-xl shadow-sm transition whitespace-nowrap">
                        Take Overall Quiz 🏆
                    </button>
                </div>
            </div>

        </div>

        <!-- ACTIVE QUIZ ENGINE VIEW (Hidden by default) -->
        <div id="quiz-runner" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-100">
                <div>
                    <span id="quiz-title-display" class="text-xs font-bold text-pastel-primary uppercase tracking-wider"></span>
                    <h3 id="quiz-subtitle-display" class="text-lg font-bold text-pastel-text"></h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">Progress</span>
                    <span id="quiz-progress" class="text-sm font-bold text-pastel-primary">1 / 3</span>
                </div>
            </div>

            <!-- Question Box -->
            <div class="mb-6">
                <h4 id="question-text" class="text-base font-semibold text-slate-800 mb-4"></h4>
                <div id="options-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                <button onclick="exitQuiz()" class="text-xs font-semibold text-slate-400 hover:text-rose-500 transition">
                    ✕ Cancel & Exit
                </button>
            </div>
        </div>

        <!-- QUIZ RESULTS VIEW (Hidden by default) -->
        <div id="quiz-result" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100 text-center">
            <div class="w-16 h-16 bg-blue-50 text-pastel-primary rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                🎯
            </div>
            <h2 class="text-2xl font-bold text-pastel-text mb-1">Quiz Completed!</h2>
            <p id="result-quiz-name" class="text-sm text-slate-500 mb-4"></p>

            <div class="bg-pastel-bg p-6 rounded-2xl border border-blue-50 max-w-xs mx-auto mb-6">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Your Score</span>
                <span id="result-score" class="text-3xl font-extrabold text-pastel-primary">0 / 0</span>
            </div>

            <div class="flex justify-center gap-3">
                <button onclick="exitQuiz()" class="px-6 py-2.5 bg-pastel-primary text-white text-xs font-bold rounded-xl hover:bg-pastel-hover transition">
                    Back to Quiz List
                </button>
                <a href="module.php?chap=<?= $selected_chap_id ?>" class="px-6 py-2.5 bg-white border border-blue-200 text-pastel-text text-xs font-bold rounded-xl hover:bg-slate-50 transition">
                    Review Module Notes
                </a>
            </div>
        </div>

    </main>

    <!-- Interactive Quiz Engine Script -->
    <script>
        let currentQuiz = null;
        let activeQIndex = 0;
        let score = 0;

        function startQuiz(quizData) {
            currentQuiz = quizData;
            activeQIndex = 0;
            score = 0;

            document.getElementById('quiz-menu').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-runner').classList.remove('hidden');

            document.getElementById('quiz-title-display').innerText = "Active Quiz";
            document.getElementById('quiz-subtitle-display').innerText = currentQuiz.title;

            renderQuestion();
        }

        function renderQuestion() {
            const qData = currentQuiz.questions[activeQIndex];
            document.getElementById('quiz-progress').innerText = `${activeQIndex + 1} / ${currentQuiz.questions.length}`;
            document.getElementById('question-text').innerText = qData.q;

            const optsDiv = document.getElementById('options-container');
            optsDiv.innerHTML = '';

            qData.options.forEach((opt, idx) => {
                const btn = document.createElement('button');
                btn.className = "p-4 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 text-sm font-medium transition duration-200";
                btn.innerText = opt;
                btn.onclick = () => selectOption(idx === qData.ans);
                optsDiv.appendChild(btn);
            });
        }

        function selectOption(isCorrect) {
            if (isCorrect) score++;

            if (activeQIndex + 1 < currentQuiz.questions.length) {
                activeQIndex++;
                renderQuestion();
            } else {
                showResults();
            }
        }

        function showResults() {
            document.getElementById('quiz-runner').classList.add('hidden');
            document.getElementById('quiz-result').classList.remove('hidden');

            document.getElementById('result-quiz-name').innerText = currentQuiz.title;
            document.getElementById('result-score').innerText = `${score} / ${currentQuiz.questions.length}`;
        }

        function exitQuiz() {
            document.getElementById('quiz-runner').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-menu').classList.remove('hidden');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>