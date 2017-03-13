<?php

$backslash = "\\";

//echo"hans.hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans.hans2"));
//echo"hans${backslash}.hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}.hans2"));
//echo"hans${backslash}hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}hans2"));
//echo"hans${backslash}${backslash}hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}${backslash}hans2"));
//echo"hans${backslash}${backslash}.hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}${backslash}.hans2"));
//echo"hans${backslash}${backslash}${backslash}.hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}${backslash}${backslash}.hans2"));
//echo"hans${backslash}${backslash}${backslash}${backslash}.hans2" . PHP_EOL;
//print_r(Craur::unescapePath("hans${backslash}${backslash}${backslash}${backslash}.hans2"));

assert("hans hans2" == implode(' ', Craur::unescapePath("hans.hans2")));
assert("hans.hans2" == implode(' ', Craur::unescapePath("hans${backslash}.hans2")));
assert("hans${backslash} hans2" == implode(' ', Craur::unescapePath("hans${backslash}${backslash}.hans2")));
assert("hans${backslash} hans2${backslash} hans3" == implode(' ', Craur::unescapePath("hans${backslash}${backslash}.hans2${backslash}${backslash}.hans3")));
assert("hans${backslash}.hans2" == implode(' ', Craur::unescapePath("hans${backslash}${backslash}${backslash}.hans2")));
assert("hans${backslash}${backslash} hans2" == implode(' ', Craur::unescapePath("hans${backslash}${backslash}${backslash}${backslash}.hans2")));
assert("hans${backslash}${backslash}.hans2" == implode(' ', Craur::unescapePath("hans${backslash}${backslash}${backslash}${backslash}${backslash}.hans2")));

$data = array(
    'website' => array(
        'http://example.com' => 'testWithDot',
        'hans\\key' => 'testWithBackslash',
        'hans\\' => array(
            'subValue' => 'testWithSubValue'
        ),
    )
);

$node = Craur::createFromJson(json_encode($data));

assert('testWithDot' == $node->get('website.http://example\.com'));
assert('testWithBackslash' == $node->get('website.hans\\key'));
assert('testWithSubValue' == $node->get('website.hans\\\\.subValue'));

$node = Craur::createFromJson('{"http://example.org": {"name": "Example Site"}}');

$book = $node->get('http://example\.org');
assert($book->get('name') == 'Example Site');

$node = Craur::createFromJson('{"http://example\\\\.org": {"name": "Example Site"}}');

$book = $node->get('http://example\\\\\\.org');
assert($book->get('name') == 'Example Site');
