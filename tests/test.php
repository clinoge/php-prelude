<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Linoge\PHPFunctional\Prelude;

$dispatcher = Prelude\make_dispatcher();
$dispatcher = Prelude\defgeneric($dispatcher, '++', function ($a, $b) {
    if (is_array($a) && is_array($b)) {
        return 'array';
    }
    if (is_string($a) && is_string($b)) {
        return 'string';
    }
});

$dispatcher = Prelude\defmethod($dispatcher, '++', ['array'], function($a,$b) {
    return array_reduce($b, function($xs, $x) {
        $xs[] = $x;
        return $xs;
    }, $a);
});

$dispatcher = Prelude\defmethod($dispatcher, '++', ['string'], function($a, $b) {
    return $a . $b;
});

var_dump(Prelude\dispatch($dispatcher, '++', ["Carlos ", "Gottberg"]));
var_dump(Prelude\dispatch($dispatcher, '++', [[1,2,3],[4,5,6]]));

$add = function($x, $y) {
    return $x + $y;
};

$multiply = function($x, $y) {
    return $x * $y;
};

$add5 = Prelude\partial($add, 5);
$multiply3 = Prelude\partial($multiply, 3);

$add_and_multiply = Prelude\compose($multiply3, $add5);
var_dump($add_and_multiply(3));

var_dump(Prelude\concatenate([1,2,3],[4,5,6]));