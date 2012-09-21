<?php

$shelf = Craur::createFromYamlFile(dirname(__FILE__) . '/fixtures/books.yaml');


assert(count($shelf->get('books[]')) === 2);

foreach ($shelf->get('books[]') as $book)
{
    if ($book->get('name') === 'My Book')
    {
        assert(count($book->get('authors[]')) === 2);
        assert($book->get('authors.name') == 'Hans');
        assert($book->get('authors.age') == '32');
        foreach ($book->get('authors[]') as $author)
        {
            assert(in_array($author->get('age'), array('32', '20')));
            assert(in_array($author->get('name'), array('Hans', 'Paul')));
        }
    }
    elseif ($book->get('name') === 'My second Book')
    {
        assert(count($book->get('authors[]')) === 1);
        assert($book->get('authors.name') == 'Erwin');
        assert($book->get('authors.age') == '10');
    }
}
