<?php

try 
{
    $shelf = Craur::createFromYamlFile(dirname(__FILE__) . '/fixtures/non_existing_books.yaml');
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Great, the file does not exist!
     */
}
