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
    $refl_function = new ReflectionFunction($fn);
    $n_params = $refl_function->getNumberOfParameters();

    $partial = function ($recurse) {
        return function ($fn, $params_left, $args) use ($recurse) {
            if ($params_left === 0) {
                return $fn(... $args);
            }

            return function (... $args_provided)
                use ($params_left, $recurse, $args, $fn) {
                    $n_provided = count($args_provided);

                    var_dump($params_left - $n_provided);

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

    return Y($partial)($fn, $n_params - count($args), $args);
}

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
    $refl_function = new ReflectionFunction($fn);
    $n_params = $refl_function->getNumberOfParameters();

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
function uncurry($fn) {

}

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

/**
 * zip
 *
 * zip a pair of arrays together
 *
 * zip :: [a] -> [b] -> [(a,b)]
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

/** make_dispatcher
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

/** defgeneric
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

/** defmethod
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

/** filter
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

/** dispatch
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
        if (!isset($dispatcher[$name])) {
            error('No function with name: ' . $name);
        }

        $dispatching_fn = $dispatcher[$name]['dispatching_function'];

        $n_args = count($args);
        $refl_dispatching = new ReflectionFunction($dispatching_fn);
        $n_params = $refl_dispatching->getNumberOfParameters();

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