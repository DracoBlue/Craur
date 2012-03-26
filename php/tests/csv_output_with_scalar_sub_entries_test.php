<?php

$data = array(
    'book' => array(
        array(
            'name' => 'My Book',
            'year' => '2012',
            'author' => array(
                array(
                    'name' => 'Hans',
                    'cities' => array(
                        'Berlin',
                        'London'
                    )
                ),
                array(
                    'name' => 'Paul'
                )
            )
        ),
        array(
            'name' => 'My second Book',
            'year' => '2010',
            'author' => array(
                array(
                    'name' => 'Erwin'
                )
            )
        )
    )
);

$shelf = new Craur($data);

$shelf->saveToCsvFile('fixtures/temp_csv_file.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
    'book[].author[].cities[]',
));

$result_csv_content = trim(file_get_contents('fixtures/temp_csv_file.csv'));
unlink('fixtures/temp_csv_file.csv');

$lines = array_slice(explode(PHP_EOL, $result_csv_content), 1);

$expected_lines = array(
    '"My Book";2012;Hans;Berlin',
    '"My Book";2012;Hans;London',
    '"My Book";2012;Paul;',
    '"My second Book";2010;Erwin;',
);

assert(count(array_diff($expected_lines, $lines)) === 0);


