<?php

header('Content-Type: application/json; charset=utf-8');

function sendResult($data, $status = 200) {
    http_response_code($status);

    echo json_encode(
        ['res' => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// Проверка входных данных
$required = ['a0', 'a1', 'a2', 'f', 'epsilon'];
$params = [];

foreach ($required as $key) {
    if (!isset($_GET[$key]) ||
        !is_scalar($_GET[$key]) ||
        !is_numeric($_GET[$key])) {

        sendResult(['error' => 'invalid_parameters'], 400);
    }

    $params[$key] = (float) $_GET[$key];

    if (!is_finite($params[$key])) {
        sendResult(['error' => 'invalid_parameters'], 400);
    }
}

$a0 = $params['a0'];
$a1 = $params['a1'];
$a2 = $params['a2'];
$target = $params['f'];
$eps = $params['epsilon'];

if ($eps <= 0) {
    sendResult(['error' => 'invalid_epsilon'], 400);
}

function f($x, $a0, $a1, $a2) {
    return $a0 + $a1 * $x + $a2 * $x * $x;
}

function calculate($a0, $a1, $a2, $target, $eps) {

    $left = -1000.0;
    $right = 1000.0;
    $step = 0.01;

    $x1 = $left;
    $f1 = f($x1, $a0, $a1, $a2) - $target;

    $found = false;

    // Поиск интервала с корнем
    while ($x1 < $right) {

        $x2 = $x1 + $step;
        $f2 = f($x2, $a0, $a1, $a2) - $target;

        if (abs($f1) < $eps) {
            $left = $x1;
            $right = $x1;
            $found = true;
            break;
        }

        if ($f1 * $f2 < 0) {
            $left = $x1;
            $right = $x2;
            $found = true;
            break;
        }

        $x1 = $x2;
        $f1 = $f2;
    }

    if (!$found) {
        return null;
    }

    // Метод дихотомии
    $iterations = 0;

    while (($right - $left) / 2 > $eps) {

        $mid = ($left + $right) / 2;

        $fLeft = f($left, $a0, $a1, $a2) - $target;
        $fMid = f($mid, $a0, $a1, $a2) - $target;

        if (abs($fMid) < $eps) {
            $left = $mid;
            $right = $mid;
            break;
        }

        if ($fLeft * $fMid < 0) {
            $right = $mid;
        } else {
            $left = $mid;
        }

        $iterations++;

        if ($iterations >= 1000) {
            return null;
        }
    }

    $x = ($left + $right) / 2;
    $error = ($right - $left) / 2;

    if (!is_finite($x) || !is_finite($error)) {
        return null;
    }

    // Округление
    $x = round($x, 4);
    $error = round($error, 4);

    return [
        'x' => $x,
        'epsilon' => $error
    ];
}

$result = calculate(
    $a0,
    $a1,
    $a2,
    $target,
    $eps
);

if ($result === null) {
    sendResult(['error' => 'no_roots']);
}

sendResult($result);

?>
