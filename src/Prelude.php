<?php
/**
 * Prelude
 *
 * The place where it all begins
 *
 * @package Prelude
 **/
namespace Linoge\PHPFunctional\Prelude;

use ReflectionFunction;
use DeepCopy\DeepCopy;
use Exception;

const module = 'Linoge\\PHPFunctional\\Prelude\\';
/**
 * Y
 *
 * Y combinator for PHP
 *
 * @param callable $fn
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function Y($fn) {
    $call_fn = function ($make_fn) use ($fn) {
        return function (... $args) use ($fn, $make_fn) {
            return $fn($make_fn($make_fn))(... $args);
        };
    };

    $make_fn = function($make_fn) use ($fn) {
        return function(... $args) use ($make_fn, $fn) {
            return $fn($make_fn($make_fn))(... $args);
        };
    };

    return $call_fn($make_fn);
}
const Y = module . 'Y';

/**
 * partial
 *
 * creates a function that might be partial applied
 *
 * partial :: (a -> b -> ... -> c) -> a -> b -> ... -> c
 *
 * @param callable $fn
 * @param mixed $args
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function partial($fn, ... $args) {
    $n_params = arity($fn);

    return n_partial($fn, $n_params, ... $args);
}

const partial = module . 'partial';

/**
 * n_partial
 *
 * creates a function that might be partial applied n args
 *
 * n_partial :: (a -> b -> ... -> c) -> Int -> a -> b -> ... -> c
 *
 * @param callable $fn
 * @param mixed $args
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function n_partial($fn, $n, ... $args) {
    $n_partial = function ($recurse) {
        return function ($fn, $params_left, $args) use ($recurse) {
            if ($params_left === 0) {
                return $fn(... $args);
            }

            return function (... $args_provided)
                use ($params_left, $recurse, $args, $fn) {
                    $n_provided = count($args_provided);

                    if (count($args_provided) === 0) {
                        return $recurse($fn, $params_left, $args);
                    }

                    foreach ($args_provided as $arg) {
                        $args[] = $arg;
                    }

                    if ($params_left - $n_provided > 0) {
                        return $recurse($fn, $params_left - $n_provided, $args);
                    }

                    return $fn(... $args);
                };
        };
    };

    return Y($n_partial)($fn, $n - count($args), $args);
}

const n_partial = module . 'n_partial';

/**
 * curry
 *
 * curries a function of N arguments
 *
 * curry :: ((a, b, ..., z) -> c) -> (((a -> b) ...) -> z) -> c
 *
 * @param callable $fn
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function curry($fn) {
    $n_params = arity($fn);

    $_curry = function ($recurse) {
        return function ($fn, $params_left, $args) use ($recurse) {
            return function ($x) use ($params_left, $recurse, $args, $fn) {
                $args[] = $x;

                if ($params_left - 1 > 0) {
                    return $recurse($fn, $params_left - 1, $args);
                }
                return $fn(... $args);
            };
        };
    };

    return Y($_curry)($fn, $n_params, []);
}

const curry = module . 'curry';

/**
 * arity
 *
 * return the arity of a function
 *
 * arity :: (a -> ... -> z) -> Int
 *
 * @param callable $fn
 * @return int
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function arity($fn) {
    $refl = new ReflectionFunction($fn);
    return $refl->getNumberOfParameters();
}

const arity = module . 'arity';

/**
 * uncurry
 *
 * uncurries a function of N arguments
 *
 * uncurry :: (((a -> b) -> ...) -> c) -> (a, b, ..., z) -> c
 *
 * @param callable
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function uncurry(... $args) {
    $uncurry = function($fn) {
        return function(... $args) use ($fn) {
            if (count($args) < arity($fn)) {
                error('Not enough argument for call');
            }
            return $fn(... $args);
        };
    };

    return partial($uncurry, ... $args);
}

const uncurry = module . 'uncurry';

/**
 * id
 *
 * the id function
 *
 * id :: a -> a
 * @param mixed $x
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function id(... $args) {
    $id = function($x) {
        return $x;
    };

    return partial($id, ... $args);
}

const id = module . 'id';

/**
 * pair
 *
 * construct a pair out of two arguments
 *
 * pair :: a -> b -> (a, b)
 *
 * @param mixed $a
 * @param mixed $b
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function pair(... $args) {
    $pair = function($a, $b) {
        return [$a, $b];
    };

    return partial($pair, ... $args);
}

const pair = module . 'pair';

/**
 * zip
 *
 * zip a pair of arrays together
 *
 * zip :: [a] -> [b] -> [(a,b)]
 *
 * @param array $xs
 * @param array $ys
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function zip(... $args) {
    $zip = function($xs, $ys) {
        return array_map(pair(), $xs, $ys);
    };

    return partial($zip, ... $args);
}

const zip = module . 'zip';

/**
 * flatten
 *
 * flatten an n-dimensional array
 *
 * flatten :: [a] -> [a]
 *
 * @param array $xs
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function flatten(... $args) {
    $flatten = function($recurse) {
        return function($xs, $ys = [], $acc = []) use ($recurse) {
            if (is_empty($xs) && is_empty($ys)) {
                return $acc;
            }
            if (is_empty($xs)) {
                return $recurse(car($ys), cdr($ys), $acc);
            }
            if (is_list($xs)) {
                return $recurse(car($xs), append($ys, cdr($xs)), $acc);
            }
            return $recurse(car($ys), cdr($ys), append($acc, $xs));
        };
    };

    return n_partial(Y($flatten), 1, ... $args);
}

const flatten = module . 'flatten';

/**
 * foldl
 *
 * fold elements to the left
 *
 * foldl :: (a -> b -> a) -> a -> [b] -> a
 *
 * @param callable $fn
 * @param mixed $initial
 * @param array $xs
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function foldl(... $args) {
    $foldl = function($recurse) {
        return partial(function ($fn, $acc, $xs) use ($recurse) {
            if (is_empty($xs)) {
                return $acc;
            }
            return $recurse($fn, $fn($acc, car($xs)), cdr($xs));
        });
    };

    return Y($foldl)(... $args);
}

const foldl = module . 'foldl';

/**
 * add
 *
 * add two numbers
 *
 * add :: (Num a) => a -> a -> a
 *
 * @param mixed $x
 * @param mixed $y
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function add(... $args) {
    $add = function($x, $y) {
        return $x + $y;
    };

    return partial($add, ... $args);
}

const add = module . 'add';

/**
 * mult
 *
 * multiply two numbers
 *
 * mult :: (Num a) => a -> a -> a
 *
 * @param mixed $x
 * @param mixed $y
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function mult(... $args) {
    $mult = function($x, $y) {
        return $x * $y;
    };

    return partial($mult, ... $args);
}

const mult = module . 'mult';

/**
 * div
 *
 * divide two numbers
 *
 * div :: (Num a) => a -> a -> a
 *
 * @param mixed $x
 * @param mixed $y
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function div(... $args) {
    $div = function($x, $y) {
        return $x / $y;
    };

    return partial($div, ... $args);
}

const div = module . 'div';

/**
 * mod
 *
 * modulus operator
 *
 * mod :: (Num a) => a -> a -> a
 *
 * @param mixed $x
 * @param mixed $y
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function mod(... $args) {
    $mod = function($x, $y) {
        return $x % $y;
    };

    return partial($mod, ... $args);
}

const mod = module . 'mod';

/**
 * l_or
 *
 * or operation turn to a function
 *
 * l_or :: Bool -> Bool -> Bool
 *
 * @param boolean $x
 * @param boolean $y
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function l_or(... $args) {
    $l_or = function($x, $y) {
        if ($x === true || $y === true) {
            return true;
        }

        return false;
    };

    return partial($l_or, ...$args);
}

const l_or = module . 'l_or';

/**
 * max
 *
 * return biggest element between
 *
 * max :: a -> a -> a
 *
 * @param mixed $x
 * @param mixed $y
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function max(... $args) {
    $max = function($x, $y) {
        if ($x > $y) {
            return $x;
        }
        else {
            return $y;
        }
    };

    return partial($max, ... $args);
}

const max = module . 'max';

/**
 * foldr
 *
 * fold elements to the right
 *
 * foldr :: (a -> b -> b) -> b -> [a] -> b
 *
 * @param callable $fn
 * @param mixed $initial
 * @param array $xs
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function foldr(... $args) {
    $foldr = function($recurse) {
        return function($fn, $initial, $xs) use ($recurse) {
            if (is_empty($xs)) {
                return $initial;
            }
            return $fn(car($xs), $recurse($fn, $initial, cdr($xs)));
        };
    };

    return partial(Y($foldr), ... $args);
}

const foldr = module . 'foldr';

/**
 * append
 *
 * append an element to a list
 *
 * append :: [a] -> a -> [a]
 *
 * @param array $xs
 * @param mixed $x
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function append(... $args) {
    $append = function($xs, $x) {
        $xss = copy($xs);
        if (is_list($x)) {
            return foldl(function($acc, $x) {$acc[] = $x; return $acc;},
                         $xss,
                         $x);
        }
        $xss[] = $x;
        return $xss;
    };

    return partial($append, ... $args);
}

const append = module . 'append';

/**
 * concatenate
 *
 * concatenate two lists
 *
 * concatenate :: [a] -> [a] -> [a]
 *
 * @param array $xs
 * @param array $ys
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function concatenate(... $args) {
    $concatenate = function($xs, $ys) {
        return foldl(append, $xs, $ys);
    };

    return partial($concatenate, ... $args);
}

const concatenate = module . 'concatenate';

/**
 * nullp
 *
 * true if value is null
 *
 * nullp

 /**
 * is_empty
 *
 * true if a list is empty
 *
 * is_empty :: [a] -> Bool
 *
 * @param array $xs
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
     function is_empty(... $args) {
         $is_empty = function($xs) {
             if (empty($xs) === true) {
                 return true;
             }
             return false;
         };

         return partial($is_empty, ... $args);
     }

const is_empty = module . 'is_empty';

/**
 * map
 *
 * map a function f over an array
 *
 * zip :: (a -> b) -> [a] -> [b]
 *
 * @param callable $fn
 * @param array $xs
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function map(... $args) {
    $map = function($fn, $xs) {
        return array_map($fn, $xs);
    };

    return partial($map, ... $args);
}

const map = module . 'map';

/**
 * make_dispatcher
 *
 * construct an instance of the dispatcher
 *
 * make-dispatcher :: b
 *
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function make_dispatcher() {
    return [];
}

const make_dispatcher = module . 'make_dispatcher';

/**
 * error
 *
 * throw an error
 *
 * error :: String -> ()
 *
 * @param string $message
 * @return void
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function error(... $args) {
    $error = function($message) {
        throw new Exception($message);
    };

    return partial($error, ... $args);
}

const error = module . 'error';

/** copy
 *
 * copy anything
 *
 * copy :: a -> a
 *
 * @param mixed $x
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function copy(... $args) {
    $deep = new DeepCopy();
    $copy = function($x) use ($deep) {
        return $deep->copy($x);
    };

    return partial($copy, ... $args);
}

const copy = module . 'copy';

/**
 * defgeneric
 *
 * defines a lisp-like generic method
 *
 * defgeneric :: Dispatcher -> String -> (a -> b) -> Dispatcher
 *
 * @param array $dispatcher
 * @param string $name
 * @param callable $dfunc
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function defgeneric(... $args) {
    $defgeneric = function($dispatcher, $name, $dfunc) {
        if (isset($dispatcher[$name])) {
            error('Function defined twice');
        }
        $new_dispatcher = copy($dispatcher);
        $new_dispatcher[$name] = [
            'dispatching_function' => $dfunc
            ,'functions' => []
        ];

        return $new_dispatcher;
    };

    return partial($defgeneric, ... $args);
}

const defgeneric = module . 'defgeneric';

/**
 * defmethod
 *
 * instantiates a lisp-like generic method
 *
 * defmethod :: Dispatcher -> String -> [a]  -> (a -> ... -> c) -> Dispatcher
 *
 * @param array $dispatcher
 * @param string $name
 * @param mixed $results
 * @param callable $fn
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function defmethod(... $args) {
    $defmethod = function($dispatcher, $name, $matching, $fn) {
        if (!isset($dispatcher[$name])) {
            error('Generic method not defined');
        }

        $new_dispatcher = copy($dispatcher);
        $new_dispatcher[$name]['functions'][] = [
            'matching' => $matching
            ,'function' => $fn
        ];

        return $new_dispatcher;
    };

    return partial($defmethod, ... $args);
}

const defmethod = module . 'defmethod';

/**
 * filter
 *
 * filter an array
 *
 * filter :: (a -> Bool) -> [a] -> [a]
 *
 * @param callable $fn
 * @param array $xs
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function filter(... $args) {
    $filter = function($fn, $xs) {
        return array_reduce($xs, function($xs, $x) use ($fn) {
            if ($fn($x)) {
                $xs[] = $x;
            }
            return $xs;
        }, []);
    };

    return partial($filter, ... $args);
}

const filter = module . 'filter';

/**
 * equals
 *
 * equals operator turn to a function
 *
 * equals :: a -> b -> Bool
 *
 * @param mixed $x
 * @param mixed $y
 * @return bool
 * @author Carlos Gottberg <42linonge@gmail.com>
 **/
