<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ai_quiz_helper.php';

$student_id = 3;

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

// Prepare adaptive AI learning data from the real academic subtopics.
$ai_academic_topics = [];
foreach ($subtopics_list as $subtopic) {
    $title = trim((string)($subtopic['title'] ?? ''));
    if ($title !== '') {
        $ai_academic_topics[] = $title;
    }
}
$ai_academic_topics = array_values(array_unique($ai_academic_topics));

// If there are no material titles, keep a safe fallback. The AI prompt is also told
// not to treat decorative island/chapter names as mathematical content.
if (empty($ai_academic_topics)) {
    $ai_academic_topics[] = 'Mathematics practice';
}

try {
    ensureAiQuizTables($pdo);
    $ai_overall_profile = getAiRecommendedLevel($pdo, $student_id, $selected_chap_id, null);
    $ai_subtopic_profiles = [];
    foreach ($ai_academic_topics as $aiTopicTitle) {
        $ai_subtopic_profiles[$aiTopicTitle] = getAiRecommendedLevel($pdo, $student_id, $selected_chap_id, $aiTopicTitle);
    }
    $recent_ai_attempts = getAiAttempts($pdo, $student_id, $selected_chap_id, 3);
} catch (Throwable $e) {
    $ai_overall_profile = ['level' => 'Beginner', 'percentage' => null, 'source' => 'first AI practice', 'answer_count' => 0];
    $ai_subtopic_profiles = [];
    $recent_ai_attempts = [];
}

