<?php
class NaithCliReport
{
    /**
     * Read the code coverage from this file
     */
    protected $coverage_file_path = null;

    /**
     * The base directory for all covered files
     */
    protected $base_directory = null;

    protected $excluded_paths = array();
    protected $minimum_code_coverage = 0;

    protected $coverage_file_result = null;
    protected $coverage_file_report = null;
    protected $coverage_file_total_statements = null;
    protected $coverage_file_executed_statements = null;

    public function __construct(array $options)
    {
        $this->coverage_file_path = $options['coverage_file_path'];
        $this->excluded_paths = $options['excluded_paths'];
        $this->base_directory = $options['base_directory'];
        $this->minimum_code_coverage = $options['minimum_code_coverage'];

        $this->evaluateCoverageFile();
    }

    protected function evaluateCoverageFile()
    {
        $full_report = array();

        foreach (explode(PHP_EOL, file_get_contents($this->coverage_file_path)) as $raw_line)
        {
            if (empty($raw_line))
            {
                continue;
            }
            $line = json_decode($raw_line, true);
            foreach ($line as $coverage_file => $coverage_data)
            {
                foreach ($this->excluded_paths as $ignore_path)
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

        $this->coverage_file_total_statements = 0;
        $this->coverage_file_executed_statements = 0;

        foreach ($full_report as $coverage_file => $coverage_data)
        {
            foreach ($coverage_data as $line => $count)
            {
                if ($count > -2)
                {
                    if ($count > 0)
                    {
                        $this->coverage_file_executed_statements++;
                    }

                    $this->coverage_file_total_statements++;
                }
            }
        }

        $this->coverage_file_report = $full_report;
    }

    public function makeCoverageOverview()
    {
        $base_dir = $this->base_directory;

        echo "" . PHP_EOL;
        echo " Code Coverage (for each File)" . PHP_EOL;
        echo "===============================" . PHP_EOL;
        echo "" . PHP_EOL;
        foreach ($this->coverage_file_report as $coverage_file => $coverage_data)
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
            echo "  " . str_pad(floor($covered_statements * 100 / $total_statements), 3, ' ', STR_PAD_LEFT) . "% " . substr($coverage_file, strlen($base_dir) + 1) . PHP_EOL;
        }

        $overall_code_coverage = 0;
        if ($this->coverage_file_total_statements)
        {
            $overall_code_coverage = $this->coverage_file_executed_statements / $this->coverage_file_total_statements;
        }
        
        if ($this->coverage_file_total_statements && $this->minimum_code_coverage > $overall_code_coverage * 100)
        {
            echo "" . PHP_EOL;
            echo " Required at least: $this->minimum_code_coverage% code coverage, but had just $overall_code_coverage%!" . PHP_EOL;
            exit(1);
        }
    }

    public function makeUntestedCodeOverview()
    {
        echo "" . PHP_EOL;
        echo " Untested Code " . PHP_EOL;
        echo "===============" . PHP_EOL;
        echo "" . PHP_EOL;
        $coverage_file_content_cache = array();

        foreach ($this->coverage_file_report as $coverage_file => $coverage_data)
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
    }

}
