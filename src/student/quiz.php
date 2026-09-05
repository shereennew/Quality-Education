<?php
$student_name = "Alex Tan";
$active_tab = $_GET['page'] ?? 'quiz';

// Nav Bar Items (Identical to student_dashboard.php)
$nav_items = [
    'home'        => ['label' => 'Home',        'url' => 'student_dashboard.php'],
    'module'      => ['label' => 'Modules',     'url' => 'module.php'],
    'quiz'        => ['label' => 'Quizzes',     'url' => 'quiz.php'],
    'math_helper' => ['label' => 'Math Helper', 'url' => 'math_helper.php']
];

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
        ]
    ]
];

// 整章综合测试 (Overall Chapter Quiz)
$overall_chapter_quiz = [
    'title' => 'Chapter 4 Overall Master Quiz',
    'desc' => '9 Mixed Questions • Full Chapter Mastery Test',
    'questions' => array_merge(
        $section_quizzes['4.1']['questions'],
        $section_quizzes['4.2']['questions'],
        $section_quizzes['4.3']['questions']
    )
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Center - <?= htmlspecialchars($student_name) ?></title>
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
</head>
<body class="bg-pastel-bg text-pastel-text font-sans min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-pastel-nav shadow-sm border-b border-blue-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Left: User Avatar & Nav Items -->
                <div class="flex items-center space-x-8">
                    <div class="flex items-center space-x-3 pr-4 border-r border-blue-200">
                        <div class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                            <?= strtoupper(substr($student_name, 0, 1)) ?>
                        </div>
                        <span class="font-semibold text-pastel-text text-base whitespace-nowrap">
                            <?= htmlspecialchars($student_name) ?>
                        </span>
                    </div>

                    <!-- Navigation Links -->
                    <div class="flex space-x-2">
                        <?php foreach ($nav_items as $key => $item): ?>
                            <a href="<?= $item['url'] ?>" 
                               class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $key === 'quiz' ? 'bg-pastel-primary text-white shadow-sm' : 'text-pastel-text hover:bg-white/60' ?>">
                                <?= $item['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Grade Tag -->
                <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1 rounded-full">
                    Grade 5 Math
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
        
        <!-- Header Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Quiz Center</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= $chapter_info['title'] ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= $chapter_info['topic'] ?></p>
            </div>
            <a href="student_dashboard.php" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Dashboard
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($section_quizzes as $code => $s_quiz): ?>
                        <div class="bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm flex flex-col justify-between hover:border-pastel-primary transition">
                            <div>
                                <span class="text-xs font-bold text-pastel-primary">Section <?= $code ?></span>
                                <h3 class="font-bold text-base text-pastel-text mt-1"><?= $s_quiz['title'] ?></h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed"><?= $s_quiz['desc'] ?></p>
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
                        <p class="text-xs text-slate-500">Comprehensive test combining all concepts from Chapter 4.</p>
                    </div>
                    <span class="text-xs bg-purple-100 text-purple-700 font-bold px-2.5 py-1 rounded-md">Full Assessment</span>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-indigo-50/50 p-6 rounded-2xl border border-blue-200 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-sm">
                    <div>
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Chapter Final Test</span>
                        <h3 class="text-xl font-bold text-pastel-text mt-0.5"><?= $overall_chapter_quiz['title'] ?></h3>
                        <p class="text-xs text-slate-600 mt-1"><?= $overall_chapter_quiz['desc'] ?></p>
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
                <a href="module.php" class="px-6 py-2.5 bg-white border border-blue-200 text-pastel-text text-xs font-bold rounded-xl hover:bg-slate-50 transition">
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
</body>
</html>