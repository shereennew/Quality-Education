<?php

function ensureAiQuizTables(PDO $pdo): void
{
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS ai_quiz_attempts (\n            id INTEGER PRIMARY KEY AUTOINCREMENT,\n            attempt_key TEXT NOT NULL UNIQUE,\n            student_id INTEGER NOT NULL,\n            chapter_id INTEGER NOT NULL,\n            topic_label TEXT NOT NULL,\n            scope_type TEXT NOT NULL DEFAULT 'overall',\n            subtopic TEXT DEFAULT NULL,\n            difficulty TEXT NOT NULL,\n            recommended_level TEXT NOT NULL,\n            score INTEGER NOT NULL DEFAULT 0,\n            total INTEGER NOT NULL DEFAULT 0,\n            percentage REAL NOT NULL DEFAULT 0,\n            weak_skill TEXT DEFAULT NULL,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        )\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS ai_quiz_answers (\n            id INTEGER PRIMARY KEY AUTOINCREMENT,\n            attempt_id INTEGER NOT NULL,\n            question_order INTEGER NOT NULL,\n            question_text TEXT NOT NULL,\n            options_json TEXT NOT NULL,\n            correct_index INTEGER NOT NULL,\n            student_index INTEGER NOT NULL,\n            student_answer TEXT NOT NULL,\n            correct_answer TEXT NOT NULL,\n            is_correct INTEGER NOT NULL DEFAULT 0,\n            skill TEXT NOT NULL,\n            difficulty TEXT NOT NULL,\n            explanation TEXT NOT NULL,\n            FOREIGN KEY (attempt_id) REFERENCES ai_quiz_attempts(id) ON DELETE CASCADE\n        )\n    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ai_attempt_student_chapter ON ai_quiz_attempts(student_id, chapter_id, created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ai_answer_attempt ON ai_quiz_answers(attempt_id, question_order)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ai_answer_skill ON ai_quiz_answers(skill)");
}

function aiLevelFromPercentage(float $percentage): string
{
    if ($percentage >= 80) {
        return 'Advanced';
    }

    if ($percentage >= 50) {
        return 'Intermediate';
    }

    return 'Beginner';
}

function getAiRecommendedLevel(PDO $pdo, int $studentId, int $chapterId, ?string $subtopic = null): array
{
    ensureAiQuizTables($pdo);

    if ($subtopic !== null && trim($subtopic) !== '') {
        $stmt = $pdo->prepare("\n            SELECT a.is_correct\n            FROM ai_quiz_answers a\n            JOIN ai_quiz_attempts q ON q.id = a.attempt_id\n            WHERE q.student_id = ?\n              AND q.chapter_id = ?\n              AND LOWER(a.skill) = LOWER(?)\n            ORDER BY a.id DESC\n            LIMIT 20\n        ");
        $stmt->execute([$studentId, $chapterId, trim($subtopic)]);
    } else {
        $stmt = $pdo->prepare("\n            SELECT a.is_correct\n            FROM ai_quiz_answers a\n            JOIN ai_quiz_attempts q ON q.id = a.attempt_id\n            WHERE q.student_id = ?\n              AND q.chapter_id = ?\n            ORDER BY a.id DESC\n            LIMIT 30\n        ");
        $stmt->execute([$studentId, $chapterId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($rows)) {
        $correct = array_sum(array_map('intval', $rows));
        $total = count($rows);
        $percentage = ($correct / $total) * 100;

        return [
            'level' => aiLevelFromPercentage($percentage),
            'percentage' => round($percentage, 1),
            'source' => 'AI practice history',
            'answer_count' => $total
        ];
    }

    // First AI attempt: use existing chapter assessment performance when available.
    if ($subtopic === null || trim($subtopic) === '') {
        try {
            $stmt = $pdo->prepare("\n                SELECT score\n                FROM student_assessments\n                WHERE student_id = ? AND island_id = ?\n                ORDER BY submitted_at DESC\n                LIMIT 5\n            ");
            $stmt->execute([$studentId, $chapterId]);
            $scores = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $percentages = [];
            foreach ($scores as $scoreText) {
                if (preg_match('/(\\d+(?:\\.\\d+)?)\\s*\\/\\s*(\\d+(?:\\.\\d+)?)/', (string)$scoreText, $m)) {
                    $earned = (float)$m[1];
                    $possible = (float)$m[2];
                    if ($possible > 0) {
                        $percentages[] = ($earned / $possible) * 100;
                    }
                } elseif (is_numeric($scoreText)) {
                    $numeric = (float)$scoreText;
                    if ($numeric >= 0 && $numeric <= 100) {
                        $percentages[] = $numeric;
                    }
                }
            }

            if (!empty($percentages)) {
                $percentage = array_sum($percentages) / count($percentages);
                return [
                    'level' => aiLevelFromPercentage($percentage),
                    'percentage' => round($percentage, 1),
                    'source' => 'chapter assessment history',
                    'answer_count' => count($percentages)
                ];
            }
        } catch (Throwable $e) {
            // Fall through to the safe first-time default below.
        }
    }

    return [
        'level' => 'Beginner',
        'percentage' => null,
        'source' => 'first AI practice',
        'answer_count' => 0
    ];
}

function getRecentAiQuestions(PDO $pdo, int $studentId, int $chapterId, ?string $skill = null, int $limit = 20): array
{
    ensureAiQuizTables($pdo);
    $limit = max(1, min(50, $limit));

    if ($skill !== null && trim($skill) !== '') {
        $stmt = $pdo->prepare("\n            SELECT a.question_text\n            FROM ai_quiz_answers a\n            JOIN ai_quiz_attempts q ON q.id = a.attempt_id\n            WHERE q.student_id = ?\n              AND q.chapter_id = ?\n              AND LOWER(a.skill) = LOWER(?)\n            ORDER BY a.id DESC\n            LIMIT {$limit}\n        ");
        $stmt->execute([$studentId, $chapterId, trim($skill)]);
    } else {
        $stmt = $pdo->prepare("\n            SELECT a.question_text\n            FROM ai_quiz_answers a\n            JOIN ai_quiz_attempts q ON q.id = a.attempt_id\n            WHERE q.student_id = ?\n              AND q.chapter_id = ?\n            ORDER BY a.id DESC\n            LIMIT {$limit}\n        ");
        $stmt->execute([$studentId, $chapterId]);
    }

    return array_values(array_unique(array_filter(array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN)))));
}

function getAiAttempts(PDO $pdo, int $studentId, ?int $chapterId = null, int $limit = 20): array
{
    ensureAiQuizTables($pdo);
    $limit = max(1, min(100, $limit));

    if ($chapterId !== null) {
        $stmt = $pdo->prepare("\n            SELECT *\n            FROM ai_quiz_attempts\n            WHERE student_id = ? AND chapter_id = ?\n            ORDER BY created_at DESC, id DESC\n            LIMIT {$limit}\n        ");
        $stmt->execute([$studentId, $chapterId]);
    } else {
        $stmt = $pdo->prepare("\n            SELECT *\n            FROM ai_quiz_attempts\n            WHERE student_id = ?\n            ORDER BY created_at DESC, id DESC\n            LIMIT {$limit}\n        ");
        $stmt->execute([$studentId]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAiAttemptAnswers(PDO $pdo, int $attemptId): array
{
    ensureAiQuizTables($pdo);
    $stmt = $pdo->prepare("\n        SELECT *\n        FROM ai_quiz_answers\n        WHERE attempt_id = ?\n        ORDER BY question_order ASC, id ASC\n    ");
    $stmt->execute([$attemptId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
