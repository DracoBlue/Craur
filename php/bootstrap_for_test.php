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
        $exception = new AssertionException($message);
        $exception->setFileAndLine($file, $line);
        throw $exception;
    }
    
    static function throwFromError($code, $message, $file, $line)
    {
        restore_error_handler();
        $exception = new AssertionException($message);
        $exception->setFileAndLine($file, $line);
        throw $exception;
    }
    
    public function setFileAndLine($file, $line)
    {
        $this->file = $file;
        $this->line = $line;
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