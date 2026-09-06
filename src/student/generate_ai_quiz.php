<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ai_quiz_helper.php';

header('Content-Type: application/json');

$secretsFile = __DIR__ . '/../config/secrets.php';

if (!file_exists($secretsFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'secrets.php was not found.'
    ]);
    exit;
}

$secrets = require $secretsFile;
$GEMINI_API_KEY = $secrets['gemini_api_key'] ?? '';

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
    exit;
}

$studentId = 1;
$chapterId = (int)($input['chapter_id'] ?? 0);
$scopeType = ($input['scope_type'] ?? 'overall') === 'subtopic' ? 'subtopic' : 'overall';
$topic = trim((string)($input['topic'] ?? ''));
$subtopic = trim((string)($input['subtopic'] ?? ''));
$difficulty = ucfirst(strtolower(trim((string)($input['difficulty'] ?? 'Beginner'))));
$studentLevel = ucfirst(strtolower(trim((string)($input['student_level'] ?? 'Beginner'))));
$focusSkill = trim((string)($input['focus_skill'] ?? ''));
$academicTopics = $input['academic_topics'] ?? [];

$allowedLevels = ['Beginner', 'Intermediate', 'Advanced'];
if (!in_array($difficulty, $allowedLevels, true)) {
    $difficulty = 'Beginner';
}
if (!in_array($studentLevel, $allowedLevels, true)) {
    $studentLevel = 'Beginner';
}

if (!is_array($academicTopics)) {
    $academicTopics = [];
}
$academicTopics = array_values(array_filter(array_map(static fn($value) => trim((string)$value), $academicTopics)));

if ($scopeType === 'subtopic' && $subtopic !== '') {
    $topic = $subtopic;
    $academicTopics = [$subtopic];
}

if ($topic === '' && !empty($academicTopics)) {
    $topic = implode(', ', $academicTopics);
}

if ($topic === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No quiz topic was provided.'
    ]);
    exit;
}

if ($GEMINI_API_KEY === '' || $GEMINI_API_KEY === 'PUT_YOUR_GEMINI_API_KEY_HERE') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gemini API key has not been configured.'
    ]);
    exit;
}

$recentQuestions = [];
if ($chapterId > 0) {
    try {
        $recentQuestions = getRecentAiQuestions(
            $pdo,
            $studentId,
            $chapterId,
            $focusSkill !== '' ? $focusSkill : ($scopeType === 'subtopic' ? $subtopic : null),
            20
        );
    } catch (Throwable $e) {
        $recentQuestions = [];
    }
}

$topicListText = !empty($academicTopics)
    ? implode("\n- ", $academicTopics)
    : $topic;

$focusInstruction = $focusSkill !== ''
    ? "This is targeted weakness practice. Focus mainly on this weak skill: {$focusSkill}."
    : "Cover the selected learning scope fairly.";

$difficultyGuide = match ($difficulty) {
    'Advanced' => 'Use multi-step thinking, less obvious distractors, and harder applications while staying suitable for primary school.',
    'Intermediate' => 'Use a mix of direct understanding and short multi-step problems.',
    default => 'Use clear, direct questions that test the basic idea with friendly numbers and simple wording.'
};

$avoidText = '';
if (!empty($recentQuestions)) {
    $avoidLines = array_map(static fn($q) => '- ' . $q, $recentQuestions);
    $avoidText = "\nDo NOT repeat or closely copy these questions the student has already answered:\n" . implode("\n", $avoidLines) . "\n";
}

$prompt = <<<PROMPT
You are generating an adaptive mathematics practice quiz for a primary-school student.

The student's current standard on this topic is: {$studentLevel}
The student selected quiz difficulty: {$difficulty}

Academic learning scope:
- {$topicListText}

{$focusInstruction}

Difficulty guidance:
{$difficultyGuide}

