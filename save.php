<?php
// save.php
// Receives JSON { expenses: [...], members: [...] } and overwrites data.json.
// Deploy this file alongside data.json and tinhtiensan.html on Apache with PHP enabled.
//
// Optional: set a token to protect the endpoint.
$saveToken = '';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if ($saveToken !== '') {
    $clientToken = $_SERVER['HTTP_X_SAVE_TOKEN'] ?? '';
    if (!hash_equals($saveToken, $clientToken)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    // Fallback for form posts
    $raw = $_POST['data'] ?? '';
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

function normalize_date($v) {
    $v = preg_replace('/\s+/', ' ', trim((string)$v));
    if ($v === '') return '';
    // Hard cap to keep JSON reasonable
    if (strlen($v) > 100) $v = substr($v, 0, 100);
    return $v;
}

function to_number($v) {
    if (is_int($v) || is_float($v)) return $v + 0;
    if (is_string($v)) {
        $v = trim(str_replace(',', '', $v));
        if ($v === '') return 0;
        return is_numeric($v) ? ($v + 0) : 0;
    }
    return 0;
}

function normalize_join($v) {
    return ($v === 'N') ? 'N' : 'Y';
}

// Sanitize to keep the exact top-level structure { expenses, members }
$out = [
    'days' => []
];

function sanitize_expenses($expenses) {
    $out = [];
    foreach ($expenses as $e) {
        if (!is_array($e)) continue;
        $out[] = [
            'type' => (string)($e['type'] ?? ''),
            'price' => to_number($e['price'] ?? 0),
            'quantity' => to_number($e['quantity'] ?? 0),
            'total' => to_number($e['total'] ?? 0)
        ];
    }
    return $out;
}

function sanitize_members($members) {
    $out = [];
    foreach ($members as $m) {
        if (!is_array($m)) continue;
        $computed = is_array($m['computed'] ?? null) ? $m['computed'] : [];
        $extra = to_number($m['extra'] ?? 0);
        $out[] = [
            'name' => (string)($m['name'] ?? ''),
            'hours' => to_number($m['hours'] ?? 1) ?: 1,
            'join' => normalize_join($m['join'] ?? 'Y'),
            'extra' => $extra,
            'computed' => [
                'san' => to_number($computed['san'] ?? 0),
                'cau' => to_number($computed['cau'] ?? 0),
                'tea' => to_number($computed['tea'] ?? 0),
                'other' => to_number($computed['other'] ?? 0),
                'incurred' => to_number($computed['incurred'] ?? $extra),
                'sum' => to_number($computed['sum'] ?? 0)
            ]
        ];
    }
    return $out;
}

// New preferred format: { days: [ { date, expenses, members } ] }
if (is_array($data['days'] ?? null)) {
    foreach ($data['days'] as $day) {
        if (!is_array($day)) continue;
        $date = normalize_date($day['date'] ?? '');
        if ($date === '') continue;
        $expenses = is_array($day['expenses'] ?? null) ? $day['expenses'] : [];
        $members = is_array($day['members'] ?? null) ? $day['members'] : [];

        $out['days'][] = [
            'date' => $date,
            'expenses' => sanitize_expenses($expenses),
            'members' => sanitize_members($members)
        ];
    }
} else {
    // Legacy format: { expenses: [...], members: [...] }
    $expenses = $data['expenses'] ?? null;
    $members = $data['members'] ?? null;

    if (!is_array($expenses) || !is_array($members)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing days[] or expenses/members arrays']);
        exit;
    }

    $today = date('Y-m-d');
    $out['days'][] = [
        'date' => $today,
        'expenses' => sanitize_expenses($expenses),
        'members' => sanitize_members($members)
    ];
}

$target = __DIR__ . DIRECTORY_SEPARATOR . 'data.json';
$json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to encode JSON']);
    exit;
}

// Convert default 4-space indentation to tabs to match your existing style.
$json = preg_replace_callback('/^( +)/m', function($m) {
    $len = strlen($m[1]);
    $tabs = intdiv($len, 4);
    return str_repeat("\t", $tabs);
}, $json);

$ok = @file_put_contents($target, $json . "\n", LOCK_EX);
if ($ok === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to write data.json. Check file permissions for Apache user.'
    ]);
    exit;
}

echo json_encode(['ok' => true]);
