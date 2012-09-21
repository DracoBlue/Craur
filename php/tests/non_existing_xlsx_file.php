<?php

try 
{
    $shelf = Craur::createFromExcelFile(dirname(__FILE__) . '/fixtures/non_existing_books.xlsx', array(
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
