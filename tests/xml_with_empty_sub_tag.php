<?php

$invalid_xml = '<root>
    <zerovalue key1="value1"></zerovalue>
</root>';

$craur = Craur::createFromXml($invalid_xml);
assert($craur->get('root.zerovalue.@key1') == 'value1');
assert($craur->get('root.zerovalue') == '');
