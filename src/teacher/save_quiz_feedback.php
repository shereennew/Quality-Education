<?php

session_start();

require_once __DIR__ . '/../config/db.php';

$classroom_id = isset($_POST['classroom_id'])
    ? intval($_POST['classroom_id'])
    : 0;

$student_id = isset($_POST['student_id'])
    ? intval($_POST['student_id'])
    : 0;

$chapter_name = trim($_POST['chapter_name'] ?? '');
$subtopic_name = trim($_POST['subtopic_name'] ?? '');
$comment = trim($_POST['teacher_feedback'] ?? '');

if (
    $classroom_id <= 0 ||
    $student_id <= 0 ||
    $chapter_name === '' ||
    $subtopic_name === '' ||
    $comment === ''
) {
    die('Invalid feedback data.');
}

$stmt = $pdo->prepare("
    INSERT INTO teacher_quiz_feedback
    (teacher_id, student_id, chapter_name, subtopic_name, comment)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    1,
    $student_id,
    $chapter_name,
    $subtopic_name,
    $comment
]);

header("Location: ../teacher/classroom.php?id=" . $classroom_id);
exit;