<?php
// module.php - SAVES QUIZ ATTEMPTS TO DB, PREVENTS RE-ATTEMPTING, AND PERSISTS ANSWERS/EXPLANATIONS ACROSS PAGE LOADS
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

// Determine student ID dynamically without hardcoding
if (isset($_SESSION['student_id'])) {
    $student_id = (int)$_SESSION['student_id'];
} elseif (isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
} else {
    // Fallback to fetch the first valid student from the database if no session exists
    try {
        $stmt_fallback = $pdo->query("SELECT id FROM students ORDER BY id ASC LIMIT 1");
        $student_id = (int)$stmt_fallback->fetchColumn();
        if (!$student_id) {
            $student_id = 3; // Absolute fallback if students table is empty
        }
    } catch (Exception $e) {
        $student_id = 3;
    }
}

// Fetch Student Info from DB
try {
    $stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt_student->execute([$student_id]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $student = null;
}

if (!$student) {
    $student = [
        'name' => 'Aina',
        'level' => 4,
        'xp' => 320
    ];
}

$classroom_id = $student['classroom_id'] ?? 1;

// Handle AJAX submission of quiz answers

// ============================================================
// HANDLE AJAX SUBMISSIONS
// ============================================================


// ============================================================
// 1. SUBMIT CHAPTER TEST
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'submit_chapter_test'
) {

    header('Content-Type: application/json');

    $chapter_num = (int)($_POST['chapter_num'] ?? 1);
    $chapter_name = trim($_POST['chapter_name'] ?? '');
    $answers = $_POST['answers'] ?? [];

    try {

        // ----------------------------------------------------
        // Check whether ALL subtopic quizzes have been attempted.
        // IMPORTANT: the lesson score does NOT affect unlocking.
        // A submitted subtopic quiz counts as completed even if the
        // student got every question wrong.
        // ----------------------------------------------------

        $stmt_required = $pdo->prepare("
            SELECT COUNT(DISTINCT subtopic_name)
            FROM chapter_materials
            WHERE chapter_name = ?
              AND subtopic_name IS NOT NULL
              AND TRIM(subtopic_name) <> ''
        ");
        $stmt_required->execute([$chapter_name]);
        $required_count = (int)$stmt_required->fetchColumn();

        $stmt_completed = $pdo->prepare("
            SELECT COUNT(DISTINCT title)
            FROM student_assessments
            WHERE student_id = ?
              AND island_id = ?
              AND type = 'Quiz'
              AND status = 'Completed'
              AND title LIKE 'Subtopic % Assessment'
        ");
        $stmt_completed->execute([
            $student_id,
            $chapter_num
        ]);
        $completed_count = (int)$stmt_completed->fetchColumn();

        // Do not allow Chapter Test until every subtopic is completed.
        if (
            $required_count <= 0 ||
            $completed_count < $required_count
        ) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'Complete all lessons before taking the Chapter Test.'
            ]);

            exit;
        }


        // ----------------------------------------------------
        // Prevent Chapter Test re-attempt
        // ----------------------------------------------------

        $test_title =
            'Chapter ' .
            $chapter_num .
            ' Final Test';

        $stmt_check = $pdo->prepare("
            SELECT id
            FROM student_assessments
            WHERE student_id = ?
              AND island_id = ?
              AND title = ?
              AND type = 'Chapter Test'
            LIMIT 1
        ");

        $stmt_check->execute([
            $student_id,
            $chapter_num,
            $test_title
        ]);

        if ($stmt_check->fetch()) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'Chapter Test already submitted.'
            ]);

            exit;
        }


        // ----------------------------------------------------
        // Get all Chapter Test questions
        // ----------------------------------------------------

        $stmt_test = $pdo->prepare("
            SELECT *
            FROM chapter_quizzes
            WHERE chapter_name = ?
            ORDER BY id ASC
        ");

        $stmt_test->execute([
            $chapter_name
        ]);

        $test_questions =
            $stmt_test->fetchAll(PDO::FETCH_ASSOC);

        if (empty($test_questions)) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'No Chapter Test questions found.'
            ]);

            exit;
        }


        // ----------------------------------------------------
        // Check answers
        // ----------------------------------------------------

        $correctCount = 0;
        $totalQuestions = count($test_questions);
        $detailed_results = [];

        foreach ($test_questions as $q) {

            $qId = (int)$q['id'];

            $correctOpt =
                strtolower(
                    trim(
                        $q['correct_option'] ?? ''
                    )
                );

            $userAns =
                isset($answers[$qId])
                    ? strtolower(
                        trim(
                            $answers[$qId]
                        )
                    )
                    : '';

            $isCorrect =
                (
                    $userAns !== '' &&
                    $userAns === $correctOpt
                );

            if ($isCorrect) {
                $correctCount++;
            }

            $detailed_results[$qId] = [
                'user_answer' => $userAns,
                'correct_answer' => $correctOpt,
                'is_correct' => $isCorrect,
                'explanation' =>
                    $q['explanation']
                    ?? 'No explanation provided for this question.'
            ];
        }


        // ----------------------------------------------------
        // Calculate Chapter Mastery
        // ----------------------------------------------------

        $percentage =
            $totalQuestions > 0
                ? ($correctCount / $totalQuestions) * 100
                : 0;

        if ($percentage >= 80) {

            $level = 3;
            $level_name = 'Master';

        } elseif ($percentage >= 50) {

            $level = 2;
            $level_name = 'Intermediate';

        } else {

            $level = 1;
            $level_name = 'Beginner';
        }

        $score_text =
            $correctCount .
            '/' .
            $totalQuestions;


        // ----------------------------------------------------
        // Save assessment + answers + mastery together
        // ----------------------------------------------------

        $pdo->beginTransaction();


        // Save Chapter Test assessment
        $stmt_save = $pdo->prepare("
            INSERT INTO student_assessments
            (
                student_id,
                island_id,
                title,
                type,
                score,
                status
            )
            VALUES (?, ?, ?, 'Chapter Test', ?, 'Completed')
        ");

        $stmt_save->execute([
            $student_id,
            $chapter_num,
            $test_title,
            $score_text
        ]);

        $assessment_id =
            (int)$pdo->lastInsertId();


        // Save detailed answers
        $stmt_answer = $pdo->prepare("
            INSERT INTO student_quiz_answers
            (
                assessment_id,
                student_id,
                quiz_id,
                question_text,
                student_answer,
                correct_answer,
                is_correct,
                explanation,
                answer_status,
                score
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($test_questions as $q) {

            $qId = (int)$q['id'];

            $result =
                $detailed_results[$qId];

            $stmt_answer->execute([
                $assessment_id,
                $student_id,
                $qId,
                $q['question'],
                $result['user_answer'],
                $result['correct_answer'],
                $result['is_correct'] ? 1 : 0,
                $result['explanation'],
                $result['is_correct']
                    ? 'Correct'
                    : 'Incorrect',
                $result['is_correct'] ? 1 : 0
            ]);
        }


        // ----------------------------------------------------
        // Update student_progress
        // ----------------------------------------------------

        $stmt_progress = $pdo->prepare("
            SELECT id
            FROM student_progress
            WHERE student_id = ?
              AND island_id = ?
            LIMIT 1
        ");

        $stmt_progress->execute([
            $student_id,
            $chapter_num
        ]);

        $progress_id =
            $stmt_progress->fetchColumn();

        if ($progress_id) {

            $stmt_update = $pdo->prepare("
                UPDATE student_progress
                SET level = ?,
                    status = 'Completed'
                WHERE id = ?
            ");

            $stmt_update->execute([
                $level,
                $progress_id
            ]);

        } else {

            $stmt_insert = $pdo->prepare("
                INSERT INTO student_progress
                (
                    student_id,
                    island_id,
                    chapter_name,
                    level,
                    status
                )
                VALUES (?, ?, ?, ?, 'Completed')
            ");

            $stmt_insert->execute([
                $student_id,
                $chapter_num,
                $chapter_name,
                $level
            ]);
        }


        $pdo->commit();


        echo json_encode([
            'status' => 'success',
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'score' => round($percentage),
            'level' => $level,
            'level_name' => $level_name,
            'answers' => $detailed_results
        ]);

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    exit;
}



// ============================================================
// 2. SUBMIT SUBTOPIC LESSON
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'submit_quiz'
) {

    header('Content-Type: application/json');

    $chapter_num =
        (int)($_POST['chapter_num'] ?? 1);

    $subtopic_num =
        trim($_POST['subtopic_num'] ?? '');

    $chapter_name =
        trim($_POST['chapter_name'] ?? '');

    $answers =
        $_POST['answers'] ?? [];

    $assessment_title =
        'Subtopic ' .
        $subtopic_num .
        ' Assessment';

    try {

        // ----------------------------------------------------
        // Prevent re-attempt
        // ----------------------------------------------------

        $stmt_existing = $pdo->prepare("
            SELECT id
            FROM student_assessments
            WHERE student_id = ?
              AND island_id = ?
              AND title = ?
              AND type = 'Quiz'
            LIMIT 1
        ");

        $stmt_existing->execute([
            $student_id,
            $chapter_num,
            $assessment_title
        ]);

        if ($stmt_existing->fetch()) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'This lesson has already been submitted.'
            ]);

            exit;
        }


        // ----------------------------------------------------
        // Get questions for this subtopic
        // ----------------------------------------------------

        $stmt_questions = $pdo->prepare("
            SELECT *
            FROM chapter_quizzes
            WHERE chapter_name = ?
              AND subtopic_name = ?
            ORDER BY id ASC
        ");

        $stmt_questions->execute([
            $chapter_name,
            $subtopic_num
        ]);

        $subtopic_questions =
            $stmt_questions->fetchAll(PDO::FETCH_ASSOC);


        if (empty($subtopic_questions)) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'No questions found for this lesson.'
            ]);

            exit;
        }


        // ----------------------------------------------------
        // Check answers
        // ----------------------------------------------------

        $correctCount = 0;
        $totalQuestions =
            count($subtopic_questions);

        $detailed_results = [];

        foreach ($subtopic_questions as $q) {

            $qId =
                (int)$q['id'];

            $correctOpt =
                strtolower(
                    trim(
                        $q['correct_option'] ?? ''
                    )
                );

            $userAns =
                isset($answers[$qId])
                    ? strtolower(
                        trim(
                            $answers[$qId]
                        )
                    )
                    : '';

            $is_correct =
                (
                    $userAns !== '' &&
                    $userAns === $correctOpt
                );

            if ($is_correct) {
                $correctCount++;
            }

            $detailed_results[$qId] = [
                'user_answer' => $userAns,
                'correct_answer' => $correctOpt,
                'is_correct' => $is_correct,
                'explanation' =>
                    $q['explanation']
                    ?? 'No explanation provided for this question.'
            ];
        }


        $score =
            $totalQuestions > 0
                ? ($correctCount / $totalQuestions) * 100
                : 0;

        $score_text =
            $correctCount .
            '/' .
            $totalQuestions;


        // ----------------------------------------------------
        // Save lesson assessment + answers
        // ----------------------------------------------------

        $pdo->beginTransaction();


        $stmt_save_assessment = $pdo->prepare("
            INSERT INTO student_assessments
            (
                student_id,
                island_id,
                title,
                type,
                score,
                status
            )
            VALUES (?, ?, ?, 'Quiz', ?, 'Completed')
        ");

        $stmt_save_assessment->execute([
            $student_id,
            $chapter_num,
            $assessment_title,
            $score_text
        ]);

        $assessment_id =
            (int)$pdo->lastInsertId();


        $stmt_save_answer = $pdo->prepare("
            INSERT INTO student_quiz_answers
            (
                assessment_id,
                student_id,
                quiz_id,
                question_text,
                student_answer,
                correct_answer,
                is_correct,
                explanation,
                answer_status,
                score
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($subtopic_questions as $q) {

            $qId =
                (int)$q['id'];

            $result =
                $detailed_results[$qId];

            $stmt_save_answer->execute([
                $assessment_id,
                $student_id,
                $qId,
                $q['question'],
                $result['user_answer'],
                $result['correct_answer'],
                $result['is_correct'] ? 1 : 0,
                $result['explanation'],
                $result['is_correct']
                    ? 'Correct'
                    : 'Incorrect',
                $result['is_correct'] ? 1 : 0
            ]);
        }


        $pdo->commit();


        echo json_encode([
            'status' => 'success',
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'score' => round($score),
            'answers' => $detailed_results
        ]);

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// Fetch student quiz completion history & saved answers data
$completed_quizzes_data = [];

try {
    // Get all quiz assessments completed by this student
    $stmt_history = $pdo->prepare("
        SELECT id, island_id, title, score, status
        FROM student_assessments
        WHERE student_id = ?
          AND type = 'Quiz'
    ");

    $stmt_history->execute([$student_id]);

    $assessment_rows = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assessment_rows as $assessment) {

        // Example:
        // Subtopic 1.1 Assessment
        // Subtopic 1.2 Assessment
        if (preg_match('/^Subtopic (.+) Assessment$/', $assessment['title'], $matches)) {

            $subtopic_val = $matches[1];

            $quiz_set_id =
                'sub_' .
                $assessment['island_id'] .
                '_' .
                str_replace('.', '_', $subtopic_val);

            // Get saved answers for this assessment
            $stmt_answers = $pdo->prepare("
                SELECT
                    quiz_id,
                    question_text,
                    student_answer,
                    correct_answer,
                    is_correct,
                    explanation
                FROM student_quiz_answers
                WHERE assessment_id = ?
                ORDER BY id ASC
            ");

            $stmt_answers->execute([$assessment['id']]);

            $answers = [];

            foreach ($stmt_answers->fetchAll(PDO::FETCH_ASSOC) as $answer) {

                $answers[$answer['quiz_id']] = [
                    'user_answer' => strtolower($answer['student_answer']),
                    'correct_answer' => strtolower($answer['correct_answer']),
                    'is_correct' => (bool)$answer['is_correct'],
                    'explanation' => $answer['explanation']
                ];
            }

            $completed_quizzes_data[$quiz_set_id] = [
                'score' => $assessment['score'],
                'answers' => $answers
            ];
        }
    }

} catch (Exception $e) {
    $completed_quizzes_data = [];
}

$completed_quiz_ids = array_keys($completed_quizzes_data);

// 1. Fetch only chapters unlocked for this specific classroom
$chapters = [];
$db_chapters = [];

$stmt = $pdo->prepare("
    SELECT chapter_name
    FROM classroom_chapters
    WHERE classroom_id = ?
      AND is_unlocked = 1
    ORDER BY id ASC
");

$stmt->execute([$classroom_id]);

$db_chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);


if (empty($db_chapters)) {
    $chapters = [];
} else {
    foreach ($db_chapters as $index => $chapter_name) {
        $stmt_materials = $pdo->prepare("SELECT * FROM chapter_materials WHERE chapter_name = ? ORDER BY id ASC");
        $stmt_materials->execute([$chapter_name]);
        $materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);

        $db_resources = [];
        try {
            $stmt_res = $pdo->prepare("SELECT * FROM additional_resources WHERE chapter_name = ?");
            $stmt_res->execute([$chapter_name]);
            $db_resources = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_resources = [];
        }

        // Fetch all quiz questions belonging to this chapter
        $db_quizzes = [];
        try {
            $stmt_quiz = $pdo->prepare("SELECT * FROM chapter_quizzes WHERE chapter_name = ? ORDER BY id");
            $stmt_quiz->execute([$chapter_name]);
            $db_quizzes = $stmt_quiz->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db_quizzes = [];
        }

        $subtopics = [];
        $chapter_num = $index + 1;

        foreach ($materials as $material_index => $material) {
            $material_title = $material['title'] ?? $material['file_name'] ?? 'Teacher Material';
            $file_path = $material['file_path'] ?? null;
            $current_subtopic_idx = $material_index + 1;
            
            $subtopic_num = $material['subtopic_name'] ?? $current_subtopic_idx;

            if (!$file_path && !empty($material['file_name'])) {
                $file_path = '../uploads/' . rawurlencode($material['file_name']);
            } elseif (strpos((string)$file_path, 'uploads/') === 0) {
                $file_path = '../' . $file_path;
            }

            // Filter resources specifically meant for this subtopic
            $subtopic_resources = [];
            foreach ($db_resources as $res) {
                if (isset($res['subtopic_index']) && (int)$res['subtopic_index'] === (int)$current_subtopic_idx) {
                    $subtopic_resources[] = [
                        'title' => $res['title'],
                        'url' => $res['url'],
                        'type' => $res['type'] ?? 'Remedial'
                    ];
                }
            }

            $subtopic_questions = [];
            foreach ($db_quizzes as $q) {
                $q_sub = $q['subtopic_name'] ?? null;
                if ($q_sub !== null && ((string)$q_sub === (string)$subtopic_num || (string)$q_sub === (string)$current_subtopic_idx)) {
                    $subtopic_questions[] = $q;
                }
            }

            // Fetch teacher feedback for this subtopic
$teacher_feedback = null;

try {
    $stmt_feedback = $pdo->prepare("
        SELECT comment
        FROM teacher_quiz_feedback
        WHERE student_id = ?
          AND chapter_name = ?
          AND subtopic_name = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt_feedback->execute([
        $student_id,
        $chapter_name,
        $subtopic_num
    ]);

    $feedback_row = $stmt_feedback->fetch(PDO::FETCH_ASSOC);

    if ($feedback_row) {
        $teacher_feedback = $feedback_row['comment'];
    }

} catch (Exception $e) {
    $teacher_feedback = null;
}

$quiz_set_id = 'sub_' . $chapter_num . '_' . str_replace('.', '_', $subtopic_num);

// Check directly from database whether this lesson was already submitted
$stmt_completed_check = $pdo->prepare("
    SELECT id, score
    FROM student_assessments
    WHERE student_id = ?
      AND island_id = ?
      AND title = ?
      AND type = 'Quiz'
    ORDER BY id DESC
    LIMIT 1
");

$stmt_completed_check->execute([
    $student_id,
    $chapter_num,
    'Subtopic ' . $subtopic_num . ' Assessment'
]);

$completed_assessment = $stmt_completed_check->fetch(PDO::FETCH_ASSOC);

$is_completed = !empty($completed_assessment);

$status = $is_completed ? 'Completed' : 'Available';
$badge_color = $is_completed
    ? 'bg-emerald-100 text-emerald-700'
    : 'bg-blue-100 text-blue-700';

$subtopics[(string)$current_subtopic_idx] = [
    'title' => $material_title,
    'badge_color' => $badge_color,
    'status' => $status,
    'is_completed' => $is_completed,
'saved_data' => $is_completed
    ? ($completed_quizzes_data[$quiz_set_id] ?? null)
    : null,
        'teacher_feedback' => $teacher_feedback,
    'notes' => [
        'overview' => 'Learning material provided by your teacher for this chapter.',
        'points' => ['Read the material carefully and click the quizzes tab above when ready to test your knowledge.'],
        'example' => $file_path ? '<a class="text-pastel-primary font-bold underline" href="' . htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8') . '" target="_blank">Open teacher material</a>' : 'No file attached.'
    ],
    'questions' => $subtopic_questions,
    'chapter_num' => $chapter_num,
    'subtopic_name_val' => $chapter_name,
    'subtopic_num' => $subtopic_num,
    'additional_resources' => $subtopic_resources
];

        }


        // Check whether all subtopic quizzes have been submitted.
        // Unlock is based ONLY on completion, never on the lesson score.
$total_subtopics = count($subtopics);

$stmt_completed_lessons = $pdo->prepare("
    SELECT COUNT(DISTINCT title)
    FROM student_assessments
    WHERE student_id = ?
      AND island_id = ?
      AND type = 'Quiz'
      AND status = 'Completed'
      AND title LIKE 'Subtopic % Assessment'
");
$stmt_completed_lessons->execute([
    $student_id,
    $chapter_num
]);

$completed_subtopics = (int)$stmt_completed_lessons->fetchColumn();

$chapter_test_unlocked = (
    $total_subtopics > 0 &&
    $completed_subtopics >= $total_subtopics
);

// Check whether Chapter Test has already been completed
$chapter_test_title = 'Chapter ' . $chapter_num . ' Final Test';

$stmt_chapter_test = $pdo->prepare("
    SELECT id, score, status
    FROM student_assessments
    WHERE student_id = ?
      AND island_id = ?
      AND title = ?
      AND type = 'Chapter Test'
    ORDER BY id DESC
    LIMIT 1
");

$stmt_chapter_test->execute([
    $student_id,
    $chapter_num,
    $chapter_test_title
]);

$chapter_test_data = $stmt_chapter_test->fetch(PDO::FETCH_ASSOC);
$chapter_test_completed = !empty($chapter_test_data);


$chapters[$index + 1] = [
    'title' => $chapter_name,
    'topic' => 'Chapter ' . ($index + 1),
    'subtopics' => $subtopics,

    'chapter_test_unlocked' => $chapter_test_unlocked,
    'chapter_test_completed' => $chapter_test_completed,
    'chapter_test_data' => $chapter_test_data,
    'chapter_test_questions' => $db_quizzes
];
    }
}

$selected_chap_id = isset($_GET['chap']) && isset($chapters[$_GET['chap']]) ? (int)$_GET['chap'] : 1;
$active_chapter = $chapters[$selected_chap_id];
$subtopic_keys = array_keys($active_chapter['subtopics']);
$first_subtopic_key = !empty($subtopic_keys) ? $subtopic_keys[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduHunt - Modules</title>
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss5.1"></script>
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
        .main-navbar { height:96px; background:#e1e9f5; border-bottom:1px solid rgba(125,160,202,.25); box-shadow:0 3px 15px rgba(75,100,130,.10); position:fixed; top:0; left:0; width:100%; z-index:1000; display:flex; align-items:center; }
        .nav-inner { width:100%; max-width:1500px; margin:auto; padding:0 45px; display:grid; grid-template-columns:1fr auto 1fr; align-items:center; }
        .brand { display:flex; align-items:center; gap:13px; text-decoration:none; color:#2c3e50; }
        .brand-icon { width:53px; height:53px; border-radius:16px; background:#cbe0f5; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:900; }
        .brand-name { font-size:30px; font-weight:900; }
        .nav-links { display:flex; align-items:center; gap:8px; }
        .nav-links a { text-decoration:none; color:#2c3e50; padding:13px 24px; border-radius:15px; font-size:18px; font-weight:800; transition:.2s; }
        .nav-links a:hover { background:#cbd9eb; }
        .nav-links a.active { background:#7da0ca; color:white; }
        .profile-area { display:flex; justify-content:flex-end; position:relative; }
        .profile-button { border:1px solid rgba(125,160,202,.35); background:white; border-radius:999px; padding:7px 16px 7px 8px; display:flex; align-items:center; gap:10px; cursor:pointer; color:#2c3e50; font-weight:800; font-size:16px; }
        .avatar { width:44px; height:44px; border-radius:50%; background:#cbe0f5; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:900; }
        .profile-dropdown { position:absolute; right:0; top:61px; width:220px; background:white; border:1px solid #dce5f0; border-radius:17px; padding:9px; box-shadow:0 14px 30px rgba(55,80,110,.16); display:none; }
        .profile-dropdown.show { display:block; }
        .dropdown-name { padding:12px; border-bottom:1px solid #e7edf4; font-weight:900; }
        .profile-dropdown a { display:block; padding:12px; margin-top:3px; border-radius:10px; color:#2c3e50; text-decoration:none; font-weight:700; }
        .profile-dropdown a:hover { background:#f0f4f9; }
        @media (max-width:1000px) { .nav-inner { grid-template-columns:auto 1fr; } .nav-links { display:none; } .profile-area { justify-self:end; } }
        @media (max-width:650px) { .brand-name, .profile-name { display:none; } }
    </style>

</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center p-6 pt-32">

    <!-- NAV BAR -->
    <nav class="main-navbar">
        <div class="nav-inner">
            <a href="index.php" class="brand">
                <div class="brand-icon">E</div>
                <div class="brand-name">EduHunt</div>
            </a>

            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="discussion.php">Discussion</a>
                <a href="module.php" class="active">Modules</a>
                <a href="mathhelper.php">Math Helper</a>
                <a href="history.php">History</a>
            </div>

            <div class="profile-area">
                <div class="profile-button">
                    <div class="avatar">
                        <?= htmlspecialchars(strtoupper(substr($student['name'], 0, 1))) ?>
                    </div>
                    <span class="profile-name"><?= htmlspecialchars($student['name']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Chapter Selector -->
        <div class="mb-6 flex gap-3 overflow-x-auto pb-2">
            <?php foreach ($chapters as $chap_num => $chap): ?>
                <a href="module.php?chap=<?= $chap_num ?>" class="px-6 py-3 rounded-xl text-sm font-bold transition shadow-sm whitespace-nowrap flex items-center gap-2 <?= $chap_num === $selected_chap_id ? 'bg-pastel-primary text-white' : 'bg-pastel-card text-slate-600 hover:bg-blue-50 border border-blue-100' ?>">
                    <span>Chapter <?= $chap_num ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Header -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-pastel-text mt-1"><?= htmlspecialchars($active_chapter['title']) ?></h1>
                <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($active_chapter['topic']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Subtopics -->
            <div class="lg:col-span-4 bg-pastel-card p-5 rounded-2xl border border-blue-100 shadow-sm h-fit">
                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider px-3 mb-3">Subtopics</h2>
                <div class="space-y-2.5">
                    <?php foreach ($active_chapter['subtopics'] as $key => $subtopic): ?>
                        <button type="button" onclick="selectSubtopic('<?= $key ?>')" id="subtopic-btn-<?= str_replace('.', '_', $key) ?>" class="subtopic-btn w-full text-left p-4 rounded-xl border transition <?= $key === $first_subtopic_key ? 'border-pastel-primary bg-blue-50/70 shadow-sm' : 'border-slate-100 hover:border-blue-200' ?>">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-md <?= $subtopic['badge_color'] ?>"><?= $subtopic['status'] ?></span>
                            </div>
                            <h3 class="font-bold text-base text-pastel-text"><?= htmlspecialchars($subtopic['title']) ?></h3>
                        </button>
                    <?php endforeach; ?>
                    <?php
$chapterTestUnlocked = !empty($active_chapter['chapter_test_unlocked']);
$chapterTestCompleted = !empty($active_chapter['chapter_test_completed']);
?>

<div class="mt-5 pt-5 border-t border-slate-100">

    <button
        type="button"
        id="chapter-test-btn"
        onclick="<?= $chapterTestUnlocked ? 'openChapterTest()' : 'return false;' ?>"
        class="w-full text-left p-4 rounded-xl border transition
        <?= $chapterTestUnlocked
            ? 'border-emerald-200 bg-emerald-50 hover:border-emerald-400 cursor-pointer'
            : 'border-slate-200 bg-slate-50 cursor-not-allowed opacity-80' ?>"
    >

        <div class="flex justify-between items-center mb-1.5">

            <span class="text-xs font-bold px-2.5 py-0.5 rounded-md
                <?= $chapterTestCompleted
                    ? 'bg-emerald-100 text-emerald-700'
                    : ($chapterTestUnlocked
                        ? 'bg-blue-100 text-blue-700'
                        : 'bg-slate-200 text-slate-500') ?>">

                <?= $chapterTestCompleted
                    ? 'Completed'
                    : ($chapterTestUnlocked ? 'Unlocked' : 'Locked') ?>

            </span>

        </div>

        <h3 class="font-bold text-base text-pastel-text">

            <?= $chapterTestUnlocked
                ? '📝 Chapter Test'
                : '🔒 Chapter Test' ?>

        </h3>

        <p class="text-xs text-slate-500 mt-1">

            <?php if ($chapterTestCompleted): ?>

                Test completed. View your result.

            <?php elseif ($chapterTestUnlocked): ?>

                All lessons completed. Start your Chapter Test.

            <?php else: ?>

                Complete all lessons to unlock.

            <?php endif; ?>

        </p>

    </button>

</div>

                </div>
            </div>

            <!-- Right: Content -->
            <div class="lg:col-span-8 bg-pastel-card p-6 sm:p-8 rounded-2xl border border-blue-100 shadow-sm">
                
                <!-- Tabs -->
                <div id="module-tabs"
     class="flex border-b border-slate-100 mb-6 gap-8 overflow-x-auto">

                    <button type="button" onclick="switchTab('notes')" id="tab-btn-notes" class="pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap">
                        📖 Notes
                    </button>
                    <button type="button" onclick="switchTab('lessons')" id="tab-btn-lessons" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        ✏️ Lessons
                    </button>
                    <button type="button" onclick="switchTab('resources')" id="tab-btn-resources" class="pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap">
                        🔗 Additional Resources <span id="resource-badge-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 font-bold hidden"></span>
                    </button>
                </div>

                <!-- Notes View -->
                <div id="view-notes" class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Overview</h3>
                        <p id="note-overview" class="text-base text-slate-600 bg-pastel-bg p-5 rounded-xl border border-blue-50 leading-relaxed"></p>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Key Steps</h3>
                        <ul id="note-points" class="list-disc list-inside text-base text-slate-600 space-y-2"></ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Worked Example</h3>
                        <div id="note-example" class="p-5 bg-blue-50/50 border border-blue-100 rounded-xl text-center text-lg font-semibold text-pastel-text"></div>
                    </div>
                </div>

<!-- Quizzes View -->
<div id="view-lessons" class="hidden space-y-6">

    <!-- Teacher Feedback -->
    <div id="teacher-feedback-box" class="hidden p-5 rounded-2xl bg-amber-50 border border-amber-200">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-lg">💬</span>
            <h3 class="text-sm font-extrabold text-amber-800">
                Teacher Feedback
            </h3>
        </div>

        <p id="teacher-feedback-text"
           class="text-sm text-amber-900 leading-relaxed"></p>
    </div>

    <div id="quiz-form" class="space-y-6">

    <input type="hidden" id="form-chapter" value="">
    <input type="hidden" id="form-subtopic" value="">

    <!-- Result at the top -->
    <div id="quiz-result-summary"
         class="hidden mb-6 p-6 rounded-2xl bg-white border border-blue-100 shadow-sm text-center space-y-3">
    </div>

    <!-- Questions -->
    <div id="quiz-questions-container" class="space-y-6">
        <!-- Populated dynamically via JS -->
    </div>

    <!-- Submit button at the bottom -->
    <div id="quiz-submit-btn-wrapper" class="flex flex-col pt-4 hidden">
        <!-- Injected via JavaScript below -->
    </div>
</div>

                </div>

                <!-- Additional Resources View -->
                <div id="view-resources" class="hidden space-y-4">
                    <p class="text-base text-slate-500 mb-2">Optional supplementary materials</p>
                    <div id="resources-list" class="space-y-3"></div>
                </div>

                <!-- Chapter Test View -->
<div id="view-chapter-test" class="hidden space-y-6">

    <div id="chapter-test-container"></div>

</div>

            </div>

        </div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
    <script>
        const subtopicData = <?= json_encode($active_chapter['subtopics']) ?>;
      
        const chapterTestUnlocked =
    <?= !empty($active_chapter['chapter_test_unlocked']) ? 'true' : 'false' ?>;

let chapterTestCompleted = 
<?= !empty($active_chapter['chapter_test_completed']) ? 'true' : 'false' ?>;

let chapterTestData = 
<?= json_encode($active_chapter['chapter_test_data'] ?? null) ?>;


const chapterTestQuestions =
    <?= json_encode($active_chapter['chapter_test_questions'] ?? []) ?>;

let chapterTestMode = false;

        let selectedKey = "<?= $first_subtopic_key ?>";
        let activeTab = 'notes';

        function openChapterTest() {

    if (!chapterTestUnlocked) {
        return;
    }

    chapterTestMode = true;

    // Hide Notes / Lessons / Resources tabs
    document.getElementById('module-tabs').classList.add('hidden');

    // Hide all normal views
    document.getElementById('view-notes').classList.add('hidden');
    document.getElementById('view-lessons').classList.add('hidden');
    document.getElementById('view-resources').classList.add('hidden');

    // Show Chapter Test
    document.getElementById('view-chapter-test').classList.remove('hidden');

    renderChapterTest();
}

    function selectSubtopic(key) {

    chapterTestMode = false;

    document.getElementById('module-tabs').classList.remove('hidden');
    document.getElementById('view-chapter-test').classList.add('hidden');

    selectedKey = key;

    document.querySelectorAll('.subtopic-btn').forEach(btn => {
        btn.classList.remove(
            'border-pastel-primary',
            'bg-blue-50/70',
            'shadow-sm'
        );

        btn.classList.add('border-slate-100');
    });

    const formattedId =
        `subtopic-btn-${key.replace('.', '_')}`;

    const activeBtn =
        document.getElementById(formattedId);

    if (activeBtn) {
        activeBtn.classList.remove('border-slate-100');

        activeBtn.classList.add(
            'border-pastel-primary',
            'bg-blue-50/70',
            'shadow-sm'
        );
    }

    renderContent();
}
function renderChapterTest() {

    const container =
        document.getElementById('chapter-test-container');

    if (!chapterTestUnlocked) {

        container.innerHTML = `
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                <div class="text-4xl mb-3">🔒</div>

                <h2 class="text-xl font-extrabold text-pastel-text">
                    Chapter Test Locked
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Complete all lessons in this chapter first.
                </p>
            </div>
        `;

        return;
    }

    // Already completed
    if (chapterTestCompleted) {

        renderChapterTestResult(
            chapterTestData
        );

        return;
    }

    // No questions
    if (!chapterTestQuestions ||
        chapterTestQuestions.length === 0) {

        container.innerHTML = `
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                <h2 class="text-xl font-bold text-pastel-text">
                    No Chapter Test Available
                </h2>

                <p class="text-sm text-slate-500 mt-2">
                    Your teacher has not added Chapter Test questions yet.
                </p>
            </div>
        `;

        return;
    }

    let html = `
        <div class="mb-6">

            <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">
                Chapter Test
            </span>
<h2 class="text-2xl font-extrabold text-pastel-text mt-1">
    Chapter <?= $selected_chap_id ?> Final Test
</h2>

            <p class="text-sm text-slate-500 mt-2">
                Answer all questions. Your result will determine your chapter mastery level.
            </p>

        </div>

        <div id="chapter-test-questions" class="space-y-6">
    `;

    chapterTestQuestions.forEach((q, index) => {

        const qId = q.id;

        html += `
            <div class="p-6 rounded-2xl border border-blue-50 bg-pastel-bg space-y-4">

                <div>

                    <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">
                        Question ${index + 1} of ${chapterTestQuestions.length}
                    </span>

                    <h3 class="text-base font-bold text-pastel-text mt-1">
                        ${q.question}
                    </h3>

                </div>

                <div class="space-y-2.5">

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100 bg-white hover:border-pastel-primary cursor-pointer">
                        <input
                            type="radio"
                            name="chapter_answers[${qId}]"
                            value="a"
                            class="chapter-test-option"
                        >
                        <span class="text-sm font-medium">
                            A. ${q.option_a}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100 bg-white hover:border-pastel-primary cursor-pointer">
                        <input
                            type="radio"
                            name="chapter_answers[${qId}]"
                            value="b"
                            class="chapter-test-option"
                        >
                        <span class="text-sm font-medium">
                            B. ${q.option_b}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100 bg-white hover:border-pastel-primary cursor-pointer">
                        <input
                            type="radio"
                            name="chapter_answers[${qId}]"
                            value="c"
                            class="chapter-test-option"
                        >
                        <span class="text-sm font-medium">
                            C. ${q.option_c}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100 bg-white hover:border-pastel-primary cursor-pointer">
                        <input
                            type="radio"
                            name="chapter_answers[${qId}]"
                            value="d"
                            class="chapter-test-option"
                        >
                        <span class="text-sm font-medium">
                            D. ${q.option_d}
                        </span>
                    </label>

                </div>

            </div>
        `;
    });

    html += `
        </div>

        <div class="pt-4">

            <button
                type="button"
                id="submit-chapter-test-btn"
                onclick="submitChapterTest()"
                class="w-full bg-pastel-primary text-white py-3 rounded-xl font-bold hover:opacity-90 transition"
            >
                Submit Chapter Test
            </button>

        </div>
    `;

    container.innerHTML = html;
}

function submitChapterTest() {

    const formData = new FormData();

    formData.append(
        'action',
        'submit_chapter_test'
    );

    formData.append(
        'chapter_num',
        <?= $selected_chap_id ?>
    );

    formData.append(
        'chapter_name',
        <?= json_encode($active_chapter['title']) ?>
    );


    // Collect all answers
    chapterTestQuestions.forEach(q => {

        const qId = q.id;

        const selected =
            document.querySelector(
                `input[name="chapter_answers[${qId}]"]:checked`
            );

        if (selected) {

            formData.append(
                `answers[${qId}]`,
                selected.value
            );
        }
    });


    const btn =
        document.getElementById(
            'submit-chapter-test-btn'
        );

    if (!btn) {
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Submitting...';


    fetch(
        'module.php?chap=<?= $selected_chap_id ?>',
        {
            method: 'POST',
            body: formData
        }
    )
    .then(response => response.json())
    .then(res => {

        if (res.status === 'success') {

            // Important: these are LET, not CONST
            chapterTestCompleted = true;

            chapterTestData = {
                score:
                    res.correct_count +
                    '/' +
                    res.total_questions,

                percentage:
                    res.score,

                level:
                    res.level,

                level_name:
                    res.level_name,

                answers:
                    res.answers || {}
            };


            renderChapterTestResult(
                chapterTestData
            );


            // Update left Chapter Test button
            const testBtn =
                document.getElementById(
                    'chapter-test-btn'
                );

            if (testBtn) {

                testBtn.classList.remove(
                    'border-emerald-200',
                    'bg-emerald-50'
                );

                testBtn.classList.add(
                    'border-emerald-200',
                    'bg-emerald-50'
                );

                testBtn.innerHTML = `
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-700">
                            Completed
                        </span>
                    </div>

                    <h3 class="font-bold text-base text-pastel-text">
                        📝 Chapter Test
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Test completed. View your result.
                    </p>
                `;
            }

        } else {

            alert(
                res.message ||
                'Error submitting Chapter Test.'
            );

            btn.disabled = false;
            btn.innerText =
                'Submit Chapter Test';
        }

    })
    .catch(error => {

        console.error(error);

        alert(
            'An error occurred during submission.'
        );

        btn.disabled = false;
        btn.innerText =
            'Submit Chapter Test';
    });
}


function renderChapterTestResult(result) {

    const container =
        document.getElementById(
            'chapter-test-container'
        );

    const scoreParts =
        String(result.score || '0/0')
            .split('/')
            .map(Number);

    const correct =
        scoreParts[0] || 0;

    const total =
        scoreParts[1] || 0;

    const percentage =
        result.percentage !== undefined
            ? result.percentage
            : (
                total > 0
                    ? Math.round((correct / total) * 100)
                    : 0
            );

    const level =
        result.level_name ||
        (
            percentage >= 80
                ? 'Master'
                : percentage >= 50
                    ? 'Intermediate'
                    : 'Beginner'
        );

    container.innerHTML = `

        <div class="mb-6">

            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">
                Chapter Test Completed
            </span>

<h2 class="text-2xl font-extrabold text-pastel-text mt-1">
    <?= htmlspecialchars($active_chapter['title']) ?> — Final Test
</h2>


        </div>

        <!-- Result -->
        <div class="p-6 rounded-2xl bg-white border border-blue-100 shadow-sm text-center">

            <div class="text-4xl font-black text-pastel-primary mb-2">
                ${percentage}%
            </div>

            <p class="text-sm text-slate-500">
                ${correct} / ${total} correct
            </p>

            <div class="mt-5 inline-flex px-4 py-2 rounded-xl
                ${
                    level === 'Master'
                        ? 'bg-emerald-100 text-emerald-700'
                        : level === 'Intermediate'
                            ? 'bg-orange-100 text-orange-700'
                            : 'bg-red-100 text-red-700'
                }
                font-bold">

                Level: ${level}

            </div>

        </div>

    `;
}


        function switchTab(tab) {
            activeTab = tab;
            const notesBtn = document.getElementById('tab-btn-notes');
            const lessonsBtn = document.getElementById('tab-btn-lessons');
            const resourcesBtn = document.getElementById('tab-btn-resources');

            [notesBtn, lessonsBtn, resourcesBtn].forEach(btn => {
                btn.className = "pb-3 text-base font-bold border-b-2 border-transparent text-slate-400 hover:text-pastel-text transition whitespace-nowrap";
            });

            ['view-notes', 'view-lessons', 'view-resources'].forEach(viewId => {
                const el = document.getElementById(viewId);
                el.classList.add('hidden');
                el.classList.remove('block');
            });

            if (tab === 'notes') {
                notesBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-notes').classList.add('block');
                document.getElementById('view-notes').classList.remove('hidden');
            } else if (tab === 'lessons') {
                lessonsBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-lessons').classList.add('block');
                document.getElementById('view-lessons').classList.remove('hidden');
            } else if (tab === 'resources') {
                resourcesBtn.className = "pb-3 text-base font-bold border-b-2 border-pastel-primary text-pastel-primary transition whitespace-nowrap";
                document.getElementById('view-resources').classList.add('block');
                document.getElementById('view-resources').classList.remove('hidden');
            }
        }
 
        function renderContent() {
            const data = subtopicData[selectedKey];
            if (!data) return;
            
            // Render Teacher Feedback
const feedbackBox = document.getElementById('teacher-feedback-box');
const feedbackText = document.getElementById('teacher-feedback-text');

if (data.teacher_feedback && data.teacher_feedback.trim() !== '') {
    feedbackText.innerText = data.teacher_feedback;
    feedbackBox.classList.remove('hidden');
} else {
    feedbackText.innerText = '';
    feedbackBox.classList.add('hidden');
}

            document.getElementById('note-overview').innerText = data.notes.overview;
            
            const pointsList = document.getElementById('note-points');
            pointsList.innerHTML = '';
            data.notes.points.forEach(point => {
                const li = document.createElement('li');
                li.innerHTML = point;
                pointsList.appendChild(li);
            });
            document.getElementById('note-example').innerHTML = data.notes.example;

            // Set hidden form inputs for reference
            document.getElementById('form-chapter').value = data.chapter_num || <?= $selected_chap_id ?>;
            document.getElementById('form-subtopic').value = data.subtopic_num || selectedKey;

            const questionsContainer = document.getElementById('quiz-questions-container');
            const submitBtnWrapper = document.getElementById('quiz-submit-btn-wrapper');
            questionsContainer.innerHTML = '';

            if (data.questions && data.questions.length > 0) {
                submitBtnWrapper.classList.remove('hidden');
                
                const isCompleted = data.is_completed;
                const savedData = data.saved_data || {};
                const savedAnswers = savedData.answers || {};

                if (!isCompleted) {
submitBtnWrapper.innerHTML = `
    <button type="button"
            id="submit-quiz-btn"
            class="w-full bg-pastel-primary text-white py-3 rounded-xl font-bold hover:opacity-90 transition">
        Submit Lesson Answers 
    </button>
`;

               } else {

    let correctCount = 0;
    let totalQ = data.questions.length;

    data.questions.forEach((q) => {

        const qId = q.id;

        if (
            savedAnswers[qId] &&
            savedAnswers[qId].is_correct
        ) {
            correctCount++;
        }
    });

    let scorePercent = totalQ > 0
        ? Math.round((correctCount / totalQ) * 100)
        : 0;

    submitBtnWrapper.innerHTML = '';

    submitBtnWrapper.classList.add('hidden');

    const resultBox = document.getElementById('quiz-result-summary');

    if (resultBox) {

        resultBox.classList.remove('hidden');

        resultBox.innerHTML = `
            <h3 class="text-lg font-extrabold text-pastel-text">
                Lesson Results
            </h3>

            <div class="flex justify-center gap-6 my-3 text-sm font-semibold">
                <span class="text-green-600">
                    ✅ Correct: ${correctCount}
                </span>

                <span class="text-red-500">
                    ❌ Incorrect: ${totalQ - correctCount}
                </span>
            </div>

            <p class="text-sm text-slate-600">
                Your Score:
                <strong class="text-pastel-primary text-base">
                    ${scorePercent}%
                </strong>

                (${correctCount}/${totalQ})
            </p>
        `;
    }
}
data.questions.forEach((q, index) => {

    const qId = q.id || (index + 1);
    const qText = q.question || q.question_text || 'Question text missing';

    const optA = q.option_a || q.opt_a || '';
    const optB = q.option_b || q.opt_b || '';
    const optC = q.option_c || q.opt_c || '';
    const optD = q.option_d || q.opt_d || '';

    const correctOpt = (
        q.correct_option ||
        q.answer ||
        'a'
    ).toLowerCase();

    const explanation = q.explanation ||
        'No explanation provided for this question.';

    const userRecord = savedAnswers[qId] || {};
    const userVal = (userRecord.user_answer || '').toLowerCase();

    const questionDiv = document.createElement('div');

    questionDiv.className =
        "p-6 rounded-2xl border border-blue-50 bg-pastel-bg space-y-4 quiz-item";

    // =====================================================
    // COMPLETED LESSON
    // =====================================================

    if (isCompleted) {

        const savedCorrectAnswer =
            (userRecord.correct_answer || correctOpt).toLowerCase();

        const isCorrect = !!userRecord.is_correct;

        const userAnswerText =
            userVal === 'a' ? optA :
            userVal === 'b' ? optB :
            userVal === 'c' ? optC :
            userVal === 'd' ? optD :
            'No answer';

        const correctAnswerText =
            savedCorrectAnswer === 'a' ? optA :
            savedCorrectAnswer === 'b' ? optB :
            savedCorrectAnswer === 'c' ? optC :
            savedCorrectAnswer === 'd' ? optD :
            'Unknown';

        questionDiv.innerHTML = `
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider mb-1 block">
                    Question ${index + 1} of ${data.questions.length}
                </span>

                <h3 class="text-base font-bold text-pastel-text">
                    ${qText}
                </h3>
            </div>

            <!-- Student Answer -->
            <div class="p-4 rounded-xl border ${
                isCorrect
                    ? 'border-green-200 bg-green-50'
                    : 'border-red-200 bg-red-50'
            }">

                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold uppercase tracking-wide ${
                        isCorrect
                            ? 'text-green-700'
                            : 'text-red-700'
                    }">
                        Your Answer
                    </span>

                    <span class="text-xs font-bold ${
                        isCorrect
                            ? 'text-green-700'
                            : 'text-red-700'
                    }">
                        ${isCorrect ? '✓ Correct' : '✗ Incorrect'}
                    </span>
                </div>

                <p class="text-sm font-semibold ${
                    isCorrect
                        ? 'text-green-800'
                        : 'text-red-800'
                }">
                    ${userVal
                        ? userVal.toUpperCase() + '. ' + userAnswerText
                        : 'No answer'}
                </p>

            </div>

            <!-- Correct Answer -->
            ${
                !isCorrect
                ? `
                    <div class="p-4 rounded-xl border border-blue-200 bg-blue-50">
                        <span class="text-xs font-bold text-blue-700 uppercase tracking-wide">
                            Correct Answer
                        </span>

                        <p class="text-sm font-semibold text-blue-800 mt-1">
                            ${savedCorrectAnswer.toUpperCase()}. ${correctAnswerText}
                        </p>
                    </div>
                `
                : ''
            }

            <!-- Explanation -->
            <div class="p-4 rounded-xl bg-white border border-blue-200 text-sm text-slate-700">
                <span class="font-bold block mb-1 text-pastel-text">
                    💡 Explanation
                </span>

                <p class="leading-relaxed">
                    ${userRecord.explanation || explanation}
                </p>
            </div>
        `;

    }

    // =====================================================
    // NOT COMPLETED
    // =====================================================

    else {

        questionDiv.innerHTML = `
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider mb-1 block">
                    Question ${index + 1} of ${data.questions.length}
                </span>

                <h3 class="text-base font-bold text-pastel-text">
                    ${qText}
                </h3>
            </div>

            <div class="space-y-2.5 options-group">

                ${
                    optA
                    ? `
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition">
                            <input
                                type="radio"
                                name="answers[${qId}]"
                                value="a"
                                class="text-pastel-primary focus:ring-pastel-primary option-input"
                            >
                            <span class="text-sm text-pastel-text font-medium">
                                A. ${optA}
                            </span>
                        </label>
                    `
                    : ''
                }

                ${
                    optB
                    ? `
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition">
                            <input
                                type="radio"
                                name="answers[${qId}]"
                                value="b"
                                class="text-pastel-primary focus:ring-pastel-primary option-input"
                            >
                            <span class="text-sm text-pastel-text font-medium">
                                B. ${optB}
                            </span>
                        </label>
                    `
                    : ''
                }

                ${
                    optC
                    ? `
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition">
                            <input
                                type="radio"
                                name="answers[${qId}]"
                                value="c"
                                class="text-pastel-primary focus:ring-pastel-primary option-input"
                            >
                            <span class="text-sm text-pastel-text font-medium">
                                C. ${optC}
                            </span>
                        </label>
                    `
                    : ''
                }

                ${
                    optD
                    ? `
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-blue-100/60 bg-white hover:border-pastel-primary cursor-pointer transition">
                            <input
                                type="radio"
                                name="answers[${qId}]"
                                value="d"
                                class="text-pastel-primary focus:ring-pastel-primary option-input"
                            >
                            <span class="text-sm text-pastel-text font-medium">
                                D. ${optD}
                            </span>
                        </label>
                    `
                    : ''
                }

            </div>
        `;
    }

    questionsContainer.appendChild(questionDiv);
});
     
                // Attach submit event listener if not completed
                const submitBtn = document.getElementById('submit-quiz-btn');
                if (submitBtn) {
                    submitBtn.addEventListener('click', () => {
                        let formData = new FormData();
                        formData.append('action', 'submit_quiz');
                        formData.append('chapter_num', data.chapter_num || <?= $selected_chap_id ?>);
                        formData.append('subtopic_num', data.subtopic_num || selectedKey);
                        formData.append('chapter_name', data.subtopic_name_val || '<?= addslashes($active_chapter['title']) ?>');

                        data.questions.forEach((q, index) => {
                            const qId = q.id || (index + 1);
                            const selectedInput = document.querySelector(`input[name="answers[${qId}]"]:checked`);
                            if (selectedInput) {
                                formData.append(`answers[${qId}]`, selectedInput.value);
                            }
                        });

                        submitBtn.disabled = true;
                        submitBtn.innerText = 'Submitting...';

                        fetch('module.php?chap=<?= $selected_chap_id ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
.then(res => {
if (res.status === 'success') {

    data.is_completed = true;

    data.saved_data = {
        score: res.score,
        answers: res.answers || {}
    };

    renderContent();

} else {
    alert(res.message || 'Error submitting lesson.');

    submitBtn.disabled = false;
    submitBtn.innerText = 'Submit Lesson Answers';
}
                        })
                        .catch(err => {
                            console.error(err);
                            alert('An error occurred during submission.');
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Submit Quiz Answers →';
                        });
                    });
                }

            } else {
                submitBtnWrapper.classList.add('hidden');
                questionsContainer.innerHTML = '<p class="text-sm text-slate-500 py-4">No quiz questions available for this subtopic yet.</p>';
            }

            // Render Additional Resources List & Badge Count
            const resourcesList = document.getElementById('resources-list');
            const resourceBadge = document.getElementById('resource-badge-count');
            resourcesList.innerHTML = '';

            if (data.additional_resources && data.additional_resources.length > 0) {
                resourceBadge.innerText = data.additional_resources.length;
                resourceBadge.classList.remove('hidden');

                data.additional_resources.forEach(res => {
                    const resAnchor = document.createElement('a');
                    resAnchor.className = "block p-4 rounded-xl border border-blue-50 bg-pastel-bg hover:border-pastel-primary transition flex justify-between items-center";
                    resAnchor.href = res.url;
                    resAnchor.target = "_blank";
                    resAnchor.innerHTML = `
                        <div>
                            <span class="text-xs font-bold text-pastel-primary uppercase tracking-wide">${res.type}</span>
                            <p class="font-semibold text-sm text-pastel-text mt-0.5">${res.title}</p>
                        </div>
                        <span class="text-pastel-primary text-sm font-bold">↗</span>
                    `;
                    resourcesList.appendChild(resAnchor);
                });
            } else {
                resourceBadge.classList.add('hidden');
                resourcesList.innerHTML = '<p class="text-sm text-slate-500">No additional remedial resources assigned for this subtopic.</p>';
            }
        }

        renderContent();
    </script>
</body>
</html>