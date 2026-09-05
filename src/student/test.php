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
    <title>15-Question Adaptive Test - <?= htmlspecialchars($student_name) ?></title>
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
        
        <!-- Header Info -->
        <div class="bg-pastel-card rounded-2xl p-6 shadow-sm border border-blue-100 mb-6">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Chapter <?= $chapter_id ?> Adaptive Assessment</span>
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
            <h2 id="question-text" class="text-lg font-semibold mb-6 text-slate-800">Loading Question...</h2>

            <div id="options-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <!-- Options injected dynamically by JS -->
            </div>
        </div>

        <!-- End Result View (Hidden initially) -->
        <div id="result-card" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100 text-center">
            <div class="w-16 h-16 bg-blue-50 text-pastel-primary rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                🏆
            </div>
            <h2 class="text-2xl font-bold text-pastel-text mb-2">Adaptive Test Complete!</h2>
            <p id="final-level" class="text-lg font-semibold text-pastel-hover mb-2"></p>
            <p id="final-stats" class="text-sm text-slate-500 max-w-md mx-auto mb-6"></p>

            <a href="index.php?page=practice" class="inline-block bg-pastel-primary hover:bg-pastel-hover text-white px-8 py-3 rounded-xl font-semibold text-sm transition shadow-sm">
                Go to Recommended Practice
            </a>
        </div>

    </main>

    <script>
        // Question Pool across 3 Standards
        const questionPool = {
            Foundation: [
                { q: "What fraction of the shape is shaded if 1 out of 4 parts is colored?", options: ["1/4", "2/4", "3/4", "4/4"], answer: 0 },
                { q: "In the fraction 3/5, what is the numerator?", options: ["5", "3", "8", "2"], answer: 1 },
                { q: "Which fraction is equal to 1 whole?", options: ["1/2", "2/2", "1/4", "3/4"], answer: 1 },
                { q: "Simplify 2/4 to its lowest terms:", options: ["1/3", "1/2", "2/2", "1/4"], answer: 1 },
                { q: "Solve: 1/5 + 2/5 =", options: ["3/10", "3/5", "2/5", "1/5"], answer: 1 }
            ],
            Intermediate: [
                { q: "Find the equivalent fraction for 2/3:", options: ["4/6", "3/6", "2/6", "5/6"], answer: 0 },
                { q: "Solve: 1/4 + 2/8 =", options: ["3/12", "1/2", "3/8", "2/4"], answer: 1 },
                { q: "Which fraction is greater: 1/2 or 2/5?", options: ["1/2", "2/5", "They are equal", "None"], answer: 0 },
                { q: "Solve: 5/6 - 1/3 =", options: ["4/3", "1/2", "4/6", "2/3"], answer: 1 },
                { q: "Convert 5/2 into a mixed number:", options: ["2 1/2", "1 1/2", "2 1/5", "3 1/2"], answer: 0 }
            ],
            Master: [
                { q: "Solve: 1/3 + 2/5 =", options: ["3/8", "11/15", "7/15", "3/15"], answer: 1 },
                { q: "Solve: 3/4 - 2/3 =", options: ["1/12", "1/1", "5/12", "1/7"], answer: 0 },
                { q: "A recipe needs 3/4 cup of sugar. Alex uses 1/2 cup. How much more is needed?", options: ["1/4 cup", "1/2 cup", "1/8 cup", "2/4 cup"], answer: 0 },
                { q: "Solve: 2/5 × 3/4 =", options: ["5/9", "3/10", "6/20", "8/15"], answer: 1 },
                { q: "Which sum results in a value greater than 1?", options: ["1/2 + 1/3", "3/4 + 2/3", "2/5 + 1/2", "1/4 + 2/4"], answer: 1 }
            ]
        };

        // Engine State Variables
        let currentQuestionIndex = 1; // 1 to 15
        let currentStandard = "Foundation"; // Foundation, Intermediate, Master
        let consecutiveCorrect = 0;
        let consecutiveWrong = 0;
        let totalScore = 0;

        // Visual Styling Config for Standards
        const levelStyles = {
            Foundation: { bg: 'bg-emerald-100', text: 'text-emerald-700', name: 'Foundation' },
            Intermediate: { bg: 'bg-blue-100', text: 'text-blue-700', name: 'Intermediate' },
            Master: { bg: 'bg-purple-100', text: 'text-purple-700', name: 'Master' }
        };

        function loadQuestion() {
            // Update Headers
            document.getElementById('question-number').innerText = `${currentQuestionIndex} / 15`;
            document.getElementById('progress-bar').style.width = `${(currentQuestionIndex / 15) * 100}%`;

            const badge = document.getElementById('level-indicator');
            const style = levelStyles[currentStandard];
            badge.className = `text-xs font-bold px-3 py-1 rounded-full ${style.bg} ${style.text}`;
            badge.innerText = style.name;

            // Pick a random question from the current standard pool
            const pool = questionPool[currentStandard];
            const qData = pool[Math.floor(Math.random() * pool.length)];

            document.getElementById('question-text').innerText = qData.q;

            const optionsContainer = document.getElementById('options-container');
            optionsContainer.innerHTML = '';

            qData.options.forEach((optText, idx) => {
                const btn = document.createElement('button');
                btn.className = 'p-4 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 font-medium transition duration-200';
                btn.innerText = optText;
                btn.onclick = () => submitAnswer(idx === qData.answer);
                optionsContainer.appendChild(btn);
            });
        }

        function submitAnswer(isCorrect) {
            if (isCorrect) {
                totalScore++;
                consecutiveCorrect++;
                consecutiveWrong = 0;

                // Adaptive Rule: 3 consecutive correct answers -> Promote level
                if (consecutiveCorrect >= 3) {
                    if (currentStandard === "Foundation") {
                        currentStandard = "Intermediate";
                        consecutiveCorrect = 0;
                    } else if (currentStandard === "Intermediate") {
                        currentStandard = "Master";
                        consecutiveCorrect = 0;
                    }
                }
            } else {
                consecutiveWrong++;
                consecutiveCorrect = 0;

                // Adaptive Rule: 2 wrong answers -> Lower difficulty
                if (consecutiveWrong >= 2) {
                    if (currentStandard === "Master") {
                        currentStandard = "Intermediate";
                        consecutiveWrong = 0;
                    } else if (currentStandard === "Intermediate") {
                        currentStandard = "Foundation";
                        consecutiveWrong = 0;
                    }
                }
            }

            // Advance Question
            if (currentQuestionIndex < 15) {
                currentQuestionIndex++;
                loadQuestion();
            } else {
                finishQuiz();
            }
        }

        function finishQuiz() {
            document.getElementById('quiz-card').classList.add('hidden');
            document.getElementById('result-card').classList.remove('hidden');

            document.getElementById('final-level').innerText = `Final Assessed Standard: ${currentStandard}`;
            document.getElementById('final-stats').innerText = `You scored ${totalScore} out of 15 questions. Our engine has tailored your next practice modules to match your ${currentStandard} skill level!`;
        }

        // Initialize quiz
        loadQuestion();
    </script>
</body>
</html>