<?php
require_once __DIR__ . '/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/config/gemini_api.php';

$GEMINI_KEY = $GEMINI_API_KEY ?? null;
$MODEL      = $GEMINI_MODEL   ?? 'gemini-2.5-flash';

if (!$GEMINI_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration: GEMINI API key missing.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No prompt provided.']);
    exit;
}

$systemInstruction = <<<EOT
You generate SHORT student task suggestions.

You MUST output ONLY a JSON array.
No explanations. No text outside the array.

Each item must follow THIS EXACT FORMAT:

{
  "title": "Short task name",
  "subject": "Subject name",
  "category": "Study" | "General" | "Urgent",
  "estimated_minutes": number
}

- Max 3 items
- estimated_minutes must be an integer
- No due dates
- No extra fields
EOT;

$userPrompt = "Generate task suggestions based on: \"$prompt\"";

$payload = [
    "contents" => [
        [
            "role"  => "user",
            "parts" => [
                ["text" => $systemInstruction . "\n\n" . $userPrompt]
            ]
        ]
    ],
    "generationConfig" => [
        "responseMimeType" => "application/json",
        "responseSchema"   => [
            "type"  => "array",
            "items" => [
                "type"       => "object",
                "properties" => [
                    "title"             => ["type" => "string",  "description" => "Short task name"],
                    "subject"           => ["type" => "string",  "description" => "Subject name"],
                    "category"          => ["type" => "string",  "description" => "'Study', 'General', or 'Urgent'"],
                    "estimated_minutes" => ["type" => "integer", "description" => "Estimated time in minutes"]
                ],
                "required" => ["title", "subject", "category", "estimated_minutes"]
            ]
        ]
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$GEMINI_KEY}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["curl_error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        "http_status"    => $httpCode,
        "response_raw"   => $response,
        "error_message"  => "API request failed with HTTP status {$httpCode}."
    ]);
    exit;
}

$data           = json_decode($response, true);
$suggestionText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (empty($suggestionText)) {
    $block_reason = $data['candidates'][0]['safetyRatings'][0]['blockReason'] ?? 'NO_CANDIDATE_RESPONSE';
    http_response_code(500);
    echo json_encode([
        "success"      => false,
        "error"        => "AI failed to generate content or was blocked.",
        "raw_response" => $data,
        "block_reason" => $block_reason
    ]);
    exit;
}

$suggestions = json_decode(trim($suggestionText), true);

if ($suggestions === null) {
    http_response_code(500);
    echo json_encode([
        'success'           => false,
        'error'             => 'Failed to parse JSON output from AI.',
        'raw_text_received' => $suggestionText
    ]);
    exit;
}

if (!is_array($suggestions) || (is_array($suggestions) && isset($suggestions['title']))) {
    $suggestions = [$suggestions];
}

foreach ($suggestions as &$s) {
    $s['title']             = $s['title']             ?? "Untitled Task";
    $s['subject']           = $s['subject']           ?? "General";
    $s['category']          = $s['category']          ?? "General";
    $s['estimated_minutes'] = isset($s['estimated_minutes']) ? intval($s['estimated_minutes']) : 30;
}
unset($s);

echo json_encode([
    "success"     => true,
    "suggestions" => $suggestions
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);