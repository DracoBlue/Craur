<?php

$invalid_html = file_get_contents(dirname(__FILE__) . '/fixtures/invalid_character_in_html.html');

$craur = Craur::createFromHtml($invalid_html, 'iso-8859-1');

assert($craur->get('html.head.title') == 'xx');