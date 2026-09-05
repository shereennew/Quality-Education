<?php
// src/teacher/quiz.php
session_start();
require_once __DIR__ . '/../config/db.php';

$chapter_name = isset($_GET['chapter']) ? trim(urldecode($_GET['chapter'])) : '';

if (empty($chapter_name)) {
    die("Chapter not specified.");
}

// Fetch all quiz questions for this chapter[cite: 1]
$stmt_quizzes = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? ORDER BY id DESC");
$stmt_quizzes->execute([$chapter_name]);
$quizzes = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?php echo htmlspecialchars($chapter_name); ?> - EduPulse</title>
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
                            light: '#f8fafc',
                            badge: '#cbe0f5'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <!-- Navbar -->
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-4xl mx-auto px-6 py-3.5 flex justify-between items-center w-full">
            <div class="flex items-center space-x-4">
                <a href="chapter_details.php?chapter=<?php echo urlencode($chapter_name); ?>" class="text-pastel-text hover:text-pastel-hover bg-white/60 hover:bg-white px-3 py-1.5 rounded-lg text-xs font-semibold transition border border-blue-100">&larr; Back to Chapter</a>
                <h1 class="text-base font-bold text-pastel-text tracking-wide">Quiz Preview: <?php echo htmlspecialchars($chapter_name); ?></h1>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-10 flex-1 w-full space-y-6">
        <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Interactive Assessment</span>
            <h2 class="text-xl font-bold text-pastel-text mt-1"><?php echo htmlspecialchars($chapter_name); ?> Quiz</h2>
            <p class="text-xs text-slate-500 mt-1">Reviewing active questions created for student evaluation.</p>
        </div>

        <?php if (count($quizzes) > 0): ?>
            <form class="space-y-6">
                <?php foreach ($quizzes as $index => $quiz): ?>
                    <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-pastel-text flex items-center space-x-2">
                            <span class="bg-pastel-badge text-pastel-hover px-2.5 py-1 rounded-lg text-xs">Q<?php echo $index + 1; ?></span>
                            <span><?php echo htmlspecialchars($quiz['question']); ?>[cite: 1]</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pl-2">
                            <label class="flex items-center space-x-3 p-3 rounded-xl border border-blue-50 bg-pastel-bg/30 text-xs cursor-pointer hover:border-pastel-primary">
                                <input type="radio" name="question_<?php echo $quiz['id']; ?>" value="A">
                                <span><strong>A:</strong> <?php echo htmlspecialchars($quiz['option_a']); ?>[cite: 1]</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 rounded-xl border border-blue-50 bg-pastel-bg/30 text-xs cursor-pointer hover:border-pastel-primary">
                                <input type="radio" name="question_<?php echo $quiz['id']; ?>" value="B">
                                <span><strong>B:</strong> <?php echo htmlspecialchars($quiz['option_b']); ?>[cite: 1]</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 rounded-xl border border-blue-50 bg-pastel-bg/30 text-xs cursor-pointer hover:border-pastel-primary">
                                <input type="radio" name="question_<?php echo $quiz['id']; ?>" value="C">
                                <span><strong>C:</strong> <?php echo htmlspecialchars($quiz['option_c']); ?>[cite: 1]</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 rounded-xl border border-blue-50 bg-pastel-bg/30 text-xs cursor-pointer hover:border-pastel-primary">
                                <input type="radio" name="question_<?php echo $quiz['id']; ?>" value="D">
                                <span><strong>D:</strong> <?php echo htmlspecialchars($quiz['option_d']); ?>[cite: 1]</span>
                            </label>
                        </div>
                        <div class="text-right pt-2">
                            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">
                                Correct Answer: Option <?php echo htmlspecialchars($quiz['correct_option']); ?>[cite: 1]
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        <?php else: ?>
            <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-12 text-center">
                <p class="text-xs text-slate-400">No quiz questions have been added for this chapter yet. Go back to chapter details to add some questions[cite: 1].</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>