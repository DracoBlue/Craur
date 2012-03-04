<?php
error_reporting(E_ALL | E_STRICT);
require_once('../Craur.class.php');

$xml_string = <<<XMLSTRING
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
        <title>Example Feed</title>
        <subtitle>A subtitle.</subtitle>
        <dc:link href="http://example.org/feed/" rel="self" />
        <link href="http://example.org/" />
        <id>urn:uuid:60a76c80-d399-11d9-b91C-0003939e0af6</id>
        <updated>2003-12-13T18:30:02Z</updated>
        <author>
                <name>John Doe</name>
                <email>johndoe@example.com</email>
        </author>
 
        <entry>
                <dc:title>My Title</dc:title>
                <title>Atom-Powered Robots Run Amok</title>
                <link href="http://example.org/2003/12/13/atom03" />
                <link rel="alternate" type="text/html" href="http://example.org/2003/12/13/atom03.html"/>
                <link rel="edit" href="http://example.org/2003/12/13/atom03/edit"/>
                <id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
                <updated>2003-12-13T18:30:02Z</updated>
                <summary>Some text.</summary>
        </entry>
</feed>

XMLSTRING;

$node = Craur::createFromXml($xml_string);

// $node = Craur::createFromJson(json_encode(array(
    // 'feed' => array(
        // '@xmlns' => 'http://www.w3.org/2005/Atom',
        // 'title' => array(
            // '@' => 'Example Feed',
            // 'lang' => 'en'
        // ),
        // 'link' => array(
            // array(
                // '@href' => 'http://example.org/feed/',
                // '@rel' => 'self'
            // ),
            // array(
                // '@href' => 'http://example.org',
            // )
        // ),
        // 'author' => array(
            // 'name' => 'John Doe',
            // 'email' => 'johndoe@example.com'
        // )
    // )
// )));
// 
// echo $node->get('feed.@xmlns') . PHP_EOL;
// echo $node->get('feed.title') . PHP_EOL;
// 
// $titles = $node->get('feed.title[]');
// var_dump((string) $titles[0]);
// 
// foreach ($node->get('feed.entry.link[]') as $link) {
    // var_dump($link->get('@href'));
// }

// echo $node->toXmlString();
// echo $node->toJsonString();

/*
 * Assertions
 */

 
assert((string) $node->get('feed.title') === 'Example Feed');
assert($node->get('feed.non_existant_key', 'default') === 'default');

try
{
    $node->get('feed.non_existant_key');
    /*
     * This should not work!
     */
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Nice, we got an exception!
     */
}

try
{
    $node->get('feed.non_existant_key[]');
    /*
     * This should not work!
     */
    assert(false);
}
catch (Exception $exception)
{
    /*
     * Nice, we got an exception!
     */
}


/*
 * In case of default value, it should work!
 */
$values = $node->get('feed.non_existant_key[]', array());

assert(is_array($values));
assert(count($values) === 0);

/*
 * JSON -> Craur -> XML -> Craur -> JSON 
 */
$json = $node->toJsonString();
$test_node_from_json = Craur::createFromJson($json);
$xml = $test_node_from_json->toXmlString();
$test_node_from_xml_from_json = Craur::createFromXml($xml);
$json_from_xml_from_json = $test_node_from_xml_from_json->toJsonString();

echo "Before:" . PHP_EOL;
echo $json .PHP_EOL . PHP_EOL;
echo "After:" . PHP_EOL;
echo $json_from_xml_from_json . PHP_EOL . PHP_EOL;
assert($json === $json_from_xml_from_json);
