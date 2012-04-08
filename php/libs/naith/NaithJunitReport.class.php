<?php
class NaithJunitReport
{
    /**
     * The base directory for all covered files
     */
    protected $base_directory = null;
    
    protected $tests_report_path = null;

    protected $excluded_paths = array();

    public function __construct(array $options)
    {
        $this->excluded_paths = $options['excluded_paths'];
        $this->base_directory = $options['base_directory'];
        $this->tests_report_path = $options['tests_report_path'];
    }

    public function writeJunitXmlToFile($junit_file_path)
    {
        file_put_contents($junit_file_path, '<?xml version="1.0" encoding="UTF-8" ?' . '>');
        
        $report = array();
        $errors_count = 0;
        $assertions_count = 0;
        $tests_count = 0;
        $time = 0;
        
        $body_xml = array();
        
        foreach (explode(PHP_EOL, file_get_contents($this->tests_report_path)) as $raw_line)
        {
            if (empty($raw_line))
            {
                continue;
            }
            $tests_count++;
            $test_report_data = json_decode($raw_line, true);
            $file_name = $test_report_data['file_name'];
            foreach ($this->excluded_paths as $ignore_path)
            {
                if ($ignore_path === substr($file_name, 0, strlen($ignore_path)))
                {
                    continue 2;
                }
            }
            
            $errors_count += $test_report_data['errors'];
            $assertions_count += $test_report_data['assertions'];
            $time += $test_report_data['time'];
            
            $relative_file_name = substr($file_name, strlen($this->base_directory) + 1);
            
            $body_xml[] = '<testcase';
            $body_xml[] = ' classname="'. htmlspecialchars(str_replace('/', '.', dirname($relative_file_name))). '"';
            $body_xml[] = ' name="'. htmlspecialchars(basename($relative_file_name)). '"';
            $body_xml[] = ' assertions="'. htmlspecialchars(basename($test_report_data['assertions'])). '"';
            $body_xml[] = ' errors="'. htmlspecialchars(basename($test_report_data['errors'])). '"';
            $body_xml[] = ' time="'. htmlspecialchars(basename($test_report_data['time'])). '"';
            
            if ($test_report_data['errors']) {
                $body_xml[] = '><error>' . htmlspecialchars($test_report_data['error_message']) . '</error></testcase>';
            } else {
                $body_xml[] = ' />';
            }
            
            $body_xml[] = PHP_EOL;
        }

        $head_xml = array();
        $head_xml[] = '<testsuite ';
        $head_xml[] = ' name="Naith Results"';
        $head_xml[] = ' tests="'. htmlspecialchars($tests_count). '"';
        $head_xml[] = ' time="'. htmlspecialchars($time). '"';
        $head_xml[] = ' errors="'. htmlspecialchars($errors_count). '"';
        $head_xml[] = ' assertions="'. htmlspecialchars($assertions_count). '">' . PHP_EOL;

        file_put_contents($junit_file_path, implode('', $head_xml), FILE_APPEND);
        file_put_contents($junit_file_path, implode('', $body_xml), FILE_APPEND);
        file_put_contents($junit_file_path, '</testsuite>', FILE_APPEND);
    }

}
