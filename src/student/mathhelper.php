<?php
// Mock student data for UI prototyping
$student = [
    'name' => 'Aina',
    'level' => 4,
    'xp' => 320
];

// Chapter notes data aligned with the 7 math topics from the map with fun island themes
$chapters = [
    1 => [
        'title' => 'Ancient Pyramid',
        'topic' => 'Arithmetic',
        'badge' => 'Mastered! ✨',
        'badge_color' => 'bg-emerald-100 text-emerald-700',
        'summary' => 'Unlock ancient secrets with giant numbers up to 100,000!',
        'key_points' => [
            'Super-powered addition and subtraction with magical regrouping.',
            'Multiplying and dividing giant treasures by single & double digits.',
            'Following the royal Secret Code rules (BODMAS/PEMDAS).'
        ]
    ],
    2 => [
        'title' => 'Cherry Blossom Valley',
        'topic' => 'Multiplication',
        'badge' => 'Mastered! ✨',
        'badge_color' => 'bg-emerald-100 text-emerald-700',
        'summary' => 'Speed-multiply through petal storms and magical arrays!',
        'key_points' => [
            'Turbo-charging numbers by 10, 100, and 1,000 in a flash.',
            'Building towering vertical towers of 3-digit by 2-digit multiplication.',
            'Spotting secret number twins (Multiples and Common Factors).'
        ]
    ],
    3 => [
        'title' => 'Volcanic Jungle',
        'topic' => 'Fractions',
        'badge' => 'Current Quest! 🌋',
        'badge_color' => 'bg-sky-100 text-sky-700 font-bold animate-pulse',
        'summary' => 'Slice up lava-cakes and master parts of a whole!',
        'key_points' => [
            'Transforming sneaky mixed numbers into wild improper fractions.',
            'Combining and subtracting delicious fraction slices with friendly denominators.',
            'Hunting down exact fraction portions of a giant chest of gold.'
        ]
    ],
    4 => [
        'title' => 'Hidden Cove',
        'topic' => 'Geometry',
        'badge' => 'Locked 🔒',
        'badge_color' => 'bg-slate-100 text-slate-500',
        'summary' => 'Discover secret pirate maps, shapes, and hidden angles!',
        'key_points' => [
            'Finding secret mirror lines (Lines of Symmetry) on pirate flags.',
            'Spotting sharp acute, wide obtuse, and perfect right angles.',
            'Walking the full perimeter and measuring the buried treasure area.'
        ]
    ],
    5 => [
        'title' => 'Waterfall Cliffs',
        'topic' => 'Measurement',
        'badge' => 'Locked 🔒',
        'badge_color' => 'bg-slate-100 text-slate-500',
        'summary' => 'Leap across rushing waterfalls using length, weight, and volume!',
        'key_points' => [
            'Magical unit portal jumping (kilometers to meters, grams to kilograms).',
            'Beating the ticking clock with wild time-travel word puzzles.',
            'Mastering pirate coin calculations and treasure chest budgets.'
        ]
    ],
    6 => [
        'title' => 'Frozen Igloo',
        'topic' => 'Data Handling',
        'badge' => 'Locked 🔒',
        'badge_color' => 'bg-slate-100 text-slate-500',
        'summary' => 'Track penguin footprints and build ice-bar charts!',
        'key_points' => [
            'Reading crystal-clear bar graphs and picture-charts.',
            'Building secret tally charts out of wild snow-monster sightings.',
            'Hunting for the most popular ice-cream flavor (Mode & Mean averages).'
        ]
    ],
    7 => [
        'title' => 'Desert Treasure',
        'topic' => 'Word Problems',
        'badge' => 'Locked 🔒',
        'badge_color' => 'bg-slate-100 text-slate-500',
        'summary' => 'Solve the ultimate pyramid puzzles to claim the golden crown!',
        'key_points' => [
            'The 4 Legendary Hero Steps: Understand, Plan, Solve, Check!',
            'Translating confusing jungle riddles into clean math spells.',
            'Double-checking your magic spells with estimation and reverse power.'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality-Education - Island Spellbooks!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-[#f0f7fc] text-slate-800 min-h-screen flex flex-col items-center p-6 pt-28">

    <nav class="bg-white fixed w-full h-20 z-20 top-0 start-0 border-b border-sky-100 shadow-sm flex items-center">
      <div class="max-w-[90rem] w-full mx-auto px-4 flex items-center justify-between">
        
        <a href="#" class="flex items-center space-x-3">
            <span class="bg-sky-100 text-sky-700 p-2.5 rounded-2xl text-lg font-bold">📖</span>
            <span class="self-center text-xl text-sky-900 font-black whitespace-nowrap hidden sm:inline">Quality-Education</span>
        </a>

        <div class="hidden md:flex absolute left-1/2 transform -translate-x-1/2 items-center justify-center">
          <ul class="font-bold flex space-x-10 text-lg text-slate-700">
            <li>
              <a href="index.php" class="hover:text-sky-500 transition-colors">Home</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Announcements'); return false;" class="hover:text-sky-500 transition-colors">Announcements</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="hover:text-sky-500 transition-colors">Modules</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Quizzes'); return false;" class="hover:text-sky-500 transition-colors">Quizzes</a>
            </li>
            <li>
              <a href="mathhelper.php" class="text-sky-600 hover:text-sky-500 transition-colors">Math Helper</a>
            </li>
          </ul>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" class="flex text-sm bg-sky-100 border-2 border-sky-200 rounded-full focus:ring-4 focus:ring-sky-100" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
              <span class="sr-only">Open user menu</span>
              <div class="w-11 h-11 rounded-full flex items-center justify-center font-black text-sky-700 text-base">
                  <?= strtoupper(substr($student['name'], 0, 1)) ?>
              </div>
            </button>
            
            <div class="z-50 hidden bg-white border border-sky-100 rounded-2xl shadow-xl w-48" id="user-dropdown">
              <div class="px-4 py-3 text-base border-b border-sky-100">
                <span class="block text-sky-900 font-bold"><?= $student['name'] ?></span>
              </div>
              <ul class="p-2 text-base text-slate-700 font-medium" aria-labelledby="user-menu-button">
                <li>
                  <a href="#" class="block w-full p-2.5 hover:bg-sky-50 hover:text-sky-700 rounded-xl">Profile</a>
                </li>
                <li>
                  <a href="#" class="block w-full p-2.5 hover:bg-sky-50 hover:text-sky-700 rounded-xl">Settings</a>
                </li>
                <li>
                  <a href="#" class="block w-full p-2.5 hover:bg-sky-50 hover:text-sky-700 rounded-xl">Sign out</a>
                </li>
              </ul>
            </div>

            <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2.5 w-11 h-11 justify-center text-sky-600 rounded-xl md:hidden hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-200" aria-controls="navbar-user" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
            </button>
        </div>
      </div>

      <div class="items-center justify-between hidden w-full md:hidden px-4 pb-4 absolute top-20 left-0 bg-white border-b border-sky-100 shadow-md" id="navbar-user">
        <ul class="font-bold flex flex-col space-y-2 text-base text-slate-700 bg-sky-50 p-4 rounded-2xl border border-sky-100 my-2">
          <li>
            <a href="index.php" class="block py-2.5 px-3 rounded-xl hover:bg-white">Home</a>
          </li>
          <li>
            <a href="#" onclick="alert('Opening Announcements'); return false;" class="block py-2.5 px-3 rounded-xl hover:bg-white">Announcements</a>
          </li>
          <li>
            <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="block py-2.5 px-3 rounded-xl hover:bg-white">Modules</a>
          </li>
          <li>
            <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="block py-2.5 px-3 rounded-xl hover:bg-white">Modules</a>
          </li>
          <li>
            <a href="mathhelper.php" class="block py-2.5 px-3 text-sky-600 bg-white rounded-xl shadow-sm">Math Helper</a>
          </li>
        </ul>
      </div>
    </nav>

    <div class="w-full max-w-[90rem] mt-2 flex justify-between items-center mb-6 bg-white border border-sky-100 px-8 py-5 rounded-3xl shadow-xl shadow-sky-100/30">
        <div class="flex items-center space-x-5">
            <div class="w-14 h-14 rounded-2xl bg-sky-100 border border-sky-200 flex items-center justify-center font-black text-sky-700 text-xl">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-sky-900">Ahoy, <?= $student['name'] ?>'s Spellbooks! 📜</h1>
                <p class="text-base font-semibold text-sky-600">Review your Island Knowledge Scrolls before your next adventure!</p>
            </div>
        </div>
        <div class="flex items-center space-x-6 text-lg font-bold">
            <div class="bg-sky-50 text-sky-800 px-5 py-3 rounded-2xl border border-sky-100 flex items-center gap-2.5">⭐ Level <?= $student['level'] ?></div>
            <div class="bg-sky-50 text-sky-800 px-5 py-3 rounded-2xl border border-sky-100 flex items-center gap-2.5">🪙 <?= $student['xp'] ?> XP</div>
        </div>
    </div>

    <div class="w-full max-w-[90rem] grid grid-cols-1 xl:grid-cols-2 gap-8 mb-12">
        <?php foreach ($chapters as $id => $chap): ?>
            <div class="items-stretch bg-white border-2 border-sky-100 rounded-3xl shadow-xl shadow-sky-100/40 sm:flex hover:border-sky-300 transition-all overflow-hidden">
                <div class="w-full sm:w-2/5 bg-sky-50/70 p-8 flex flex-col justify-center items-center text-center sm:border-r border-sky-100">
                    <span class="text-sm font-black uppercase tracking-wider bg-white text-sky-600 px-4 py-1.5 rounded-2xl border border-sky-200 shadow-sm mb-4">
                        🏝️ Chapter 0<?= $id ?>
                    </span>
                    <h3 class="text-2xl font-black text-sky-900 mb-3 leading-snug">
                        <?= $chap['title'] ?>
                    </h3>
                    <span class="text-sm px-4 py-1.5 rounded-2xl font-bold shadow-sm <?= $chap['badge_color'] ?>"><?= $chap['badge'] ?></span>
                </div>

                <div class="p-8 flex-1 flex flex-col justify-between bg-white">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-bold text-sky-700 bg-sky-50 px-3 py-1 rounded-xl border border-sky-100"><?= $chap['topic'] ?></span>
                        </div>
                        <p class="text-sm sm:text-base font-medium text-slate-600 mb-5 leading-relaxed">
                            <?= $chap['summary'] ?>
                        </p>
                        
                        <div class="bg-sky-50/70 border border-sky-100 rounded-2xl p-5 mb-6">
                            <p class="text-xs font-bold uppercase tracking-wider text-sky-800 mb-3 flex items-center gap-1.5">
                                <span>🔑</span> Key Spells:
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ($chap['key_points'] as $point): ?>
                                    <li class="text-sm font-semibold text-slate-700 flex items-start gap-2 leading-relaxed">
                                        <span class="text-sky-500 leading-none">•</span>
                                        <span><?= $point ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-sky-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-sky-400">Scroll #0<?= $id ?></span>
                        <button onclick="alert('Unfurling the magical scroll for Island <?= $id ?>: <?= $chap['title'] ?>!'); return false;" class="px-5 py-2.5 bg-sky-100 hover:bg-sky-500 hover:text-white text-sky-800 text-sm font-black rounded-xl border border-sky-200 transition-all shadow-md">
                            📜 Open Scroll →
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-auto text-center text-base font-bold text-sky-800/60 pb-8">
        Quality-Education Island Adventure Portal • Ready for Learning! 🏴‍☠️
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>