<?php
require_once __DIR__ . '/../config/db.php';

$student_id = 3;
$assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;

if ($assessment_id <= 0) {
    die("Invalid assessment ID.");
}

// Fetch assessment header details
$stmt = $pdo->prepare("
    SELECT *
    FROM student_assessments
    WHERE id = ?
      AND student_id = ?
");
$stmt->execute([$assessment_id, $student_id]);

$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    die("Assessment record not found.");
}

// Fetch all questions and recorded answers
$q_stmt = $pdo->prepare("
    SELECT *
    FROM student_quiz_answers
    WHERE assessment_id = ?
    ORDER BY id ASC
");

$q_stmt->execute([$assessment_id]);
$questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Answered Quiz Review - <?= htmlspecialchars($assessment['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex justify-between items-center">
            <div>
<a href="history.php?island_id=<?= $assessment['island_id'] ?>"
   class="inline-flex items-center gap-2 text-base font-bold px-5 py-3 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl border border-indigo-200 transition-all shadow-sm">
    ← Back to History
</a>
                <h1 class="text-2xl font-black mt-1"><?= htmlspecialchars($assessment['title']) ?></h1>
                <p class="text-xs text-slate-500">Submitted on: <?= $assessment['submitted_at'] ?></p>
            </div>
            <div class="text-right">
                <span class="text-xs uppercase font-bold text-slate-400">Final Score</span>
                <div class="text-2xl font-black text-indigo-600"><?= htmlspecialchars($assessment['score']) ?></div>
            </div>
        </div>

        <!-- Question-by-Question Break Down -->
        <div class="space-y-4">
            <?php foreach ($questions as $index => $q): ?>
                <div class="bg-white p-6 rounded-2xl border <?= $q['is_correct'] ? 'border-slate-200' : 'border-rose-200 bg-rose-50/30' ?> shadow-sm space-y-3">
                    
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-sm text-slate-700">Question <?= $index + 1 ?></span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $q['is_correct'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                            <?= $q['is_correct'] ? '✓ Correct' : '✕ Salah' ?>
                        </span>
                    </div>

                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($q['question_text']) ?></p>

                    <div class="grid grid-cols-2 gap-4 text-sm pt-2">
                        <div class="p-3 rounded-xl <?= $q['is_correct'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-900 border border-rose-200' ?>">
                            <span class="block text-xs font-bold opacity-75">Your Answer</span>
                            <strong><?= htmlspecialchars($q['student_answer']) ?></strong>
                        </div>

                        <?php if (!$q['is_correct']): ?>
                            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="block text-xs font-bold opacity-75">Correct Answer</span>
                                <strong><?= htmlspecialchars($q['correct_answer']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($q['explanation'])): ?>
                        <div class="text-xs bg-slate-100 p-3 rounded-xl text-slate-600">
                            💡 <strong>Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
</body>
</html>