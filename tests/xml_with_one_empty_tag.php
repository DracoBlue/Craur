<?php

$empty_xml = '<root>
    <zerovalue>hans</zerovalue>
    <zerovalue></zerovalue>
</root>';

$craur = Craur::createFromXml($empty_xml);

assert($craur->get('root.zerovalue') === 'hans');
assert(count($craur->get('root.zerovalue[]')) == 1);

$empty_xml = '<root>
    <zerovalue></zerovalue>
    <zerovalue>hans</zerovalue>
    <zerovalue></zerovalue>
</root>';

$craur = Craur::createFromXml($empty_xml);

assert($craur->get('root.zerovalue') === 'hans');
assert(count($craur->get('root.zerovalue[]')) == 1);

$empty_xml = '<root>
    <zerovalue></zerovalue>
    <zerovalue>hans</zerovalue>
    <zerovalue></zerovalue>
    <zerovalue>hans2</zerovalue>
    <zerovalue></zerovalue>
</root>';

$craur = Craur::createFromXml($empty_xml);

assert(count($craur->get('root.zerovalue[]')) == 2);

assert(implode(',', $craur->get('root.zerovalue[]')) === 'hans,hans2');
