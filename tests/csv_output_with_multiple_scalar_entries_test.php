<?php

return ;

$data = array(
    'issues' => array(
        array(
            'number' => '123',
            'labels' => array(
                'bug',
                'feature'
            )
        ),
        array(
            'number' => '815',
            'labels' => array(
            )
        )
    )
);

$shelf = new Craur($data);

$shelf->saveToCsvFile('fixtures/temp_csv_file.csv', array(
    'issues[].number',
    'issues[].labels[]',
));

$result_csv_content = trim(file_get_contents('fixtures/temp_csv_file.csv'));
unlink('fixtures/temp_csv_file.csv');
echo $result_csv_content;
$lines = array_slice(explode(PHP_EOL, $result_csv_content), 1);

$expected_lines = array(
    '123;bug',
    '123;feature',
    '815;',
);

assert(count(array_diff($expected_lines, $lines)) === 0);