function equals(... $args) {
    $equals = function($x, $y) {
        return $x === $y;
    };

    return partial($equals, ... $args);
}

const equals = module . 'equals';

/**
 * compose
 *
 * function composition
 *
 * compose :: (b -> c) -> (a -> b) -> ... (w -> a) -> w -> c
 *
 * @param callable $f1
 * @param callable $fn
 * @return callable
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function compose(... $args) {
    $compose = function($args) {
        return array_reduce($args, function($acc, $fn) {
            return function($x) use ($fn, $acc) {
                return $acc($fn($x));
            };
        }, id());
    };

    return partial($compose, $args);
}

const compose = module . 'compose';

/**
 * dispatch
 *
 * dispatches a function throguh a dispatcher
 *
 * dispatch :: Dispatcher -> String -> (a, b, ...) -> c
 *
 * @param array $dispatcher
 * @param string $name
 * @param array $args
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function dispatch(... $args) {
    $dispatch = function ($dispatcher, $name, $args) {
        $exists_in_dispatcher = isset($dispatcher[$name]);
        $exists_outside = function_exists($name);
        $exists = $exists_in_dispatcher || $exists_outside;

        if ($exists_in_dispatcher === false) {
            error('No function with name: ' . $name);
        }

        $dispatching_fn = $dispatcher[$name]['dispatching_function'];

        $n_args = count($args);
        $n_params = arity($dispatching_fn);

        if ($n_params === 1) {
            $result = map($dispatching_fn, $args);
        } else if ($n_params === $n_args) {
            $result = [$dispatching_fn(... $args)];
        } else {
            error('Dispatching function does not match arguments');
        }

        $functions = $dispatcher[$name]['functions'];

        $matching_functions = filter(function($fn) use ($result) {
            if ($result === $fn['matching']) {
                return true;
            }
            return false;
        }, $functions);

        if (count($matching_functions) === 0) {
            error('No dispatching function matched arguments');
        }

        $fn = $matching_functions[0]['function'];
        return $fn(... $args);
    };

    return partial($dispatch, ... $args);
}

const dispatch = module . 'dispatch';

/**
 * cons
 *
 * create a cons cell (not precise)
 *
 * @param mixed $x
 * @param mixed $y
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function cons(... $args) {
    $cons = function($x, $y) {
        return append([$x], $y);
    };

    return partial($cons, ... $args);
};

const cons = module . 'cons';

/**
 * stream_cons
 *
 * create a new stream
 *
 * stream_cons :: a -> ([b] -> c) -> [b] -> (a, ([b] -> c))
 *
 * @param mixed $a
 * @param callable $fn
 * @param array $b
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function stream_cons(... $args) {
    $stream_cons = function($x, $fn, ...$args) {
        return [$x, function () { return $fn(...$args); }];
    };

    return $stream_cons(... $args);
}

const stream_cons = module . 'stream_cons';

/**
 * stream_car
 *
 * get first element out of a stream
 *
 * stream_car :: Stream a b -> a
 *
 * @param array $stream
 * @return mixed $a
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function stream_car(... $args) {
    $stream_car = function($stream) {
        return $stream[0];
    };

    return partial($stream_car, ... $args);
}

const stream_car = module . 'stream_car';

/**
 * stream_cdr
 *
 * get second element out of a stream
 *
 * stream_cdr :: Stream a b -> b
 *
 * @param array $stream
 * @return mixed $b
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function stream_cdr(... $args) {
    $stream_cdr = function($stream) {
        return $stream[1]();
    };

    return partial($stream_cdr, ... $args);
}

const stream_cdr = module . 'stream_cdr';

/**
 * car
 *
 * get first element out of a list
 *
 * car :: [a] -> a
 *
 * @param array $xs
 * @return mixed $x
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function car(... $args) {
    $car = function($xs) {
        if (isset($xs[0])) {
            return $xs[0];
        }
        return [];
    };

    return partial($car, ... $args);
}

const car = module . 'car';

/**
 * cdr
 *
 * get tail of list
 *
 * cdr :: [a] -> [a]
 *
 * @param mixed $xs
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function cdr(... $args) {
    $cdr = function ($xs) {
        if (is_empty($xs)) {
            return [];
        }

        $result =  array_slice($xs, 1);

        if ($result === NULL) {
            return [];
        }

        return $result;
    };

    return partial($cdr, ... $args);
}

const cdr = module . 'cdr';

/**
 * is_list
 *
 * is the argument a list?
 *
 * is_list :: a -> Bool
 *
 * @param mixed $a
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function is_list(... $args) {
    $is_list = function($x) {
        if (is_array($x) === true) {
            return true;
        }
        return false;
    };

    return partial($is_list, ... $args);
}

const is_list = module . 'is_list';

/**
 * any
 *
 * does any of the function application return true
 *
 * any :: (a -> Bool) -> [a] -> Bool
 *
 * @param callable $fn
 * @param array $xs
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function any(... $args) {
    $any = function($fn, $xs) {
        $xss = map($fn, $xs);
        return foldl(l_or, false, $xs);
    };

    return partial($any, ... $args);
}

const any = module . 'any';

/**
 * has_list
 *
 * does the list contain any sub-list
 *
 * has_list :: [a] -> Bool
 *
 * @param array $xs
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function has_list(... $args) {
    $has_list = function($xs) {
        return any(is_list, $xs);
    };

    return partial($has_list, ... $args);
}

const has_list = module . 'has_list';

/**
 * evaluate
 *
 * evaluate an expression a la Lisp
 *
 * evaluate :: ??
 *
 * @param $expr
 * @return mixed
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function evaluate(... $args) {
    $evaluate = function($recurse) {
        return partial(function($xs) use ($recurse) {
            if (! is_list($xs)) {
                return $xs;
            }

            $result = map($recurse, $xs);

            $fn = car($xs);
            $args = cdr($result);

            if (function_exists($fn) || is_callable($fn)) {
                return $fn(... $args);
            }

            return $xs;
        });
    };

    return Y($evaluate)(... $args);
}

const evaluate = module . 'evaluate';

/**
 * l_and
 *
 * and operation turn to a function
 *
 * l_and :: Bool -> Bool -> Bool
 *
 * @param $x
 * @param $y
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function l_and(... $args) {
    $l_and = function($x, $y) {
        if ($x === true && $y === true) {
            return true;
        }
        return false;
    };

    return partial($l_and, ... $args);
}

const l_and = module . 'l_and';

/**
 * all
 *
 * do a function, applied to every argument on a list,
 * always returns true?
 *
 * all :: (a -> Bool) -> [a] -> Bool
 *
 * @param callable $fn
 * @param array $xs
 * @return boolean
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function all(... $args) {
    $all = function($fn, $xs) {
        $xss = map($fn, $xs);
        return foldl(l_and, true, $xss);
    };

    return partial($all, ... $args);
}

const all = module . 'all';

/**
 * export
 *
 * register all functions within the module
 *
 * export :: Dispatcher -> Dispatcher
 *
 * @param array $dispatcher
 * @return array
 * @author Carlos Gottberg <42linoge@gmail.com>
 **/
function export(... $args) {
    $export = function($dispatcher) {

    };

    return partial($export, ... $args);
}

const export = module . 'export';