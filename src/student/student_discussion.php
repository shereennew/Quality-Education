<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Session / Active Student Handling
$student_id = 3;
$stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$current_student = $stmt_student->fetch();

if (!$current_student) {
    $current_student = ['id' => 1, 'name' => 'Amina Yusuf', 'role' => 'student'];
}

$active_tab = $_GET['page'] ?? 'discussion';
$nav_items = [
    'home'        => ['label' => 'Home',        'url' => 'student_dashboard.php'],
    'module'      => ['label' => 'Modules',     'url' => 'module.php'],
    'quiz'        => ['label' => 'Quizzes',     'url' => 'quiz.php'],
    'discussion'  => ['label' => 'Discussion',  'url' => 'student_discussion.php'],
    'math_helper' => ['label' => 'Math Helper', 'url' => 'math_helper.php']
];

// Helper function to handle image uploads
function handleImageUpload($fileInputName) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
        $fileName = $_FILES[$fileInputName]['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return '../uploads/' . $newFileName;
            }
        }
    }
    return null;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action A: Create Post
    if (isset($_POST['action']) && $_POST['action'] === 'create_post') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $imageUrl = handleImageUpload('post_image');

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("INSERT INTO discussion_posts (student_id, title, content, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$current_student['id'], $title, $content, $imageUrl]);

            header("Location: student_discussion.php");
            exit;
        }
    }

    // Action B: Create Reply
    if (isset($_POST['action']) && $_POST['action'] === 'create_reply') {
        $post_id = (int)($_POST['post_id'] ?? 0);
        $reply_content = trim($_POST['reply_content'] ?? '');
        $imageUrl = handleImageUpload('reply_image');

        if ($post_id > 0 && (!empty($reply_content) || $imageUrl !== null)) {
            $stmt = $pdo->prepare("INSERT INTO discussion_replies (post_id, student_id, content, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$post_id, $current_student['id'], $reply_content, $imageUrl]);

            header("Location: student_discussion.php");
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// Fetch Active Teacher Announcements (Dynamic DB Fetch)
// -----------------------------------------------------------------------------
$announcementsStmt = $pdo->query("
    SELECT id, title, content, created_at 
    FROM announcements 
    WHERE is_active = 1 
    ORDER BY created_at DESC
");
$announcements = $announcementsStmt->fetchAll();

// Fetch Discussions & Replies
$postsStmt = $pdo->query("
    SELECT p.id, p.title, p.content, p.image_url, p.created_at, s.name as author, s.role 
    FROM discussion_posts p 
    LEFT JOIN students s ON p.student_id = s.id 
    ORDER BY p.created_at DESC
");
$discussions = $postsStmt->fetchAll();

foreach ($discussions as &$post) {
    $repliesStmt = $pdo->prepare("
        SELECT r.id, r.content, r.image_url, r.created_at, s.name as author, s.role 
        FROM discussion_replies r 
        LEFT JOIN students s ON r.student_id = s.id 
        WHERE r.post_id = ? 
        ORDER BY r.created_at ASC
    ");
    $repliesStmt->execute([$post['id']]);
    $post['replies'] = $repliesStmt->fetchAll();
}
unset($post);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Discussion - <?= htmlspecialchars($current_student['name']) ?></title>
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

    <nav class="bg-pastel-nav shadow-sm border-b border-blue-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3 pr-4 border-r border-blue-200">
                    <div class="w-9 h-9 rounded-full bg-pastel-primary text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <?= strtoupper(substr($current_student['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <span class="font-semibold text-pastel-text text-sm block leading-tight">
                            <?= htmlspecialchars($current_student['name']) ?>
                        </span>
                        <span class="text-[10px] text-slate-500 font-medium capitalize">
                            <?= htmlspecialchars($current_student['role'] ?? 'student') ?>
                        </span>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <?php foreach ($nav_items as $key => $item): ?>
                        <a href="<?= $item['url'] ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $key === $active_tab ? 'bg-pastel-primary text-white shadow-sm' : 'text-pastel-text hover:bg-white/60' ?>">
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center space-x-3">
                    <span class="text-xs bg-pastel-badge text-pastel-hover font-semibold px-3 py-1.5 rounded-full whitespace-nowrap">Grade 5 Math</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Pinned Teacher Announcements Section -->
        <?php if (!empty($announcements)): ?>
            <div class="space-y-4 mb-6">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-400 p-5 rounded-2xl shadow-xs relative">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="bg-amber-400 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">Pinned Announcement</span>
                                <span class="text-[11px] text-amber-700 font-medium"><?= htmlspecialchars($announcement['created_at']) ?></span>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold text-amber-900 mb-1"><?= htmlspecialchars($announcement['title']) ?></h3>
                        <p class="text-xs text-amber-800 leading-relaxed"><?= htmlspecialchars($announcement['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Forum Header -->
        <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm mb-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Classroom Forum</span>
                <h1 class="text-2xl font-bold text-pastel-text mt-1">Student Q&A</h1>
                <p class="text-sm text-slate-500 mt-0.5">Need help with math? Ask a question or help out a classmate!</p>
            </div>
            <button onclick="toggleModal()" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold px-5 py-3 rounded-xl transition shadow-sm flex items-center gap-2">
                <span>+ Ask Question</span>
            </button>
        </div>

        <!-- Discussion Thread List -->
        <div class="space-y-6">
            <?php if (empty($discussions)): ?>
                <div class="bg-pastel-card p-12 text-center rounded-2xl border border-blue-100 shadow-sm">
                    <p class="text-slate-400 text-sm">No questions asked yet. Click <strong>+ Ask Question</strong> to start one!</p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $post): ?>
                    <div class="bg-pastel-card p-6 rounded-2xl border border-blue-100 shadow-sm">
                        
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-pastel-primary font-bold text-xs flex items-center justify-center">
                                    <?= strtoupper(substr($post['author'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-pastel-text">
                                            <?= htmlspecialchars($post['author'] ?? 'Deleted Student') ?>
                                        </span>
                                        <?php if (($post['role'] ?? '') === 'teacher'): ?>
                                            <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-md">Teacher</span>
                                        <?php else: ?>
                                            <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md">Student</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($post['created_at']) ?></span>
                                </div>
                            </div>
                        </div>

                        <h2 class="text-base font-bold text-pastel-text mb-2"><?= htmlspecialchars($post['title']) ?></h2>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed"><?= htmlspecialchars($post['content']) ?></p>

                        <?php if (!empty($post['image_url'])): ?>
                            <div class="mb-6">
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Question attachment" class="max-h-72 rounded-xl border border-slate-200 shadow-xs object-cover">
                            </div>
                        <?php endif; ?>

                        <!-- Answers Section -->
                        <div class="space-y-3 bg-pastel-bg p-4 rounded-xl border border-blue-50 mb-4">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Answers & Discussion (<?= count($post['replies']) ?>)
                            </h3>

                            <?php if (empty($post['replies'])): ?>
                                <p class="text-xs text-slate-400 italic">No answers yet. Help <?= htmlspecialchars($post['author'] ?? 'them') ?> out!</p>
                            <?php else: ?>
                                <?php foreach ($post['replies'] as $reply): ?>
                                    <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-xs">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-semibold text-xs text-pastel-text">
                                                <?= htmlspecialchars($reply['author'] ?? 'Deleted Student') ?>
                                            </span>
                                            <?php if (($reply['role'] ?? '') === 'teacher'): ?>
                                                <span class="bg-amber-100 text-amber-700 text-[9px] font-bold px-1.5 py-0.2 rounded">Teacher</span>
                                            <?php else: ?>
                                                <span class="bg-blue-100 text-blue-700 text-[9px] font-bold px-1.5 py-0.2 rounded">Student</span>
                                            <?php endif; ?>
                                            <span class="text-[10px] text-slate-400 ml-auto"><?= htmlspecialchars($reply['created_at']) ?></span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-normal mb-2"><?= htmlspecialchars($reply['content']) ?></p>

                                        <?php if (!empty($reply['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($reply['image_url']) ?>" alt="Reply attachment" class="max-h-48 rounded-lg border border-slate-200 mt-2 object-cover">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Reply Form -->
                        <form action="student_discussion.php" method="POST" enctype="multipart/form-data" class="space-y-2">
                            <input type="hidden" name="action" value="create_reply">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <div class="flex gap-2">
                                <input type="text" name="reply_content" placeholder="Write a reply or answer..." class="flex-1 text-xs bg-pastel-bg px-3 py-2.5 rounded-xl border border-blue-100 focus:outline-none focus:border-pastel-primary">
                                <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
                                    Reply
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-[11px] text-slate-500 font-semibold cursor-pointer hover:text-pastel-primary flex items-center gap-1">
                                    <span>📎 Attach Photo (Optional):</span>
                                    <input type="file" name="reply_image" accept="image/*" class="text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-pastel-primary hover:file:bg-blue-100 cursor-pointer">
                                </label>
                            </div>
                        </form>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal Form: Ask Question -->
    <div id="question-modal" class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-blue-100 overflow-hidden">
            <div class="bg-pastel-nav p-4 border-b border-blue-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-pastel-text">Ask a New Question</h3>
                <button onclick="toggleModal()" class="text-slate-400 hover:text-slate-600 text-base font-bold">✕</button>
            </div>
            
            <form action="student_discussion.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_post">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Topic / Title</label>
                    <input type="text" name="title" required placeholder="e.g. How do I simplify 12/16?" class="w-full text-xs bg-pastel-bg p-3 rounded-xl border border-blue-100 focus:outline-none focus:border-pastel-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Details</label>
                    <textarea name="content" rows="4" required placeholder="Describe what you are confused about..." class="w-full text-xs bg-pastel-bg p-3 rounded-xl border border-blue-100 focus:outline-none focus:border-pastel-primary"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach Picture (Optional)</label>
                    <input type="file" name="post_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-pastel-primary hover:file:bg-blue-100 cursor-pointer">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold px-5 py-2 rounded-xl transition shadow-sm">Post Question</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal() {
            document.getElementById('question-modal').classList.toggle('hidden');
        }
    </script>

</body>
</html>