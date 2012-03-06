<?php
/*
 * We want to be strict and handle all errors.
 */
error_reporting(E_ALL | E_STRICT);

$code_coverage_file = null;
if (isset($argv[2]))
{
    $code_coverage_file = $argv[2];
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
}


/*
 * Throw AssertionExceptions, if something went wrong. Don't ignore it!
 */
class AssertionException extends Exception
{
    static function throwFromAssertion($file, $line, $message)
    {
        restore_error_handler();
        trigger_error($message . ' in ' . $file . ' on line ' . $line . "\n", E_USER_ERROR);
    }
    
    static function throwFromError($code, $message, $file, $line)
    {
        restore_error_handler();
        list($me, $callee) = debug_backtrace(2);
        trigger_error($message . ' in ' . $callee['file'] . ' on line ' . $callee['line'] . "\n", E_USER_ERROR);
    }

    static function onShutdown()
    {
        global $code_coverage_file;
        if ($code_coverage_file)
        {
            file_put_contents($code_coverage_file, json_encode(xdebug_get_code_coverage()) . PHP_EOL, FILE_APPEND);
        }
    }
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL, 0);
assert_options(ASSERT_CALLBACK, 'AssertionException::throwFromAssertion');

/*
 * Let's not ignore warnings, but fail with an uncaught exception!
 */
set_error_handler('AssertionException::throwFromError');
register_shutdown_function('AssertionException::onShutdown');

/*
 * Load for every test
 */
require_once (dirname(__FILE__) . '/Craur.class.php');
