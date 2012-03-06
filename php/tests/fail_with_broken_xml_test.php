<?php
include(dirname(__FILE__) . '/../bootstrap_for_test.php');

try
{
    $node = Craur::createFromXml('<book><author></author</book>');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Great, it broke! :)
     */
}
