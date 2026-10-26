<?php

header('Content-Type: application/json; charset=utf-8');

function sendResult($data) {
    echo json_encode(
        ['res' => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// Проверка входных параметров
$required = ['a0', 'a1', 'a2', 'f', 'epsilon'];

foreach ($required as $key) {
    if (!isset($_GET[$key]) ||
        !is_scalar($_GET[$key]) ||
        !is_numeric($_GET[$key])) {

        http_response_code(400);
        sendResult(['error' => 'invalid_parameters']);
    }
}

// Получение параметров
$a0 = (float) $_GET['a0'];
$a1 = (float) $_GET['a1'];
$a2 = (float) $_GET['a2'];
$f = (float) $_GET['f'];
$epsilon = (float) $_GET['epsilon'];

foreach ([$a0, $a1, $a2, $f, $epsilon] as $value) {
    if (!is_finite($value)) {
        http_response_code(400);
        sendResult(['error' => 'invalid_parameters']);
    }
}

if ($epsilon <= 0) {
    http_response_code(400);
    sendResult(['error' => 'invalid_epsilon']);
}

// Границы поиска
$left = -1000.0;
$right = 1000.0;

// Функция для вычисления
$func = function ($x) use ($a0, $a1, $a2, $f) {
    return $a0 * $x * $x + $a1 * $x + $a2 - $f;
};

// Метод дихотомии
function dichotomy($func, $left, $right, $epsilon) {

    $fLeft = $func($left);
    $fRight = $func($right);

    if ($fLeft == 0.0) {
        return [$left, 0.0];
    }

    if ($fRight == 0.0) {
        return [$right, 0.0];
    }

    if ($fLeft * $fRight > 0) {
        return null;
    }

    for ($i = 0; $i < 1000; $i++) {

        $middle = $left + ($right - $left) / 2;
        $fMiddle = $func($middle);

        if ($fMiddle == 0.0) {
            return [$middle, 0.0];
        }

        if (($right - $left) / 2 <= $epsilon) {
            return [
                $middle,
                ($right - $left) / 2
            ];
        }

        if (($fLeft < 0 && $fMiddle > 0) ||
            ($fLeft > 0 && $fMiddle < 0)) {

            $right = $middle;

        } else {
            $left = $middle;
            $fLeft = $fMiddle;
        }
    }

    return null;
}

$intervals = [];

// Если функция квадратичная,
// разделяем интервал по вершине параболы
if ($a0 != 0.0) {

    $vertex = -$a1 / (2 * $a0);

    if ($vertex > $left && $vertex < $right) {
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

    if ($root !== null) {
        $roots[] = $root;
    }
}

// Если корней нет
if (empty($roots)) {
    sendResult(['error' => 'no_roots']);
}

// Выбираем наименьший найденный корень
usort($roots, function ($a, $b) {
    return $a[0] <=> $b[0];
});

sendResult([
    'x' => round($roots[0][0], 3),
    'epsilon' => round($roots[0][1], 3)
]);

?>