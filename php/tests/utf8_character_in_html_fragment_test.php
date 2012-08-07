<?php

$craur = Craur::createFromHtml('<a href="http://dracoblue.net">© by … dracoblue</a>');
assert($craur->get('a') == '© by … dracoblue');
assert($craur->get('a.@href') == 'http://dracoblue.net');

