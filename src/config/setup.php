<?php
// setup.php
require_once 'db.php';

// Drop tables if they exist (MySQL syntax)
$pdo->exec("DROP TABLE IF EXISTS student_progress;");
$pdo->exec("DROP TABLE IF EXISTS students;");
$pdo->exec("DROP TABLE IF EXISTS classrooms;");

// Create tables with MySQL AUTO_INCREMENT syntax
$pdo->exec("CREATE TABLE classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    avg_mastery VARCHAR(50) NOT NULL
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY(classroom_id) REFERENCES classrooms(id)
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    chapter_name VARCHAR(255) NOT NULL,
    level INT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES students(id)
) ENGINE=InnoDB;");

// Seed Classrooms
$pdo->exec("INSERT INTO classrooms (id, name, avg_mastery) VALUES 
    (1, 'Grade 5 Mathematics - Section A', '68%'),
    (2, 'Grade 5 Mathematics - Section B', '54%'),
    (3, 'Grade 6 Remedial Math', '79%');");

// Seed Students and Progress for Classroom 1
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-sm border text-center">
        <h1 class="text-xl font-bold text-slate-800 mb-2">MySQL Database Setup Successful!</h1>
        <p class="text-slate-500 text-sm mb-6">Tables created and seeded via PDO.</p>
        <a href="teacher_home.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-lg transition">Go to Teacher Dashboard &rarr;</a>
    </div>
</body>
</html>