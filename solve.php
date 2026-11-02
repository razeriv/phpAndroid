<?php

header('Content-Type: application/json; charset=utf-8');

function sendResult($data, $status = 200) {
    http_response_code($status);

    echo json_encode(
        ['res' => $data],
        JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
    exit;
}

// Проверка параметров
$required = ['a0', 'a1', 'a2', 'f', 'epsilon'];
$params = [];

foreach ($required as $key) {
    if (!isset($_GET[$key]) ||
        !is_string($_GET[$key]) ||
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
$f = $params['f'];
$epsilon = $params['epsilon'];

if ($epsilon <= 0) {
    sendResult(['error' => 'invalid_epsilon'], 400);
}

// a0*x^2 + a1*x + c = 0
$c = $a2 - $f;

if (!is_finite($c)) {
    sendResult(['error' => 'calculation_error'], 400);
}

// Особые случаи
if ($a0 == 0.0 && $a1 == 0.0) {
    if ($c == 0.0) {
        sendResult(['error' => 'infinite_roots']);
    }

    sendResult(['error' => 'no_roots']);
}

// Функция уравнения
$func = function ($x) use ($a0, $a1, $c) {
    return ($a0 * $x + $a1) * $x + $c;
};

// Проверка противоположных знаков
function oppositeSigns($a, $b) {
    return ($a < 0 && $b > 0) ||
           ($a > 0 && $b < 0);
}

// Метод дихотомии
function dichotomy($func, $left, $right, $epsilon) {

    $fLeft = $func($left);
    $fRight = $func($right);

    if (!is_finite($fLeft) || !is_finite($fRight)) {
        return null;
    }

    if ($fLeft == 0.0) {
        return [$left, 0.0];
    }

    if ($fRight == 0.0) {
        return [$right, 0.0];
    }

    if (!oppositeSigns($fLeft, $fRight)) {
        return null;
    }

    for ($i = 0; $i < 1000; $i++) {

        $middle = $left + ($right - $left) / 2.0;
        $error = ($right - $left) / 2.0;

        if ($middle == $left || $middle == $right) {
            return [$middle, $error];
        }

        $fMiddle = $func($middle);

        if (!is_finite($fMiddle)) {
            return null;
        }

        if ($fMiddle == 0.0) {
            return [$middle, 0.0];
        }

        if ($error <= $epsilon) {
            return [$middle, $error];
        }

        if (oppositeSigns($fLeft, $fMiddle)) {
            $right = $middle;
        } else {
            $left = $middle;
            $fLeft = $fMiddle;
        }
    }

    return null;
}

$left = -1000.0;
$right = 1000.0;

$intervals = [];

// диапазон по вершине параболы
if ($a0 != 0.0) {

    $vertex = -($a1 / $a0) / 2.0;

    if (is_finite($vertex) &&
        $vertex > $left &&
        $vertex < $right) {

        $intervals[] = [$left, $vertex];
        $intervals[] = [$vertex, $right];

    } else {
        $intervals[] = [$left, $right];
    }

} else {
    // Линейная функция
    $intervals[] = [$left, $right];
}

// Поиск корней
$roots = [];

foreach ($intervals as $interval) {

    $root = dichotomy(
        $func,
        $interval[0],
        $interval[1],
        $epsilon
    );

    if ($root === null) {
        continue;
    }

    // Удаляю дубликаты корней
    $duplicate = false;

    foreach ($roots as $existing) {
        if ($root[0] == $existing[0]) {
            $duplicate = true;
            break;
        }
    }

    if (!$duplicate) {
        $roots[] = $root;
    }
}

// Если корней нет
if (empty($roots)) {
    sendResult(['error' => 'no_roots']);
}

// Сортировка по возрастанию
usort($roots, function ($a, $b) {
    return $a[0] <=> $b[0];
});

sendResult([
    'x' => round($roots[0][0], 3),
    'epsilon' => round($roots[0][1], 3)
]);

?>
