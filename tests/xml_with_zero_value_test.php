<?php

$invalid_xml = file_get_contents(dirname(__FILE__) . '/fixtures/xml_with_0_value.xml');

$craur = Craur::createFromXml($invalid_xml);
assert($craur->get('root.zerovalue') == '0');
