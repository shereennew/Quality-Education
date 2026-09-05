<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $classroom_id = $_POST['classroom_id'] ?? 1;
    $feedback = trim($_POST['teacher_feedback'] ?? '');

    // Ensure database table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        quiz_id INT DEFAULT 0,
        teacher_feedback TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if ($student_id && !empty($feedback)) {
        $stmt = $pdo->prepare("INSERT INTO quiz_feedback (student_id, teacher_feedback) VALUES (?, ?)");
        $stmt->execute([$student_id, $feedback]);
    }

    header("Location: ../teacher/classroom.php?id=" . $class_id);
    exit;
}