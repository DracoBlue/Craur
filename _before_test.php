<?php

if (file_exists(dirname(__FILE__)) . '/vendor/autoload.php')
{
    require_once(dirname(__FILE__) . '/vendor/autoload.php');
}

/*
 * Load for every test
 */
require_once (dirname(__FILE__) . '/src/Craur.php');
