<?php

$empty_xml = '<root>
    <zerovalue></zerovalue>
</root>';

$craur = Craur::createFromXml($empty_xml);

try
{
    $craur->get('root.zerovalue');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * This was no callback, so it must fail! Nice!
     */
}