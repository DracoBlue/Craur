<?php
$node = Craur::createFromJson(json_encode(array(
    'website' => array(
        'http://example.com' => 'test'
        )
    )
));

assert('test' == $node->get('website.http://example\.com'));
