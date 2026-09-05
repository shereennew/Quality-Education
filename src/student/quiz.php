<?php
require_once __DIR__ . '/../config/db.php';

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

// Teacher-created chapter quizzes are available here as assigned quizzes.
$assigned_quizzes = [];
try {
    $stmt_assigned = $pdo->query("SELECT * FROM chapter_quizzes ORDER BY chapter_name, id");
    foreach ($stmt_assigned->fetchAll(PDO::FETCH_ASSOC) as $quiz) {
        $assigned_quizzes[$quiz['chapter_name']][] = $quiz;
    }
} catch (PDOException $e) {
    // Keep the practice quiz available if the database has not been initialized yet.
    $assigned_quizzes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Quizzes</title>
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

    <!-- EXACT NAV BAR MATCHING HISTORY.PHP -->
    <nav class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-2xl">📖</span>
                </div>
                <span class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block">Eduhunt</span>
            </a>

            <div class="hidden md:flex items-center justify-center flex-1 mx-6">
                <ul class="flex items-center gap-3 text-lg font-bold">
                    <li><a href="index.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Home</a></li>
                    <li><a href="discussion.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Discussion</a></li>
                    <li><a href="module.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Modules</a></li>
                    <li><a href="quiz.php" class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm">Quizzes</a></li>
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

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
        
        <!-- Header Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">AI Quiz Center</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($chapter_info['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($chapter_info['topic']) ?></p>
            </div>
            <a href="module.php?chap=<?= $selected_chap_id ?>" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Module Notes
            </a>
        </div>

        <!-- QUIZ MENU SELECTION VIEW -->
        <div id="quiz-menu" class="space-y-6">
            
            <!-- AI Dynamic Generator Banner -->
            <div class="bg-gradient-to-r from-purple-50 via-blue-50 to-indigo-50 p-6 rounded-2xl border border-purple-200 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Powered by Gemini AI</span>
                    <h2 class="text-xl font-bold text-pastel-text mt-0.5">Generate Dynamic Chapter Quiz ✨</h2>
                    <p class="text-xs text-slate-600 mt-1">Instantly build custom multiple-choice questions tailored to this module using AI.</p>
                </div>
                <button onclick="fetchAIQuiz('<?= htmlspecialchars($chapter_info['title'] . ' - ' . $chapter_info['topic']) ?>')" class="w-full sm:w-auto px-6 py-3.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md transition whitespace-nowrap flex items-center justify-center gap-2">
                    <span>Generate AI Quiz ⚡</span>
                </button>
            </div>

            <!-- TEACHER-ASSIGNED QUIZZES -->
            <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Teacher assigned</span>
                        <h2 class="text-xl font-bold text-pastel-text mt-0.5">Complete Your Assigned Quizzes</h2>
                        <p class="text-xs text-slate-600 mt-1">Choose a quiz prepared by your teacher to start answering the questions.</p>
                    </div>
                    <span class="hidden sm:block text-2xl">📝</span>
                </div>

                <?php if (!empty($assigned_quizzes)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($assigned_quizzes as $chapter_name => $questions): ?>
                            <div class="p-4 rounded-xl border-2 border-emerald-100 bg-emerald-50/40 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-sm text-pastel-text"><?= htmlspecialchars($chapter_name) ?></h3>
                                    <p class="text-xs text-slate-500 mt-1"><?= count($questions) ?> question<?= count($questions) === 1 ? '' : 's' ?></p>
                                </div>
                                <button type="button" onclick="startAssignedQuiz(<?= htmlspecialchars(json_encode($chapter_name), ENT_QUOTES, 'UTF-8') ?>)" class="shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    Start Quiz
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500">
                        No quizzes have been assigned yet.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chapter Navigation Selector -->
            <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">
                <h3 class="text-sm font-bold text-pastel-text mb-3">Select Chapter Module:</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($chapters as $id => $chap): ?>
                        <a href="quiz.php?chapter=<?= $id ?>" class="p-4 rounded-xl border-2 transition flex items-center justify-between <?= $id === $selected_chap_id ? 'border-pastel-primary bg-blue-50/50 shadow-sm' : 'border-slate-100 hover:border-pastel-primary/50' ?>">
                            <div>
                                <span class="text-xs font-bold text-pastel-primary">Chapter <?= $id ?></span>
                                <h4 class="font-bold text-sm text-pastel-text"><?= htmlspecialchars($chap['title']) ?></h4>
                            </div>
                            <span class="text-xs font-semibold text-slate-400"><?= $id === $selected_chap_id ? 'Active 📍' : 'Select →' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ACTIVE QUIZ ENGINE VIEW (Hidden by default) -->
        <div id="quiz-runner" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-100">
                <div>
                    <span id="quiz-title-display" class="text-xs font-bold text-pastel-primary uppercase tracking-wider">AI Quiz</span>
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

            <!-- AI Feedback Display Container -->
            <div id="ai-feedback-container" class="hidden mb-6 p-4 rounded-xl bg-blue-50/70 border border-blue-200">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-base">💡</span>
                    <h5 class="text-xs font-bold text-pastel-primary uppercase tracking-wider">AI Explanations & Feedback</h5>
                </div>
                <p id="ai-feedback-text" class="text-sm text-slate-700 leading-relaxed"></p>
                <button id="next-question-btn" onclick="proceedToNextQuestion()" class="mt-4 px-4 py-2 bg-pastel-primary text-white text-xs font-bold rounded-xl hover:bg-pastel-hover transition hidden">
                    Next Question →
                </button>
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
            <h2 class="text-2xl font-bold text-pastel-text mb-1">AI Quiz Completed!</h2>
            <p id="result-quiz-name" class="text-sm text-slate-500 mb-4"></p>

            <div class="bg-pastel-bg p-6 rounded-2xl border border-blue-50 max-w-xs mx-auto mb-6">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Your Score</span>
                <span id="result-score" class="text-3xl font-extrabold text-pastel-primary">0 / 0</span>
            </div>

            <div class="flex justify-center gap-3">
                <button onclick="exitQuiz()" class="px-6 py-2.5 bg-pastel-primary text-white text-xs font-bold rounded-xl hover:bg-pastel-hover transition">
                    Back to Quiz Menu
                </button>
                <a href="module.php?chap=<?= $selected_chap_id ?>" class="px-6 py-2.5 bg-white border border-blue-200 text-pastel-text text-xs font-bold rounded-xl hover:bg-slate-50 transition">
                    Review Module Notes
                </a>
            </div>
        </div>

    </main>

    <!-- Interactive Quiz Engine & AI Integration Script -->
    <script>
        let currentQuiz = null;
        let activeQIndex = 0;
        let score = 0;
        let isEvaluating = false;
        const assignedQuizzes = <?= json_encode($assigned_quizzes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function startAssignedQuiz(chapterName) {
            const questions = (assignedQuizzes[chapterName] || []).map((question) => ({
                q: question.question,
                options: [question.option_a, question.option_b, question.option_c, question.option_d],
                ans: ['A', 'B', 'C', 'D'].indexOf(question.correct_option.toUpperCase())
            }));

            if (questions.length === 0) {
                alert('This assigned quiz has no questions yet.');
                return;
            }

            document.getElementById('quiz-menu').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-runner').classList.remove('hidden');
            startQuizEngine({
                title: 'Teacher Quiz: ' + chapterName,
                questions: questions
            });
        }

        async function fetchAIQuiz(topicName) {
            document.getElementById('quiz-menu').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-runner').classList.remove('hidden');

            document.getElementById('quiz-title-display').innerText = "Gemini AI Generator";
            document.getElementById('quiz-subtitle-display').innerText = "Topic: " + topicName;
            document.getElementById('question-text').innerText = "⏳ Generating custom AI quiz questions, please wait...";
            document.getElementById('options-container').innerHTML = '';
            document.getElementById('ai-feedback-container').classList.add('hidden');

            try {
                const response = await fetch('generate_ai_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ topic: topicName })
                });
                
                const result = await response.json();
                
                if (!result.candidates || !result.candidates[0].content) {
                    throw new Error("Invalid response format from server.");
                }

                const rawJsonText = result.candidates[0].content.parts[0].text;
                const quizPayload = JSON.parse(rawJsonText);
                
                if (!quizPayload.questions || quizPayload.questions.length === 0) {
                    throw new Error("No questions returned in JSON payload.");
                }

                startQuizEngine({
                    title: "AI Quiz: " + topicName,
                    questions: quizPayload.questions
                });
                
            } catch (error) {
                console.error("Failed to generate AI quiz:", error);
                alert("Could not generate AI quiz. Please check your API key and network connection.");
                exitQuiz();
            }
        }

        function startQuizEngine(quizData) {
            currentQuiz = quizData;
            activeQIndex = 0;
            score = 0;
            isEvaluating = false;

            document.getElementById('quiz-title-display').innerText = "Active AI Quiz";
            document.getElementById('quiz-subtitle-display').innerText = currentQuiz.title;

            renderQuestion();
        }

        function renderQuestion() {
            isEvaluating = false;
            const qData = currentQuiz.questions[activeQIndex];
            document.getElementById('quiz-progress').innerText = `${activeQIndex + 1} / ${currentQuiz.questions.length}`;
            document.getElementById('question-text').innerText = qData.q;

            // Hide previous feedback box & clear text
            document.getElementById('ai-feedback-container').classList.add('hidden');
            document.getElementById('ai-feedback-text').innerText = '';
            document.getElementById('next-question-btn').classList.add('hidden');

            const optsDiv = document.getElementById('options-container');
            optsDiv.innerHTML = '';
            optsDiv.classList.remove('pointer-events-none', 'opacity-50');

            qData.options.forEach((opt, idx) => {
                const btn = document.createElement('button');
                btn.className = "p-4 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 text-sm font-medium transition duration-200";
                btn.innerText = opt;
                btn.onclick = () => handleOptionSelection(idx, qData);
                optsDiv.appendChild(btn);
            });
        }

        async function handleOptionSelection(selectedIndex, qData) {
            if (isEvaluating) return;
            isEvaluating = true;

            const selectedAnswerText = qData.options[selectedIndex];
            const isCorrect = selectedIndex === qData.ans;
            if (isCorrect) score++;

            // Lock options to prevent double-clicking
            const optsDiv = document.getElementById('options-container');
            optsDiv.classList.add('pointer-events-none', 'opacity-50');

            // Show loading placeholder in AI feedback container
            const feedbackContainer = document.getElementById('ai-feedback-container');
            const feedbackText = document.getElementById('ai-feedback-text');
            feedbackContainer.classList.remove('hidden');
            feedbackText.innerText = 'Evaluating answer with AI...';

            try {
                const aiText = await submitAnswerToAI(qData.q, selectedAnswerText);
                feedbackText.innerText = aiText;
            } catch (error) {
                feedbackText.innerText = isCorrect 
                    ? 'Correct! (Note: Could not fetch advanced AI feedback at the moment.)' 
                    : 'Incorrect. (Note: Could not fetch advanced AI feedback at the moment.)';
            }

            // Show next action button
            document.getElementById('next-question-btn').classList.remove('hidden');
        }

        async function submitAnswerToAI(questionText, studentAnswer) {
            const response = await fetch('evaluate_quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: questionText, answer: studentAnswer })
            });
            const result = await response.json();
            const aiText = result.candidates[0].content.parts[0].text;
            return aiText;
        }

        function proceedToNextQuestion() {
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