<?php
error_reporting(E_ALL | E_STRICT);
require_once('../Craur.class.php');

$xml_string = file_get_contents(dirname(__FILE__) . '/fixtures/working_feed.xml');
$node = Craur::createFromXml($xml_string);

assert((string) $node->get('feed.title') === 'Example Feed');
assert($node->get('feed.non_existant_key', 'default') === 'default');

try
{
    $node->get('feed.non_existant_key');
    /*
     * This should not work!
     */
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Nice, we got an exception!
     */
}

try
{
    $node->get('feed.non_existant_key[]');
    /*
     * This should not work!
     */
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Nice, we got an exception!
     */
}


/*
 * In case of default value, it should work!
 */
$values = $node->get('feed.non_existant_key[]', array());

assert(is_array($values));
assert(count($values) === 0);