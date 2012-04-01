<?php

$craur = new Craur(
    array(
        'name' => 'My Book',
        'year' => '2012',
        'categories' => array(
            'comedy',
            'fantasy'
        ),
        'authors' => array(
            array('name' => 'Paul'),
            array('name' => 'Erwin')
        ),
        'pages' => '212'
    )
);

$expected_data = array(
    0 => 'My Book',
    1 => '2012',
    4 => '212'
);

$result_data = CraurCsvWriter::extractDirectDescendants($craur, array(
    'name',
    'year',
    'categories[]',
    'authors[].name',
    'pages',
),'');

assert(json_encode($expected_data) == json_encode($result_data));



$craur = new Craur(
    array(
        'name' => 'My Book',
        'year' => '2012',
        'categories' => array(
            array('name' => 'comedy'),
        ),
        'authors' => array(
            array('name' => 'Paul', 'age' => '30'),
            array('name' => 'Erwin', 'age' => '20')
        ),
        'pages' => '212'
    )
);

$expected_data = array(
    array(
        'My Book',
        '2012',
        'comedy',
        'Paul',
        '30',
        '212'
    ),
    array(
        'My Book',
        '2012',
        '',
        'Erwin',
        '20',
        '212'
    ),
);

$result_data = CraurCsvWriter::extractAllDescendants($craur, array(
    'name',
    'year',
    'categories[].name',
    'authors[].name',
    'authors[].age',
    'pages',
),'');


assert(json_encode($expected_data) == json_encode($result_data));


$craur = new Craur(array(
    'book' => array(
        array(
            'name' => 'My Book',
            'year' => '2012',
            'categories' => array(
                array('name' => 'comedy'),
            ),
            'authors' => array(
                array('name' => 'Paul', 'age' => '30'),
                array('name' => 'Erwin', 'age' => '20')
            ),
            'pages' => '212'
        )
    )
));

$expected_data = array(
    array(
        'My Book',
        '2012',
        'comedy',
        'Paul',
        '30',
        '212'
    ),
    array(
        'My Book',
        '2012',
        '',
        'Erwin',
        '20',
        '212'
    ),
);

$result_data = CraurCsvWriter::extractAllDescendants($craur, array(
    'book[].name',
    'book[].year',
    'book[].categories[].name',
    'book[].authors[].name',
    'book[].authors[].age',
    'book[].pages',
),'');


assert(json_encode($expected_data) == json_encode($result_data));




$craur = new Craur(
    array(
        'name' => 'My Book',
        'year' => '2012',
        'authors' => array(
            array('name' => 'Paul', 'age' => '30'),
            array('name' => 'Erwin', 'age' => '20'),
            array('name' => 'Hans', 'age' => '10')
        ),
        'categories' => array(
            'comedy',
            'fantasy'
        ),
        'pages' => '212'
    )
);

$expected_data = array(
    array(
        'My Book',
        '2012',
        'Paul',
        '30',
        'comedy',
        '212'
    ),
    array(
        'My Book',
        '2012',
        'Erwin',
        '20',
        'fantasy',
        '212'
    ),
    array(
        'My Book',
        '2012',
        'Hans',
        '10',
        '',
        '212'
    ),
);

$result_data = CraurCsvWriter::extractAllDescendants($craur, array(
    'name',
    'year',
    'authors[].name',
    'authors[].age',
    'categories[]',
    'pages',
),'');


assert(json_encode($expected_data) == json_encode($result_data));
