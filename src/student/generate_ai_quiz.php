<?php

header('Content-Type: application/json');

$GEMINI_API_KEY = 'PUT_YOUR_GEMINI_API_KEY_HERE';

$input = json_decode(file_get_contents('php://input'), true);

$topic = trim($input['topic'] ?? '');

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

$prompt = <<<PROMPT
You are generating a mathematics quiz for a primary-school student.

Topic:
{$topic}

Generate exactly 5 multiple-choice questions.

Rules:
- Keep the language simple.
- Questions must match the topic.
- Each question must have exactly 4 answer choices.
- Only one answer is correct.
- ans must be the zero-based index of the correct answer:
  0 = first option
  1 = second option
  2 = third option
  3 = fourth option

Return ONLY valid JSON.

Use exactly this structure:

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
      "ans": 0
    }
  ]
}
PROMPT;

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent';

$requestBody = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $prompt
                ]
            ]
        ]
    ],

    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'temperature' => 0.7
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

    echo json_encode([
        'success' => false,
        'error' => $geminiResponse['error']['message']
            ?? 'Gemini API request failed.',
        'http_code' => $httpCode
    ]);

    exit;
}

$text =
    $geminiResponse['candidates'][0]['content']['parts'][0]['text']
    ?? '';

if ($text === '') {

    echo json_encode([
        'success' => false,
        'error' => 'Gemini returned no quiz content.'
    ]);

    exit;
}

$quiz = json_decode($text, true);

if (
    !is_array($quiz) ||
    !isset($quiz['questions']) ||
    !is_array($quiz['questions'])
) {

    echo json_encode([
        'success' => false,
        'error' => 'Gemini returned invalid quiz JSON.',
        'raw' => $text
    ]);

    exit;
}

$cleanQuestions = [];

foreach ($quiz['questions'] as $question) {

    if (
        !isset(
            $question['q'],
            $question['options'],
            $question['ans']
        )
    ) {
        continue;
    }

    if (
        !is_array($question['options']) ||
        count($question['options']) !== 4
    ) {
        continue;
    }

    $answerIndex = (int)$question['ans'];

    if ($answerIndex < 0 || $answerIndex > 3) {
        continue;
    }

    $cleanQuestions[] = [
        'q' => trim($question['q']),
        'options' => array_values($question['options']),
        'ans' => $answerIndex
    ];
}

if (empty($cleanQuestions)) {

    echo json_encode([
        'success' => false,
        'error' => 'No valid questions were generated.'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'questions' => $cleanQuestions
]);