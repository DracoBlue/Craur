<?php

$craur = Craur::createFromXml(file_get_contents('fixtures/example_atom_feed.xml'));

$craur->saveToCsvFile('fixtures/temp_csv_file.csv', array(
    'feed.entry[].title',
    'feed.entry[].link[].@rel',
    'feed.entry[].link[].nonexistantsubkey[].name',
    'feed.entry[].link[].@href',
));

$result_csv_content = trim(file_get_contents('fixtures/temp_csv_file.csv'));
unlink('fixtures/temp_csv_file.csv');

$lines = array_slice(explode(PHP_EOL, $result_csv_content), 1);

$expected_lines = array(
    '"Atom draft-07 snapshot";alternate;;http://example.org/2005/04/02/atom',
    '"Atom draft-07 snapshot";enclosure;;http://example.org/audio/ph34r_my_podcast.mp3'
);

assert(count(array_diff($expected_lines, $lines)) === 0);


