<?php
// teacher_home.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Fetch classrooms and count students dynamically using MySQL PDO/SQLite
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pastel: {
                            bg: '#f0f4f9',       // Very soft pastel blue background
                            card: '#ffffff',     // Card background
                            nav: '#e1e9f5',      // Soft blue for navbar
                            primary: '#7da0ca',  // Main pastel blue accent
                            hover: '#688dbb',    // Darker pastel hover state
                            text: '#2c3e50',     // Deep slate text for contrast
                            light: '#f8fafc',    // Ultra light container
                            badge: '#cbe0f5'     // Light pastel highlight tag
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <!-- Clean Navbar matching pastel theme -->
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h1 class="text-lg font-bold text-pastel-text tracking-wide flex items-center gap-2">
                    EduPulse <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold py-0.5 px-2.5 rounded-full">Teacher Dashboard</span>
                </h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs bg-white/60 text-pastel-text px-3 py-1.5 rounded-full font-medium border border-blue-100">Teacher: Mr. Davis</span>
                <div class="w-8 h-8 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-xs shadow-sm">D</div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 flex-1 w-full">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-pastel-text">Your Classrooms</h2>
                <p class="text-slate-500 text-sm mt-1">Select a classroom to view real-time learning gaps and adaptive progress.</p>
            </div>
            <a href="../../setup.php" class="text-xs bg-white hover:bg-slate-50 text-pastel-text border border-blue-100 px-3.5 py-2 rounded-lg transition font-medium shadow-sm">Reset / Re-seed DB</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($classrooms as $room): ?>
                <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-pastel-badge text-pastel-hover font-semibold text-xs px-3 py-1 rounded-full">Active Class</span>
                            <span class="text-slate-400 text-xs font-semibold"><?php echo $room['students_count']; ?> Students</span>
                        </div>
                        <h3 class="text-lg font-bold text-pastel-text mb-2"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p class="text-sm text-slate-500 mb-6">Class Average Mastery: <span class="font-bold text-pastel-hover"><?php echo htmlspecialchars($room['avg_mastery']); ?></span></p>
                    </div>
                    <a href="classroom.php?id=<?php echo $room['id']; ?>" class="w-full bg-pastel-primary hover:bg-pastel-hover text-white font-medium py-2.5 rounded-xl text-center transition block shadow-sm text-sm">
                        Open Classroom View &rarr;
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>