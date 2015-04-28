<?php
$node = Craur::createFromJson(json_encode(array(
    'feed' => array(
        '@xmlns' => 'http://www.w3.org/2005/Atom',
        'title' => array(
            '@' => 'Example Feed',
            '@lang' => 'en'
        ),
        'link' => array(
            array(
                '@href' => 'http://example.org/feed/',
                '@rel' => 'self'
            ),
            array(
                '@href' => 'http://example.org',
            )
        ),
        'author' => array(
            'name' => 'John Doe',
            'email' => 'johndoe@example.com'
        )
    )
)));

assert('http://www.w3.org/2005/Atom' == $node->get('feed.@xmlns'));
assert('Example Feed' == $node->get('feed.title'));

$titles = $node->get('feed.title[]');
assert('Example Feed' == $titles[0]);

foreach ($node->get('feed.link[]') as $link) {
    assert(in_array($link->get('@href'), array('http://example.org/feed/', 'http://example.org')));
}

assert(strlen($node->toXmlString()));
assert(strlen($node->toJsonString()));


assert($node->get('feed.author')->__toString() == '');
