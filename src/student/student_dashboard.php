<?php
// Mock student data
$student_name = "Alex Tan";
$current_page = $_GET['page'] ?? 'practice'; // Default active page

// Navigation items array
$nav_items = [
    'notes' => 'Notes',
    'practice' => 'Practice',
    'quiz' => 'Quiz',
    'module' => 'Module'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?= htmlspecialchars($student_name) ?></title>
    <!-- Tailwind CSS CDN -->
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

<body class="bg-pastel-bg text-pastel-text font-sans min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-pastel-nav shadow-sm border-b border-blue-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Leftmost Side: Student Name & Nav Links -->
                <div class="flex items-center space-x-8">
                    <!-- Student Name / Profile Badge -->
                    <div class="flex items-center space-x-3 pr-4 border-r border-blue-200">
                        <div
                            class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                            <?= strtoupper(substr($student_name, 0, 1)) ?>
                        </div>
                        <span class="font-semibold text-pastel-text text-base whitespace-nowrap">
                            <?= htmlspecialchars($student_name) ?>
                        </span>
                    </div>

                    <!-- Navigation Links -->
                    <div class="flex space-x-2">
                        <?php foreach ($nav_items as $key => $label): ?>
                            <?php $isActive = ($current_page === $key); ?>
                            <a href="?page=<?= $key ?>"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 <?= $isActive ? 'bg-pastel-primary text-white shadow-sm' : 'text-pastel-text hover:bg-white/60 hover:text-pastel-hover' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-3">
                    <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1 rounded-full">Grade 5
                        Math</span>
                    <button
                        class="text-xs bg-white text-pastel-text px-3 py-1.5 rounded-lg border border-blue-100 hover:bg-slate-50 transition">Logout</button>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header Banner -->
        <div
            class="bg-gradient-to-r from-pastel-nav to-white p-6 rounded-2xl border border-blue-100 shadow-sm mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-pastel-text capitalize">
                    <?= htmlspecialchars($nav_items[$current_page] ?? 'Dashboard') ?> Section
                </h1>
                <p class="text-sm text-slate-500 mt-1">Keep up the great work! You have completed 3 adaptive tasks
                    today.</p>
            </div>
            <div class="hidden sm:block text-right">
                <span class="text-xs text-slate-400 block uppercase tracking-wider font-semibold">Weekly Goal</span>
                <span class="text-lg font-bold text-pastel-hover">85% Completed</span>
            </div>
        </div>

        <?php if ($current_page === 'practice'): ?>
            <!-- Interactive Practice View (Default) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Practice Card -->
                <div class="lg:col-span-2 bg-pastel-card p-6 rounded-2xl shadow-sm border border-blue-100">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-pastel-hover">Module 3:
                            Fractions</span>
                        <span class="text-xs text-slate-400">Question 3 of 5</span>
                    </div>

                    <h2 class="text-lg font-semibold mb-3">Solve the following problem:</h2>
                    <div class="p-4 bg-pastel-bg rounded-xl mb-6 text-center text-xl font-medium text-slate-700">
                        $$\frac{1}{3} + \frac{2}{6} = ?$$
                    </div>

                    <!-- Answer Options -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <button onclick="selectOption(this)"
                            class="option-btn p-3 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 transition font-medium">
                            A) $\frac{3}{9}$
                        </button>
                        <button onclick="selectOption(this)"
                            class="option-btn p-3 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 transition font-medium">
                            B) $\frac{2}{3}$
                        </button>
                        <button onclick="selectOption(this)"
                            class="option-btn p-3 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 transition font-medium">
                            C) $\frac{3}{6}$
                        </button>
                        <button onclick="selectOption(this)"
                            class="option-btn p-3 text-left border-2 border-slate-100 rounded-xl hover:border-pastel-primary hover:bg-blue-50/50 transition font-medium">
                            D) $1$
                        </button>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                        <button class="text-sm text-pastel-hover font-medium hover:underline">💡 Need a Hint?</button>
                        <button
                            class="bg-pastel-primary hover:bg-pastel-hover text-white px-6 py-2 rounded-xl font-medium text-sm transition shadow-sm">Submit
                            Answer</button>
                    </div>
                </div>

                <!-- Side Progress Card -->
                <div class="bg-pastel-card p-6 rounded-2xl shadow-sm border border-blue-100">
                    <h3 class="font-semibold text-base mb-4">Skill Progress</h3>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Visual Fractions</span>
                                <span class="text-emerald-600">Mastered</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-400 h-full" style="width: 100%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Equivalent Fractions</span>
                                <span class="text-pastel-hover">In Progress (75%)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-pastel-primary h-full" style="width: 75%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Unlike Denominators</span>
                                <span class="text-amber-500">Needs Review (40%)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-300 h-full" style="width: 40%"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- Generic Placeholder for Other Pages -->
            <div class="bg-pastel-card p-12 rounded-2xl shadow-sm border border-blue-100 text-center">
                <p class="text-slate-400">Content for <span
                        class="font-semibold text-pastel-text capitalize"><?= htmlspecialchars($current_page) ?></span> goes
                    here.</p>
            </div>
        <?php endif; ?>

    </main>

    <!-- Client-side Interactive Logic -->
    <script>
        function selectOption(selectedBtn) {
            // Reset state for all buttons
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('border-pastel-primary', 'bg-blue-50', 'text-pastel-hover');
                btn.classList.add('border-slate-100');
            });
            // Highlight selected button
            selectedBtn.classList.remove('border-slate-100');
            selectedBtn.classList.add('border-pastel-primary', 'bg-blue-50', 'text-pastel-hover');
        }
    </script>
</body>

</html>