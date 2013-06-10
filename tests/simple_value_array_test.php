<?php
$node = Craur::createFromJson('{"book": {"@": "My Book"}}');
assert(strpos($node->toXmlString(), 'My Book') > 0);
