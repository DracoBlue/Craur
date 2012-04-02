<?php

$row_data = array(
    'My Book',
    2012,
    'Hans',
    '32',
    'fantasy'
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age',
    'book[].category[]'
);
$expected_entry = array('book' => array(
        'name' => 'My Book',
        'year' => 2012,
        'author' => array(
            'name' => 'Hans',
            'age' => '32'
        ),
        'category' => array('fantasy')
    ));

$result_data = CraurCsvReader::expandPathsIntoArray($row_data, $field_mappings);

assert(json_encode($expected_entry) === json_encode($result_data));

$row_data = array(
    'My Book',
    2012,
    'Hans',
    '32',
    'fantasy'
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age',
    'book[].category[].name'
);
$expected_entry = array(
    'book' => array(
        'name' => 'My Book',
        'year' => 2012,
        'author' => array(
            'name' => 'Hans',
            'age' => '32'
        ),
        'category' => array(
            'name' => 'fantasy'
        )
    )
);

$result_data = CraurCsvReader::expandPathsIntoArray($row_data, $field_mappings);

assert(json_encode($expected_entry) === json_encode($result_data));



$row_data = array(
    'My Book',
    2012,
    'Hans',
    '32',
    ''
);
$field_mappings = array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].age',
    'book[].category[].name'
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

$result_data = CraurCsvReader::expandPathsIntoArray($row_data, $field_mappings);

assert(json_encode($expected_entry) === json_encode($result_data));




$entries = array(
    array(
        'book' => array(
            'name' => 'My Book',
            'year' => 2012,
            'author' => array(
                'name' => 'Hans',
                'age' => '32'
            ),
            'category' => array(
                'comedy'
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
            ),
            'category' => array(
                'fantasy'
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
            ),
            'category' => array(
                'comedy'
            )
        )
    )
);

$expected_entries = array(
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
            ),
            'category' => array(
                'comedy',
                'fantasy',
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
            ),
            'category' => array(
                'comedy',
            )
        )
    )
);
$merged_entries = CraurCsvReader::mergePathEntriesRecursive($entries);

assert(count($merged_entries) === 1);
assert(json_encode($expected_entries) === json_encode($merged_entries[0]));

