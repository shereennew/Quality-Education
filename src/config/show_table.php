<?php
// show_table.php
require_once __DIR__ . '/db.php';

// Fetch students and their classrooms from SQLite
$stmt = $pdo->query("
    SELECT s.name AS student_name, s.status, c.name AS classroom_name 
    FROM students s 
    JOIN classrooms c ON s.classroom_id = c.id
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Table View - EduPulse</title>
    <!-- Auto-refreshes the page every 10 seconds -->
    <meta http-equiv="refresh" content="10">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-8 font-sans">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Live Database Table View</h1>
                <p class="text-xs text-slate-500 mt-0.5">Auto-refreshes every 10 seconds (or click manual refresh)</p>
            </div>
            <button onclick="location.reload();" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition font-medium shadow-sm">
                🔄 Refresh Now
            </button>
        </div>

        <div class="overflow-x-auto border border-slate-100 rounded-lg">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-4">Student Name</th>
                        <th class="py-3 px-4">Classroom</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (count($rows) > 0): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-medium text-slate-800"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td class="py-3 px-4 text-slate-600"><?php echo htmlspecialchars($row['classroom_name']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 text-xs rounded-full font-medium bg-indigo-50 text-indigo-700">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-6 text-center text-slate-400">No records found. Please run setup.php first.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>