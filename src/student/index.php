<?php
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
];

// Mock island progress states and text labels for all 7 levels
$islands = [
    1 => ['name' => 'Ancient Pyramid', 'topic' => 'Arithmetic', 'x' => 44, 'y' => 84, 'status' => 'mastered'],
    2 => ['name' => 'Cherry Blossom Valley', 'topic' => 'Multiplication', 'x' => 30, 'y' => 48, 'status' => 'mastered'],
    3 => ['name' => 'Volcanic Jungle', 'topic' => 'Fractions', 'x' => 50, 'y' => 48, 'status' => 'pending'],
    4 => ['name' => 'Hidden Cove', 'topic' => 'Geometry', 'x' => 70, 'y' => 64, 'status' => 'beginner'],
    5 => ['name' => 'Waterfall Cliffs', 'topic' => 'Measurement', 'x' => 70, 'y' => 32, 'status' => 'locked'],
    6 => ['name' => 'Frozen Igloo', 'topic' => 'Data Handling', 'x' => 54, 'y' => 12, 'status' => 'locked'],
    7 => ['name' => 'Desert Treasure', 'topic' => 'Word Problems', 'x' => 32, 'y' => 20, 'status' => 'locked'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduhunt - Island Math Adventure!</title>
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
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
</head>

<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-center p-6 pt-32">

    <nav
        class="bg-pastel-nav fixed w-full h-24 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">

        <div class="w-full max-w-[90rem] mx-auto px-10 flex items-center justify-between">

            <!-- LEFT: LOGO -->
            <a href="index.php" class="flex items-center gap-4 flex-shrink-0">

                <div class="bg-pastel-badge w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-3xl">📖</span>
                </div>

                <span class="text-3xl font-black tracking-wide text-pastel-text hidden lg:block">
                    Eduhunt
                </span>

            </a>


            <!-- CENTER: LARGE NAVIGATION -->
            <div class="hidden md:flex items-center justify-center flex-1 mx-8">

                <ul class="flex items-center gap-3 text-2xl font-bold">

                    <!-- HOME -->
                    <li>
                        <a href="index.php" class="flex items-center px-7 py-4 rounded-2xl
                        bg-pastel-primary text-white
                        shadow-md
                        transition-all duration-200
                        hover:bg-pastel-hover hover:-translate-y-0.5">
                            <span>Home</span>
                        </a>
                    </li>


                    <!-- DISCUSSION -->
                    <li>
                        <a href="discussion.php" class="flex items-center px-6 py-4 rounded-2xl
                              text-pastel-text
                              hover:bg-pastel-card
                              hover:text-pastel-primary
                              hover:shadow-sm
                              transition-all duration-200">

                            <span>Discussion</span>
                        </a>
                    </li>


                    <!-- MODULES -->
                    <li>
                        <a href="module.php" class="flex items-center px-6 py-4 rounded-2xl
                              text-pastel-text
                              hover:bg-pastel-card
                              hover:text-pastel-primary
                              hover:shadow-sm
                              transition-all duration-200">

                            <span>Modules</span>
                        </a>
                    </li>


                    <!-- QUIZZES -->
                    <li>
                        <a href="quiz.php" class="flex items-center px-6 py-4 rounded-2xl
                              text-pastel-text
                              hover:bg-pastel-card
                              hover:text-pastel-primary
                              hover:shadow-sm
                              transition-all duration-200">

                            <span>Quizzes</span>
                        </a>
                    </li>

                </ul>

            </div>


            <!-- RIGHT: PROFILE -->
            <div class="flex items-center flex-shrink-0">

                <button type="button" class="flex items-center gap-3
                    py-3 px-5
                    bg-pastel-card
                    border-2 border-pastel-primary/20
                    rounded-2xl
                    shadow-sm
                    hover:border-pastel-primary/50
                    hover:shadow-md
                    transition-all duration-200" <!-- Avatar -->
                    <div class="w-12 h-12 rounded-full
                            bg-pastel-badge
                            flex items-center justify-center
                            font-black text-pastel-text text-xl">

                        <?= strtoupper(substr($student['name'], 0, 1)) ?>

                    </div>


                    <!-- Name -->
                    <span class="text-xl font-bold text-pastel-text hidden sm:block">
                        <?= $student['name'] ?>
                    </span>


                    <!-- Dropdown Arrow -->
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">

                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="m19 9-7 7-7-7" />

                    </svg>

                </button>


                <!-- PROFILE DROPDOWN -->
                <div class="z-50 hidden
                       bg-pastel-card
                       border-2 border-pastel-nav
                       rounded-2xl
                       shadow-xl
                       w-60 mt-2" id="user-dropdown">

                    <div class="px-5 py-4 border-b-2 border-pastel-nav">

                        <span class="block text-lg font-bold text-pastel-text">
                            <?= $student['name'] ?>
                        </span>

                        <span class="block text-sm text-pastel-primary font-semibold mt-1">
                            Level <?= $student['level'] ?>
                        </span>

                    </div>


                    <ul class="p-2 text-lg text-pastel-text font-medium" aria-labelledby="user-menu-button">

                        <li>
                            <a href="#" class="block w-full px-4 py-3
                                  hover:bg-pastel-bg
                                  hover:text-pastel-primary
                                  rounded-xl
                                  transition-colors">

                                Profile

                            </a>
                        </li>

                        <li>
                            <a href="#" class="block w-full px-4 py-3
                                  hover:bg-pastel-bg
                                  hover:text-pastel-primary
                                  rounded-xl
                                  transition-colors">

                                Settings

                            </a>
                        </li>

                        <li>
                            <a href="#" class="block w-full px-4 py-3
                                  hover:bg-pastel-bg
                                  hover:text-pastel-primary
                                  rounded-xl
                                  transition-colors">

                                Sign out

                            </a>
                        </li>

                    </ul>

                </div>


                <!-- MOBILE MENU BUTTON -->
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center
                       p-3 ml-3
                       w-14 h-14
                       justify-center
                       text-pastel-primary
                       rounded-2xl
                       bg-pastel-card
                       border-2 border-pastel-primary/20
                       md:hidden
                       hover:bg-pastel-bg
                       focus:outline-none
                       focus:ring-2
                       focus:ring-pastel-primary/30" aria-controls="navbar-user" aria-expanded="false">

                    <span class="sr-only">Open main menu</span>

                    <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">

                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M5 7h14M5 12h14M5 17h14" />

                    </svg>

                </button>

            </div>

        </div>


        <!-- MOBILE MENU -->
        <div class="hidden w-full md:hidden
               px-5 pb-5
               absolute top-24 left-0
               bg-pastel-nav
               border-b-2 border-pastel-primary/20
               shadow-lg" id="navbar-user">

            <ul class="flex flex-col gap-2
                   text-lg font-bold
                   bg-pastel-card
                   p-4
                   rounded-2xl
                   border-2 border-pastel-nav
                   shadow-sm">

                <li>
                    <a href="index.php" class="block py-4 px-5
                          text-white
                          bg-pastel-primary
                          rounded-xl
                          shadow-sm">

                        Home

                    </a>
                </li>


                <li>
                    <a href="discussion.php" class="block py-4 px-5
                          text-pastel-text
                          rounded-xl
                          hover:bg-pastel-bg
                          hover:text-pastel-primary
                          transition-colors">

                        Discussion

                    </a>
                </li>


                <li>
                    <a href="module.php" class="block py-4 px-5
                          text-pastel-text
                          rounded-xl
                          hover:bg-pastel-bg
                          hover:text-pastel-primary
                          transition-colors">

                        Modules

                    </a>
                </li>


                <li>
                    <a href="quiz.php" class="block py-4 px-5
                          text-pastel-text
                          rounded-xl
                          hover:bg-pastel-bg
                          hover:text-pastel-primary
                          transition-colors">

                        Quizzes

                    </a>
                </li>

            </ul>

        </div>

    </nav>
    <div
        class="w-full max-w-[90rem] flex justify-between items-center mb-6 bg-pastel-card border border-pastel-nav px-8 py-5 rounded-3xl shadow-xl shadow-pastel-nav/30">
        <div class="flex items-center space-x-4">
            <div
                class="w-12 h-12 rounded-2xl bg-pastel-badge border border-pastel-nav flex items-center justify-center font-black text-pastel-text text-lg">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-lg font-black text-pastel-text">Ahoy, <?= $student['name'] ?>! 🏴‍☠️</h1>
                <p class="text-sm font-semibold text-pastel-primary">Ready to conquer your Year 4 Math Islands?</p>
            </div>
        </div>
        <div class="flex items-center space-x-5 text-base font-bold">
            <div
                class="bg-pastel-bg text-pastel-text px-4 py-2 rounded-2xl border border-pastel-nav flex items-center gap-2">
                ⭐ Level <?= $student['level'] ?></div>
            <div
                class="bg-pastel-bg text-pastel-text px-4 py-2 rounded-2xl border border-pastel-nav flex items-center gap-2">
                🪙 <?= $student['xp'] ?> XP</div>
        </div>
    </div>

    <div
        class="relative w-full max-w-[90rem] aspect-[16/8.5] rounded-3xl overflow-hidden border-8 border-pastel-card shadow-2xl shadow-pastel-nav bg-pastel-bg">

        <img src="/Quality-Education/src/student/map.jpeg" alt="Eduhunt Map"
            class="absolute inset-0 w-full h-full object-cover select-none z-0">

        <?php foreach ($islands as $id => $island):
            $status = $island['status'];

            $pinColor = "bg-slate-200 border-white text-slate-400 cursor-not-allowed shadow-md";
            $pulse = "";
            $statusLabel = "Locked 🔒";

            if ($status === 'beginner') {
                $pinColor = "bg-rose-100 border-rose-300 text-rose-700 hover:bg-rose-200 cursor-pointer hover:scale-110 shadow-rose-200/50 shadow-xl";
                $pulse = "animate-bounce";
                $statusLabel = "Beginner 🌱";
            } elseif ($status === 'pending') {
                $pinColor = "bg-amber-100 border-amber-300 text-amber-700 hover:bg-amber-200 cursor-pointer hover:scale-110 shadow-amber-200/50 shadow-xl";
                $pulse = "animate-bounce";
                $statusLabel = "Waiting for Teacher! ⏳";
            } elseif ($status === 'mastered') {
                $pinColor = "bg-emerald-500 border-white text-white hover:bg-emerald-400 cursor-pointer hover:scale-110 shadow-emerald-500/50 shadow-xl";
                $statusLabel = "Mastered! ✨";
            }
            ?>
            <div class="absolute transform -translate-x-1/2 -translate-y-1/2 z-30 <?= $pulse ?>"
                style="left: <?= $island['x'] ?>%; top: <?= $island['y'] ?>%;">

                <?php if ($status !== 'locked'): ?>
                    <a href="#"
                        onclick="alert('Embark on level <?= $id ?>: <?= $island['topic'] ?>! Status: <?= $statusLabel ?>'); return false;"
                        class="flex flex-col items-center group">
                        <div
                            class="w-16 h-16 rounded-3xl border-4 flex items-center justify-center font-black text-xl transition-all duration-200 <?= $pinColor ?>">
                            <?php if ($status === 'mastered'): ?>
                                ✓
                            <?php elseif ($status === 'pending'): ?>
                                ⏳
                            <?php else: ?>
                                <?= $id ?>
                            <?php endif; ?>
                        </div>
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/90 text-white text-sm font-bold px-4 py-2 rounded-2xl whitespace-nowrap mt-3 border border-slate-700 shadow-2xl z-40">
                            Island <?= $id ?>: <?= $island['name'] ?> (<span
                                class="text-pastel-primary"><?= $island['topic'] ?></span>) — <span
                                class="text-amber-300"><?= $statusLabel ?></span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="flex flex-col items-center group cursor-not-allowed">
                        <div
                            class="w-14 h-14 rounded-3xl border-4 flex items-center justify-center font-bold text-base <?= $pinColor ?>">
                            🔒
                        </div>
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/90 text-slate-400 text-sm font-bold px-4 py-2 rounded-2xl whitespace-nowrap mt-3 border border-slate-700 shadow-2xl z-40">
                            Island <?= $id ?>: Locked 🔒
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>

    <div
        class="mt-6 w-full max-w-[90rem] flex flex-col sm:flex-row justify-between items-center bg-pastel-card border border-pastel-nav px-8 py-4 rounded-2xl shadow-sm text-sm font-semibold text-pastel-text gap-4">
        <div class="text-pastel-text font-bold">🗺️ Island Adventure Legend:</div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center gap-2"><span
                    class="w-4 h-4 rounded-full bg-rose-200 border border-rose-300"></span> Beginner</div>
            <div class="flex items-center gap-2"><span
                    class="w-4 h-4 rounded-full bg-amber-200 border border-amber-300"></span> Pending Review</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-emerald-500"></span> Mastered
            </div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-slate-200"></span> Locked</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>

</html>