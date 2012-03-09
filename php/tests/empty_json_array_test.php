<?php
$node = Craur::createFromJson('{"items":[]}');

/*
 * This should work
 */
$node->get('items[]');

try
{
    $node->get('noitems[]');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * This should not work, since we have no noitems attribute at all!
     */
}

try
{
    $node->get('items');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Yes, we expected it to fail since there is no value at items!
     */
}

/*
 * But it should work with default value!
 */
$node->get('items', "me item!");
