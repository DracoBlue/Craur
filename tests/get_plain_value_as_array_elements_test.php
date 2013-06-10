<?php
$node = Craur::createFromJson('{"items":"hans"}');

/*
 * Even though items is no numeric array and contains directly
 * the member, we want to get it as items!
 */
$items = $node->get('items[]');
assert(count($items) == 1);
assert($items[0] == "hans");
