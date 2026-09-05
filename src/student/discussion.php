<?php
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduhunt - Discussion</title>
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

    <nav class="bg-pastel-nav fixed w-full h-24 z-50 top-0 start-0 border-b-2 border-pastel-primary/20 shadow-md flex items-center">
        <div class="w-full max-w-[90rem] mx-auto px-10 flex items-center justify-between">
            
            <a href="index.php" class="flex items-center gap-4 flex-shrink-0">
                <div class="bg-pastel-badge w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm">
                    <span class="text-3xl">📖</span>
                </div>
                <span class="text-3xl font-black tracking-wide text-pastel-text hidden lg:block">
                    Eduhunt
                </span>
            </a>

            <div class="hidden md:flex items-center justify-center flex-1 mx-8">
                <ul class="flex items-center gap-3 text-2xl font-bold">
                    <li>
                        <a href="index.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="discussion.php" class="flex items-center px-7 py-4 rounded-2xl bg-pastel-primary text-white shadow-md transition-all duration-200 hover:bg-pastel-hover hover:-translate-y-0.5">
                            <span>Discussion</span>
                        </a>
                    </li>
                    <li>
                        <a href="module.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Modules</span>
                        </a>
                    </li>
                    <li>
                        <a href="quiz.php" class="flex items-center px-6 py-4 rounded-2xl text-pastel-text hover:bg-pastel-card hover:text-pastel-primary hover:shadow-sm transition-all duration-200">
                            <span>Quizzes</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="flex items-center flex-shrink-0 relative">
                <button type="button" class="flex items-center gap-3 py-3 px-5 bg-pastel-card border-2 border-pastel-primary/20 rounded-2xl shadow-sm hover:border-pastel-primary/50 hover:shadow-md transition-all duration-200" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-12 h-12 rounded-full bg-pastel-badge flex items-center justify-center font-black text-pastel-text text-xl">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <span class="text-xl font-bold text-pastel-text hidden sm:block">
                        <?= $student['name'] ?>
                    </span>
                    <svg class="w-5 h-5 text-pastel-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" />
                    </svg>
                </button>

                <div class="z-50 hidden bg-pastel-card border-2 border-pastel-nav rounded-2xl shadow-xl w-60 mt-2" id="user-dropdown">
                    <div class="px-5 py-4 border-b-2 border-pastel-nav">
                        <span class="block text-lg font-bold text-pastel-text"><?= $student['name'] ?></span>
                        <span class="block text-sm text-pastel-primary font-semibold mt-1">Level <?= $student['level'] ?></span>
                    </div>
                    <ul class="p-2 text-lg text-pastel-text font-medium" aria-labelledby="user-menu-button">
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Profile</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Settings</a></li>
                        <li><a href="#" class="block w-full px-4 py-3 hover:bg-pastel-bg hover:text-pastel-primary rounded-xl transition-colors">Sign out</a></li>
                    </ul>
                </div>

                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-3 ml-3 w-14 h-14 justify-center text-pastel-primary rounded-2xl bg-pastel-card border-2 border-pastel-primary/20 md:hidden hover:bg-pastel-bg focus:outline-none focus:ring-2 focus:ring-pastel-primary/30" aria-controls="navbar-user" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 7h14M5 12h14M5 17h14" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="hidden w-full md:hidden px-5 pb-5 absolute top-24 left-0 bg-pastel-nav border-b-2 border-pastel-primary/20 shadow-lg" id="navbar-user">
            <ul class="flex flex-col gap-2 text-lg font-bold bg-pastel-card p-4 rounded-2xl border-2 border-pastel-nav shadow-sm">
                <li><a href="index.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Home</a></li>
                <li><a href="discussion.php" class="block py-4 px-5 text-white bg-pastel-primary rounded-xl shadow-sm">Discussion</a></li>
                <li><a href="module.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Modules</a></li>
                <li><a href="quiz.php" class="block py-4 px-5 text-pastel-text rounded-xl hover:bg-pastel-bg hover:text-pastel-primary transition-colors">Quizzes</a></li>
            </ul>
        </div>
    </nav>

    <div class="w-full max-w-[90rem] flex flex-col gap-6">
        <div class="bg-pastel-card border border-pastel-nav px-8 py-8 rounded-3xl shadow-xl shadow-pastel-nav/30">
            <h1 class="text-2xl font-black text-pastel-text">💬 Discussion Board</h1>
            <p class="text-pastel-primary font-semibold mt-1">Connect with your peers and ask questions about your math adventures!</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>