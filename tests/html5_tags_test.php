<?php
$craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>HTML5 Test</title></head><body><aside>A sidebar?</aside></body></html>');

assert($craur->get('html.head.title') == 'HTML5 Test');
assert($craur->get('html.body.aside') == 'A sidebar?');

try
{
    $craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>HTML5 Test</title></head><body><aside>A sidebar?</aside></body><html>');
    assert(false);
}
catch (Exception $exception)
{
    assert(strpos($exception->getMessage(), 'Invalid html') > -1);
}

try
{
    $craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>HTML5 Test</title></head><body><aside>A sidebar?</aside><img></img></body></html>');
    assert(false);
}
catch (Exception $exception)
{
    assert(strpos($exception->getMessage(), 'Unexpected end tag : img') > -1);
}

try
{
    $craur = Craur::createFromHtml('<!DOCTYPE html>' . PHP_EOL . '<html><head><title>HTML5 Test</title></head><body><img></img><aside>A sidebar?</aside></body></html>');
    assert(false);
}
catch (Exception $exception)
{
    assert(strpos($exception->getMessage(), 'Unexpected end tag : img') > -1);
}
