<?php
include(dirname(__FILE__) . '/../bootstrap_for_test.php');

$node = Craur::createFromJson('{"book": {"@": "My Book"}}');
echo $node->toXmlString();
