<?php
require_once __DIR__ . '/db.php';

$status = 'success';
$error_msg = '';

try {
    $pdo->beginTransaction();

    // 1. Drop existing tables for a clean setup
    $tables = [
        'student_quiz_answers',
        'student_assessments',
        'discussion_replies',
        'discussion_posts',
        'quiz_questions',
        'chapter_quizzes',
        'chapter_materials',
        'classroom_chapters',
        'student_progress',
        'students',
        'teachers',
        'classrooms',
        'announcements'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table};");
    }

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
        island_id INTEGER NOT NULL, 
        chapter_name TEXT NOT NULL, 
        level INTEGER DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'In Progress', 
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE student_assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        island_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        type TEXT NOT NULL,
        score TEXT NOT NULL,
        status TEXT NOT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
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
        correct_option TEXT NOT NULL,
        score INTEGER DEFAULT 1 NOT NULL
    );");

    $pdo->exec("CREATE TABLE student_quiz_answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        assessment_id INTEGER NOT NULL,
        student_id INTEGER,
        quiz_id INTEGER,
        question_text TEXT NOT NULL,
        student_answer TEXT NOT NULL,
        correct_answer TEXT NOT NULL,
        is_correct INTEGER NOT NULL,
        explanation TEXT NOT NULL,
        answer_status VARCHAR(50) DEFAULT 'Pending',
        score INT DEFAULT 0,
        FOREIGN KEY(assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY(quiz_id) REFERENCES chapter_quizzes(id) ON DELETE CASCADE
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
        [1, 'Amina Yusuf', 'Mastering', 95],
        [1, 'Bao Nguyen', 'On Track', 82],
        [1, 'Carlos Mendez', 'Struggling', 60],
    ];

    $stmt_stu = $pdo->prepare("INSERT INTO students (classroom_id, name, status, score) VALUES (?, ?, ?, ?)");
    foreach ($all_students as $stu) {
        $stmt_stu->execute([$stu[0], $stu[1], $stu[2], $stu[3]]);
    }

    $pdo->exec("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES 
        (1, 'Ancient Pyramid: Fundamentals', 1),
        (1, 'Cherry Blossom: Multiplications', 1),
        (1, 'Volcanic Jungle: Fractions & Decimals', 1);");


    // Seed chapter materials so the JOIN in module.php finds them
    $pdo->exec("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES 
        ('Ancient Pyramid: Fundamentals', '1.1', 'Introduction to Fundamentals & Place Value', NULL),
        ('Ancient Pyramid: Fundamentals', '1.2', 'Basic Operations & Fractions Review', NULL),
        ('Cherry Blossom: Multiplications', '2.1', 'Multiplication Tables & Factors', NULL),
        ('Cherry Blossom: Multiplications', '2.2', 'Simplifying Fractions to Lowest Terms', NULL),
        ('Volcanic Jungle: Fractions & Decimals', '3.1', 'Mixed Numbers and Improper Fractions', NULL),
        ('Volcanic Jungle: Fractions & Decimals', '3.2', 'Decimal Conversion and Advanced Operations', NULL);");

        
    $pdo->exec("INSERT INTO student_progress (student_id, island_id, chapter_name, level, status) VALUES 
        (1, 1, 'Ancient Pyramid: Fundamentals', 3, 'Completed'),
        (1, 2, 'Cherry Blossom: Multiplications', 2, 'Completed'),
        (1, 3, 'Volcanic Jungle: Fractions & Decimals', 2, 'Completed');");

    $pdo->exec("INSERT INTO student_assessments (id, student_id, island_id, title, type, score, status, submitted_at) VALUES 
        (1, 1, 1, 'Chapter 1 Standard Test: Addition & Subtraction', 'Test', '3/3', 'Mastered', '2026-03-01 09:00:00'),
        (2, 1, 2, 'Chapter 2 Standard Test: Equivalent Fractions', 'Test', '3/3', 'Mastered', '2026-03-02 11:30:00'),
        (3, 1, 3, 'Chapter 3 Standard Test: Mixed Numbers & Decimals', 'Test', '3/3', 'Mastered', '2026-03-03 14:00:00');");

    $pdo->exec("INSERT INTO discussion_posts (student_id, title, content) VALUES 
        (1, 'How do I simplify 12/16 to its lowest terms?', 'I know I need to divide numerator and denominator by the highest common factor, but I am stuck.');");

    // Seed Chapter Quiz Bank First so IDs exist for linking answers
    $chapter_quizzes_data = [
        ['Ancient Pyramid: Fundamentals', NULL, 'What is 1/5 + 2/5?', '2/5', '3/5', '4/5', '3/10', 'B', 1],
        ['Ancient Pyramid: Fundamentals', NULL, 'What is 3/4 + 2/4?', '5/4', '5/8', '1/4', '6/4', 'A', 1],
        ['Ancient Pyramid: Fundamentals', NULL, 'What is 7/10 - 3/10?', '4/10', '4/0', '10/10', '3/10', 'A', 1],
        ['Cherry Blossom: Multiplications', NULL, 'Simplify 6/8 to lowest terms.', '3/4', '3/6', '2/4', '6/4', 'A', 1],
        ['Cherry Blossom: Multiplications', NULL, 'Simplify 5/10 to lowest terms.', '1/5', '2/5', '1/2', '5/2', 'C', 1],
        ['Cherry Blossom: Multiplications', NULL, 'Which fraction is equivalent to 1/3?', '2/3', '2/6', '3/6', '1/6', 'B', 1],
        ['Volcanic Jungle: Fractions & Decimals', NULL, 'Convert 7/3 to a mixed number.', '2 1/3', '3 1/2', '1 4/3', '2 2/3', 'A', 1],
        ['Volcanic Jungle: Fractions & Decimals', NULL, 'Convert 3 1/2 to an improper fraction.', '6/2', '7/2', '5/2', '7/1', 'B', 1],
        ['Volcanic Jungle: Fractions & Decimals', NULL, 'Calculate 1/3 + 1/4.', '2/7', '7/12', '1/12', '2/12', 'B', 1]
    ];

    $stmt_q_bank = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, subtopic_name, question, option_a, option_b, option_c, option_d, correct_option, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($chapter_quizzes_data as $q) {
        $stmt_q_bank->execute([$q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7], $q[8]]);
    }

    // Seed Student Quiz Answers linked to students (student_id = 1) and quizzes (quiz_id = 1 to 9)
    $student_answers_data = [
        // Assessment 1 (Student 1, Quiz IDs 1, 2, 3)
        [1, 1, 1, 'What is 1/5 + 2/5?', '3/5', '3/5', 1, 'Add numerators directly when denominators are identical.', 'Correct', 1],
        [1, 1, 2, 'What is 3/4 + 2/4?', '5/8', '5/4 or 1 1/4', 0, 'Keep denominator as 4 when adding common fractions.', 'Incorrect', 0],
        [1, 1, 3, 'What is 7/10 - 3/10?', '4/10', '4/10', 1, 'Subtract numerators: 7 - 3 = 4.', 'Correct', 1],
        // Assessment 2 (Student 1, Quiz IDs 4, 5, 6)
        [2, 1, 4, 'Simplify 6/8 to lowest terms.', '3/6', '3/4', 0, 'Divide top and bottom by greatest common divisor (2).', 'Incorrect', 0],
        [2, 1, 5, 'Simplify 5/10 to lowest terms.', '1/2', '1/2', 1, 'Divide numerator and denominator by 5.', 'Correct', 1],
        [2, 1, 6, 'Which fraction is equivalent to 1/3?', '2/6', '2/6', 1, 'Multiply numerator and denominator by 2.', 'Correct', 1],
        // Assessment 3 (Student 1, Quiz IDs 7, 8, 9)
        [3, 1, 7, 'Convert 7/3 to a mixed number.', '2 1/3', '2 1/3', 1, '7 divided by 3 equals 2 remainder 1.', 'Correct', 1],
        [3, 1, 8, 'Convert 3 1/2 to an improper fraction.', '7/2', '7/2', 1, '(3 * 2) + 1 = 7 over denominator 2.', 'Correct', 1],
        [3, 1, 9, 'Calculate 1/3 + 1/4.', '7/12', '7/12', 1, 'Find common denominator (12): 4/12 + 3/12 = 7/12.', 'Correct', 1]
    ];

    $stmt_ans = $pdo->prepare("INSERT INTO student_quiz_answers (assessment_id, student_id, quiz_id, question_text, student_answer, correct_answer, is_correct, explanation, answer_status, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($student_answers_data as $ans) {
        $stmt_ans->execute($ans);
    }

    $pdo->exec("INSERT INTO announcements (title, content, is_active) VALUES 
        ('📢 Additional Math Support Class', 'Teacher Sarah has added an extra online tutoring session this Thursday at 3:00 PM for review.', 1);");

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
            <div
                class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">
                ✓</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Setup Complete!</h1>
            <p class="text-xs text-slate-500">All tables and quiz questions have been successfully populated.</p>
        <?php else: ?>
            <div
                class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">
                ✕</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Update Failed</h1>
            <p class="text-xs text-rose-500 font-mono bg-rose-50 p-2 rounded text-left break-all mt-2">
                <?= htmlspecialchars($error_msg) ?></p>
        <?php endif; ?>
    </div>
</body>

</html>