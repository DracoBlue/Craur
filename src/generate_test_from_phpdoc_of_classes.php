<?php
/*
 * Generate an extra test for all php doc snippets in the class documentation.
 * 
 * Usage: php php/generate_test_from_phpdoc_of_classes.php > php/tests/php_doc_snippet_test.php 
 */

require_once(dirname(__FILE__) . '/Craur.php');

$result = array();

foreach (array('Craur', 'CraurCsvWriter', 'CraurCsvReader') as $class_name)
{
    
    $reflection = new ReflectionClass($class_name);
    
    foreach ($reflection->getMethods() as $reflection_method)
    {
        $example_string = $reflection_method->getDocComment();
        preg_match_all('/@example([.\s\S]+)(@return[\s\S]+$|$)/m', $example_string, $matches);
        foreach ($matches[1] as $match)
        {
            $result[] = '/* ' . $reflection->getName() . '#' . $reflection_method->getName(). ' */' . PHP_EOL;
            $result[] = PHP_EOL;
            $match = trim($match);
            $match = preg_replace('/(@return[\s\S]+)$/m', '', $match);
            $match = preg_replace('/^([\s]*\*)/m', '', $match);
            
            /*
             * Remove indention
             */
            $minimum_indention = strlen($match);
            foreach (explode(PHP_EOL, $match) as $line)
            {
                if (trim($line))
                {
                    preg_match('/^([\s]*)/', $line, $space_matches);
                    $minimum_indention = min($minimum_indention, strlen($space_matches[0]));    
                }
            }
    
            foreach (explode(PHP_EOL, $match) as $line)
            {
                $result[] = substr($line, $minimum_indention) . PHP_EOL;
            }
        }
    }
}

echo '<?php' . PHP_EOL . implode('', $result);
