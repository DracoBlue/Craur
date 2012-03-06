<?php
/*
 * We want to be strict and handle all errors.
 */
error_reporting(E_ALL | E_STRICT);

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
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL, 0);
assert_options(ASSERT_CALLBACK, 'AssertionException::throwFromAssertion');

/*
 * Let's not ignore warnings, but fail with an uncaught exception!
 */
set_error_handler('AssertionException::throwFromError');

/*
 * Load for every test
 */
require_once (dirname(__FILE__) . '/Craur.class.php');
