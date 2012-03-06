<?php
include(dirname(__FILE__) . '/../bootstrap_for_test.php');

try
{
    $node = Craur::createFromJson('{"key":"valu}');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Great, it broke! :)
     */
}
