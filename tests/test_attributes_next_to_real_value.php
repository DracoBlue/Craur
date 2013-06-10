<?php
/*
 * @see <https://github.com/DracoBlue/Craur/issues/1>
 */

$node = Craur::createFromJson(json_encode(array(
    'feed' => array(
        'title' => array(
            '@' => 'Example Feed',
            '@lang' => 'en'
        )
    )
)));

assert('Example Feed' == $node->get('feed.title'));
/*
 * So this Example Feed should be in the xml and json response
 */
assert(strpos($node->toJsonString(), 'Example Feed') > 0);
assert(strpos($node->toXmlString(), 'Example Feed') > 0);