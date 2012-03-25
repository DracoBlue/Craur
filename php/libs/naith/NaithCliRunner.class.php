<?php
class NaithCliRunner
{
    static $coverage_file_path = null;

    static function setCoverageFilePath($file_path)
    {
        self::$coverage_file_path = $file_path;
    }

    static function bootstrapForTest()
    {
        if (self::$coverage_file_path)
        {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
            register_shutdown_function(__CLASS__ . '::onShutdown');
        }

        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 1);
        assert_options(ASSERT_BAIL, 0);
        assert_options(ASSERT_CALLBACK, __CLASS__ . '::throwFromAssertion');

        /*
         * Let's not ignore warnings, but fail with an uncaught exception!
         */
        set_error_handler(__CLASS__ . '::throwFromError');
    }

    static function assertException($callable)
    {
        $threw_exception = false;

        try
        {
            $callable();
        }
        catch (Exception $exception)
        {
            $threw_exception = true;
        }

        if (!$threw_exception)
        {
            restore_error_handler();
            list($callee) = debug_backtrace(2);
            trigger_error('Expected an exception! in ' . $callee['file'] . ' on line ' . $callee['line'] . "\n", E_USER_ERROR);
        }
    }

    static function throwFromAssertion($file, $line, $message)
    {
        restore_error_handler();
        trigger_error($message . ' in ' . $file . ' on line ' . $line . "\n", E_USER_ERROR);
    }

    static function throwFromError($code, $message, $file, $line)
    {
        restore_error_handler();
        list($me, $callee) = debug_backtrace(2);
        trigger_error($message . ' in ' . $file . ' on line ' . $line . "\n", E_USER_ERROR);
    }

    static function onShutdown()
    {
        file_put_contents(self::$coverage_file_path, json_encode(xdebug_get_code_coverage()) . PHP_EOL, FILE_APPEND);
    }

}
