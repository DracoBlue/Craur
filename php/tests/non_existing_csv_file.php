<?php

try 
{
    $shelf = Craur::createFromCsvFile(dirname(__FILE__) . '/fixtures/non_existing_books.csv', array(
        'book[].name',
    ));
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Great, the file does not exist!
     */
}
