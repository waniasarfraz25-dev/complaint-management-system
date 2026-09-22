<?php
/**
 * api/index.php — SOLE Vercel serverless function.
 * Saare requests yahi handle karti hai: URL ke path ko project ki
 * asal .php file se match karke, usay usi tarah load karti hai jaise
 * pehle direct access hoti thi (relative includes/requires bhi chalte
 * rahein, isliye chdir() kiya gaya hai).
 */

$projectRoot = realpath(dirname(__DIR__)); // api/ se ek level upar = project root

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri  = urldecode($uri);
$path = ltrim($uri, '/');

if ($path === '') {
    $path = 'index.php';
}

$candidate = realpath($projectRoot . '/' . $path);
error_log("DEBUG path=[$path] candidate=[" . var_export($candidate, true) . "] root=[$projectRoot]");


// Folders jinhe kabhi bhi directly serve nahi karna (safety)
$blocked = [
    realpath($projectRoot . '/vendor'),
    realpath($projectRoot . '/api'),
];

$isBlocked = false;
if ($candidate !== false) {
    foreach ($blocked as $b) {
        if ($b !== false && strpos($candidate, $b) === 0) {
            $isBlocked = true;
            break;
        }
    }
}

$valid = (
    $candidate !== false &&
    strpos($candidate, $projectRoot) === 0 &&
    pathinfo($candidate, PATHINFO_EXTENSION) === 'php' &&
    !$isBlocked &&
    is_file($candidate)
);

if (!$valid) {
    http_response_code(404);
    echo "404 - Not Found";
    exit;
}

// Original file jis folder mein hai, usi mein "chdir" — taake us file
// ke andar ke relative require/include (jaise 'includes/header.php',
// '../db.php') bilkul pehle jaisa hi kaam karein.
chdir(dirname($candidate));
require $candidate;