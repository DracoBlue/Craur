<?php
$xml_string = file_get_contents(dirname(__FILE__) . '/fixtures/example_atom_feed.xml');
$document = Craur::createFromXml($xml_string);

/*
 * Can we grab all feeds? (it should be just one)
 */
$feeds = $document->get('feed[]');
assert(count($feeds) === 1);

$feed = $document->get('feed');

foreach ($feed->get('entry[]', array()) as $entry)
{
    /*
     * At least 1 contributor
     */
    $contributors = $entry->get('contributor[]');
    assert(count($contributors) > 0);
    
    /*
     * first link.@rel must be alternate or self
     */
    assert(in_array($entry->get('link.@rel'), array('alternate', 'self')));
    
    $entry_data = $entry->getValues(
        array(
            'id' => 'id',
            'title' => 'title',
            'link' => 'link.@href',
            'author_name' => 'author.name'
        )
    );
    
    assert(0 === count(array_diff(array_keys($entry_data), array('id', 'title', 'link', 'author_name'))));
}
