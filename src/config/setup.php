<?php
require_once __DIR__ . '/db.php';

$status = 'success';
$error_msg = '';

try {
    $pdo->beginTransaction();

    // Enable foreign key constraints in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");

    $pdo->exec("DROP TABLE IF EXISTS discussion_replies;");
    $pdo->exec("DROP TABLE IF EXISTS discussion_posts;");
    $pdo->exec("DROP TABLE IF EXISTS student_quiz_answers;");
    $pdo->exec("DROP TABLE IF EXISTS chapter_quizzes;");
    $pdo->exec("DROP TABLE IF EXISTS chapter_materials;");
    $pdo->exec("DROP TABLE IF EXISTS classroom_chapters;");
    $pdo->exec("DROP TABLE IF EXISTS student_progress;");
    $pdo->exec("DROP TABLE IF EXISTS students;");
    $pdo->exec("DROP TABLE IF EXISTS teachers;");
    $pdo->exec("DROP TABLE IF EXISTS classrooms;");
    $pdo->exec("DROP TABLE IF EXISTS announcements;");

    // -------------------------------------------------------------------------
    // Create Schema
    // -------------------------------------------------------------------------
    $pdo->exec("CREATE TABLE teachers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        department TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE classrooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        teacher_id INTEGER,
        name TEXT NOT NULL,
        avg_mastery TEXT NOT NULL,
        FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
    );");

    $pdo->exec("CREATE TABLE students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        classroom_id INTEGER,
        name TEXT NOT NULL,
        status TEXT NOT NULL,
        score INTEGER DEFAULT 0,
        role TEXT DEFAULT 'student',
        FOREIGN KEY(classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL
    );");

    $pdo->exec("CREATE TABLE student_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        chapter_name TEXT NOT NULL,
        level INTEGER NOT NULL,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE classroom_chapters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        classroom_id INTEGER NOT NULL,
        chapter_name TEXT NOT NULL,
        is_unlocked INTEGER DEFAULT 0,
        FOREIGN KEY(classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE chapter_materials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chapter_name TEXT NOT NULL,
        subtopic_name TEXT DEFAULT NULL,
        title TEXT NOT NULL,
        file_path TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

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

    $pdo->exec("CREATE TABLE announcements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE discussion_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        image_url TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE discussion_replies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        student_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        image_url TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES discussion_posts(id) ON DELETE CASCADE,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE student_quiz_answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        quiz_id INTEGER NOT NULL,
        answer_status TEXT NOT NULL,
        score INTEGER DEFAULT 0,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY(quiz_id) REFERENCES chapter_quizzes(id) ON DELETE CASCADE
    );");

    // -------------------------------------------------------------------------
    // Seed Data
    // -------------------------------------------------------------------------
    
    $pdo->exec("INSERT INTO teachers (id, name, email, department) VALUES 
        (1, 'Teacher Sarah', 'sarah@eduhunt.com', 'Mathematics Department');");

    $pdo->exec("INSERT INTO classrooms (id, teacher_id, name, avg_mastery) VALUES 
        (1, 1, 'Grade 5 Mathematics - Section A', '68%'),
        (2, 1, 'Grade 5 Mathematics - Section B', '54%'),
        (3, 1, 'Grade 6 Remedial Math', '79%');");

    $all_students = [
        [1, 'Amina Yusuf', 'Mastering', [3, 2, 2]],
        [1, 'Bao Nguyen', 'On Track', [3, 3, 3]],
        [1, 'Carlos Mendez', 'Struggling', [1, 1, 1]],
    ];

    $chapters = ["Fractions (Ch 1)", "Decimals (Ch 2)", "Percentages (Ch 3)"];

    $stmt_student = $pdo->prepare("INSERT INTO students (classroom_id, name, status, score) VALUES (?, ?, ?, ?)");
    $stmt_progress = $pdo->prepare("INSERT INTO student_progress (student_id, chapter_name, level) VALUES (?, ?, ?)");

    foreach ($all_students as $s) {
        $stmt_student->execute([$s[0], $s[1], $s[2], 0]);
        $student_id = $pdo->lastInsertId();

        foreach ($s[3] as $index => $level) {
            $stmt_progress->execute([$student_id, $chapters[$index], $level]);
        }
    }

    $stmt_cc = $pdo->prepare("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES (?, ?, ?)");
    foreach ([1, 2, 3] as $cid) {
        $stmt_cc->execute([$cid, "Fractions (Ch 1)", 1]);
        $stmt_cc->execute([$cid, "Decimals (Ch 2)", 1]);
        $stmt_cc->execute([$cid, "Percentages (Ch 3)", 1]);
    }

    $sample_materials = [
        ["Fractions (Ch 1)", NULL, "Fractions Introduction Notes", "uploads/Fractions_Introduction_Notes.pdf"],
        ["Decimals (Ch 2)", NULL, "Decimals Place Value Chart", "uploads/Decimals_Place_Value_Chart.pdf"],
        ["Percentages (Ch 3)", NULL, "Percentage Basics Workbook", "uploads/Percentage_Basics_Workbook.pdf"]
    ];

    $stmt_mat = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, ?, ?, ?)");
    foreach ($sample_materials as $mat) {
        $stmt_mat->execute([$mat[0], $mat[1], $mat[2], $mat[3]]);
    }

    // Reduced & Streamlined Quiz Data
    $sample_quizzes = [
        // Fractions (Ch 1)
        ["Fractions (Ch 1)", NULL, "What is 1/2 + 1/4?", "1/6", "3/4", "2/6", "2/4", "B"],
        ["Fractions (Ch 1)", "Subtopic 1.1: Like Fractions", "Which fraction is equivalent to 2/4?", "1/3", "1/4", "1/2", "3/5", "C"],

        // Decimals (Ch 2)
        ["Decimals (Ch 2)", NULL, "What is 0.4 converted to a fraction in simplest form?", "2/5", "4/10", "1/4", "4/5", "A"],
        ["Decimals (Ch 2)", "Subtopic 2.1: Tenths", "What is the value of the digit 5 in 3.45?", "5 ones", "5 tenths", "5 hundredths", "5 tens", "C"],

        // Percentages (Ch 3)
        ["Percentages (Ch 3)", NULL, "What is 50% expressed as a decimal?", "0.05", "0.5", "5.0", "0.55", "B"],
        ["Percentages (Ch 3)", "Subtopic 3.1: Basics", "What is 25% as a fraction in lowest terms?", "1/4", "1/2", "3/4", "1/5", "A"]
    ];

    $stmt_q = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sample_quizzes as $q) {
        $stmt_q->execute([$q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7]]);
    }

    $pdo->exec("INSERT INTO discussion_posts (student_id, title, content) VALUES 
        (1, 'How do I simplify 12/16 to its lowest terms?', 'I know I need to divide numerator and denominator by the highest common factor, but I am stuck.');");

    $pdo->exec("INSERT INTO discussion_replies (post_id, student_id, content) VALUES 
        (1, 2, 'Divide both top and bottom numbers by 4 to get 3/4!');");

    $pdo->exec("INSERT INTO announcements (title, content, is_active) VALUES 
        ('📢 Additional Math Support Class', 'Teacher Sarah has added an extra online tutoring session this Thursday at 3:00 PM for review.', 1);");

    $sample_quiz_answers = [
        [1, 1, 'Correct', 10],
        [1, 2, 'Attempted', 5],
        [2, 1, 'Correct', 10],
        [2, 3, 'Correct', 10],
        [3, 1, 'Attempted', 5]
    ];

    $stmt_sqa = $pdo->prepare("INSERT INTO student_quiz_answers (student_id, quiz_id, answer_status, score) VALUES (?, ?, ?, ?)");
    foreach ($sample_quiz_answers as $ans) {
        $stmt_sqa->execute([$ans[0], $ans[1], $ans[2], $ans[3]]);
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $status = 'error';
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 max-w-md w-full rounded-2xl shadow-sm border border-slate-100 text-center">
        <?php if ($status === 'success'): ?>
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✓</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Initialized Successfully!</h1>
            <p class="text-xs text-slate-500">Streamlined quizzes and materials have been seeded successfully.</p>
        <?php else: ?>
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✕</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Update Failed</h1>
            <p class="text-xs text-rose-500 font-mono bg-rose-50 p-2 rounded text-left break-all mt-2"><?= htmlspecialchars($error_msg) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>