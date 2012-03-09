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

function youngerThenThreeYears(Craur $value)
{
    if ($value->get('@age') < 3)
    {
        return $value;
    }
    
    throw new Exception('Is not younger then three years!');
};

function isAnAlien(Craur $value)
{
    throw new Exception('This is not an alien!');
};

/*
 * Get all animals, which are less then 3 years old!
 */
$animals = $node->getWithFilter('animals[]', "youngerThenThreeYears");
assert(count($animals) == 2);

/*
 * Get all animals, which are less then 3 years old!
 */
$first_animal_with_less_then_3_years = $node->getWithFilter('animals', "youngerThenThreeYears");
assert($first_animal_with_less_then_3_years->get('@name') == 'cat');

/*
 * Get all aliens (hopefully 0)
 */
$animals = $node->getWithFilter('animals', "isAnAlien", array());

/*
 * Fail to retrieve just one alien
 */
try
{
    $animals = $node->getWithFilter('animals', "isAnAlien");
}
catch (Exception $exception)
{
    /*
     * Great, we expected this!
     */
}