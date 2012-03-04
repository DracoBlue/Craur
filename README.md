# Craur

This is still work in progress. But release early and release often :).

The library craur has two main purposes:

1. Implement a convention to convert XML to JSON without loosing any information
2. Query a JSON/XML-String to recieve elements or exactly one element

## What is wrong with vanilla JSON?

There is nothing wrong with JSON. But take this example:

    item = {
        "link": "http://example.org"
    }

If you want to query for the link, you do: `item.link`. But what if there can be multiple links? Like this:

    item = {
        "link": ["http://example.org", "http://subdomain.example.org"]
    }

Now you have to use `item.link[0]` to query for the first one. If you are converting xml programmaticly to json, you cannot be sure what is meant.

With craur querying for this value looks like this:

    craur_node.get('item.link') // gets: "http://example.org"

And if you want to have an array, you do it like this:

    craur_node.get('item.link[]') // gets: ["http://example.org", "http://subdomain.example.org"]

For craur it does not matter if you have an array or a simple object. Both calls will work.

## Example in php

This example is how it looks like if you parse a simple atom-feed with craur.

    $craur_node = Craur::createFromXml($xml_string);
    var_dump($craur_node->->get('feed.@xmlns')); // http://www.w3.org/2005/Atom
    foreach ($craur_node->get('feed.entry.link[]') as $link) {
        var_dump($link->get('@href'));
    }

## Changelog

- 1.0-dev
  - added Makefile (do `make test` to execute tests)
  - added default_value for `Craur->get($path, $default_value)`
  - initial version 

## License

This work is copyright by DracoBlue (<http://dracoblue.net>) and licensed under the terms of MIT License.
