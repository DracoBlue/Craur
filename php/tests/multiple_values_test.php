<?php
include(dirname(__FILE__) . '/../bootstrap_for_test.php');

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

$values = $node->getValues(
    array(
        'title' => 'feed.title',
        'title_language' => 'feed.title.@lang',
        'author_name' => 'feed.author.name',
        'author_email' => 'feed.author.email'
    )
);

assert($values['title'] == 'Example Feed');
assert($values['title_language'] == 'en');
assert($values['author_name'] == 'John Doe');
assert($values['author_email'] == 'johndoe@example.com');


