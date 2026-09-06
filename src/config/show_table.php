<?php
// show_all_tables.php
require_once __DIR__ . '/db.php';

try {
    // Fetch all table names from SQLite metadata
    $tablesStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $databaseData = [];
    foreach ($tables as $table) {
        $safeTable = '"' . str_replace('"', '""', $table) . '"';
        $stmt = $pdo->query("SELECT * FROM $safeTable");
        $databaseData[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $db_error = null;
} catch (PDOException $e) {
    $tables = [];
    $databaseData = [];
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Database Tables - EduPulse</title>
    <meta http-equiv="refresh" content="10">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-8 font-sans">
    <div class="max-w-6xl mx-auto space-y-8">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-slate-800">EduPulse Database Inspector</h1>
                <p class="text-xs text-slate-500 mt-0.5">Auto-refreshes every 10 seconds • Displaying all active tables</p>
            </div>
            <button onclick="location.reload();" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition font-medium shadow-sm">
                🔄 Refresh All
            </button>
        </div>

        <?php if ($db_error): ?>
            <div class="p-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                <?php echo htmlspecialchars($db_error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tables)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-400">
                No tables found in the database. Please run setup.php first.
            </div>
        <?php else: ?>
            <?php foreach ($tables as $table): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800 font-mono">
                            📂 <?php echo htmlspecialchars($table); ?>
                        </h2>
                        <span class="text-xs px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full font-medium">
                            <?php echo count($databaseData[$table]); ?> rows
                        </span>
                    </div>

                    <div class="overflow-x-auto border border-slate-100 rounded-lg">
                        <table class="w-full text-left border-collapse">
                            <?php $rows = $databaseData[$table]; ?>
                            <?php if (count($rows) > 0): ?>
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-100">
                                        <?php foreach (array_keys($rows[0]) as $column): ?>
                                            <th class="py-3 px-4"><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <?php foreach ($rows as $row): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <?php foreach ($row as $value): ?>
                                                <td class="py-3 px-4 text-slate-600">
                                                    <?php echo htmlspecialchars($value ?? 'NULL'); ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            <?php else: ?>
                                <tbody>
                                    <tr>
                                        <td class="py-6 text-center text-slate-400 text-sm">Table is empty.</td>
                                    </tr>
                                </tbody>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>