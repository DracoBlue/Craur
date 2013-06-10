<?php

$craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>Test Title</title></head><body></body></html>');

assert($craur->get('html.head.title') == 'Test Title');

$craur = Craur::createFromHtml(file_get_contents(dirname(__FILE__) . '/fixtures/strict_html_file.html'));
assert($craur->get('html.head.title') == 'Test Title');
assert($craur->get('html.@xmlns:atom') == 'http://www.w3.org/2005/Atom');
assert($craur->get('html.body.p.img.@width') == '20');
assert($craur->get('html.body.p.img.@height') == '30');
assert($craur->get('html.body.p.img.@src') == 'http://example.org/image.png');
assert($craur->get('html.body.p') == 'testtest2');

try
{
    $craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>Test Title</title></head><body></body><html>');
    assert(false);
}
catch (Exception $exception)
{
    assert(strpos($exception->getMessage(), 'Invalid html') > -1);
}
