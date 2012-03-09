<?php

$source_file = $argv[1];
$minimum_code_coverage = (int) $argv[2];
$ignore_paths = array(
    dirname(__FILE__) . '/bootstrap_for_test.php',
    dirname(__FILE__) . '/tests/'
);
$full_report = array();

foreach (explode(PHP_EOL, file_get_contents($source_file)) as $raw_line)
{
    if (empty($raw_line))
    {
        continue;
    }
    $line = json_decode($raw_line, true);
    foreach ($line as $coverage_file => $coverage_data)
    {
        foreach ($ignore_paths as $ignore_path)
        {
            if ($ignore_path === substr($coverage_file, 0, strlen($ignore_path)))
            {
                continue 2;
            }
        }
        if (!isset($full_report[$coverage_file]))
        {
            $full_report[$coverage_file] = array();
        }
        foreach ($coverage_data as $line => $count)
        {
            if (isset($full_report[$coverage_file][$line]))
            {
                $full_report[$coverage_file][$line] = max($full_report[$coverage_file][$line], $count);
            }
            else
            {
                $full_report[$coverage_file][$line] = $count;
            }
        }
    }
}

$base_dir = dirname(dirname(__FILE__));

$overall_total_statements = 0;
$overall_covered_statements = 0;

echo " Code Coverage " . PHP_EOL;
echo "===============" . PHP_EOL;
echo "" . PHP_EOL;
foreach ($full_report as $coverage_file => $coverage_data)
{
    $covered_statements = 0;
    $total_statements = 0;
    foreach ($coverage_data as $line => $count)
    {
        if ($count > -2)
        {
            if ($count > 0)
            {
                $covered_statements++;
            }
            
            $total_statements++;
        }
    }
    $overall_covered_statements += $covered_statements;
    $overall_total_statements += $total_statements;
    echo "   - "  . str_pad(floor($covered_statements*100/$total_statements), 3, ' ', STR_PAD_LEFT). "% " . substr($coverage_file, strlen($base_dir) + 1)  . PHP_EOL;
}
echo "" . PHP_EOL;

echo " Untested Code " . PHP_EOL;
echo "===============" . PHP_EOL;
echo "" . PHP_EOL;
$coverage_file_content_cache = array();

foreach ($full_report as $coverage_file => $coverage_data)
{
    $covered_statements = 0;
    $total_statements = 0;
    $max_line = max(array_keys($coverage_data));
    foreach ($coverage_data as $line => $count)
    {
        if ($count == -1)
        {
            if (empty($coverage_file_content_cache[$coverage_file]))
            {
                $coverage_file_content_cache[$coverage_file] = explode("\n", file_get_contents($coverage_file));
            }
            echo basename($coverage_file) . ":" . str_pad($line, strlen($max_line)) . " > " . $coverage_file_content_cache[$coverage_file][$line - 1] . PHP_EOL;
        }
    }
}

$overall_code_coverage = 100 * $overall_covered_statements / $overall_total_statements;
echo "" . PHP_EOL;
if ($overall_code_coverage < $minimum_code_coverage)
{
    echo "Required at least: $minimum_code_coverage% code coverage, but had just $overall_code_coverage%!" . PHP_EOL;
    exit(1);
}
else
{
    echo "Everything tested. Awesome!" . PHP_EOL;
}
