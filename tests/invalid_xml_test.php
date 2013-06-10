<?php

$invalid_xml = file_get_contents(dirname(__FILE__) . '/fixtures/invalid_xml.xml');

$craur = Craur::createFromXml($invalid_xml, 'iso-8859-1');

assert($craur->get('test.wrong') == 'xx');
