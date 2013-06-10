<?php
$xml_string = file_get_contents(dirname(__FILE__) . '/fixtures/working_feed.xml');
$node = Craur::createFromXml($xml_string);

/*
 * JSON -> Craur -> XML -> Craur -> JSON 
 */
$json = $node->toJsonString();
$test_node_from_json = Craur::createFromJson($json);
$xml = $test_node_from_json->toXmlString();
$test_node_from_xml_from_json = Craur::createFromXml($xml);
$json_from_xml_from_json = $test_node_from_xml_from_json->toJsonString();

assert($json === $json_from_xml_from_json);
