<?php
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
