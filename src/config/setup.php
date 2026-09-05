<?php
require_once __DIR__ . '/db.php';

$status = 'success';
$error_msg = '';

try {
    $pdo->beginTransaction();

    // 1. Drop existing tables for a clean setup
// 1. Drop existing tables for a clean setup (including any old quiz answer tables)
    $pdo->exec("DROP TABLE IF EXISTS student_answers;");
    $pdo->exec("DROP TABLE IF EXISTS student_assessments;");
    $pdo->exec("DROP TABLE IF EXISTS student_quiz_answers;"); // Clean up old table if present
    $pdo->exec("DROP TABLE IF EXISTS discussion_replies;");
    $pdo->exec("DROP TABLE IF EXISTS discussion_posts;");
    $pdo->exec("DROP TABLE IF EXISTS quiz_questions;");
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
        island_id INTEGER NOT NULL, 
        chapter_name TEXT NOT NULL, 
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

    $pdo->exec("CREATE TABLE student_answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        assessment_id INTEGER NOT NULL,
        question_text TEXT NOT NULL,
        student_answer TEXT NOT NULL,
        correct_answer TEXT NOT NULL,
        is_correct INTEGER NOT NULL,
        explanation TEXT NOT NULL,
        FOREIGN KEY(assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE
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

    function seedAssessmentAnswers($pdo, $assessment_id, $data) {
        $stmt = $pdo->prepare("INSERT INTO student_answers (assessment_id, question_text, student_answer, correct_answer, is_correct, explanation) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($data as $q) {
            $stmt->execute([$assessment_id, $q[0], $q[1], $q[2], $q[3], $q[4]]);
        }
    }

    function seedChapterQuizBank($pdo, $chapter_name, $questions) {
        $stmt = $pdo->prepare("INSERT INTO chapter_quizzes (chapter_name, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($questions as $q) {
            $stmt->execute([$chapter_name, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]]);
        }
    }

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

    $pdo->exec("INSERT INTO classroom_chapters (classroom_id, chapter_name, is_unlocked) VALUES 
        (1, 'Ancient Pyramid: Fundamentals', 1),
        (1, 'Cherry Blossom: Multiplications', 1),
        (1, 'Volcanic Jungle: Fractions & Decimals', 1);");

    $pdo->exec("INSERT INTO student_progress (student_id, island_id, chapter_name, status) VALUES 
        (1, 1, 'Ancient Pyramid: Fundamentals', 'Completed'),
        (1, 2, 'Cherry Blossom: Multiplications', 'Completed'),
        (1, 3, 'Volcanic Jungle: Fractions & Decimals', 'Completed');");

    $pdo->exec("INSERT INTO student_assessments (id, student_id, island_id, title, type, score, status, submitted_at) VALUES 
        (1, 1, 1, 'Chapter 1 Standard Test: Addition & Subtraction', 'Test', '13/15', 'Mastered', '2026-03-01 09:00:00'),
        (2, 1, 2, 'Chapter 2 Standard Test: Equivalent Fractions', 'Test', '12/15', 'Mastered', '2026-03-02 11:30:00'),
        (3, 1, 3, 'Chapter 3 Standard Test: Mixed Numbers & Decimals', 'Test', '14/15', 'Mastered', '2026-03-03 14:00:00');");

    $sample_materials = [
        ["Fractions (Ch 1)", NULL, "Fractions Introduction Notes", "uploads/Fractions_Introduction_Notes.pdf"],
        ["Decimals (Ch 2)", NULL, "Decimals Place Value Chart", "uploads/Decimals_Place_Value_Chart.pdf"],
        ["Percentages (Ch 3)", NULL, "Percentage Basics Workbook", "uploads/Percentage_Basics_Workbook.pdf"]
    ];

    $stmt_mat = $pdo->prepare("INSERT INTO chapter_materials (chapter_name, subtopic_name, title, file_path) VALUES (?, ?, ?, ?)");
    foreach ($sample_materials as $mat) {
        $stmt_mat->execute([$mat[0], $mat[1], $mat[2], $mat[3]]);
    }

    $sample_quizzes = [
        // Fractions (Ch 1)
        ["Fractions (Ch 1)", NULL, "What is 3/5 - 1/5?", "1/5", "2/5", "3/5", "4/5", "B"],
        ["Fractions (Ch 1)", NULL, "Which of the following is a proper fraction?", "5/4", "4/3", "2/3", "7/2", "C"],
        ["Fractions (Ch 1)", NULL, "What is 1/3 + 1/3?", "1/6", "2/6", "2/3", "3/3", "C"],
        ["Fractions (Ch 1)", NULL, "Which fraction is greater: 1/2 or 1/4?", "1/4", "1/2", "They are equal", "Cannot be determined", "B"],
        ["Fractions (Ch 1)", "Subtopic 1.1: Like Fractions", "What is 2/7 + 3/7?", "5/14", "5/7", "1/7", "6/7", "B"],
        ["Fractions (Ch 1)", "Subtopic 1.1: Like Fractions", "What is 5/8 - 2/8?", "1/8", "2/8", "3/8", "4/8", "C"],

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

    // Chapter 1 Test Answers
    seedAssessmentAnswers($pdo, 1, [
        ['What is 1/5 + 2/5?', '3/5', '3/5', 1, 'Add numerators directly when denominators are identical.'],
        ['What is 3/4 + 2/4?', '5/8', '5/4 or 1 1/4', 0, 'Keep denominator as 4 when adding common fractions.'],
        ['What is 7/10 - 3/10?', '4/10', '4/10', 1, 'Subtract numerators: 7 - 3 = 4.'],
        ['What is 5/6 - 2/6?', '3/6', '3/6', 1, 'Subtract numerators directly.'],
        ['What is 1/3 + 1/3?', '2/3', '2/3', 1, '1 + 1 = 2 over common denominator 3.'],
        ['What is 4/7 + 2/7?', '6/7', '6/7', 1, '4 + 2 = 6.'],
        ['What is 9/12 - 4/12?', '5/12', '5/12', 1, '9 - 4 = 5.'],
        ['What is 2/8 + 3/8?', '5/8', '5/8', 1, '2 + 3 = 5.'],
        ['What is 1/4 + 1/4?', '2/4', '2/4', 1, '1 + 1 = 2.'],
        ['What is 3/3 - 1/3?', '1/3', '2/3', 0, '3 - 1 = 2 over common denominator 3.'],
        ['What is 5/9 + 2/9?', '7/9', '7/9', 1, '5 + 2 = 7.'],
        ['What is 8/15 - 3/15?', '5/15', '5/15', 1, '8 - 3 = 5.'],
        ['What is 1/10 + 7/10?', '8/10', '8/10', 1, '1 + 7 = 8.'],
        ['What is 6/11 - 2/11?', '4/11', '4/11', 1, '6 - 2 = 4.'],
        ['What is 3/6 + 2/6?', '5/6', '5/6', 1, '3 + 2 = 5.']
    ]);

    // Chapter 2 Test Answers
    seedAssessmentAnswers($pdo, 2, [
        ['Simplify 6/8 to lowest terms.', '3/6', '3/4', 0, 'Divide top and bottom by greatest common divisor (2).'],
        ['Simplify 5/10 to lowest terms.', '1/2', '1/2', 1, 'Divide numerator and denominator by 5.'],
        ['Which fraction is equivalent to 1/3?', '2/6', '2/6', 1, 'Multiply numerator and denominator by 2.'],
        ['Which fraction is larger: 1/2 or 1/4?', '1/4', '1/2', 0, 'Smaller denominator indicates larger slice size.'],
        ['Simplify 4/12.', '1/3', '1/3', 1, 'Divide top and bottom by 4.'],
        ['Is 2/4 equivalent to 3/6?', 'Yes', 'Yes', 1, 'Both simplify to 1/2.'],
        ['Simplify 8/10.', '4/5', '4/5', 1, 'Divide numerator and denominator by 2.'],
        ['Which is smaller: 3/5 or 1/5?', '1/5', '1/5', 1, 'Compare numerators when denominators match.'],
        ['Find an equivalent fraction for 3/4.', '6/8', '6/8', 1, 'Multiply top and bottom by 2.'],
        ['Simplify 9/12.', '3/4', '3/4', 1, 'Divide numerator and denominator by 3.'],
        ['Which is larger: 2/3 or 2/5?', '2/5', '2/3', 0, 'Thirds represent larger partitions than fifths.'],
        ['Simplify 10/15.', '2/3', '2/3', 1, 'Divide numerator and denominator by 5.'],
        ['Is 3/9 equivalent to 1/3?', 'Yes', 'Yes', 1, 'Divide top and bottom by 3.'],
        ['Simplify 2/10.', '1/5', '1/5', 1, 'Divide top and bottom by 2.'],
        ['Which fraction is equal to 1 whole?', '4/4', '4/4', 1, 'Numerator equal to denominator equals 1 whole.']
    ]);

    // Chapter 3 Test Answers
    seedAssessmentAnswers($pdo, 3, [
        ['Convert 7/3 to a mixed number.', '2 1/3', '2 1/3', 1, '7 divided by 3 equals 2 remainder 1.'],
        ['Convert 3 1/2 to an improper fraction.', '7/2', '7/2', 1, '(3 * 2) + 1 = 7 over denominator 2.'],
        ['Calculate 1/3 + 1/4.', '7/12', '7/12', 1, 'Find common denominator (12): 4/12 + 3/12 = 7/12.'],
        ['What is 2/3 of 12?', '8', '8', 1, '(12 ÷ 3) * 2 = 8.'],
        ['Convert 9/4 to a mixed number.', '2 1/4', '2 1/4', 1, '9 ÷ 4 = 2 with remainder 1.'],
        ['Calculate 1/2 * 1/3.', '1/6', '1/6', 1, 'Multiply numerators and denominators across.'],
        ['Convert 1 3/4 to an improper fraction.', '5/4', '7/4', 0, '(1 * 4) + 3 = 7 over denominator 4.'],
        ['Subtract 1 - 2/5.', '3/5', '3/5', 1, '5/5 - 2/5 = 3/5.'],
        ['Calculate 3/5 * 1/2.', '3/10', '3/10', 1, '3 * 1 = 3 and 5 * 2 = 10.'],
        ['What is half of 1/2?', '1/4', '1/4', 1, '1/2 divided by 2 equals 1/4.'],
        ['Convert 5/2 to a mixed number.', '2 1/2', '2 1/2', 1, '5 ÷ 2 = 2 with remainder 1.'],
        ['Find least common denominator for 1/2 and 1/5.', '10', '10', 1, 'LCM of 2 and 5 is 10.'],
        ['What is 2/5 + 1/5?', '3/5', '3/5', 1, 'Add numerators directly.'],
        ['Which is larger: 1 1/2 or 5/4?', '1 1/2', '1 1/2', 1, '1 1/2 equals 1.5, whereas 5/4 equals 1.25.'],
        ['What is 4/4 equal to?', '1', '1', 1, 'Equal numerator and denominator equals 1 whole.']
    ]);

    // Chapter Quiz Bank Seeding
    seedChapterQuizBank($pdo, 'Ancient Pyramid: Fundamentals', [
        ['What is 1/5 + 2/5?', '2/5', '3/5', '4/5', '3/10', 'B'],
        ['What is 3/4 + 2/4?', '5/4', '5/8', '1/4', '6/4', 'A'],
        ['What is 7/10 - 3/10?', '4/10', '4/0', '10/10', '3/10', 'A'],
        ['What is 5/6 - 2/6?', '3/0', '3/12', '3/6', '7/6', 'C'],
        ['What is 1/3 + 1/3?', '2/6', '2/3', '1/6', '3/3', 'B'],
        ['What is 4/7 + 2/7?', '6/14', '2/7', '6/7', '8/7', 'C'],
        ['What is 9/12 - 4/12?', '5/12', '5/0', '13/12', '5/24', 'A'],
        ['What is 2/8 + 3/8?', '5/16', '1/8', '5/8', '6/8', 'C'],
        ['What is 1/4 + 1/4?', '2/8', '2/4', '1/8', '3/4', 'B'],
        ['What is 3/3 - 1/3?', '2/3', '2/0', '1/3', '3/0', 'A'],
        ['What is 5/9 + 2/9?', '7/18', '7/9', '3/9', '8/9', 'B'],
        ['What is 8/15 - 3/15?', '5/30', '11/15', '5/15', '5/0', 'C'],
        ['What is 1/10 + 7/10?', '8/20', '8/10', '6/10', '7/10', 'B'],
        ['What is 6/11 - 2/11?', '4/22', '8/11', '4/11', '4/0', 'C'],
        ['What is 3/6 + 2/6?', '5/12', '5/6', '1/6', '6/6', 'B']
    ]);

    seedChapterQuizBank($pdo, 'Cherry Blossom: Multiplications', [
        ['Simplify 6/8 to lowest terms.', '3/4', '3/6', '2/4', '6/4', 'A'],
        ['Simplify 5/10 to lowest terms.', '1/5', '2/5', '1/2', '5/2', 'C'],
        ['Which fraction is equivalent to 1/3?', '2/3', '2/6', '3/6', '1/6', 'B'],
        ['Which fraction is larger: 1/2 or 1/4?', '1/4', '1/2', 'Equal', 'Cannot compare', 'B'],
        ['Simplify 4/12.', '1/4', '1/3', '2/6', '4/3', 'B'],
        ['Is 2/4 equivalent to 3/6?', 'Yes', 'No', 'Only sometimes', 'None', 'A'],
        ['Simplify 8/10.', '4/5', '2/5', '8/5', '4/10', 'A'],
        ['Which is smaller: 3/5 or 1/5?', '3/5', '1/5', 'Equal', 'None', 'B'],
        ['Find equivalent fraction for 3/4.', '6/4', '3/8', '6/8', '9/8', 'C'],
        ['Simplify 9/12.', '3/3', '3/4', '1/3', '9/4', 'B'],
        ['Which is larger: 2/3 or 2/5?', '2/3', '2/5', 'Equal', 'None', 'A'],
        ['Simplify 10/15.', '1/3', '2/5', '2/3', '5/3', 'C'],
        ['Is 3/9 equivalent to 1/3?', 'Yes', 'No', 'Sometimes', 'None', 'A'],
        ['Simplify 2/10.', '1/10', '1/5', '2/5', '1/2', 'B'],
        ['Which is equal to 1 whole?', '1/2', '3/4', '4/4', '5/4', 'C']
    ]);

    seedChapterQuizBank($pdo, 'Volcanic Jungle: Fractions & Decimals', [
        ['Convert 7/3 to a mixed number.', '2 1/3', '3 1/2', '1 4/3', '2 2/3', 'A'],
        ['Convert 3 1/2 to an improper fraction.', '6/2', '7/2', '5/2', '7/1', 'B'],
        ['Calculate 1/3 + 1/4.', '2/7', '7/12', '1/12', '2/12', 'B'],
        ['What is 2/3 of 12?', '4', '6', '8', '10', 'C'],
        ['Convert 9/4 to a mixed number.', '2 1/4', '2 1/2', '1 5/4', '3 1/4', 'A'],
        ['Calculate 1/2 * 1/3.', '2/5', '1/5', '1/6', '2/6', 'C'],
        ['Convert 1 3/4 to an improper fraction.', '4/3', '7/4', '5/4', '7/3', 'B'],
        ['Subtract 1 - 2/5.', '3/5', '1/5', '2/5', '4/5', 'A'],
        ['Calculate 3/5 * 1/2.', '3/10', '4/7', '3/7', '6/10', 'A'],
        ['What is half of 1/2?', '1', '1/2', '1/4', '1/8', 'C'],
        ['Convert 5/2 to a mixed number.', '2 1/2', '1 3/2', '2 1/5', '3 1/2', 'A'],
        ['Find LCM for denominators 2 and 5.', '7', '10', '12', '20', 'B'],
        ['What is 2/5 + 1/5?', '3/10', '3/5', '1/5', '4/5', 'B'],
        ['Which is larger: 1 1/2 or 5/4?', '1 1/2', '5/4', 'Equal', 'None', 'A'],
        ['What is 4/4 equal to?', '0', '1', '4', '8', 'B']
    ]);

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
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✓</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Setup Complete!</h1>
            <p class="text-xs text-slate-500">All tables and quiz questions have been successfully populated.</p>
        <?php else: ?>
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✕</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Update Failed</h1>
            <p class="text-xs text-rose-500 font-mono bg-rose-50 p-2 rounded text-left break-all mt-2"><?= htmlspecialchars($error_msg) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>