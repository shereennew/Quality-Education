<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Session / Active Student Handling
$student_id = 3;
$stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$current_student = $stmt_student->fetch();

if (!$current_student) {
    $current_student = [
        'id' => 1, 
        'name' => 'Aina', 
        'role' => 'student',
        'level' => 4,
        'xp' => 320
    ];
} else {
    // Ensure backwards compatibility keys exist if pulled from DB
    $current_student['level'] = $current_student['level'] ?? 4;
    $current_student['xp'] = $current_student['xp'] ?? 320;
}

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

            header("Location: discussion.php");
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

            header("Location: discussion.php");
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
    <title>EduHunt - Discussion</title>
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

    <style>
        /* EXACT navbar styling copied from current index.php */


        .main-navbar {

            height: 96px;

            background: #e1e9f5;

            border-bottom:
                1px solid rgba(125, 160, 202, .25);

            box-shadow:
                0 3px 15px rgba(75, 100, 130, .10);

            position: sticky;

            top: 0;

            z-index: 1000;

            display: flex;

            align-items: center;
            width: 100%;
            align-self: stretch;
        }


        .nav-inner {

            width: 100%;

            max-width: 1500px;

            margin: auto;

            padding: 0 45px;

            display: grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items: center;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 13px;

            text-decoration: none;

            color: #2c3e50;
        }


        .brand-icon {

            width: 53px;
            height: 53px;

            border-radius: 16px;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;

            font-weight: 900;
        }


        .brand-name {

            font-size: 30px;

            font-weight: 900;
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .nav-links a {

            text-decoration: none;

            color: #2c3e50;

            padding: 13px 24px;

            border-radius: 15px;

            font-size: 18px;

            font-weight: 800;

            transition: .2s;
        }


        .nav-links a:hover {

            background: #cbd9eb;
        }


        .nav-links a.active {

            background: #7da0ca;

            color: white;
        }


        .profile-area {

            display: flex;

            justify-content: flex-end;

            position: relative;
        }


        .profile-button {

            border:
                1px solid rgba(125, 160, 202, .35);

            background: white;

            border-radius: 999px;

            padding:
                7px 16px 7px 8px;

            display: flex;

            align-items: center;

            gap: 10px;

            cursor: default;

            color: #2c3e50;

            font-weight: 800;

            font-size: 16px;
        }


        .avatar {

            width: 44px;
            height: 44px;

            border-radius: 50%;

            background: #cbe0f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            font-weight: 900;
        }



        
        @media (max-width: 1000px) {
            .nav-inner {
                grid-template-columns:
                    auto 1fr;
            }

            .nav-links {
                display: none;
            }

            .profile-area {
                justify-self: end;
            }
        }

        @media (max-width: 650px) {
            .brand-name {
                font-size: 23px;
            }

            .profile-name {
                display: none;
            }
        }

    </style>

</head>
<body class="bg-pastel-bg text-pastel-text min-h-screen flex flex-col items-center justify-start">

    <nav class="main-navbar">

        <div class="nav-inner">


            <a href="index.php" class="brand">

                <div class="brand-icon">
                    E
                </div>

                <div class="brand-name">
                    EduHunt
                </div>

            </a>


            <div class="nav-links">

                <a href="index.php">
                    Home
                </a>

                <a href="discussion.php" class="active">
                    Discussion
                </a>

                <a href="module.php">
                    Modules
                </a>

                <a href="mathhelper.php">
                    Math Helper
                </a>

                <a href="history.php">
                    History
                </a>

            </div>


            <div class="profile-area">
                <div class="profile-button">
                    <div class="avatar">
                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $current_student['name'],
                                    0,
                                    1
                                )
                            )
                        ) ?>
                    </div>

                    <span class="profile-name">
                        <?= htmlspecialchars(
                            $current_student['name']
                        ) ?>
                    </span>
                </div>
            </div>

        </div>

    </nav>

    <main class="w-full max-w-[85rem] flex flex-col gap-6 p-4">
        
        <div class="bg-pastel-card border border-pastel-nav px-8 py-8 rounded-3xl shadow-xl shadow-pastel-nav/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-pastel-primary uppercase tracking-wider">Classroom Forum</span>
                <h1 class="text-2xl sm:text-3xl font-black text-pastel-text mt-1">💬 Discussion Board</h1>
                <p class="text-pastel-primary font-semibold mt-1">Connect with your peers and ask questions about your math adventures!</p>
            </div>
            <button onclick="toggleModal()" class="bg-pastel-primary hover:bg-pastel-hover text-white text-sm font-bold px-6 py-4 rounded-2xl transition shadow-md flex items-center gap-2 cursor-pointer">
                <span>+ Ask Question</span>
            </button>
        </div>

        <?php if (!empty($announcements)): ?>
            <div class="space-y-4">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-400 p-6 rounded-3xl shadow-sm relative">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="bg-amber-400 text-white text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider">Pinned Announcement</span>
                                <span class="text-xs text-amber-700 font-medium"><?= htmlspecialchars($announcement['created_at']) ?></span>
                            </div>
                        </div>
                        <h3 class="text-base font-bold text-amber-900 mb-1"><?= htmlspecialchars($announcement['title']) ?></h3>
                        <p class="text-sm text-amber-800 leading-relaxed"><?= htmlspecialchars($announcement['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if (empty($discussions)): ?>
                <div class="bg-pastel-card p-12 text-center rounded-3xl border border-pastel-nav shadow-sm">
                    <p class="text-slate-400 text-base">No questions asked yet. Click <strong>+ Ask Question</strong> to start one!</p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $post): ?>
                    <div class="bg-pastel-card p-8 rounded-3xl border border-pastel-nav shadow-xl shadow-pastel-nav/30">
                        
                        <div class="flex items-center justify-between border-b border-pastel-nav pb-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-pastel-badge text-pastel-primary font-bold text-sm flex items-center justify-center">
                                    <?= strtoupper(substr($post['author'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base text-pastel-text">
                                            <?= htmlspecialchars($post['author'] ?? 'Deleted Student') ?>
                                        </span>
                                        <?php if (($post['role'] ?? '') === 'teacher'): ?>
                                            <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2.5 py-0.5 rounded-md">Teacher</span>
                                        <?php else: ?>
                                            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-md">Student</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-slate-400"><?= htmlspecialchars($post['created_at']) ?></span>
                                </div>
                            </div>
                        </div>

                        <h2 class="text-xl font-bold text-pastel-text mb-2"><?= htmlspecialchars($post['title']) ?></h2>
                        <p class="text-base text-slate-600 mb-6 leading-relaxed"><?= htmlspecialchars($post['content']) ?></p>

                        <?php if (!empty($post['image_url'])): ?>
                            <div class="mb-6">
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Question attachment" class="max-h-80 rounded-2xl border border-slate-200 shadow-sm object-cover">
                            </div>
                        <?php endif; ?>

                        <div class="space-y-4 bg-pastel-bg p-6 rounded-2xl border border-pastel-nav mb-6">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">
                                Answers & Discussion (<?= count($post['replies']) ?>)
                            </h3>

                            <?php if (empty($post['replies'])): ?>
                                <p class="text-sm text-slate-400 italic">No answers yet. Help <?= htmlspecialchars($post['author'] ?? 'them') ?> out!</p>
                            <?php else: ?>
                                <?php foreach ($post['replies'] as $reply): ?>
                                    <div class="bg-white p-4 rounded-2xl border border-pastel-nav shadow-xs">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-bold text-sm text-pastel-text">
                                                <?= htmlspecialchars($reply['author'] ?? 'Deleted Student') ?>
                                            </span>
                                            <?php if (($reply['role'] ?? '') === 'teacher'): ?>
                                                <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded">Teacher</span>
                                            <?php else: ?>
                                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded">Student</span>
                                            <?php endif; ?>
                                            <span class="text-xs text-slate-400 ml-auto"><?= htmlspecialchars($reply['created_at']) ?></span>
                                        </div>
                                        <p class="text-sm text-slate-600 leading-normal mb-3"><?= htmlspecialchars($reply['content']) ?></p>

                                        <?php if (!empty($reply['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($reply['image_url']) ?>" alt="Reply attachment" class="max-h-48 rounded-xl border border-slate-200 mt-2 object-cover">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form action="discussion.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                            <input type="hidden" name="action" value="create_reply">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <div class="flex gap-3">
                                <input type="text" name="reply_content" placeholder="Write a reply or answer..." class="flex-1 text-sm bg-pastel-bg px-4 py-3 rounded-2xl border border-pastel-nav focus:outline-none focus:border-pastel-primary">
                                <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-sm font-bold px-6 py-3 rounded-2xl transition shadow-sm cursor-pointer">
                                    Reply
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-slate-500 font-semibold cursor-pointer hover:text-pastel-primary flex items-center gap-1">
                                    <span>📎 Attach Photo (Optional):</span>
                                    <input type="file" name="reply_image" accept="image/*" class="text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-pastel-primary hover:file:bg-blue-100 cursor-pointer">
                                </label>
                            </div>
                        </form>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <div id="question-modal" class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-pastel-nav overflow-hidden">
            <div class="bg-pastel-nav p-5 border-b border-pastel-nav flex items-center justify-between">
                <h3 class="text-base font-bold text-pastel-text">Ask a New Question</h3>
                <button onclick="toggleModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">✕</button>
            </div>
            
            <form action="discussion.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_post">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Topic / Title</label>
                    <input type="text" name="title" required placeholder="e.g. How do I simplify 12/16?" class="w-full text-sm bg-pastel-bg p-3.5 rounded-2xl border border-pastel-nav focus:outline-none focus:border-pastel-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Details</label>
                    <textarea name="content" rows="4" required placeholder="Describe what you are confused about..." class="w-full text-sm bg-pastel-bg p-3.5 rounded-2xl border border-pastel-nav focus:outline-none focus:border-pastel-primary"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attach Picture (Optional)</label>
                    <input type="file" name="post_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-pastel-primary hover:file:bg-blue-100 cursor-pointer">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal()" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition cursor-pointer">Cancel</button>
                    <button type="submit" class="bg-pastel-primary hover:bg-pastel-hover text-white text-xs font-bold px-6 py-2.5 rounded-2xl transition shadow-sm cursor-pointer">Post Question</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal() {
            document.getElementById('question-modal').classList.toggle('hidden');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>
</body>
</html>