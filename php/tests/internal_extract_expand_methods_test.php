<?php

$entry = new Craur(array(
    'name' => 'My Book',
    'year' => '2012'
));
$raw_mapping_keys = array(
    'name',
    'year'
);
$field_mappings = array(
    'name',
    'year'
);
$expected_row_data = array(
    'My Book',
    '2012'
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode(array($expected_row_data)) == json_encode($results_row_data));

$entry = new Craur(array(
    'name' => 'My Book',
    'year' => '2012',
    'author' => array(
        'name' => 'Hans',
        'age' => '32'
    )
));
$raw_mapping_keys = array(
    'name',
    'year',
    'author.name',
    'author.age'
);
$field_mappings = array(
    'name',
    'year',
    'author[].name',
    'author[].age'
);
$expected_row_data = array(
    'My Book',
    '2012',
    'Hans',
    '32'
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode(array($expected_row_data)) == json_encode($results_row_data));


$entry = new Craur(array(
    'name' => 'My Book',
    'year' => '2012',
    'author' => array(
        'name' => 'Hans',
        'age' => '32'
    )
));
$raw_mapping_keys = array(
    'name',
    'year',
    'author.name',
    'author.age'
);
$field_mappings = array(
    'name',
    'year',
    'author[].name',
    'author[].age'
);
$expected_row_data = array(
    'My Book',
    '2012',
    'Hans',
    '32'
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode(array($expected_row_data)) == json_encode($results_row_data));



$entry = new Craur(array(
   'book' => array(
       'name' => 'My Book',
       'year' => '2012',
        'author' => array(
            'name' => 'Hans',
            'age' => '32'
        )
    )
));
$raw_mapping_keys = array(
    'book.name',
    'book.year',
    'book.author.name',
    'book.author.age'
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age'
);
$expected_row_data = array(
    'My Book',
    '2012',
    'Hans',
    '32'
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode(array($expected_row_data)) == json_encode($results_row_data));





$entry = new Craur(array(
   'book' => array(
        array(
           'name' => 'My Book',
           'year' => '2012',
            'author' => array(
                'name' => 'Hans',
                'age' => '32'
            )
        ),
        array(
           'name' => 'My second Book',
           'year' => '2010',
            'author' => array(
                'name' => 'Paul',
                'age' => '20'
            )
        )
    )
));
$raw_mapping_keys = array(
    'book.name',
    'book.year',
    'book.author.name',
    'book.author.age'
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age'
);
$expected_rows_data = array(
    array(
        'My Book',
        '2012',
        'Hans',
        '32'
    ),
    array(
        'My second Book',
        '2010',
        'Paul',
        '20'
    )
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode($expected_rows_data) == json_encode($results_row_data));



$entry = new Craur(array(
   'book' => array(
        array(
           'name' => 'My second Book',
           'year' => '2010',
            'author' => array(
                'name' => 'Paul',
                'age' => '20'
            )
        ),
        array(
            'name' => 'My Book',
            'year' => '2012',
            'author' => array(
                array(
                    'name' => 'Hans',
                    'age' => '32'
                ),
                array(
                    'name' => 'Erwin',
                    'age' => '10'
                )
            )
        )
    )
));
$raw_mapping_keys = array(
    'book.name',
    'book.year',
    'book.author.name',
    'book.author.age'
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age'
);
$expected_rows_data = array(
    array(
        'My second Book',
        '2010',
        'Paul',
        '20'
    ),
    array(
        'My Book',
        '2012',
        'Hans',
        '32'
    ),
    array(
        'My Book',
        '2012',
        'Erwin',
        '10'
    )
);

$results_row_data = CraurCsvWriter::extractAllDescendants($entry, $raw_mapping_keys, $field_mappings);

assert(json_encode($expected_rows_data) == json_encode($results_row_data));

