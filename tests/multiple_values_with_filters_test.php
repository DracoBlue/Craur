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
                '@href' => 'http://example.org',
                '@rel' => 'self'
            ),
            array(
                '@href' => 'http://example.org/feed/',
                '@rel' => 'feed'
            )
        ),
        'author' => array(
            'name' => 'John Doe',
            'email' => 'johndoe@example.com'
        )
    )
)));

function getFeedLink(Craur $value)
{
    if ($value->get('@rel', '') === 'feed')
    {
        return $value->get('@href');
    }
    
    throw new Exception('This is no feed link!');
}

function getNoFollowLink(Craur $value)
{
    throw new Exception('This is no nofollow link!');
}

$values = $node->getValuesWithFilters(
    array(
        'title' => 'feed.title',
        'title_language' => 'feed.title.@lang',
        'link' => 'feed.link',
        'author_name' => 'feed.author.name',
        'author_email' => 'feed.author.email'
    ),
    array(
        'link' => 'getFeedLink'
    )
);

assert($values['title'] == 'Example Feed');
assert($values['title_language'] == 'en');
assert($values['link'] == 'http://example.org/feed/');
assert($values['author_name'] == 'John Doe');
assert($values['author_email'] == 'johndoe@example.com');


/*
 * Test overall default value
 */
$values = $node->getValuesWithFilters(
    array(
        'title' => 'feed.title',
        'link' => 'feed.link',
        'extra_link' => 'feed.link',
        'non_existant' => 'feed.non_existant2'
    ),
    array(
        'link' => 'getNoFollowLink',
        'extra_link' => 'getNoFollowLink',
    ),
    array(
        'link' => 'http://nofollow.example.org',
        'non_existant' => 'test'
    ),
    false
);

assert($values['title'] == 'Example Feed');
assert($values['link'] == 'http://nofollow.example.org');
assert($values['extra_link'] === false);
assert($values['non_existant'] == 'test');
