<?php
require_once 'db.php';

// Drop tables if they exist
$pdo->exec("DROP TABLE IF EXISTS chapter_quizzes;");
$pdo->exec("DROP TABLE IF EXISTS chapter_materials;");
$pdo->exec("DROP TABLE IF EXISTS classroom_chapters;");
$pdo->exec("DROP TABLE IF EXISTS student_progress;");
$pdo->exec("DROP TABLE IF EXISTS students;");
$pdo->exec("DROP TABLE IF EXISTS classrooms;");

// Create tables with SQLite syntax[cite: 4]
$pdo->exec("CREATE TABLE classrooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    avg_mastery TEXT NOT NULL
);");

$pdo->exec("CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    classroom_id INTEGER,
    name TEXT NOT NULL,
    status TEXT NOT NULL,
    FOREIGN KEY(classroom_id) REFERENCES classrooms(id)
);");

$pdo->exec("CREATE TABLE student_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER,
    chapter_name TEXT NOT NULL,
    level INTEGER NOT NULL,
    FOREIGN KEY(student_id) REFERENCES students(id)
);");

$pdo->exec("CREATE TABLE classroom_chapters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    classroom_id INTEGER NOT NULL,
    chapter_name TEXT NOT NULL,
    is_unlocked INTEGER DEFAULT 0,
    FOREIGN KEY(classroom_id) REFERENCES classrooms(id)
);");

$pdo->exec("CREATE TABLE chapter_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    file_name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

$pdo->exec("CREATE TABLE chapter_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option TEXT NOT NULL
);");

// Seed Classrooms[cite: 4]
$pdo->exec("INSERT INTO classrooms (id, name, avg_mastery) VALUES 
    (1, 'Grade 5 Mathematics - Section A', '68%'),
    (2, 'Grade 5 Mathematics - Section B', '54%'),
    (3, 'Grade 6 Remedial Math', '79%');");

$students_c1 = [
    [1, 'Amina Yusuf', 'Advancing', [3, 2, 1]],
    [1, 'Bao Nguyen', 'Mastering', [3, 3, 2]],
    [1, 'Carlos Mendez', 'Struggling', [1, 1, 0]],
    [1, 'Deepa Patel', 'On Track', [2, 2, 1]],
    [1, 'Elias Thorne', 'Needs Help', [1, 0, 0]],
    [1, 'Fatima Al-Fassi', 'Mastering', [3, 3, 3]]
];

$chapters = ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];

foreach ($students_c1 as $s) {
    $stmt = $pdo->prepare("INSERT INTO students (classroom_id, name, status) VALUES (?, ?, ?)");
    $stmt->execute([$s[0], $s[1], $s[2]]);
    $student_id = $pdo->lastInsertId();

    foreach ($s[3] as $index => $level) {
        $stmt_p = $pdo->prepare("INSERT INTO student_progress (student_id, chapter_name, level) VALUES (?, ?, ?)");
        $stmt_p->execute([$student_id, $chapters[$index], $level]);
    }
}

// Seed initial global chapter unlocks (Chapter 1 unlocked by default for classrooms)
foreach ([1, 2, 3] as $cid) {
    $stmt_cc = $pdo->prepare("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES (?, ?, ?)");
    $stmt_cc->execute([$cid, "Fractions (Ch 1)", 1]);
    $stmt_cc->execute([$cid, "Decimals (Ch 2)", 0]);
    $stmt_cc->execute([$cid, "Percentages (Ch 3)", 0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQLite Setup Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
</html>