<?php
require_once __DIR__ . '/db.php';

$status = 'success';
$error_msg = '';

try {
    // Enable foreign key constraints in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->beginTransaction();

    // 1. Drop existing tables in correct order (children first, then parents)
    $tables = [
        'student_quiz_answers',
        'student_assessments',
        'teacher_quiz_feedback',
        'discussion_replies',
        'discussion_posts',
        'chapter_quizzes',
        'chapter_materials',
        'teacher_quizzes',
        'classroom_chapters',
        'student_progress',
        'students',
        'classrooms',
        'teachers',
        'announcements'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table};");
    }

    // -------------------------------------------------------------------------
    // Create Schema (Parents -> Children)
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
        chapter_number INTEGER DEFAULT 1 NOT NULL,
        chapter_name TEXT NOT NULL,
        subtopic_name TEXT DEFAULT NULL,
        title TEXT DEFAULT NULL,
        question TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        correct_option TEXT NOT NULL,
        explanation TEXT,
        score INTEGER DEFAULT 1 NOT NULL
    );");

        $pdo->exec("CREATE TABLE teacher_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER,
    chapter_name TEXT NOT NULL,
    title TEXT NOT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option TEXT NOT NULL,
    explanation TEXT,
    score INTEGER DEFAULT 1 NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);");

    $pdo->exec("CREATE TABLE teacher_quiz_feedback (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        teacher_id INTEGER,
        student_id INTEGER NOT NULL,
        chapter_name TEXT NOT NULL,
        subtopic_name TEXT NOT NULL,
        comment TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE student_quiz_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    assessment_id INTEGER NOT NULL,
    student_id INTEGER,
    quiz_id INTEGER,
    quiz_type TEXT DEFAULT 'chapter',
    question_text TEXT NOT NULL,
    student_answer TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    is_correct INTEGER NOT NULL,
    explanation TEXT NOT NULL,
    answer_status VARCHAR(50) DEFAULT 'Pending',
    score INT DEFAULT 0,
    FOREIGN KEY(assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE

    );");

    $pdo->exec("CREATE TABLE classroom_chapters (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        classroom_id INTEGER NOT NULL, 
        chapter_number INTEGER DEFAULT 1 NOT NULL,
        chapter_name TEXT NOT NULL, 
        is_unlocked INTEGER DEFAULT 0, 
        FOREIGN KEY(classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE chapter_materials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chapter_number INTEGER DEFAULT 1 NOT NULL,
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
    // Seed Data (Establishing Relationships: Teacher -> Classrooms -> Students)
    // -------------------------------------------------------------------------

    // 1. Seed Teacher
    $pdo->exec("INSERT INTO teachers (id, name, email, department) VALUES 
        (1, 'Teacher Sarah', 'sarah@eduhunt.com', 'Mathematics Department');");

    // 2. Seed Classrooms linked to Teacher Sarah (teacher_id = 1)
    $pdo->exec("INSERT INTO classrooms (id, teacher_id, name, avg_mastery) VALUES 
        (1, 1, 'Year 4 Mathematics - Class January', '42%'),
        (2, 1, 'Year 4 Mathematics - Class February', '54%'),
        (3, 1, 'Year 6 Mathematics', '79%');");

    // 3. Seed Students mapped to specific classrooms via classroom_id
    $all_students = [
        [1, 'Amina Yusuf', 'Mastering', 95],      // Class 1
        [1, 'Bao Nguyen', 'On Track', 82],         // Class 1
        [1, 'Carlos Mendez', 'Struggling', 60],    // Class 1
        [2, 'Diana Prince', 'Mastering', 90],      // Class 2
        [3, 'Ethan Hunt', 'On Track', 85],         // Class 3
    ];

    $stmt_stu = $pdo->prepare("INSERT INTO students (classroom_id, name, status, score) VALUES (?, ?, ?, ?)");
    foreach ($all_students as $stu) {
        $stmt_stu->execute([$stu[0], $stu[1], $stu[2], $stu[3]]);
    }

    $pdo->exec("INSERT INTO classroom_chapters (classroom_id, chapter_number, chapter_name, is_unlocked) VALUES 
        (1, 1, 'Ancient Pyramid: Fundamentals', 1),
        (1, 2, 'Cherry Blossom: Multiplications', 1),
        (1, 3, 'Volcanic Jungle: Fractions & Decimals', 1),
        (1, 4, 'The Cave: Geometry', 0),
        (2, 1, 'Ancient Pyramid: Fundamentals', 1),
        (3, 1, 'Ancient Pyramid: Fundamentals', 1);");

    $pdo->exec("INSERT INTO chapter_materials (chapter_number, chapter_name, subtopic_name, title, file_path) VALUES 
        (1, 'Ancient Pyramid: Fundamentals', '1.1', 'Introduction to Fundamentals & Place Value', NULL),
        (1, 'Ancient Pyramid: Fundamentals', '1.2', 'Basic Operations & Fractions Review', NULL),
        (2, 'Cherry Blossom: Multiplications', '2.1', 'Multiplication Tables & Factors', NULL),
        (2, 'Cherry Blossom: Multiplications', '2.2', 'Simplifying Fractions to Lowest Terms', NULL),
        (3, 'Volcanic Jungle: Fractions & Decimals', '3.1', 'Mixed Numbers and Improper Fractions', NULL),
        (3, 'Volcanic Jungle: Fractions & Decimals', '3.2', 'Decimal Conversion and Advanced Operations', NULL),
        (4, 'The Cave: Geometry', '4.1', 'Introduction to Shapes and Angles', NULL);");

    $pdo->exec("INSERT INTO student_progress (student_id, island_id, chapter_name, level, status) VALUES 
        (1, 1, 'Ancient Pyramid: Fundamentals', 0, 'In Progress'),
        (1, 2, 'Cherry Blossom: Multiplications', 0, 'In Progress'),
        (1, 3, 'Volcanic Jungle: Fractions & Decimals', 0, 'In Progress'),
        (2, 1, 'Ancient Pyramid: Fundamentals', 1, 'In Progress'),
        (2, 2, 'Cherry Blossom: Multiplications', 0, 'In Progress'),
        (2, 3, 'Volcanic Jungle: Fractions & Decimals', 0, 'In Progress'),
        (3, 1, 'Ancient Pyramid: Fundamentals', 2, 'Completed'),
        (3, 2, 'Cherry Blossom: Multiplications', 0, 'In Progress'),
        (3, 3, 'Volcanic Jungle: Fractions & Decimals', 0, 'In Progress');");

    $pdo->exec("INSERT INTO student_assessments 
        (id, student_id, island_id, title, type, score, status, submitted_at) VALUES 
        (1, 2, 1, 'Subtopic 1.1 Assessment', 'Quiz', '3/4', 'Completed', '2026-09-01 09:00:00'),
        (2, 3, 1, 'Subtopic 1.1 Assessment', 'Quiz', '4/4', 'Completed', '2026-09-01 10:00:00'),
        (3, 3, 1, 'Subtopic 1.2 Assessment', 'Quiz', '3/4', 'Completed', '2026-09-01 11:00:00');");

    $pdo->exec("INSERT INTO discussion_posts (student_id, title, content) VALUES 
        (1, 'How do I simplify 12/16 to its lowest terms?', 'I know I need to divide numerator and denominator by the highest common factor, but I am stuck.');");


    $pdo->exec("INSERT INTO teacher_quizzes
(teacher_id, chapter_name, title, question, option_a, option_b, option_c, option_d, correct_option, explanation)
VALUES

(1,
 'Ancient Pyramid: Fundamentals',
 'Fraction Challenge',
 'A cake is divided into 8 equal pieces. Sarah eats 3 pieces. What fraction of the cake is left?',
 '3/8',
 '5/8',
 '6/8',
 '1/8',
 'B',
 'There are 8 pieces in total and 3 are eaten, so 8 - 3 = 5 pieces remain. Therefore, 5/8 is left.'),

(1,
 'Ancient Pyramid: Fundamentals',
 'Fraction Challenge',
 'Which fraction is greater?',
 '2/5',
 '1/2',
 '1/3',
 '1/4',
 'B',
 '1/2 is greater than 2/5 because 1/2 = 5/10 while 2/5 = 4/10.'),

(1,
 'Ancient Pyramid: Fundamentals',
 'Fraction Challenge',
 'Tom has 6/10 of a chocolate bar and gives away 2/10. How much does he have now?',
 '2/10',
 '3/10',
 '4/10',
 '8/10',
 'C',
 'Subtract the fractions with the same denominator: 6/10 - 2/10 = 4/10.'),

(1,
 'Ancient Pyramid: Fundamentals',
 'Fraction Challenge',
 'Which fraction is equivalent to 3/6?',
 '1/2',
 '1/3',
 '2/3',
 '3/4',
 'A',
 '3/6 can be simplified by dividing both numerator and denominator by 3, giving 1/2.');");

// Seed Chapter Quiz Bank
$chapter_quizzes_data = [

    // ==========================================
    // Chapter 1: Ancient Pyramid: Fundamentals
    // ==========================================

    // Subtopic 1.1
    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.1',
        'Subtopic 1.1 Assessment',
        'What is 1/5 + 2/5?',
        '2/5',
        '3/5',
        '4/5',
        '3/10',
        'B',
        'When the denominators are the same, add the numerators and keep the denominator. 1/5 + 2/5 = 3/5.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.1',
        'Subtopic 1.1 Assessment',
        'What is 3/4 + 2/4?',
        '5/4',
        '5/8',
        '1/4',
        '6/4',
        'A',
        'When the denominators are the same, add the numerators and keep the denominator. 3/4 + 2/4 = 5/4.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.1',
        'Subtopic 1.1 Assessment',
        'What is 2/3 + 1/3?',
        '3/3',
        '1/1',
        '1',
        'All of the above',
        'D',
        '2/3 + 1/3 = 3/3. The values 3/3, 1/1, and 1 are all equal to 1.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.1',
        'Subtopic 1.1 Assessment',
        'What is 1/8 + 3/8?',
        '4/8',
        '1/2',
        '2/4',
        'All of the above',
        'D',
        '1/8 + 3/8 = 4/8. The fraction 4/8 is equal to 1/2 and 2/4, so all of the answers have the same value.',
        1
    ],

    // Subtopic 1.2
    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.2',
        'Subtopic 1.2 Assessment',
        'What is 7/10 - 3/10?',
        '4/10',
        '4/0',
        '10/10',
        '3/10',
        'A',
        'When the denominators are the same, subtract the numerators and keep the denominator. 7/10 - 3/10 = 4/10.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.2',
        'Subtopic 1.2 Assessment',
        'What is 9/10 - 4/10?',
        '5/10',
        '3/10',
        '1/2',
        '13/10',
        'A',
        'Subtract the numerators while keeping the denominator the same. 9/10 - 4/10 = 5/10.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.2',
        'Subtopic 1.2 Assessment',
        'What is 5/6 - 2/6?',
        '3/6',
        '1/2',
        '7/6',
        'Both A and B',
        'D',
        '5/6 - 2/6 = 3/6. The fraction 3/6 is equal to 1/2, so both A and B have the same value.',
        1
    ],

    [
        1,
        'Ancient Pyramid: Fundamentals',
        '1.2',
        'Subtopic 1.2 Assessment',
        'What is 4/5 - 1/5?',
        '3/5',
        '3/10',
        '2/5',
        '1/5',
        'A',
        'Subtract the numerators while keeping the denominator the same. 4/5 - 1/5 = 3/5.',
        1
    ],

    // ==========================================
    // Chapter 2: Cherry Blossom: Multiplications
    // ==========================================

    // Subtopic 2.1
    [
        2,
        'Cherry Blossom: Multiplications',
        '2.1',
        'Subtopic 2.1 Assessment',
        'Simplify 6/8 to lowest terms.',
        '3/4',
        '3/6',
        '2/4',
        '6/4',
        'A',
        'Divide both the numerator and denominator by their greatest common factor, 2. 6/8 becomes 3/4.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.1',
        'Subtopic 2.1 Assessment',
        'Simplify 4/6 to lowest terms.',
        '2/3',
        '1/2',
        '3/4',
        '4/6',
        'A',
        'Divide both the numerator and denominator by their greatest common factor, 2. 4/6 becomes 2/3.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.1',
        'Subtopic 2.1 Assessment',
        'Simplify 2/4 to lowest terms.',
        '1/2',
        '1/4',
        '2/2',
        '1/3',
        'A',
        'Divide both the numerator and denominator by 2. 2/4 simplifies to 1/2.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.1',
        'Subtopic 2.1 Assessment',
        'Simplify 9/12 to lowest terms.',
        '3/4',
        '2/3',
        '3/5',
        '4/5',
        'A',
        'Divide both the numerator and denominator by their greatest common factor, 3. 9/12 becomes 3/4.',
        1
    ],

    // Subtopic 2.2
    [
        2,
        'Cherry Blossom: Multiplications',
        '2.2',
        'Subtopic 2.2 Assessment',
        'Simplify 5/10 to lowest terms.',
        '1/5',
        '2/5',
        '1/2',
        '5/2',
        'C',
        'Divide both the numerator and denominator by 5. 5/10 simplifies to 1/2.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.2',
        'Subtopic 2.2 Assessment',
        'Which fraction is equivalent to 1/3?',
        '2/3',
        '2/6',
        '3/6',
        '1/6',
        'B',
        'Multiply both the numerator and denominator of 1/3 by 2. This gives 2/6, which has the same value as 1/3.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.2',
        'Subtopic 2.2 Assessment',
        'Which fraction is equivalent to 2/4?',
        '1/2',
        '3/6',
        '4/8',
        'All of the above',
        'D',
        '2/4, 1/2, 3/6, and 4/8 all have the same value. Therefore, all of the above are equivalent to 2/4.',
        1
    ],

    [
        2,
        'Cherry Blossom: Multiplications',
        '2.2',
        'Subtopic 2.2 Assessment',
        'Which fraction is equivalent to 3/5?',
        '6/10',
        '9/15',
        '12/20',
        'All of the above',
        'D',
        'Multiplying the numerator and denominator by the same number gives equivalent fractions. 6/10, 9/15, and 12/20 all equal 3/5.',
        1
    ],

    // ==========================================
    // Chapter 3: Volcanic Jungle: Fractions & Decimals
    // ==========================================

    // Subtopic 3.1
    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.1',
        'Subtopic 3.1 Assessment',
        'Convert 7/3 to a mixed number.',
        '2 1/3',
        '3 1/2',
        '1 4/3',
        '2 2/3',
        'A',
        '7 divided by 3 gives 2 with a remainder of 1. Therefore, 7/3 = 2 1/3.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.1',
        'Subtopic 3.1 Assessment',
        'Convert 5/2 to a mixed number.',
        '2 1/2',
        '1 1/2',
        '2 1/3',
        '3 1/2',
        'A',
        '5 divided by 2 gives 2 with a remainder of 1. Therefore, 5/2 = 2 1/2.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.1',
        'Subtopic 3.1 Assessment',
        'Convert 9/4 to a mixed number.',
        '2 1/4',
        '2 1/2',
        '1 3/4',
        '3 1/4',
        'A',
        '9 divided by 4 gives 2 with a remainder of 1. Therefore, 9/4 = 2 1/4.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.1',
        'Subtopic 3.1 Assessment',
        'Convert 11/3 to a mixed number.',
        '3 2/3',
        '2 2/3',
        '3 1/3',
        '4 1/3',
        'A',
        '11 divided by 3 gives 3 with a remainder of 2. Therefore, 11/3 = 3 2/3.',
        1
    ],

    // Subtopic 3.2
    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.2',
        'Subtopic 3.2 Assessment',
        'Convert 3 1/2 to an improper fraction.',
        '6/2',
        '7/2',
        '5/2',
        '7/1',
        'B',
        'Multiply the whole number by the denominator and add the numerator: (3 × 2) + 1 = 7. Therefore, the improper fraction is 7/2.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.2',
        'Subtopic 3.2 Assessment',
        'Calculate 1/3 + 1/4.',
        '2/7',
        '7/12',
        '1/12',
        '2/12',
        'B',
        'Use a common denominator of 12. 1/3 = 4/12 and 1/4 = 3/12, so 4/12 + 3/12 = 7/12.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.2',
        'Subtopic 3.2 Assessment',
        'Convert 2 1/4 to an improper fraction.',
        '9/4',
        '7/4',
        '8/4',
        '9/2',
        'A',
        'Multiply the whole number by the denominator and add the numerator: (2 × 4) + 1 = 9. Therefore, the improper fraction is 9/4.',
        1
    ],

    [
        3,
        'Volcanic Jungle: Fractions & Decimals',
        '3.2',
        'Subtopic 3.2 Assessment',
        'Calculate 1/2 + 1/3.',
        '2/5',
        '5/6',
        '2/6',
        '3/6',
        'B',
        'Use a common denominator of 6. 1/2 = 3/6 and 1/3 = 2/6, so 3/6 + 2/6 = 5/6.',
        1
    ]
];

// Insert Chapter Quiz Bank
$stmt_q_bank = $pdo->prepare("
    INSERT INTO chapter_quizzes
    (
        chapter_number,
        chapter_name,
        subtopic_name,
        title,
        question,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_option,
        explanation,
        score
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($chapter_quizzes_data as $q) {
    $stmt_q_bank->execute([
        $q[0],  // chapter_number
        $q[1],  // chapter_name
        $q[2],  // subtopic_name
        $q[3],  // title
        $q[4],  // question
        $q[5],  // option_a
        $q[6],  // option_b
        $q[7],  // option_c
        $q[8],  // option_d
        $q[9],  // correct_option
        $q[10], // explanation
        $q[11]  // score
    ]);
}

    $student_answers_data = [
        [1, 2, 1, 'What is 1/5 + 2/5?', '3/5', '3/5', 1, 'Add the numerators directly when the denominators are the same.', 'Correct', 1],
        [1, 2, 2, 'What is 3/4 + 2/4?', '5/8', '5/4', 0, 'Keep the denominator as 4 when adding fractions with the same denominator.', 'Incorrect', 0],
        [1, 2, 3, 'What is 2/3 + 1/3?', 'All of the above', 'All of the above', 1, '2/3 + 1/3 = 3/3 = 1. All of the listed answers represent the same value.', 'Correct', 1],
        [1, 2, 4, 'What is 1/8 + 3/8?', 'All of the above', 'All of the above', 1, '1/8 + 3/8 = 4/8 = 1/2 = 2/4.', 'Correct', 1],

        [2, 3, 1, 'What is 1/5 + 2/5?', '3/5', '3/5', 1, 'Add the numerators directly when the denominators are the same.', 'Correct', 1],
        [2, 3, 2, 'What is 3/4 + 2/4?', '5/4', '5/4', 1, 'Keep the denominator as 4 and add the numerators.', 'Correct', 1],
        [2, 3, 3, 'What is 2/3 + 1/3?', '1', '1', 1, '2/3 + 1/3 = 3/3 = 1.', 'Correct', 1],
        [2, 3, 4, 'What is 1/8 + 3/8?', '1/2', '1/2', 1, '1/8 + 3/8 = 4/8 = 1/2.', 'Correct', 1],

        [3, 3, 5, 'What is 7/10 - 3/10?', '4/10', '4/10', 1, 'Subtract the numerators: 7 - 3 = 4.', 'Correct', 1],
        [3, 3, 6, 'What is 9/10 - 4/10?', '5/10', '5/10', 1, 'Subtract the numerators: 9 - 4 = 5.', 'Correct', 1],
        [3, 3, 7, 'What is 5/6 - 2/6?', '3/6', '3/6', 1, 'Subtract the numerators: 5 - 2 = 3.', 'Correct', 1],
        [3, 3, 8, 'What is 4/5 - 1/5?', '2/5', '3/5', 0, 'Subtract the numerators: 4 - 1 = 3, so the answer is 3/5.', 'Incorrect', 0]
    ];

    $stmt_ans = $pdo->prepare("
        INSERT INTO student_quiz_answers
        (assessment_id, student_id, quiz_id, question_text, student_answer, correct_answer, is_correct, explanation, answer_status, score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

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
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✓</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Setup Complete!</h1>
            <p class="text-xs text-slate-500">All tables, foreign keys, and relationships have been successfully configured and populated.</p>
        <?php else: ?>
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-lg">✕</div>
            <h1 class="text-lg font-bold text-slate-800 mb-1">Database Update Failed</h1>
            <p class="text-xs text-rose-500 font-mono bg-rose-50 p-2 rounded text-left break-all mt-2">
                <?= htmlspecialchars($error_msg) ?>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>