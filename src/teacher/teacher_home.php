<?php
session_start();
require_once 'db.php';

// Fetch classrooms and count students dynamically using MySQL PDO
$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as students_count 
    FROM classrooms c 
    LEFT JOIN students s ON c.id = s.classroom_id 
    GROUP BY c.id
");
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EduPulse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen font-sans">
    <header class="bg-indigo-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wide">EduPulse Dashboard <span class="text-xs bg-indigo-500 py-1 px-2 rounded ml-2">MySQL PDO Mode</span></h1>
            <div class="text-sm font-medium">Teacher: Mr. Davis</div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Your Classrooms</h2>
                <p class="text-slate-500 text-sm">Select a classroom to view real-time learning gaps and adaptive progress.</p>
            </div>
            <a href="setup.php" class="text-xs bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1.5 rounded transition">Reset / Re-seed DB</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($classrooms as $room): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-indigo-50 text-indigo-700 font-semibold text-xs px-2.5 py-1 rounded-full">Active Class</span>
                            <span class="text-slate-400 text-sm font-medium"><?php echo $room['students_count']; ?> Students</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p class="text-sm text-slate-500 mb-6">Class Average Mastery: <span class="font-bold text-slate-700"><?php echo htmlspecialchars($room['avg_mastery']); ?></span></p>
                    </div>
                    <a href="classroom.php?id=<?php echo $room['id']; ?>" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg text-center transition block">
                        Open Classroom View &rarr;
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>