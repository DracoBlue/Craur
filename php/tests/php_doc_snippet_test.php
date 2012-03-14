<?php
/* Craur#createFromJson */

$node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
$authors = $node->get('book.authors[]');
assert(count($authors) == 2);


/* Craur#createFromXml */

$node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
$authors = $node->get('book.author[]');
assert(count($authors) == 2);


/* Craur#createFromCsvFile */

// If the file loooks like this:
// Book Name;Book Year;Author Name
// My Book;2012;Hans
// My Book;2012;Paul
// My second Book;2010;Erwin
$shelf = Craur::createFromCsvFile('fixtures/books.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
));
assert(count($shelf->get('book[]')) === 2);
foreach ($shelf->get('book[]') as $book)
{
    assert(in_array($book->get('name'), array('My Book', 'My second Book')));
    foreach ($book->get('author[]') as $author)
    {
        assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
    }
}


/* Craur#mergePathEntriesRecursive */

$entries = array(
    array(
        'book' => array(
            'name' => 'My Book',
            'year' => 2012,
            'author' => array(
                'name' => 'Hans',
                'age' => '32'
            )
        )
    ),
    array(
        'book' => array(
            'name' => 'My Book',
            'year' => 2012,
            'author' => array(
                'name' => 'Paul',
                'age' => '20'
            )
        )
    ),
    array(
        'book' => array(
            'name' => 'My second Book',
            'year' => 2010,
            'author' => array(
                'name' => 'Erwin',
                'age' => '10'
            )
        )
    )
);
$merged_entries = Craur::mergePathEntriesRecursive($entries);
assert(count($merged_entries) === 1);
assert(json_encode(array(
    'book' => array(
        array(
            'name' => 'My Book',
            'year' => 2012,
            'author' => array(
                array(
                    'name' => 'Hans',
                    'age' => '32'
                ),
                array(
                    'name' => 'Paul',
                    'age' => '20'
                )
            )
        ),
        array(
            'name' => 'My second Book',
            'year' => 2010,
            'author' => array(
                array(
                    'name' => 'Erwin',
                    'age' => '10'
                )
            )
        )
    )
)) === json_encode($merged_entries[0]));


/* Craur#expandPathsIntoArray */

$row_data = array(
    'My Book',
    2012,
    'Hans',
    '32'
);
$raw_mapping_keys = array(
    'book.name',
    'book.year',
    'book.author.name',
    'book.author.age'
);
$raw_identifier_keys = array(
    'book',
    'book.author'
);
$expected_entry = array(
   'book' => array(
       'name' => 'My Book',
       'year' => 2012,
        'author' => array(
            'name' => 'Hans',
            'age' => '32'
        )
    )
);

assert(json_encode($expected_entry) === json_encode(Craur::expandPathsIntoArray($row_data, $raw_mapping_keys, $raw_identifier_keys)));

/* Craur#getValues */

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


/* Craur#getValuesWithFilters */

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


/* Craur#get */

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$book = $node->get('book');
assert($book->get('name') == 'MyBook');
assert($book->get('price', 20) == 20);

$authors = $node->get('book.authors[]');
assert(count($authors) == 2);


/* Craur#getWithFilter */

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