// Fetch student quiz completion history from database if available
$completed_quiz_ids = [];
$student_id = 3;

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
    <style>


        .main-navbar {

            height: 96px;

            background: #e1e9f5;

            border-bottom:
                1px solid rgba(125, 160, 202, .25);

            box-shadow:
                0 3px 15px rgba(75, 100, 130, .10);

            position: sticky;

            top: 0;

            z-index: 1000;

            display: flex;

            align-items: center;
            width: 100%;
            max-width: none;
            align-self: stretch;
            flex-shrink: 0;
        }


        .nav-inner {

            width: 100%;

            max-width: 1500px;

            margin: auto;

            padding: 0 45px;

            display: grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items: center;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 13px;

            text-decoration: none;

            color: #2c3e50;
        }


        .brand-icon {

            width: 53px;
            height: 53px;

            border-radius: 16px;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

            font-weight: 900;
        }


        .brand-name {

            font-size: 30px;

            font-weight: 900;
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .nav-links a {

            text-decoration: none;

            color: #2c3e50;

            padding: 13px 24px;

            border-radius: 15px;

            font-size: 18px;

            font-weight: 800;

            transition: .2s;
        }


        .nav-links a:hover {

            background: #cbd9eb;
        }


        .nav-links a.active {

            background: #7da0ca;

            color: white;
        }


        .profile-area {

            display: flex;

            justify-content: flex-end;

            position: relative;
        }


        .profile-button {

            border:
                1px solid rgba(125, 160, 202, .35);

            background: white;

            border-radius: 999px;

            padding:
                7px 16px 7px 8px;

            display: flex;

            align-items: center;

            gap: 10px;

            cursor: default;

            color: #2c3e50;

            font-weight: 800;

            font-size: 16px;
        }


        .avatar {

            width: 44px;
            height: 44px;

            border-radius: 50%;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: 900;
        }



        

        @media (max-width: 1000px) {
            .nav-inner { grid-template-columns: auto 1fr; }
            .nav-links { display: none; }
            .profile-area { justify-self: end; }
        }
        @media (max-width: 650px) {
            .brand-name { font-size: 23px; }
            .profile-name { display: none; }
        }

    </style>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center">
<nav class="main-navbar">

    <div class="nav-inner">

        <a href="index.php" class="brand">

            <div class="brand-icon">
                E
            </div>

            <div class="brand-name">
                EduHunt
            </div>

        </a>

        <div class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="discussion.php">
                Discussion
            </a>

            <a href="module.php">
                Modules
            </a>

            <a href="mathhelper.php" class="active">
                Math Helper
            </a>

            <a href="history.php">
                History
            </a>

        </div>

        <div class="profile-area">
            <div class="profile-button">

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $student['name'],
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

                <span class="profile-name">
                    <?= htmlspecialchars($student['name']) ?>
                </span>

            </div>
        </div>

    </div>

</nav>

    <!-- Main Content -->
<main class="w-full max-w-[85rem] flex flex-col gap-6 p-4">

        <!-- Header Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
<span class="text-xl font-black text-slate-700 tracking-tight">
    Chapter <?= $selected_chap_id ?>
</span>

       <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($chapter_info['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($chapter_info['topic']) ?></p>
            </div>
<a href="module.php?chap=<?= $selected_chap_id ?>"
   class="text-base font-bold px-5 py-3 bg-blue-50 text-pastel-hover hover:bg-blue-100 rounded-xl border border-blue-100 transition">
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
<span class="text-xl font-extrabold text-slate-700">
        Chapter <?= $id ?>
</span>

                </div>

<span class="text-base font-bold text-slate-500">
    <?= $id === $selected_chap_id ? 'Active 📍' : 'Select' ?>
</span>


            </a>
        <?php endforeach; ?>
    </div>
</div>

            <!-- ADAPTIVE AI QUIZ GENERATOR -->
            <div class="bg-gradient-to-r from-purple-50 via-blue-50 to-indigo-50 p-6 rounded-2xl border border-purple-200 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                    <div class="flex-1">
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Adaptive AI Practice</span>
                        <h2 class="text-xl font-bold text-pastel-text mt-0.5">Generate a Quiz for Your Level</h2>
                        <p class="text-xs text-slate-600 mt-1">Choose the whole topic or one subtopic. EduHunt recommends a level from your previous performance, but you can choose any difficulty.</p>
                    </div>
                    <div class="px-4 py-3 bg-white/80 border border-purple-100 rounded-xl min-w-[210px]">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recommended Level</span>
                        <span id="ai-recommended-level" class="text-lg font-black text-purple-700"><?= htmlspecialchars($ai_overall_profile['level']) ?></span>
                        <span id="ai-recommended-reason" class="text-[11px] text-slate-500 block mt-0.5">
                            <?php if ($ai_overall_profile['percentage'] !== null): ?>
                                Based on <?= htmlspecialchars((string)$ai_overall_profile['percentage']) ?>% recent performance
                            <?php else: ?>
                                First AI practice on this topic
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-6">
                    <div class="bg-white/75 border border-purple-100 rounded-xl p-4">
                        <span class="text-xs font-bold text-pastel-text block mb-3">1. What do you want to practise?</span>
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer mb-2 hover:border-purple-300">
                            <input type="radio" name="ai-scope" value="overall" checked onchange="updateAiScope()" class="mt-1">
                            <span>
                                <strong class="text-sm text-pastel-text">Overall Topic</strong>
                                <span class="text-xs text-slate-500 block mt-0.5"><?= htmlspecialchars(implode(', ', $ai_academic_topics)) ?></span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-purple-300">
                            <input type="radio" name="ai-scope" value="subtopic" onchange="updateAiScope()" class="mt-1">
                            <span class="w-full">
                                <strong class="text-sm text-pastel-text">Specific Subtopic</strong>
                                <select id="ai-subtopic-select" onchange="updateAiRecommendation()" disabled class="mt-2 w-full rounded-lg border-slate-200 text-sm bg-slate-50 disabled:opacity-50">
                                    <?php foreach ($ai_academic_topics as $aiTopicTitle): ?>
                                        <option value="<?= htmlspecialchars($aiTopicTitle) ?>"><?= htmlspecialchars($aiTopicTitle) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                        </label>
                    </div>

                    <div class="bg-white/75 border border-purple-100 rounded-xl p-4">
                        <span class="text-xs font-bold text-pastel-text block mb-3">2. Choose Difficulty</span>
                        <div class="grid grid-cols-3 gap-2" id="ai-difficulty-buttons">
                            <button type="button" data-level="Beginner" onclick="selectAiDifficulty('Beginner')" class="ai-difficulty-btn px-3 py-3 rounded-xl border-2 text-xs font-bold transition">Beginner</button>
                            <button type="button" data-level="Intermediate" onclick="selectAiDifficulty('Intermediate')" class="ai-difficulty-btn px-3 py-3 rounded-xl border-2 text-xs font-bold transition">Intermediate</button>
                            <button type="button" data-level="Advanced" onclick="selectAiDifficulty('Advanced')" class="ai-difficulty-btn px-3 py-3 rounded-xl border-2 text-xs font-bold transition">Advanced</button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-3">Beginner = basic understanding, Intermediate = mixed practice, Advanced = harder multi-step questions.</p>

                        <button type="button" onclick="generateAIQuizFromForm()" class="mt-4 w-full px-6 py-3.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold rounded-xl shadow-md transition">
                            Generate 5 AI Questions
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($recent_ai_attempts)): ?>
            <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">AI Quiz History</span>
                        <h2 class="text-lg font-bold text-pastel-text">Recent AI Practice on This Topic</h2>
                    </div>
                    <a href="history.php?island_id=<?= $selected_chap_id ?>" class="text-xs font-bold text-pastel-primary hover:text-pastel-hover">View Full History →</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <?php foreach ($recent_ai_attempts as $attempt): ?>
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/70">
                            <div class="flex justify-between gap-2 items-start">
                                <span class="text-[10px] font-bold uppercase px-2 py-1 rounded bg-purple-100 text-purple-700"><?= htmlspecialchars($attempt['difficulty']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= date('M d', strtotime($attempt['created_at'])) ?></span>
                            </div>
                            <h3 class="text-sm font-bold text-pastel-text mt-2"><?= htmlspecialchars($attempt['subtopic'] ?: 'Overall Topic') ?></h3>
                            <p class="text-xl font-black text-pastel-primary mt-2"><?= (int)$attempt['score'] ?> / <?= (int)$attempt['total'] ?></p>
                            <?php if (!empty($attempt['weak_skill'])): ?>
                                <p class="text-[11px] text-rose-600 mt-1">Practice: <?= htmlspecialchars($attempt['weak_skill']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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

            <div id="ai-result-analysis" class="hidden max-w-2xl mx-auto mb-6 text-left bg-purple-50 border border-purple-200 rounded-2xl p-5">
                <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Adaptive Learning Analysis</span>
                <h3 class="font-bold text-pastel-text mt-1">Your Learning Focus</h3>
                <p id="ai-result-analysis-text" class="text-sm text-slate-600 mt-2"></p>
                <div id="ai-skill-breakdown" class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4"></div>
                <button id="practice-weakness-btn" type="button" onclick="practiceWeakArea()" class="hidden mt-4 w-full sm:w-auto px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition">
                    Practice My Weak Area
                </button>
                <p id="ai-save-status" class="text-[11px] text-slate-400 mt-3"></p>
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

    <!-- Interactive Quiz Engine & Adaptive AI Integration Script -->
    <script>
        let currentQuiz = null;
        let activeQIndex = 0;
        let score = 0;
        let isEvaluating = false;
        let currentAnswers = [];
        let currentWeakSkill = '';
        let selectedAiDifficulty = <?= json_encode($ai_overall_profile['level']) ?>;

        const assignedQuizzes = <?= json_encode($assigned_quizzes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const aiAcademicTopics = <?= json_encode($ai_academic_topics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const aiOverallProfile = <?= json_encode($ai_overall_profile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const aiSubtopicProfiles = <?= json_encode($ai_subtopic_profiles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const selectedChapterId = <?= (int)$selected_chap_id ?>;

        function makeAttemptKey() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }
            return 'ai_' + Date.now() + '_' + Math.random().toString(36).slice(2);
        }

        function startAssignedQuiz(quizTitle) {
            const rawQuestions = assignedQuizzes[quizTitle] || [];

            if (rawQuestions.length === 0) {
                alert('This assigned quiz has no questions yet.');
                return;
            }

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
                    String(question.correct_option || '').toUpperCase()
                ),
                skill: quizTitle,
                difficulty: 'Teacher Assigned',
                explanation: question.explanation || 'No explanation available.'
            }));

            document.getElementById('quiz-menu').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-runner').classList.remove('hidden');

            startQuizEngine({
                title: quizTitle,
                type: 'assigned',
                questions: questions
            });
        }

        function getSelectedAiScope() {
            return document.querySelector('input[name="ai-scope"]:checked')?.value || 'overall';
        }

        function getCurrentAiProfile() {
            const scope = getSelectedAiScope();
            if (scope === 'subtopic') {
                const subtopic = document.getElementById('ai-subtopic-select').value;
                return aiSubtopicProfiles[subtopic] || { level: 'Beginner', percentage: null, source: 'first AI practice' };
            }
            return aiOverallProfile;
        }

        function updateAiScope() {
            const scope = getSelectedAiScope();
            document.getElementById('ai-subtopic-select').disabled = scope !== 'subtopic';
            updateAiRecommendation(true);
        }

        function updateAiRecommendation(applyRecommendation = false) {
            const profile = getCurrentAiProfile();
            document.getElementById('ai-recommended-level').innerText = profile.level;
            document.getElementById('ai-recommended-reason').innerText = profile.percentage !== null
                ? `Based on ${profile.percentage}% recent performance`
                : 'First AI practice on this topic';

            if (applyRecommendation) {
                selectAiDifficulty(profile.level);
            } else {
                paintDifficultyButtons();
            }
        }

        function selectAiDifficulty(level) {
            selectedAiDifficulty = level;
            paintDifficultyButtons();
        }

        function paintDifficultyButtons() {
            document.querySelectorAll('.ai-difficulty-btn').forEach((button) => {
                const active = button.dataset.level === selectedAiDifficulty;
                button.classList.toggle('border-purple-500', active);
                button.classList.toggle('bg-purple-600', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('border-slate-200', !active);
                button.classList.toggle('bg-white', !active);
                button.classList.toggle('text-slate-600', !active);
            });
        }

        function generateAIQuizFromForm() {
            const scope = getSelectedAiScope();
            const subtopic = scope === 'subtopic' ? document.getElementById('ai-subtopic-select').value : '';
            const profile = getCurrentAiProfile();
            const topicLabel = scope === 'subtopic' ? subtopic : aiAcademicTopics.join(', ');

            fetchAIQuiz({
                scopeType: scope,
                topicLabel,
                subtopic,
                difficulty: selectedAiDifficulty,
                studentLevel: profile.level,
                focusSkill: ''
            });
        }

        async function fetchAIQuiz(config) {
            document.getElementById('quiz-menu').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-runner').classList.remove('hidden');

            document.getElementById('quiz-title-display').innerText = 'Adaptive AI Quiz';
            document.getElementById('quiz-subtitle-display').innerText = config.focusSkill
                ? `Weak-area practice: ${config.focusSkill}`
                : `${config.topicLabel} • ${config.difficulty}`;
            document.getElementById('question-text').innerText = 'Generating questions for your level...';
            document.getElementById('options-container').innerHTML = '';
            document.getElementById('ai-feedback-container').classList.add('hidden');

            try {
                const response = await fetch('generate_ai_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_id: <?= (int)$student_id ?>,
                        chapter_id: selectedChapterId,
                        scope_type: config.scopeType,
                        topic: config.topicLabel,
                        subtopic: config.subtopic,
                        academic_topics: config.focusSkill ? [config.focusSkill] : aiAcademicTopics,
                        difficulty: config.difficulty,
                        student_level: config.studentLevel,
                        focus_skill: config.focusSkill || ''
                    })
                });

                const result = await response.json();
                console.log('AI Quiz response:', result);

                if (!response.ok || result.success !== true) {
                    throw new Error(result.error || 'Could not generate quiz.');
                }
                if (!Array.isArray(result.questions) || result.questions.length !== 5) {
                    throw new Error('AI did not return 5 valid questions. Please try again.');
                }

                startQuizEngine({
                    title: config.focusSkill ? `AI Practice: ${config.focusSkill}` : `AI Quiz: ${config.topicLabel}`,
                    type: 'ai',
                    attemptKey: makeAttemptKey(),
                    chapterId: selectedChapterId,
                    topicLabel: config.topicLabel,
                    scopeType: config.focusSkill ? 'subtopic' : config.scopeType,
                    subtopic: config.focusSkill || config.subtopic,
                    difficulty: config.difficulty,
                    recommendedLevel: config.studentLevel,
                    focusSkill: config.focusSkill || '',
                    questions: result.questions
                });
            } catch (error) {
                console.error('Failed to generate AI quiz:', error);
                alert('AI quiz could not be generated.\n\n' + error.message);
                exitQuiz();
            }
        }

        function startQuizEngine(quizData) {
            currentQuiz = quizData;
            activeQIndex = 0;
            score = 0;
            isEvaluating = false;
            currentAnswers = [];
            currentWeakSkill = '';

            document.getElementById('quiz-title-display').innerText = quizData.type === 'ai' ? 'Adaptive AI Quiz' : 'Active Quiz';
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
                btn.className = 'p-4 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 text-sm font-medium transition duration-200';
                btn.innerText = opt;
                btn.dataset.optionIndex = idx;
                btn.onclick = () => handleOptionSelection(idx, qData);
                optsDiv.appendChild(btn);
            });
        }

        function handleOptionSelection(selectedIndex, qData) {
            if (isEvaluating) return;
            isEvaluating = true;

            const selectedAnswerText = qData.options[selectedIndex];
            const correctAnswerText = qData.options[qData.ans];
            const isCorrect = selectedIndex === qData.ans;
            if (isCorrect) score++;

            currentAnswers.push({
                question: qData.q,
                options: qData.options,
                correct_index: qData.ans,
                student_index: selectedIndex,
                student_answer: selectedAnswerText,
                correct_answer: correctAnswerText,
                is_correct: isCorrect,
                skill: qData.skill || currentQuiz.subtopic || 'General Practice',
                difficulty: qData.difficulty || currentQuiz.difficulty || 'Beginner',
                explanation: qData.explanation || ''
            });

            const optsDiv = document.getElementById('options-container');
            optsDiv.classList.add('pointer-events-none');
            Array.from(optsDiv.children).forEach((button, idx) => {
                button.classList.remove('hover:border-pastel-primary', 'hover:bg-blue-50/50');
                if (idx === qData.ans) {
                    button.classList.add('border-emerald-400', 'bg-emerald-50');
                } else if (idx === selectedIndex) {
                    button.classList.add('border-rose-400', 'bg-rose-50');
                } else {
                    button.classList.add('opacity-50');
                }
            });

            const feedbackContainer = document.getElementById('ai-feedback-container');
            const feedbackText = document.getElementById('ai-feedback-text');
            feedbackContainer.classList.remove('hidden');

            if (currentQuiz.type === 'ai') {
                const verdict = isCorrect
                    ? 'Correct! '
                    : `Not quite. The correct answer is ${correctAnswerText}. `;
                feedbackText.innerText = verdict + (qData.explanation || 'Review the correct method for this question.');
            } else {
                feedbackText.innerText = isCorrect
                    ? 'Correct!'
                    : `Incorrect. The correct answer is ${correctAnswerText}.`;
            }

            // Show the button after the student answers.
            const nextButton = document.getElementById('next-question-btn');
            nextButton.classList.remove('hidden');
            nextButton.innerText =
                activeQIndex + 1 < currentQuiz.questions.length
                    ? 'Next Question →'
                    : 'See Results →';
        }

        function proceedToNextQuestion() {
            if (activeQIndex + 1 < currentQuiz.questions.length) {
                activeQIndex++;
                renderQuestion();
            } else {
                showResults();
            }
        }

        function analyseSkills() {
            const skillStats = {};

            currentAnswers.forEach((answer) => {
                const skill = answer.skill || 'General Practice';
                if (!skillStats[skill]) {
                    skillStats[skill] = { correct: 0, total: 0 };
                }
                skillStats[skill].total++;
                if (answer.is_correct) skillStats[skill].correct++;
            });

            let weakSkill = '';
            let lowestRate = 101;
            let largestMistakeCount = -1;

            Object.entries(skillStats).forEach(([skill, stat]) => {
                const rate = stat.total > 0 ? (stat.correct / stat.total) * 100 : 0;
                const mistakes = stat.total - stat.correct;
                if (mistakes > 0 && (rate < lowestRate || (rate === lowestRate && mistakes > largestMistakeCount))) {
                    weakSkill = skill;
                    lowestRate = rate;
                    largestMistakeCount = mistakes;
                }
            });

            return { skillStats, weakSkill };
        }

        async function showResults() {
            document.getElementById('quiz-runner').classList.add('hidden');
            document.getElementById('quiz-result').classList.remove('hidden');
            document.getElementById('result-quiz-name').innerText = currentQuiz.title;
            document.getElementById('result-score').innerText = `${score} / ${currentQuiz.questions.length}`;

            const analysisBox = document.getElementById('ai-result-analysis');
            const practiceButton = document.getElementById('practice-weakness-btn');
            const saveStatus = document.getElementById('ai-save-status');
            analysisBox.classList.add('hidden');
            practiceButton.classList.add('hidden');
            saveStatus.innerText = '';

            if (currentQuiz.type !== 'ai') return;

            const analysis = analyseSkills();
            currentWeakSkill = analysis.weakSkill;
            analysisBox.classList.remove('hidden');

            const analysisText = document.getElementById('ai-result-analysis-text');
            if (currentWeakSkill) {
                analysisText.innerText = `Your weakest area in this quiz was ${currentWeakSkill}. EduHunt can generate another set focused on that skill.`;
                practiceButton.classList.remove('hidden');
            } else {
                analysisText.innerText = 'You answered every skill correctly in this set. Great work — you can try a harder difficulty next.';
            }

            const breakdown = document.getElementById('ai-skill-breakdown');
            breakdown.innerHTML = '';
            Object.entries(analysis.skillStats).forEach(([skill, stat]) => {
                const percent = Math.round((stat.correct / stat.total) * 100);
                const card = document.createElement('div');
                card.className = 'bg-white border border-purple-100 rounded-xl p-3';
                card.innerHTML = `<span class="text-xs font-bold text-pastel-text">${escapeHtml(skill)}</span><span class="text-xs text-slate-500 block mt-1">${stat.correct}/${stat.total} correct • ${percent}%</span>`;
                breakdown.appendChild(card);
            });

            saveStatus.innerText = 'Saving this AI quiz to your history...';
            try {
                const response = await fetch('save_ai_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        attempt_key: currentQuiz.attemptKey,
                        chapter_id: currentQuiz.chapterId,
                        topic_label: currentQuiz.topicLabel,
                        scope_type: currentQuiz.scopeType,
                        subtopic: currentQuiz.subtopic,
                        difficulty: currentQuiz.difficulty,
                        recommended_level: currentQuiz.recommendedLevel,
                        score,
                        total: currentQuiz.questions.length,
                        weak_skill: currentWeakSkill,
                        answers: currentAnswers
                    })
                });
                const result = await response.json();
                if (!response.ok || result.success !== true) {
                    throw new Error(result.error || 'Could not save quiz history.');
                }
                saveStatus.innerText = 'Saved to Quiz History.';
            } catch (error) {
                console.error('Could not save AI quiz history:', error);
                saveStatus.innerText = 'Quiz completed, but history could not be saved.';
            }
        }

        function practiceWeakArea() {
            if (!currentWeakSkill || !currentQuiz || currentQuiz.type !== 'ai') return;

            fetchAIQuiz({
                scopeType: 'subtopic',
                topicLabel: currentWeakSkill,
                subtopic: currentWeakSkill,
                difficulty: currentQuiz.difficulty,
                studentLevel: currentQuiz.recommendedLevel,
                focusSkill: currentWeakSkill
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function exitQuiz() {
            document.getElementById('quiz-runner').classList.add('hidden');
            document.getElementById('quiz-result').classList.add('hidden');
            document.getElementById('quiz-menu').classList.remove('hidden');
        }

        // Start the difficulty buttons on the student's recommended level.
        document.addEventListener('DOMContentLoaded', () => {
            selectAiDifficulty(aiOverallProfile.level || 'Beginner');
            updateAiRecommendation(false);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>