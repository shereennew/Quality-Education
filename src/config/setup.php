<?php
require_once 'db.php';

// Drop tables if they exist
$pdo->exec("DROP TABLE IF EXISTS chapter_quizzes;");
$pdo->exec("DROP TABLE IF EXISTS chapter_materials;");
$pdo->exec("DROP TABLE IF EXISTS classroom_chapters;");
$pdo->exec("DROP TABLE IF EXISTS student_progress;");
$pdo->exec("DROP TABLE IF EXISTS students;");
$pdo->exec("DROP TABLE IF EXISTS classrooms;");

// Create tables with SQLite syntax (including subtopic support)
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
    score INTEGER DEFAULT 0,
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

// Added subtopic_name column to support topic and subtopic levels
$pdo->exec("CREATE TABLE chapter_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    subtopic_name TEXT DEFAULT NULL,
    title TEXT NOT NULL,
    file_path TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// Added subtopic_name column to support topic and subtopic quizzes
$pdo->exec("CREATE TABLE chapter_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_name TEXT NOT NULL,
    subtopic_name TEXT DEFAULT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option TEXT NOT NULL
);");

// Seed Classrooms[cite: 2]
$pdo->exec("INSERT INTO classrooms (id, name, avg_mastery) VALUES 
    (1, 'Grade 5 Mathematics - Section A', '68%'),
    (2, 'Grade 5 Mathematics - Section B', '54%'),
    (3, 'Grade 6 Remedial Math', '79%');");

// Ensure student_quiz_answers table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS student_quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    answer_status VARCHAR(50) DEFAULT 'Pending',
    score INT DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS quiz_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    teacher_feedback TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Expanded student seeding
$all_students = [
    // Classroom 1
    [1, 'Amina Yusuf', 'Advancing', 92, [3, 2, 1]],
    [1, 'Bao Nguyen', 'Mastering', 95, [3, 3, 2]],
    [1, 'Carlos Mendez', 'Struggling', 45, [1, 1, 0]],
    [1, 'Deepa Patel', 'On Track', 78, [2, 2, 1]],
    [1, 'Elias Thorne', 'Needs Help', 30, [1, 0, 0]],
    [1, 'Fatima Al-Fassi', 'Mastering', 98, [3, 3, 3]],
    
    // Classroom 2
    [2, 'Gabe Logan', 'Struggling', 50, [1, 0, 0]],
    [2, 'Hannah Abbott', 'On Track', 74, [2, 1, 1]],
    [2, 'Ian Malcolm', 'Mastering', 89, [3, 2, 2]],
    
    // Classroom 3
    [3, 'Julia Roberts', 'Mastering', 96, [3, 3, 3]],
    [3, 'Kevin Hart', 'Advancing', 85, [3, 2, 2]]
];

$chapters = ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];

foreach ($all_students as $s) {
    $stmt = $pdo->prepare("INSERT INTO students (classroom_id, name, status, score) VALUES (?, ?, ?, ?)");
    $stmt->execute([$s[0], $s[1], $s[2], $s[3]]);
    $student_id = $pdo->lastInsertId();

    foreach ($s[4] as $index => $level) {
        $stmt_p = $pdo->prepare("INSERT INTO student_progress (student_id, chapter_name, level) VALUES (?, ?, ?)");
        $stmt_p->execute([$student_id, $chapters[$index], $level]);
    }
}

// Seed initial global chapter unlocks for all classrooms[cite: 2]
foreach ([1, 2, 3] as $cid) {
    $stmt_cc = $pdo->prepare("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES (?, ?, ?)");
    $stmt_cc->execute([$cid, "Fractions (Ch 1)", 1]);
    $stmt_cc->execute([$cid, "Decimals (Ch 2)", 0]);
    $stmt_cc->execute([$cid, "Percentages (Ch 3)", 0]);
}

// Seed sample chapter materials with topic and subtopic levels
$sample_materials = [
    ["Fractions (Ch 1)", NULL, "Fractions Introduction Notes", "uploads/Fractions_Introduction_Notes.pdf"],
    ["Fractions (Ch 1)", "Subtopic 1.1: Like Fractions", "Adding Like Fractions Guide", "uploads/Adding_Like_Fractions_Guide.pdf"],
    ["Decimals (Ch 2)", NULL, "Decimals Place Value Chart", "uploads/Decimals_Place_Value_Chart.pdf"],
    ["Percentages (Ch 3)", NULL, "Percentage Basics Workbook", "uploads/Percentage_Basics_Workbook.pdf"]
];

foreach ($sample_materials as $mat) {
    $stmt_mat = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, ?, ?, ?)");
    $stmt_mat->execute([$mat[0], $mat[1], $mat[2], $mat[3]]);
}

// Seed sample chapter quizzes with topic and subtopic levels
$sample_quizzes = [
    [
        "Fractions (Ch 1)", 
        NULL,
        "What is 1/2 + 1/4?", 
        "1/6", 
        "3/4", 
        "2/6", 
        "2/4", 
        "B"
    ],
    [
        "Fractions (Ch 1)", 
        "Subtopic 1.1: Like Fractions",
        "Which fraction is equivalent to 2/4?", 
        "1/3", 
        "1/4", 
        "1/2", 
        "3/5", 
        "C"
    ],
    [
        "Decimals (Ch 2)", 
        NULL,
        "What is the value of the digit 5 in 3.45?", 
        "5 ones", 
        "5 tenths", 
        "5 hundredths", 
        "5 tens", 
        "C"
    ],
    [
        "Percentages (Ch 3)", 
        NULL,
        "What is 50% expressed as a decimal?", 
        "0.05", 
        "0.5", 
        "5.0", 
        "0.55", 
        "B"
    ]
];

foreach ($sample_quizzes as $q) {
    $stmt_q = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_q->execute([$q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7]]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQLite Setup Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 text-center">
        <h1 class="text-lg font-bold text-slate-800 mb-2">Database Setup Successful!</h1>
        <p class="text-xs text-slate-500">All tables have been updated with subtopic support, materials, and quizzes.</p>
    </div>
</body>
</html>