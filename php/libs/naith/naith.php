<?php
/*
 * We want to be strict and handle all errors.
 */
error_reporting(E_ALL | E_STRICT);

require_once (dirname(__FILE__) . '/NaithCliRunner.class.php');
require_once (dirname(__FILE__) . '/NaithCliReport.class.php');
require_once (dirname(__FILE__) . '/NaithJunitReport.class.php');

/*
 * Argument handling!
 */

$arguments = array_slice($argv, 1);
$next_value_key = null;
$options = array();
$action_name = null;
foreach ($arguments as $argument)
{
    if (substr($argument, 0, 2) == '--')
    {
        $next_value_key = substr($argument, 2);
    }
    else
    {
        if ($next_value_key !== null)
        {
            if (!array_key_exists($next_value_key, $options))
            {
                $options[$next_value_key] = array();
            }
            if (substr($argument, 0, 1) == '"' && substr($argument, -1, 1) == '"')
            {
                /*
                 * It's something like "example" (with quotes)
                 */
                $argument = substr($argument, 1, strlen($argument) - 2);
            }
            $options[$next_value_key][] = $argument;
            $next_value_key = null;
        }
        else
        {
            if ($action_name !== null)
            {
                throw new Exception('Unknown option ' . $argument);
            }
            $action_name = $argument;
        }
    }
}

/*
 * Execute the Command with the given options!
 */

if (in_array($action_name, array("make-coverage-overview", "make-untested-code-overview")))
{
    $excluded_paths = array();
    if (isset($options['excluded_path']))
    {
        $excluded_paths = $options['excluded_path'];
    }

    $minimum_code_coverage = 0;

    if (isset($options['minimum_code_coverage']))
    {
        list($minimum_code_coverage) = $options['minimum_code_coverage'];
    }

    $base_directory = dirname(getcwd());

    if (isset($options['base_directory']))
    {
        list($base_directory) = $options['base_directory'];
    }
    
    list($coverage_file_path) = $options['coverage_file_path'];
    
    $report = new NaithCliReport( array(
        'base_directory' => $base_directory,
        'excluded_paths' => $excluded_paths,
        'coverage_file_path' => $coverage_file_path,
        'minimum_code_coverage' => $minimum_code_coverage,
    ));
}

switch ($action_name)
{

    case "run-test":
        if (isset($options['coverage_file_path']))
        {
            NaithCliRunner::setCoverageFilePath($options['coverage_file_path'][0]);
        }
        
        if (isset($options['tests_report_path']))
        {
            NaithCliRunner::setTestsReportPath($options['tests_report_path'][0], $options['test_file'][0]);
        }

        NaithCliRunner::bootstrapForTest();

        if (isset($options['prepend_file']))
        {
            foreach ($options['prepend_file'] as $prepended_file)
            {
                require ($prepended_file);
            }

        }

        if (isset($options['test_file']))
        {
            foreach ($options['test_file'] as $test_file)
            {
                require ($test_file);
            }
        }

        break;

    case "make-coverage-overview":
        $report->makeCoverageOverview();

        break;

    case "make-untested-code-overview":
        $report->makeUntestedCodeOverview();

        break;

    case "generate-junit-xml":
        
        $junit_xml_path = null;
    
        if (isset($options['junit_xml_path']))
        {
            list($junit_xml_path) = $options['junit_xml_path'];
        }

        $excluded_paths = array();
        if (isset($options['excluded_path']))
        {
            $excluded_paths = $options['excluded_path'];
        }

        $base_directory = dirname(getcwd());
    
        if (isset($options['base_directory']))
        {
            list($base_directory) = $options['base_directory'];
        }
    
        $tests_report_path = null;
    
        if (isset($options['tests_report_path']))
        {
            list($tests_report_path) = $options['tests_report_path'];
        }
    
        $report = new NaithJunitReport( array(
            'base_directory' => $base_directory,
            'excluded_paths' => $excluded_paths,
            'tests_report_path' => $tests_report_path
        ));

        $report->writeJunitXmlToFile($junit_xml_path);

        break;
}
