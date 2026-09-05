<?php
$student_name = "Alex Tan";
$active_tab = $_GET['page'] ?? 'home';

// Nav Bar Items (Pure Text for Primary 4 / Year 4 Math)
$nav_items = [
    'home'        => ['label' => 'Home',        'url' => 'student_dashboard.php'],
    'module'      => ['label' => 'Modules',     'url' => 'module.php'],
    'quiz'        => ['label' => 'Quizzes',     'url' => 'quiz.php'],
    'math_helper' => ['label' => 'Math Helper', 'url' => 'math_helper.php']
];

// Student Learning Data
$dashboard_data = [
    'current_chapter' => 'Chapter 4: Waterfall Forest',
    'current_topic'   => 'Adding & Subtracting Unlike Fractions',
    'progress'        => 68,
    'recent_quizzes'  => [
        ['title' => '4.1 Common Denominators', 'score' => '3/3', 'date' => 'Yesterday', 'status' => 'Passed'],
        ['title' => '4.2 Adding Unlike Fractions', 'score' => '2/3', 'date' => '3 days ago', 'status' => 'Review Needed']
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?= htmlspecialchars($student_name) ?></title>
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
                            badge: '#cbe0f5'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pastel-bg text-pastel-text font-sans min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-pastel-nav shadow-sm border-b border-blue-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Leftmost: Student Avatar & Name -->
                <div class="flex items-center space-x-3 pr-4 border-r border-blue-200">
                    <div class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <?= strtoupper(substr($student_name, 0, 1)) ?>
                    </div>
                    <span class="font-semibold text-pastel-text text-base whitespace-nowrap">
                        <?= htmlspecialchars($student_name) ?>
                    </span>
                </div>

                <!-- Center Navigation Items (Text Only) -->
                <div class="flex space-x-2">
                    <?php foreach ($nav_items as $key => $item): ?>
                        <a href="<?= $item['url'] ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $key === $active_tab ? 'bg-pastel-primary text-white shadow-sm' : 'text-pastel-text hover:bg-white/60' ?>">
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Right: Utility Tools & Grade Tag (Text Only) -->
                <div class="flex items-center space-x-3">
                    
                    <!-- Read-Aloud Tool Button -->
                    <button type="button" 
                            title="Read Aloud"
                            class="px-3 py-1.5 rounded-lg bg-white/80 hover:bg-white text-slate-600 hover:text-pastel-hover text-xs border border-blue-100 transition shadow-sm font-medium">
                        Read Aloud
                    </button>

                    <!-- Digital Scratchpad Tool Button -->
                    <button type="button" 
                            title="Scratchpad"
                            class="px-3 py-1.5 rounded-lg bg-white/80 hover:bg-white text-slate-600 hover:text-pastel-hover text-xs border border-blue-100 transition shadow-sm font-medium">
                        Scratchpad
                    </button>

                    <!-- Grade Tag -->
                    <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1.5 rounded-full whitespace-nowrap">
                        Year 4 Math
                    </span>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">

        <!-- Welcome Banner -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Welcome Back</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1">Hello, <?= htmlspecialchars($student_name) ?>!</h1>
                <p class="text-sm text-slate-500 mt-0.5">Ready to continue your learning journey today?</p>
            </div>
            <a href="module.php" class="px-5 py-2.5 bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold rounded-xl shadow-sm transition whitespace-nowrap">
                Continue Learning →
            </a>
        </div>

        <!-- Current Learning Progress & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Current Module Status -->
            <div class="lg:col-span-2 bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Active Chapter</span>
                        <span class="text-xs font-semibold text-slate-400"><?= $dashboard_data['progress'] ?>% Completed</span>
                    </div>
                    <h2 class="text-xl font-bold text-pastel-text"><?= $dashboard_data['current_chapter'] ?></h2>
                    <p class="text-xs text-slate-500 mt-1"><?= $dashboard_data['current_topic'] ?></p>

                    <!-- Progress Bar -->
                    <div class="w-full bg-blue-50 rounded-full h-3 mt-4 overflow-hidden border border-blue-100">
                        <div class="bg-pastel-primary h-3 rounded-full transition-all duration-500" style="width: <?= $dashboard_data['progress'] ?>%"></div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <a href="module.php" class="flex-1 text-center py-2.5 bg-blue-50 hover:bg-pastel-primary hover:text-white text-pastel-hover text-xs font-bold rounded-xl border border-blue-100 transition">
                        Open Chapter Module
                    </a>
                    <a href="quiz.php" class="flex-1 text-center py-2.5 bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Take Chapter Quizzes
                    </a>
                </div>
            </div>

            <!-- Quick Quiz Hub Shortcut -->
            <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Quick Quiz Access</span>
                    <h3 class="text-lg font-bold text-pastel-text mt-1">Practice & Test</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Test your understanding with section quizzes or attempt the full chapter assessment.
                    </p>
                </div>
                <a href="quiz.php" class="mt-6 w-full text-center py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold rounded-xl border border-purple-100 transition">
                    Go to Quiz Hub →
                </a>
            </div>

        </div>

        <!-- Recent Quiz Activity -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-bold text-pastel-text">Recent Quiz Results</h3>
                <a href="quiz.php" class="text-xs font-bold text-pastel-primary hover:underline">View All Quizzes →</a>
            </div>

            <div class="space-y-3">
                <?php foreach ($dashboard_data['recent_quizzes'] as $quiz): ?>
                    <div class="p-4 bg-pastel-bg rounded-xl border border-blue-50 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-pastel-text"><?= $quiz['title'] ?></h4>
                            <span class="text-xs text-slate-400"><?= $quiz['date'] ?></span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-bold text-pastel-primary"><?= $quiz['score'] ?></span>
                            <span class="text-xs px-2.5 py-1 rounded-md font-semibold <?= $quiz['status'] === 'Passed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= $quiz['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>

</body>
</html>