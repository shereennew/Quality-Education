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
    <title>Quality-Education - Island Math Adventure!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-[#f0f7fc] text-slate-800 min-h-screen flex flex-col items-center justify-center p-6 pt-28">

    <nav class="bg-white fixed w-full h-20 z-20 top-0 start-0 border-b border-sky-100 shadow-sm flex items-center">
      <div class="max-w-[90rem] w-full mx-auto px-6 flex items-center justify-between">
        
        <a href="#" class="flex items-center space-x-3">
            <span class="bg-sky-100 text-sky-700 p-2.5 rounded-2xl text-lg font-bold">📖</span>
            <span class="self-center text-xl text-sky-900 font-black whitespace-nowrap hidden sm:inline">Quality-Education</span>
        </a>

        <div class="hidden md:flex absolute left-1/2 transform -translate-x-1/2 items-center justify-center">
          <ul class="font-bold flex space-x-10 text-lg text-slate-700">
            <li>
              <a href="index.php" class="text-sky-600 hover:text-sky-500 transition-colors">Home</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Announcements'); return false;" class="hover:text-sky-500 transition-colors">Announcements</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="hover:text-sky-500 transition-colors">Modules</a>
            </li>
            <li>
              <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="hover:text-sky-500 transition-colors">Quizzes</a>
            </li>
            <li>
              <a href="mathhelper.php" class="hover:text-sky-500 transition-colors">Math Helper</a>
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
            <a href="index.php" class="block py-2.5 px-3 text-sky-600 bg-white rounded-xl shadow-sm">🏠 Home</a>
          </li>
          <li>
            <a href="#" onclick="alert('Opening Announcements'); return false;" class="block py-2.5 px-3 rounded-xl hover:bg-white">📢 Announcements</a>
          </li>
          <li>
            <a href="#" onclick="alert('Opening Teacher Contact'); return false;" class="block py-2.5 px-3 rounded-xl hover:bg-white">📱 Teacher Contact</a>
          </li>
          <li>
            <a href="mathhelper.php" class="block py-2.5 px-3 rounded-xl hover:bg-white">📚 Math Helper</a>
          </li>
        </ul>
      </div>
    </nav>

    <div class="w-full max-w-[90rem] flex justify-between items-center mb-6 bg-white border border-sky-100 px-8 py-5 rounded-3xl shadow-xl shadow-sky-100/30">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-100 border border-sky-200 flex items-center justify-center font-black text-sky-700 text-lg">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-lg font-black text-sky-900">Ahoy, <?= $student['name'] ?>! 🏴‍☠️</h1>
                <p class="text-sm font-semibold text-sky-600">Ready to conquer your Year 4 Math Islands?</p>
            </div>
        </div>
        <div class="flex items-center space-x-5 text-base font-bold">
            <div class="bg-sky-50 text-sky-800 px-4 py-2 rounded-2xl border border-sky-100 flex items-center gap-2">⭐ Level <?= $student['level'] ?></div>
            <div class="bg-sky-50 text-sky-800 px-4 py-2 rounded-2xl border border-sky-100 flex items-center gap-2">🪙 <?= $student['xp'] ?> XP</div>
        </div>
    </div>

    <div class="relative w-full max-w-[90rem] aspect-[16/8.5] rounded-3xl overflow-hidden border-8 border-white shadow-2xl shadow-sky-200 bg-sky-50">
        
        <img src="/Quality-Education/src/student/map.jpeg" alt="Quality-Education Map" class="absolute inset-0 w-full h-full object-cover select-none z-0">

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
                    <a href="#" onclick="alert('Embark on level <?= $id ?>: <?= $island['topic'] ?>! Status: <?= $statusLabel ?>'); return false;" class="flex flex-col items-center group">
                        <div class="w-16 h-16 rounded-3xl border-4 flex items-center justify-center font-black text-xl transition-all duration-200 <?= $pinColor ?>">
                            <?php if ($status === 'mastered'): ?>
                                ✓
                            <?php elseif ($status === 'pending'): ?>
                                ⏳
                            <?php else: ?>
                                <?= $id ?>
                            <?php endif; ?>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/90 text-white text-sm font-bold px-4 py-2 rounded-2xl whitespace-nowrap mt-3 border border-slate-700 shadow-2xl z-40">
                            Island <?= $id ?>: <?= $island['name'] ?> (<span class="text-sky-300"><?= $island['topic'] ?></span>) — <span class="text-amber-300"><?= $statusLabel ?></span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="flex flex-col items-center group cursor-not-allowed">
                        <div class="w-14 h-14 rounded-3xl border-4 flex items-center justify-center font-bold text-base <?= $pinColor ?>">
                            🔒
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/90 text-slate-400 text-sm font-bold px-4 py-2 rounded-2xl whitespace-nowrap mt-3 border border-slate-700 shadow-2xl z-40">
                            Island <?= $id ?>: Locked 🔒
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>

    <div class="mt-6 w-full max-w-[90rem] flex flex-col sm:flex-row justify-between items-center bg-white border border-sky-100 px-8 py-4 rounded-2xl shadow-sm text-sm font-semibold text-slate-700 gap-4">
        <div class="text-sky-900 font-bold">🗺️ Island Adventure Legend:</div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-rose-200 border border-rose-300"></span> Beginner</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-amber-200 border border-amber-300"></span> Pending Review</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-emerald-500"></span> Mastered</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-slate-200"></span> Locked</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>