<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ai_quiz_helper.php';

header('Content-Type: application/json');

$studentId = 3;
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
    exit;
}

$attemptKey = trim((string)($input['attempt_key'] ?? ''));
$chapterId = (int)($input['chapter_id'] ?? 0);
$topicLabel = trim((string)($input['topic_label'] ?? ''));
$scopeType = ($input['scope_type'] ?? 'overall') === 'subtopic' ? 'subtopic' : 'overall';
$subtopic = trim((string)($input['subtopic'] ?? ''));
$difficulty = ucfirst(strtolower(trim((string)($input['difficulty'] ?? 'Beginner'))));
$recommendedLevel = ucfirst(strtolower(trim((string)($input['recommended_level'] ?? 'Beginner'))));
$score = (int)($input['score'] ?? 0);
$total = (int)($input['total'] ?? 0);
$weakSkill = trim((string)($input['weak_skill'] ?? ''));
$answers = $input['answers'] ?? [];

$allowedLevels = ['Beginner', 'Intermediate', 'Advanced'];
if (!in_array($difficulty, $allowedLevels, true)) {
    $difficulty = 'Beginner';
}
if (!in_array($recommendedLevel, $allowedLevels, true)) {
    $recommendedLevel = 'Beginner';
}

if ($attemptKey === '' || $chapterId <= 0 || $topicLabel === '' || $total <= 0 || !is_array($answers)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Quiz history data is incomplete.']);
    exit;
}

$percentage = $total > 0 ? round(($score / $total) * 100, 1) : 0;

try {
    ensureAiQuizTables($pdo);
    $pdo->beginTransaction();

    $existing = $pdo->prepare('SELECT id FROM ai_quiz_attempts WHERE attempt_key = ?');
    $existing->execute([$attemptKey]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'attempt_id' => (int)$existingId, 'already_saved' => true]);
        exit;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO ai_quiz_attempts (\n            attempt_key, student_id, chapter_id, topic_label, scope_type, subtopic,\n            difficulty, recommended_level, score, total, percentage, weak_skill\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");
    $stmt->execute([
        $attemptKey,
        $studentId,
        $chapterId,
        $topicLabel,
        $scopeType,
        $subtopic !== '' ? $subtopic : null,
        $difficulty,
        $recommendedLevel,
        $score,
        $total,
        $percentage,
        $weakSkill !== '' ? $weakSkill : null
    ]);

    $attemptId = (int)$pdo->lastInsertId();

    $answerStmt = $pdo->prepare("\n        INSERT INTO ai_quiz_answers (\n            attempt_id, question_order, question_text, options_json, correct_index,\n            student_index, student_answer, correct_answer, is_correct, skill, difficulty, explanation\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");

    foreach ($answers as $index => $answer) {
        if (!is_array($answer)) {
            continue;
        }

        $questionText = trim((string)($answer['question'] ?? ''));
        $options = $answer['options'] ?? [];
        $correctIndex = (int)($answer['correct_index'] ?? -1);
        $studentIndex = (int)($answer['student_index'] ?? -1);
        $studentAnswer = trim((string)($answer['student_answer'] ?? ''));
        $correctAnswer = trim((string)($answer['correct_answer'] ?? ''));
        $isCorrect = !empty($answer['is_correct']) ? 1 : 0;
        $skill = trim((string)($answer['skill'] ?? ($subtopic !== '' ? $subtopic : 'General Practice')));
        $questionDifficulty = ucfirst(strtolower(trim((string)($answer['difficulty'] ?? $difficulty))));
        $explanation = trim((string)($answer['explanation'] ?? ''));

        if ($questionText === '' || !is_array($options) || count($options) !== 4 || $studentIndex < 0 || $studentIndex > 3 || $correctIndex < 0 || $correctIndex > 3) {
            continue;
        }

        if (!in_array($questionDifficulty, $allowedLevels, true)) {
            $questionDifficulty = $difficulty;
        }

        $answerStmt->execute([
            $attemptId,
            $index + 1,
            $questionText,
            json_encode(array_values($options), JSON_UNESCAPED_UNICODE),
            $correctIndex,
            $studentIndex,
            $studentAnswer,
            $correctAnswer,
            $isCorrect,
            $skill !== '' ? $skill : 'General Practice',
            $questionDifficulty,
            $explanation !== '' ? $explanation : 'Review the correct method for this question.'
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'attempt_id' => $attemptId,
        'percentage' => $percentage
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not save AI quiz history.',
        'details' => $e->getMessage()
    ]);
}
