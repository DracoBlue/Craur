<?php
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
