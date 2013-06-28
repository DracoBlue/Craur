<?php
$node = new Craur(array(
    'animals' => array(
        array(
            '@name' => 'dog',
            '@age' => 6,
            '@height' => '50cm'
        ),
        array(
            '@name' => 'cat',
            '@age' => 2,
            '@height' => '30cm'
        ),
        array(
            '@name' => 'mouse',
            '@age' => 2,
            '@height' => '10cm'
        )
    ) 
));

class FiltersWithClassTest
{
    static public function myFilterCallback($value) {
        return str_replace('cm', '', $value);
    }
}

/*
 * Test to rewrite the value with a filter
 */
$height = $node->getWithFilter('animals.@height', "FiltersWithClassTest::myFilterCallback");

assert($height == "50");

/*
 * Test default value
 */
$width = $node->getWithFilter('animals.@width', "FiltersWithClassTest::myFilterCallback", '100');

assert($width == "100");

/*
 * Test with invalid callback
 */
try
{
    $node->getWithFilter('animals.@height', new stdClass());
    assert(false);
}
catch (Exception $exception)
{
    /*
     * This was no callback, so it must fail! Nice!
     */
}

/*
 * Test with invalid path and no default value
 */
try
{
    $node->getWithFilter('animals.@width', "myFilterCallback");
    assert(false);
}
catch (Exception $exception)
{
    /*
     * The path was invalid, so it must fail! Nice!
     */
}
