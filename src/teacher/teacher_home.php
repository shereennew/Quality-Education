<?php
// src/teacher/teacher_home.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Fetch all classrooms
$classrooms = $pdo->query("SELECT * FROM classrooms")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EduHunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pastel: {
                            bg: '#f0f4f9',
                            card: '#ffffff',
                            nav: '#e1e9f5',
                            primary: '#7da0ca',
                            hover: '#688dbb',
                            text: '#2c3e50',
                            light: '#f8fafc',
                            badge: '#cbe0f5'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen font-sans flex flex-col">
    <!-- Navbar -->
    <header class="bg-pastel-nav border-b border-blue-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h1 class="text-base font-bold text-pastel-text tracking-wide">EduHunt Teacher Dashboard</h1>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-pastel-badge text-pastel-hover text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
                    👨‍🏫 Educator Portal
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10 flex-1 w-full space-y-8">

        <!-- Classrooms Grid -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-pastel-text uppercase tracking-wider">Your Classrooms</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($classrooms as $class): ?>
                    <div class="bg-pastel-card rounded-2xl shadow-sm border border-blue-100 p-6 flex flex-col justify-between space-y-6 hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-100">Avg Mastery: <?php echo htmlspecialchars($class['avg_mastery']); ?></span>
                            </div>
                            <h4 class="text-base font-bold text-pastel-text pt-2"><?php echo htmlspecialchars($class['name']); ?></h4>
                        </div>
                        <div class="pt-4 border-t border-blue-50 flex items-center justify-between">
                            <span class="text-xs text-slate-400">Active Monitoring</span>
                            <a href="classroom.php?id=<?php echo $class['id']; ?>" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                                Open Classroom &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>
</body>
</html>