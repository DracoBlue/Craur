<?php

$shelf = Craur::createFromCsvFile(dirname(__FILE__) . '/fixtures/books_with_multiple_categories.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age',
    'book[].category[]',
    'book[].category[]',
));

assert(count($shelf->get('book[]')) === 2);

foreach ($shelf->get('book[]') as $book)
{
    if ($book->get('name') === 'My Book')
    {
        assert(count($book->get('author[]')) === 2);
        assert($book->get('author.name') == 'Hans');
        assert($book->get('author.age') == '32');
        foreach ($book->get('author[]') as $author)
        {
            assert(in_array($author->get('age'), array('32', '20')));
            assert(in_array($author->get('name'), array('Hans', 'Paul')));
        }
        assert(count($book->get('category[]')) === 1);
        assert($book->get('category') == 'Fantasy');
    }
    elseif ($book->get('name') === 'My second Book')
    {
        assert(count($book->get('author[]')) === 1);
        assert($book->get('author.name') == 'Erwin');
        assert($book->get('author.age') == '10');
        assert(count($book->get('category[]')) === 2);
        foreach ($book->get('category[]') as $category)
        {
            assert(in_array((string) $category, array('Fantasy', 'Comedy')));
        }
    }
}
