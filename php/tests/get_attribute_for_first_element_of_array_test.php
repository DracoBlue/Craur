<?php
/*
 * @see <https://github.com/DracoBlue/Craur/issues/3>
 */

$node = Craur::createFromJson(json_encode(array(
    'book' => array(
        'author' => array(
            array(
                '@name' => 'Hans'
            ),
            array(
                '@name' => 'Paul'
            ),
        )
    )
)));

/*
 * Should be the first name
 */
assert('Hans' == $node->get('book.author.@name'));
