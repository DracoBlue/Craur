# Craur

* Version: 1.1.0
* Date: 2012/03/06
* Build Status: [![Build Status](https://secure.travis-ci.org/DracoBlue/Craur.png?branch=master)](http://travis-ci.org/DracoBlue/Craur)

The library craur has two main purposes:

1. Make writing Xml/Json Importers very convenient (query for multiple elements or exactly one element)
2. Implement a convention to convert XML to JSON without loosing any information

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

    $craur_node->get('item.link') // gets: "http://example.org"

And if you want to have an array, you do it like this:

    $craur_node->get('item.link[]') // gets: ["http://example.org", "http://subdomain.example.org"]

For craur it does not matter if you have an array or a simple object. Both calls will work.

You may even define a default value, in case the property is optional:

    $craur_node->get('item.description', 'Default Text!') // returns 'Default Text!'

## Example in PHP

This example is how it looks like if you parse a simple atom-feed with craur.

    $craur_node = Craur::createFromXml($xml_string);
    var_dump($craur_node->get('feed.@xmlns')); // http://www.w3.org/2005/Atom
    foreach ($craur_node->get('feed.entry.link[]') as $link) {
        var_dump($link->get('@href'));
    }

If you want to see more examples, please checkout the `php/tests/` folder. It contains a lot of examples.

## Tests

You can run the tests with:

    make test

The tests are located at `php/tests/`.

## Api

### Craur::createFromJson(`$json_string`) : `Craur`

Will create and return a new craur instance for the given JSON string.

     $node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
     $authors = $node->get('book.authors[]');
     assert(count($authors) == 2);

### Craur::createFromXml(`$xml_string`) : `Craur`

Will create and return a new craur instance for the given XML string.

      $node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
      $authors = $node->get('book.author[]');
      assert(count($authors) == 2);

### Craur#get(`$path[, $default_value]`) : `Craur`|`mixed` 

Returns the value at a given path in the object. If the given path does not exist and an explicit `$default_value` is set: the `$default_value` will be returned. 

    $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
    
    $book = $node->get('book');
    assert($book->get('name') == 'MyBook');
    assert($book->get('price', 20) == 20);
    
    $authors = $node->get('book.authors[]');
    assert(count($authors) == 2);

### Craur#getValues(`array $paths_map[, array $default_values]`) : `mixed[]`

Return multiple values at once. If a given path is not set, one can use the `$default_values` array to specify a default. If a path is not set and no default value is given an exception will be thrown.

    $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
    
    $values = $node->getValues(
        array(
            'name' => 'book.name',
            'book_price' => 'price',
            'first_author' => 'book.authors'
        ),
        array(
            'book_price' => 20
        )
    );
    
    assert($values['name'] == 'MyBook');
    assert($values['book_price'] == '20');
    assert($values['first_author'] == 'Hans');

### Craur#toJsonString() : `String`

Return the object as a json string. Can be loaded from `Craur::createFromJson`.

### Craur#toXmlString() : `String`

Return the object as a xml string. Can be loaded from `Craur::createFromXml`.

## Changelog

- 1.1.0 (2012/03/06)
  - throw fatal error in case of failed assertion or an exception
  - throw error on invalid json
- 1.0.0 (2012/03/05)
  - added lots of phpdoc
  - Makefile uses ./run_tests.sh wrapper, to fail properly if one of the tests fails
  - it's now possible to retrieve a value of the first array element
  - Craur#get now also returns associative arrays as new Craur-objects, instead of failing
  - added bootstrap_for_test.php, so we can properly fail on warnings/assertions
  - added `Craur#getValues` to return multiple paths at once
  - split up the tests into separate files
  - added Makefile (do `make test` to execute tests)
  - added default_value for `Craur->get($path, $default_value)`
  - initial version 

## License

This work is copyright by DracoBlue (<http://dracoblue.net>) and licensed under the terms of MIT License.
