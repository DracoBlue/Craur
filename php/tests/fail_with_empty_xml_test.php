<?php
try
{
    $node = Craur::createFromXml('');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Great, it broke! :)
     */
}