IMPORTANT:
- Generate questions ONLY about the academic mathematics concepts listed above.
- Do not generate questions about decorative chapter names, island themes, pyramids, cherry blossoms, volcanoes, forests, story settings, or unrelated topics.
- Adapt wording and mathematical demand so it is appropriate for a student whose current standard is {$studentLevel}, while respecting the selected {$difficulty} difficulty.
- Generate exactly 5 multiple-choice questions.
- Each question must have exactly 4 answer choices.
- Only one answer is correct.
- ans must be the zero-based index of the correct answer: 0, 1, 2, or 3.
- skill must name the exact mathematical skill tested by that question. Prefer one of the academic learning scope names above.
- difficulty must be exactly one of: Beginner, Intermediate, Advanced.
- explanation must be a short, child-friendly explanation of how to get the correct answer. It will be shown whether the student answers right or wrong.
- Keep every explanation useful and specific to its question.
{$avoidText}
Return ONLY valid JSON using exactly this structure:

{
  "questions": [
    {
      "q": "Question text",
      "options": [
        "Option A",
        "Option B",
        "Option C",
        "Option D"
      ],
      "ans": 0,
      "skill": "Skill name",
      "difficulty": "{$difficulty}",
      "explanation": "Simple explanation of the correct method and answer."
    }
  ]
}
PROMPT;

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

$requestBody = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'temperature' => 0.65
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $GEMINI_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not contact Gemini.',
        'details' => $curlError
    ]);
    exit;
}

$geminiResponse = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode >= 400 && $httpCode < 600 ? $httpCode : 500);
    echo json_encode([
        'success' => false,
        'error' => $geminiResponse['error']['message'] ?? 'Gemini API request failed.',
        'http_code' => $httpCode
    ]);
    exit;
}

$text = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($text === '') {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Gemini returned no quiz content.'
    ]);
    exit;
}

$quiz = json_decode($text, true);

if (!is_array($quiz) || !isset($quiz['questions']) || !is_array($quiz['questions'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Gemini returned invalid quiz JSON.'
    ]);
    exit;
}

$cleanQuestions = [];

foreach ($quiz['questions'] as $question) {
    if (!isset($question['q'], $question['options'], $question['ans'])) {
        continue;
    }

    if (!is_array($question['options']) || count($question['options']) !== 4) {
        continue;
    }

    $questionText = trim((string)$question['q']);
    $options = array_values(array_map(static fn($option) => trim((string)$option), $question['options']));
    $answerIndex = (int)$question['ans'];
    $skill = trim((string)($question['skill'] ?? ''));
    $questionDifficulty = ucfirst(strtolower(trim((string)($question['difficulty'] ?? $difficulty))));
    $explanation = trim((string)($question['explanation'] ?? ''));

    if ($questionText === '' || in_array('', $options, true) || $answerIndex < 0 || $answerIndex > 3) {
        continue;
    }

    if (!in_array($questionDifficulty, $allowedLevels, true)) {
        $questionDifficulty = $difficulty;
    }

    if ($skill === '') {
        $skill = $focusSkill !== ''
            ? $focusSkill
            : ($scopeType === 'subtopic' && $subtopic !== '' ? $subtopic : ($academicTopics[0] ?? 'General Practice'));
    }

    if ($explanation === '') {
        $explanation = 'Review the correct method and compare it with the answer choices.';
    }

    $cleanQuestions[] = [
        'q' => $questionText,
        'options' => $options,
        'ans' => $answerIndex,
        'skill' => $skill,
        'difficulty' => $questionDifficulty,
        'explanation' => $explanation
    ];

    if (count($cleanQuestions) === 5) {
        break;
    }
}

if (count($cleanQuestions) !== 5) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Gemini did not return 5 valid questions. Please generate again.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'questions' => $cleanQuestions,
    'student_level' => $studentLevel,
    'difficulty' => $difficulty,
    'scope_type' => $scopeType,
    'focus_skill' => $focusSkill
]);
