<?php
require_once __DIR__ . '/db.php';

try {
    // Wrap database initialization inside a single atomic transaction
    $pdo->beginTransaction();

    // Drop tables in reverse dependency order
    $pdo->exec("DROP TABLE IF EXISTS discussion_replies;");
    $pdo->exec("DROP TABLE IF EXISTS discussion_posts;");
    $pdo->exec("DROP TABLE IF EXISTS chapter_quizzes;");
    $pdo->exec("DROP TABLE IF EXISTS chapter_materials;");
    $pdo->exec("DROP TABLE IF EXISTS classroom_chapters;");
    $pdo->exec("DROP TABLE IF EXISTS student_progress;");
    $pdo->exec("DROP TABLE IF EXISTS students;");
    $pdo->exec("DROP TABLE IF EXISTS classrooms;");
    $pdo->exec("DROP TABLE IF EXISTS announcements;");
    // -------------------------------------------------------------------------
    // Create Schema
    // -------------------------------------------------------------------------
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

// Create announcements table
$pdo->exec("CREATE TABLE announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    $pdo->exec("INSERT INTO classrooms (id, name, avg_mastery) VALUES 
        (1, 'Grade 5 Mathematics - Section A', '68%'),
        (2, 'Grade 5 Mathematics - Section B', '54%'),
        (3, 'Grade 6 Remedial Math', '79%');");

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

    $stmt_student = $pdo->prepare("INSERT INTO students (classroom_id, name, status, score) VALUES (?, ?, ?, ?)");
    $stmt_progress = $pdo->prepare("INSERT INTO student_progress (student_id, chapter_name, level) VALUES (?, ?, ?)");

    foreach ($all_students as $s) {
        $stmt_student->execute([$s[0], $s[1], $s[2], $s[3]]);
        $student_id = $pdo->lastInsertId();

        foreach ($s[4] as $index => $level) {
            $stmt_progress->execute([$student_id, $chapters[$index], $level]);
        }
    }

    $stmt_cc = $pdo->prepare("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES (?, ?, ?)");
    foreach ([1, 2, 3] as $cid) {
        $stmt_cc->execute([$cid, "Fractions (Ch 1)", 1]);
        $stmt_cc->execute([$cid, "Decimals (Ch 2)", 0]);
        $stmt_cc->execute([$cid, "Percentages (Ch 3)", 0]);
    }

    $pdo->exec("INSERT INTO discussion_posts (student_id, title, content) VALUES 
        (1, 'How do I simplify 12/16 to its lowest terms?', 'I know I need to divide numerator and denominator by the highest common factor, but I am stuck.');");

    $pdo->exec("INSERT INTO discussion_replies (post_id, student_id, content) VALUES 
        (1, 2, 'Divide both top and bottom numbers by 4 to get 3/4!');");

        $pdo->exec("INSERT INTO announcements (title, content, is_active) VALUES 
    ('📢 Additional Math Support Class', 'Teacher Sarah has added an extra online tutoring session this Thursday at 3:00 PM for Fractions and Decimals review. Attendance is optional but encouraged!', 1);");
    
    $pdo->commit();
    $status = 'success';
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
    <title>SQLite Setup Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 max-w-md w-full rounded-2xl shadow-sm border border-slate-100 text-center">
        <?php if ($status === 'success'): ?>
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✓</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Setup Successful!</h1>
            <p class="text-xs text-slate-500">All tables including discussion board forums have been created and seeded.</p>
        <?php else: ?>
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✕</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Setup Failed</h1>
            <p class="text-xs text-rose-500 font-mono bg-rose-50 p-2 rounded text-left break-all"><?= htmlspecialchars($error_msg) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>