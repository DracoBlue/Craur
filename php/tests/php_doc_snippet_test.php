<?php
include (dirname(__FILE__) . '/../bootstrap_for_test.php');

$node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
$authors = $node->get('book.authors[]');
assert(count($authors) == 2);

$node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
$authors = $node->get('book.author[]');
assert(count($authors) == 2);

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$book = $node->get('book');
assert($book->get('name') == 'MyBook');
assert($book->get('price', 20) == 20);

$authors = $node->get('book.authors[]');
assert(count($authors) == 2);

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$values = $node->getValues(
    array(
        'name' => 'book.name',
        'book_price' => 'price',
        'first_author' => 'book.authors'
    ),
    array(
        'book_price' => 20
    )
);

assert($values['name'] == 'MyBook');
assert($values['book_price'] == '20');
assert($values['first_author'] == 'Hans');