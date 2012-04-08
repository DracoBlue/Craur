<?php
class NaithCliRunner
{
    static $coverage_file_path = null;
    static $tests_report_path = null;
    static $tested_file_name = null;
    static $start_time = 0;
    static $assertions_count = 0;
    static $errors_count = 0;
    static $error_message = '';

    static function setCoverageFilePath($file_path)
    {
        self::$coverage_file_path = $file_path;
    }

    static function setTestsReportPath($file_path, $tested_file_name)
    {
        self::$tests_report_path = $file_path;
        self::$tested_file_name = $tested_file_name;
    }

    static function bootstrapForTest()
    {
        if (self::$coverage_file_path)
        {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
        if (self::$coverage_file_path || self::$tests_report_path)
        {
            register_shutdown_function(__CLASS__ . '::onShutdown');
        }
        
        if (self::$tests_report_path)
        {
            self::$start_time = microtime(true);
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
        self::$assertions_count++;
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
            list($callee) = debug_backtrace(2);
            self::$errors_count++;
            self::$error_message = 'Expected an exception! in ' . $callee['file'] . ' on line ' . $callee['line'];
            restore_error_handler();
            trigger_error(self::$error_message . "\n", E_USER_ERROR);
        }
    }

    static function throwFromAssertion($file, $line, $message)
    {
        self::$errors_count++;
        self::$error_message = $message . ' in ' . $file . ' on line ' . $line;
        restore_error_handler();
        trigger_error(self::$error_message . "\n", E_USER_ERROR);
    }

    static function throwFromError($code, $message, $file, $line)
    {
        self::$errors_count++;
        self::$error_message = $message . ' in ' . $file . ' on line ' . $line;
        restore_error_handler();
        trigger_error(self::$error_message . "\n", E_USER_ERROR);
    }

    static function onShutdown()
    {
        if (self::$tests_report_path)
        {
            file_put_contents(self::$tests_report_path, json_encode(array(
                'file_name' => self::$tested_file_name,
                'assertions' => self::$assertions_count,
                'errors' => self::$errors_count,
                'error_message' => self::$error_message,
                'time' => microtime(true) - self::$start_time
            )) . PHP_EOL, FILE_APPEND);
        }
        if (self::$coverage_file_path)
        {
            file_put_contents(self::$coverage_file_path, json_encode(xdebug_get_code_coverage()) . PHP_EOL, FILE_APPEND);
        }
    }

}
