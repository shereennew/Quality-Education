<?php
require_once __DIR__ . '/../config/db.php';

$student_id = 1;

// Fetch Student Info
$stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

$classroom_id = $student['classroom_id'] ?? 1;

// Fetch chapters dynamically from DB to match module.php
$chapters_db = [];
try {
    $stmt = $pdo->prepare("
        SELECT cm.chapter_name 
        FROM classroom_chapters cc
        JOIN chapter_materials cm ON cc.chapter_id = cm.id OR cc.chapter_name = cm.chapter_name
        WHERE cc.classroom_id = ? AND cc.is_unlocked = 1
        GROUP BY cm.chapter_name
        ORDER BY cm.chapter_name ASC
    ");
    $stmt->execute([$classroom_id]);
    $chapters_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT chapter_name FROM chapter_materials WHERE classroom_id = ? AND is_unlocked = 1 ORDER BY chapter_name ASC");
        $stmt->execute([$classroom_id]);
        $chapters_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $ex) {
        $stmt = $pdo->query("SELECT DISTINCT chapter_name FROM chapter_materials ORDER BY chapter_name ASC");
        $chapters_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (empty($chapters_db)) {
    $chapters_db = [
        'Ancient Pyramid: Fundamentals',
        'Cherry Blossom: Multiplications',
        'Volcanic Jungle: Fractions & Decimals'
    ];
}

$chapters = [];
foreach ($chapters_db as $idx => $chap_name) {
    $chapters[$idx + 1] = [
        'title' => $chap_name,
        'topic' => 'Chapter ' . ($idx + 1) . ' Assessment & Practice'
    ];
}

// Determine selected chapter ID from URL query parameters (default to 1)
$selected_chap_id = isset($_GET['chapter']) && isset($chapters[$_GET['chapter']]) ? (int)$_GET['chapter'] : 1;
$chapter_info = $chapters[$selected_chap_id];

// Fetch chapter materials / subtopics dynamically for this chapter
$subtopics_list = [];
try {
    $stmt_mat = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC");
    $stmt_mat->execute([$chapter_info['title']]);
    $subtopics_list = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $subtopics_list = [];
}

// Fetch student quiz completion history from database if available
$completed_quiz_ids = [];
$student_id = 1;

try {
    $stmt_history = $pdo->prepare("SELECT quiz_id FROM student_quiz_history WHERE student_id = ? AND status = 'completed'");
    $stmt_history->execute([$student_id]);
    $completed_quiz_ids = $stmt_history->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $completed_quiz_ids = [];
}

// Fetch teacher-assigned quizzes for this chapter from DB
$raw_quizzes = [];
try {
$stmt_assigned = $pdo->prepare("
    SELECT *
    FROM teacher_quizzes
    WHERE chapter_name = ?
    ORDER BY id
");
    $stmt_assigned->execute([$chapter_info['title']]);
    $raw_quizzes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $raw_quizzes = [];
}


// Group teacher-assigned quizzes by quiz title
$assigned_quizzes = [];

foreach ($raw_quizzes as $quiz) {
    $quiz_title = $quiz['title'];

    if (!isset($assigned_quizzes[$quiz_title])) {
        $assigned_quizzes[$quiz_title] = [];
    }

    $assigned_quizzes[$quiz_title][] = $quiz;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Math Helper</title>
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
    <nav class="bg-pastel-nav fixed w-full h-20 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[85rem] mx-auto px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="bg-pastel-badge w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-2xl">📖</span>
                </div>
                <span class="text-2xl font-black tracking-wide text-pastel-text hidden lg:block">EduHunt</span>
            </a>

            <div class="hidden md:flex items-center justify-center flex-1 mx-6">
                <ul class="flex items-center gap-3 text-lg font-bold">
                    <li><a href="index.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Home</a></li>
                    <li><a href="discussion.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Discussion</a></li>
                    <li><a href="module.php" class="flex items-center px-5 py-3 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary">Modules</a></li>
                    <li><a href="mathhelper.php" class="flex items-center px-6 py-3 rounded-2xl bg-pastel-primary text-white shadow-sm">Math Helper</a></li>
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
<main class="flex-1 w-full max-w-[85rem] mx-auto px-4 py-8">        
        <!-- Header Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
<span class="text-xl font-black text-slate-700 tracking-tight">
    Chapter <?= $selected_chap_id ?>
</span>

       <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($chapter_info['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($chapter_info['topic']) ?></p>
            </div>
            <a href="module.php?chap=<?= $selected_chap_id ?>" class="text-xs font-semibold px-3 py-2 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
                ← Back to Module Notes
            </a>
        </div>

        <!-- QUIZ MENU SELECTION VIEW -->
        <div id="quiz-menu" class="space-y-6">
            
            <!-- Chapter Navigation Selector -->
<!-- Chapter Navigation Selector -->
<div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">

    <div class="mb-5">
        <h3 class="text-2xl font-extrabold text-pastel-text tracking-tight">
            Select Chapter Module
        </h3>
        <p class="text-sm text-slate-500 mt-1">
            Choose a chapter to explore and practise.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <?php foreach ($chapters as $id => $chap): ?>
            <a href="mathhelper.php?chapter=<?= $id ?>"
               class="p-4 rounded-xl border-2 transition flex items-center justify-between <?= $id === $selected_chap_id ? 'border-pastel-primary bg-blue-50/50 shadow-sm' : 'border-slate-100 hover:border-pastel-primary/50' ?>">

                <div>
<span class="text-lg font-extrabold text-slate-700">
    Chapter <?= $id ?>
</span>

                </div>

                <span class="text-xs font-semibold text-slate-400">
                    <?= $id === $selected_chap_id ? 'Active 📍' : 'Select' ?>
                </span>

            </a>
        <?php endforeach; ?>
    </div>
</div>

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
                        <h2 class="text-xl font-bold text-pastel-text mt-0.5">Teacher Assigned Quizzes</h2>
                    </div>
                    <span class="hidden sm:block text-2xl">📝</span>
                </div>

                <?php if (!empty($assigned_quizzes)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($assigned_quizzes as $quizTitle => $questions): ?>
                            <div class="p-4 rounded-xl border-2 border-emerald-100 bg-emerald-50/40 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-sm text-pastel-text"><?= htmlspecialchars($quizTitle) ?></h3>
                                    <p class="text-xs text-slate-500 mt-1"><?= count($questions) ?> question<?= count($questions) === 1 ? '' : 's' ?></p>
                                </div>
                                <button type="button" onclick="startAssignedQuiz(<?= htmlspecialchars(json_encode($quizTitle), ENT_QUOTES, 'UTF-8') ?>)" class="shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    Start Quiz
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-500">
                        No quizzes have been assigned for this chapter yet.
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ACTIVE QUIZ ENGINE VIEW (Hidden by default) -->
        <div id="quiz-runner" class="hidden bg-pastel-card p-8 rounded-2xl shadow-sm border border-blue-100">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-100">
                <div>
                    <span id="quiz-title-display" class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Quiz Engine</span>
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
<div id="ai-feedback-container"
     class="hidden mb-6 p-4 rounded-xl bg-blue-50/70 border border-blue-200">

    <div class="flex items-center gap-2 mb-1">
        <span class="text-base">💡</span>

        <h5 class="text-xs font-bold text-pastel-primary uppercase tracking-wider">
            Answer Feedback
        </h5>
    </div>

    <div id="ai-feedback-text"
         class="text-sm text-slate-700 leading-relaxed">
    </div>

    <button id="next-question-btn"
            onclick="proceedToNextQuestion()"
            class="mt-4 px-4 py-2 bg-pastel-primary text-white text-xs font-bold rounded-xl hover:bg-pastel-hover transition hidden">
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
            <h2 class="text-2xl font-bold text-pastel-text mb-1">Quiz Completed!</h2>
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

function startAssignedQuiz(quizTitle) {
    const rawQuestions = assignedQuizzes[quizTitle] || [];

    const questions = rawQuestions.map((question) => ({
        id: question.id,
        q: question.question,
        options: [
            question.option_a,
            question.option_b,
            question.option_c,
            question.option_d
        ],
        ans: ['A', 'B', 'C', 'D'].indexOf(
            question.correct_option.toUpperCase()
        ),
        explanation: question.explanation || 'No explanation available.'
    }));

    if (questions.length === 0) {
        alert('This assigned quiz has no questions yet.');
        return;
    }

    document.getElementById('quiz-menu').classList.add('hidden');
    document.getElementById('quiz-result').classList.add('hidden');
    document.getElementById('quiz-runner').classList.remove('hidden');

    startQuizEngine({
        title: quizTitle,
        questions: questions,
        type: 'teacher'
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

            document.getElementById('quiz-title-display').innerText = "Active Quiz";
            document.getElementById('quiz-subtitle-display').innerText = currentQuiz.title;

            renderQuestion();
        }

        function renderQuestion() {
            isEvaluating = false;
            const qData = currentQuiz.questions[activeQIndex];
            document.getElementById('quiz-progress').innerText = `${activeQIndex + 1} / ${currentQuiz.questions.length}`;
            document.getElementById('question-text').innerText = qData.q;

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

        function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

async function handleOptionSelection(selectedIndex, qData) {
    if (isEvaluating) return;
    isEvaluating = true;

    const selectedAnswerText = qData.options[selectedIndex];
    const correctAnswerText = qData.options[qData.ans];
    const isCorrect = selectedIndex === qData.ans;

    if (isCorrect) {
        score++;
    }

    const optsDiv = document.getElementById('options-container');
    const optionButtons = optsDiv.querySelectorAll('button');

    // Prevent selecting another answer
    optionButtons.forEach(btn => {
        btn.disabled = true;
        btn.classList.remove(
            'hover:border-pastel-primary',
            'hover:bg-blue-50/50'
        );
        btn.classList.add('cursor-default');
    });

    // Highlight selected and correct answers
    optionButtons.forEach((btn, idx) => {

        // Correct answer = GREEN
        if (idx === qData.ans) {
            btn.classList.remove('border-slate-100');
            btn.classList.add(
                'border-emerald-500',
                'bg-emerald-50',
                'text-emerald-700'
            );

            // Add correct label
            if (!btn.querySelector('.answer-label')) {
                const label = document.createElement('span');
                label.className =
                    'answer-label ml-2 text-xs font-bold text-emerald-600';
                label.innerText = '✓ Correct Answer';
                btn.appendChild(label);
            }
        }

        // Selected wrong answer = RED
        if (idx === selectedIndex && !isCorrect) {
            btn.classList.remove('border-slate-100');
            btn.classList.add(
                'border-rose-500',
                'bg-rose-50',
                'text-rose-700'
            );

            const label = document.createElement('span');
            label.className =
                'answer-label ml-2 text-xs font-bold text-rose-600';
            label.innerText = '✕ Your Answer';
            btn.appendChild(label);
        }

        // Selected correct answer = GREEN + Your Answer
        if (idx === selectedIndex && isCorrect) {
            const label = document.createElement('span');
            label.className =
                'answer-label ml-2 text-xs font-bold text-emerald-600';
            label.innerText = '✓ Your Answer';
            btn.appendChild(label);
        }
    });

    // Show explanation
    const feedbackContainer =
        document.getElementById('ai-feedback-container');

    const feedbackText =
        document.getElementById('ai-feedback-text');

    feedbackContainer.classList.remove('hidden');

    if (isCorrect) {
        feedbackText.innerHTML = `
            <div class="space-y-2">
                <p class="font-bold text-emerald-600">
                    ✓ Correct!
                </p>

                <p class="text-sm text-slate-700">
                    <span class="font-semibold">Your answer:</span>
                    ${escapeHtml(selectedAnswerText)}
                </p>

                <div class="pt-2 border-t border-blue-200">
                    <p class="text-xs font-bold text-pastel-primary uppercase tracking-wider mb-1">
                        Explanation
                    </p>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        ${escapeHtml(qData.explanation)}
                    </p>
                </div>
            </div>
        `;
    } else {
        feedbackText.innerHTML = `
            <div class="space-y-3">
                <p class="font-bold text-rose-600">
                    ✕ Incorrect
                </p>

                <div>
                    <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">
                        Your Answer
                    </p>
                    <p class="text-sm text-rose-700 font-semibold">
                        ${escapeHtml(selectedAnswerText)}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">
                        Correct Answer
                    </p>
                    <p class="text-sm text-emerald-700 font-semibold">
                        ${escapeHtml(correctAnswerText)}
                    </p>
                </div>

                <div class="pt-2 border-t border-blue-200">
                    <p class="text-xs font-bold text-pastel-primary uppercase tracking-wider mb-1">
                        Explanation
                    </p>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        ${escapeHtml(qData.explanation)}
                    </p>
                </div>
            </div>
        `;
    }

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