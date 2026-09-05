<?php
$student_name = "Alex Tan";
$chapter_id = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 1;

$chapters = [
    1 => ['name' => 'Icy Peak', 'topic' => 'Visual Fractions & Fundamentals'],
    2 => ['name' => 'Desert Oasis', 'topic' => 'Equivalent Fractions'],
    3 => ['name' => 'Volcano Island', 'topic' => 'Adding & Subtracting Like Fractions'],
    4 => ['name' => 'Waterfall Forest', 'topic' => 'Adding & Subtracting Unlike Fractions'],
    5 => ['name' => 'Pirate Cove', 'topic' => 'Multiplying Fractions'],
    6 => ['name' => 'Ancient Temple', 'topic' => 'Fraction Word Problems']
];

$current_chapter = $chapters[$chapter_id] ?? $chapters[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaptive Assessment - <?= htmlspecialchars($student_name) ?></title>
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
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <?= strtoupper(substr($student_name, 0, 1)) ?>
                    </div>
                    <span class="font-semibold text-pastel-text text-base"><?= htmlspecialchars($student_name) ?></span>
                </div>
                <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1 rounded-full">Grade 5 Math</span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-8">
        
        <!-- Assessment Header -->
        <div id="header-card" class="bg-pastel-card rounded-2xl p-6 shadow-sm border border-blue-100 mb-6">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Chapter <?= $chapter_id ?> Assessment</span>
                    <h1 class="text-xl font-bold text-pastel-text"><?= htmlspecialchars($current_chapter['name']) ?></h1>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-medium">Question</span>
                    <span id="question-number" class="text-lg font-bold text-pastel-primary">1 / 15</span>
                </div>
            </div>

            <!-- Current Level Badge -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <span class="text-xs text-slate-500 font-medium">Current Difficulty Level:</span>
                <span id="level-indicator" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">
                    Foundation
                </span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-4">
                <div id="progress-bar" class="bg-pastel-primary h-full transition-all duration-300" style="width: 6.66%"></div>
            </div>
        </div>

        <!-- Question Card -->
        <div id="quiz-card" class="bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100">
            <div class="flex justify-between items-center mb-3">
                <span id="subtopic-tag" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-pastel-hover">
                    Subtopic Tag
                </span>
            </div>
            <h2 id="question-text" class="text-lg font-semibold mb-6 text-slate-800">Loading Question...</h2>

            <div id="options-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <!-- Options injected dynamically by JS -->
            </div>
        </div>

        <!-- END SUMMARY CARD (Hidden initially) -->
        <div id="result-card" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100">
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-50 text-pastel-primary rounded-full flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                    📊
                </div>
                <h2 class="text-2xl font-bold text-pastel-text">Assessment Diagnostic Summary</h2>
                <p id="final-level" class="text-base font-semibold text-pastel-hover mt-1"></p>
                <p id="final-score" class="text-xs text-slate-400 mt-0.5"></p>
            </div>

            <!-- Section Breakdown (Mastered vs Weak) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <!-- Mastered Section -->
                <div class="bg-emerald-50/60 p-5 rounded-2xl border border-emerald-100">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <h3 class="font-bold text-sm text-emerald-800 uppercase tracking-wider">Mastered Sections</h3>
                    </div>
                    <ul id="mastered-list" class="space-y-2 text-sm text-emerald-900">
                        <!-- Injected via JS -->
                    </ul>
                </div>

                <!-- Weak Section -->
                <div class="bg-rose-50/60 p-5 rounded-2xl border border-rose-100">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <h3 class="font-bold text-sm text-rose-800 uppercase tracking-wider">Needs Improvement</h3>
                    </div>
                    <ul id="weak-list" class="space-y-2 text-sm text-rose-900">
                        <!-- Injected via JS -->
                    </ul>
                </div>

            </div>

            <!-- Action Buttons with updated destination links -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <!-- Links to Module Page with Notes & Lessons -->
                <a href="module.php" class="bg-pastel-primary hover:bg-pastel-hover text-white text-center px-6 py-3 rounded-xl font-semibold text-sm transition shadow-sm">
                    Go to Module Lessons
                </a>
                <!-- Links to Quiz Page -->
                <a href="index.php?page=quiz" class="bg-white border border-blue-200 text-pastel-text hover:bg-slate-50 text-center px-6 py-3 rounded-xl font-semibold text-sm transition">
                    Start Quiz Practice
                </a>
            </div>

        </div>

    </main>

    <script>
        const questionPool = {
            Foundation: [
                { subtopic: "Visual Fraction Models", q: "What fraction of the shape is shaded if 1 out of 4 parts is colored?", options: ["1/4", "2/4", "3/4", "4/4"], answer: 0 },
                { subtopic: "Identifying Numerators & Denominators", q: "In the fraction 3/5, what is the numerator?", options: ["5", "3", "8", "2"], answer: 1 },
                { subtopic: "Simplifying Fractions", q: "Simplify 2/4 to its lowest terms:", options: ["1/3", "1/2", "2/2", "1/4"], answer: 1 },
                { subtopic: "Like Denominator Addition", q: "Solve: 1/5 + 2/5 =", options: ["3/10", "3/5", "2/5", "1/5"], answer: 1 }
            ],
            Intermediate: [
                { subtopic: "Finding Equivalent Fractions", q: "Find the equivalent fraction for 2/3:", options: ["4/6", "3/6", "2/6", "5/6"], answer: 0 },
                { subtopic: "Finding Common Multiples (LCM)", q: "What is the lowest common denominator for 1/4 and 1/6?", options: ["12", "24", "10", "8"], answer: 0 },
                { subtopic: "Unlike Denominator Addition", q: "Solve: 1/4 + 2/8 =", options: ["3/12", "1/2", "3/8", "2/4"], answer: 1 },
                { subtopic: "Unlike Denominator Subtraction", q: "Solve: 5/6 - 1/3 =", options: ["4/3", "1/2", "4/6", "2/3"], answer: 1 }
            ],
            Master: [
                { subtopic: "Multi-Step Unlike Fractions", q: "Solve: 1/3 + 2/5 =", options: ["3/8", "11/15", "7/15", "3/15"], answer: 1 },
                { subtopic: "Unlike Denominator Subtraction", q: "Solve: 3/4 - 2/3 =", options: ["1/12", "1/1", "5/12", "1/7"], answer: 0 },
                { subtopic: "Fraction Word Problems", q: "A recipe needs 3/4 cup sugar. Alex uses 1/2 cup. How much more is needed?", options: ["1/4 cup", "1/2 cup", "1/8 cup", "2/4 cup"], answer: 0 },
                { subtopic: "Fraction Multiplication", q: "Solve: 2/5 × 3/4 =", options: ["5/9", "3/10", "6/20", "8/15"], answer: 1 }
            ]
        };

        let currentQuestionIndex = 1;
        let currentStandard = "Foundation";
        let consecutiveCorrect = 0;
        let consecutiveWrong = 0;
        let totalScore = 0;

        const subtopicStats = {};

        const levelStyles = {
            Foundation: { bg: 'bg-emerald-100', text: 'text-emerald-700', name: 'Foundation' },
            Intermediate: { bg: 'bg-blue-100', text: 'text-blue-700', name: 'Intermediate' },
            Master: { bg: 'bg-purple-100', text: 'text-purple-700', name: 'Master' }
        };

        let currentQuestionData = null;

        function loadQuestion() {
            document.getElementById('question-number').innerText = `${currentQuestionIndex} / 15`;
            document.getElementById('progress-bar').style.width = `${(currentQuestionIndex / 15) * 100}%`;

            const badge = document.getElementById('level-indicator');
            const style = levelStyles[currentStandard];
            badge.className = `text-xs font-bold px-3 py-1 rounded-full ${style.bg} ${style.text}`;
            badge.innerText = style.name;

            const pool = questionPool[currentStandard];
            currentQuestionData = pool[Math.floor(Math.random() * pool.length)];

            document.getElementById('subtopic-tag').innerText = currentQuestionData.subtopic;
            document.getElementById('question-text').innerText = currentQuestionData.q;

            const optionsContainer = document.getElementById('options-container');
            optionsContainer.innerHTML = '';

            currentQuestionData.options.forEach((optText, idx) => {
                const btn = document.createElement('button');
                btn.className = 'p-4 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 font-medium transition duration-200';
                btn.innerText = optText;
                btn.onclick = () => submitAnswer(idx === currentQuestionData.answer);
                optionsContainer.appendChild(btn);
            });
        }

        function submitAnswer(isCorrect) {
            const sub = currentQuestionData.subtopic;

            if (!subtopicStats[sub]) {
                subtopicStats[sub] = { correct: 0, total: 0 };
            }
            subtopicStats[sub].total++;

            if (isCorrect) {
                totalScore++;
                consecutiveCorrect++;
                consecutiveWrong = 0;
                subtopicStats[sub].correct++;

                if (consecutiveCorrect >= 3) {
                    if (currentStandard === "Foundation") currentStandard = "Intermediate";
                    else if (currentStandard === "Intermediate") currentStandard = "Master";
                    consecutiveCorrect = 0;
                }
            } else {
                consecutiveWrong++;
                consecutiveCorrect = 0;

                if (consecutiveWrong >= 2) {
                    if (currentStandard === "Master") currentStandard = "Intermediate";
                    else if (currentStandard === "Intermediate") currentStandard = "Foundation";
                    consecutiveWrong = 0;
                }
            }

            if (currentQuestionIndex < 15) {
                currentQuestionIndex++;
                loadQuestion();
            } else {
                finishQuiz();
            }
        }

        function finishQuiz() {
            document.getElementById('header-card').classList.add('hidden');
            document.getElementById('quiz-card').classList.add('hidden');
            document.getElementById('result-card').classList.remove('hidden');

            document.getElementById('final-level').innerText = `Final Assessed Standard: ${currentStandard}`;
            document.getElementById('final-score').innerText = `Total Score: ${totalScore} / 15 questions answered correctly.`;

            const masteredList = document.getElementById('mastered-list');
            const weakList = document.getElementById('weak-list');

            masteredList.innerHTML = '';
            weakList.innerHTML = '';

            let hasMastered = false;
            let hasWeak = false;

            for (const [subtopic, data] of Object.entries(subtopicStats)) {
                const accuracy = data.correct / data.total;
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center py-1 border-b border-black/5';

                if (accuracy >= 0.7) {
                    hasMastered = true;
                    li.innerHTML = `<span>✓ ${subtopic}</span> <span class="text-xs font-bold bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded">${Math.round(accuracy * 100)}%</span>`;
                    masteredList.appendChild(li);
                } else {
                    hasWeak = true;
                    li.innerHTML = `<span>⚠ ${subtopic}</span> <span class="text-xs font-bold bg-rose-200 text-rose-800 px-2 py-0.5 rounded">${Math.round(accuracy * 100)}%</span>`;
                    weakList.appendChild(li);
                }
            }

            if (!hasMastered) {
                masteredList.innerHTML = '<li class="text-xs text-slate-500 italic">No sections mastered yet. Keep practicing!</li>';
            }
            if (!hasWeak) {
                weakList.innerHTML = '<li class="text-xs text-slate-500 italic">Great job! No weak areas detected in this session.</li>';
            }
        }

        loadQuestion();
    </script>
</body>
</html>