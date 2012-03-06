<?php
include(dirname(__FILE__) . '/../bootstrap_for_test.php');

$node = Craur::createFromJson('{"book": {"@": "My Book"}}');
assert(strpos($node->toXmlString(), 'My Book') > 0);
