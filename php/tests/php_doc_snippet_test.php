<?php
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

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$values = $node->getValuesWithFilters(
    array(
        'name' => 'book.name',
        'book_price' => 'price',
        'first_author' => 'book.authors'
    ),
    array(
        'name' => 'strtolower',
        'first_author' => 'strtoupper',
    ),
    array(
        'book_price' => 20
    )
);

assert($values['name'] == 'mybook');
assert($values['book_price'] == '20');
assert($values['first_author'] == 'HANS');


function isACheapBook(Craur $value)
{
    if ($value->get('price') > 20)
    {
        throw new Exception('Is no cheap book!');
    }
    return $value;
}

$node = Craur::createFromJson('{"books": [{"name":"A", "price": 30}, {"name": "B", "price": 10}, {"name": "C", "price": 15}]}');
$cheap_books = $node->getWithFilter('books[]', 'isACheapBook');
assert(count($cheap_books) == 2);
assert($cheap_books[0]->get('name') == 'B');
assert($cheap_books[1]->get('name') == 'C');

