# Craur

* Latest Release: [![GitHub version](https://badge.fury.io/gh/DracoBlue%2FCraur.png)](https://github.com/DracoBlue/Craur/releases)
* Build Status: [![Build Status](https://secure.travis-ci.org/DracoBlue/Craur.png?branch=master)](http://travis-ci.org/DracoBlue/Craur), 100% Code Coverage

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

The tests are located at `php/tests/`. The tests require `xdebug` to be installed and activated. A
successful test must have 100% code coverage.

### Constant/Continuous Testing

If you have `inotifywait` [linux, apt-get install inotify-tools] or `wait_on` [macosx, port install wait_on] installed, you can use:

    make test-constant

This will run the tests as soon as the files change. Very helpful if you want to do continuous testing.

## Api

### Craur::createFromJson(`$json_string`) : `Craur`

Will create and return a new craur instance for the given JSON string.

     $node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
     $authors = $node->get('book.authors[]');
     assert(count($authors) == 2);

### Craur::createFromXml(`$xml_string[, $encoding = 'utf-8']) : `Craur`

Will create and return a new craur instance for the given XML string.

      $node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
      $authors = $node->get('book.author[]');
      assert(count($authors) == 2);

### Craur::createFromHtml(`$html_string[, $encoding = 'utf-8']) : `Craur`

Will create and return a new craur instance for the given HTML string.

    $node = Craur::createFromHtml('<html><head><title>Hans</title></head><body>Paul</body></html>');
    assert($node->get('html.head.title') == 'Hans');
    assert($node->get('html.body') == 'Paul');

### Craur::createFromCsvFile(`$file_path, array $field_mappings`) : `Craur`

Will load the csv file and fill the objects according to the given `$field_mappings`.

    /*
     * If the file loooks like this:
     * Book Name;Book Year;Author Name
     * My Book;2012;Hans
     * My Book;2012;Paul
     * My second Book;2010;Erwin
     */
    $shelf = Craur::createFromCsvFile('fixtures/books.csv', array(
        'book[].name',
        'book[].year',
        'book[].author[].name',
    ));
    assert(count($shelf->get('book[]')) === 2);
    foreach ($shelf->get('book[]') as $book)
    {
        assert(in_array($book->get('name'), array('My Book', 'My second Book')));
        foreach ($book->get('author[]') as $author)
        {
            assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
        }
    }  

### Craur::createFromExcelFile(`$file_path, array $field_mappings`) : `Craur`

Will load the first sheet of an excel file and fill the objects according to the given `$field_mappings`.

    /*
     * If the file loooks like this:
     * Book Name;Book Year;Author Name
     * My Book;2012;Hans
     * My Book;2012;Paul
     * My second Book;2010;Erwin
     */
    $shelf = Craur::createFromExcelFile('fixtures/books.xlsx', array(
        'book[].name',
        'book[].year',
        'book[].author[].name',
    ));
    assert(count($shelf->get('book[]')) === 2);
    foreach ($shelf->get('book[]') as $book)
    {
        assert(in_array($book->get('name'), array('My Book', 'My second Book')));
        foreach ($book->get('author[]') as $author)
        {
            assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
        }
    }  
    
### Craur::createFromYamlFile(`$file_path`) : `Craur`

Will create and return a new craur instance for the given YAML file path.

     * If the file loooks like this:
     * books:
     * -
     *   name: My Book
     *   year: 2012
     *   authors:
     *     -
     *       name: Hans
     *       age: 32
     *     -
     *       name: Paul
     *       age: 20
     *  -
     *    name: My second Book
     *      authors:
     *        name: Erwin
     *        age: 10
     */
    $shelf = Craur::createFromYamlFile('fixtures/books.yaml', array());
    assert(count($shelf->get('books[]')) === 2);
    foreach ($shelf->get('books[]') as $book)
    {
        assert(in_array($book->get('name'), array('My Book', 'My second Book')));
        foreach ($book->get('authors[]') as $author)
        {
            assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
        }
    }

### Craur#get(`$path[, $default_value]`) : `Craur`|`mixed` 

Returns the value at a given path in the object. If the given path does not exist and an explicit `$default_value` is set: the `$default_value` will be returned. 

    $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
    
    $book = $node->get('book');
    assert($book->get('name') == 'MyBook');
    assert($book->get('price', 20) == 20);
    
    $authors = $node->get('book.authors[]');
    assert(count($authors) == 2);

### Craur#getWithFilter(`$path, $filter[, $default_value]`) : `Craur`|`mixed` 

Works similar to `Craur#get`, but can use a callable as filter object. Before returning the value, the function evaluates `$filter($value)` and returns this instead.

    $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
    
    $book = $node->get('book');
    assert($book->get('name') == 'MyBook');
    assert($book->get('price', 20) == 20);
    
    $authors = $node->get('book.authors[]');
    assert(count($authors) == 2);

The filter can also throw an exception to hide the value from the result set:
    
    function isACheapBook(Craur $value)
    {
        if ($value->get('price') > 20)
        {
            throw new Exception('Is no cheap book!');
        }
        return $value;
    }
    
    $node = Craur::createFromJson('{"books": [{"name":"A", "price": 30}, {"name": "B", "price": 10}, {"name": "C", "price": 15}]}');
    $cheap_books = $node->getWithFilter('books[]', 'isACheapBook');
    assert(count($cheap_books) == 2);
    assert($cheap_books[0]->get('name') == 'B');
    assert($cheap_books[1]->get('name') == 'C');

### Craur#getValues(`array $paths_map[, array $default_values, $default_value]`) : `mixed[]`

Return multiple values at once. If a given path is not set, one can use the `$default_values` array to specify a default. If a path is not set and no default value is given an exception will be thrown. If you want to have a default value, even if the path does not exist in `$default_values`, you can use `$default_value`.

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

### Craur#getValuesWithFilters(`array $paths_map, array $filters [, array $default_values, $default_value]`) : `mixed[]`

Works like `Craur#getValues`, but allows to set filters for each key in the `$path_map`.

    $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
    
    $values = $node->getValuesWithFilters(
        array(
            'name' => 'book.name',
            'book_price' => 'price',
            'first_author' => 'book.authors'
        ),
        array(
            'first_author' => 'strtoupper',
            'name' => 'strtolower'      
        ),
        array(
            'book_price' => 20
        )
    );
    
    assert($values['name'] == 'MyBook');
    assert($values['book_price'] == '20');
    assert($values['first_author'] == 'HANS');

### Craur#toJsonString() : `String`

Return the object as a json string. Can be loaded from `Craur::createFromJson`.

### Craur#toXmlString() : `String`

Return the object as a xml string. Can be loaded from `Craur::createFromXml`.

### Craur#saveToCsvFile(`$file_path, array $field_mappings`) : `void`

Will store the csv file with the objects content according to the given
`$field_mappings`. The file can be loaded with `Craur::loadFromCsvFile` and
the same `$field_mappings`.

    $data = array(
        'book' => array(
            array(
                'name' => 'My Book',
                'year' => '2012',
                'author' => array(
                    array('name' => 'Hans'),
                    array('name' => 'Paul')
                )
            ),
            array(
                'name' => 'My second Book',
                'year' => '2010',
                'author' => array(
                    array('name' => 'Erwin')
                )
            )
        )
    );
    
    $shelf = new Craur($data);
    $shelf->saveToCsvFile('fixtures/temp_csv_file.csv', array(
        'book[].name',
        'book[].year',
        'book[].author[].name',
    ));
    
The csv file will look like this now:

    book[].name;book[].year;book[].author[].name
    "My Book";2012;Hans
    "My Book";2012;Paul
    "My second Book";2010;Erwin
    
### 

### Craur#writeToCsvFileHandle(`$file_handle, array $field_mappings`) : `void`

Will write into the given file handle the objects content according to the given
`$field_mappings`. Use `STDOUT` constant as `$file_handle` if you want to echo the
csv content. This method is used by `Craur#saveToCsvFile`.

    $data = array(
        'book' => array(
            array(
                'name' => 'My Book',
                'year' => '2012',
                'author' => array(
                    array('name' => 'Hans'),
                    array('name' => 'Paul')
                )
            ),
            array(
                'name' => 'My second Book',
                'year' => '2010',
                'author' => array(
                    array('name' => 'Erwin')
                )
            )
        )
    );
    
    $shelf = new Craur($data);
    $shelf->writeToCsvFileHandle(STDOUT, array(
        'book[].name',
        'book[].year',
        'book[].author[].name',
    ));
    
    // will echo:
    // "My Book";2012;Hans
    // "My Book";2012;Paul
    // "My second Book";2010;Erwin

## Cli

As of 1.5.0 you can use craur on the commandline, too. Just pipe any content
into the craur-binary and you can get the content as json, xml or csv.

Example (xml to json):

    $ cat php/tests/fixtures/example_atom_feed.xml | php/craur --output_format json

Example (xml to csv, with field mapping - see `--output_format` for more details)

    $ cat php/tests/fixtures/example_atom_feed.xml | php/craur --output_format csv feed.link[].@rel feed.link[].@href
    // output:
    alternate;http://example.org/
    self;http://example.org/feed.atom

### Input-Format with `--input_format [json|xml|csv|auto]`

Specify the input format. Default is auto.

### Output-Format with `--output_format [json|xml|csv]`

Specify the output format.

Example (xml to json)

    $ cat php/tests/fixtures/example_atom_feed.xml | php/craur --output_format json
    // output:
    // ... lots of json ...

If you specify the output_format as `csv`, you have to give the field mappings
as parameter. To get all rel-attributes and href-attributes of the feed's link
element, you can do this:

    $ cat php/tests/fixtures/example_atom_feed.xml | php/craur --output_format csv feed.link[].@rel feed.link[].@href
    // output:
    alternate;http://example.org/
    self;http://example.org/feed.atom

## Changelog

- 2.0.0 (2015/04/28)
  - BC: `__toString` returns empty string if the xml tag has only attributes) - was throwing a fatal error earlier
- 1.8.2 (2015/03/17)
  - downgrade to phpexcel `1.7.8` (instead of 1.8.0)
- 1.8.1 (2015/03/17)
  - switched to `phpoffice/phpexcel` since `codeplex/phpexcel` is deprecated
- 1.8.0 (2014/07/19)
  - added support for html5 tags (by simply ignoring every unexpected tag) #24
- 1.7.4 (2013/06/28)
  - xml with 0 as value, did not work
  - calling a class did not work as filter
- 1.7.3 (2013/06/10)
  - use composer package for phpexcel instead of custom pearplex repository
- 1.7.2 (2013/06/10)
  - added composer package information
  - moved files from php/ to src/
  - moved tests from php/tests to tests/
  - moved craur from php/craur to bin/craur
  - renamed from .class.php to .php
- 1.7.1 (2013/02/05)
  - throw exception on empty xml string (fixes #14)
- 1.7.0 (2012/09/22)
  - excluded naith into composer.json
  - added composer.json for dependency managment
  - added `Craur::createFromExcelFile($file_path, array $field_mappings)`
  - added `Craur::createFromYamlFile($file_path)`
- 1.6.0 (2012/08/07)
  - added html as input_format to craur cli
  - added possibility to load html fragments (breaking change: fragments no longer create html.body stub)
- 1.5.3 (2012/04/16)
  - added `Craur::createFromHtml($html_string, $encoding = 'utf-8')`
- 1.5.2 (2012/04/12)
  - strip invalid utf8 characters in createFromXml
  - added encoding parameter for createFromXml
- 1.5.1 (2012/04/05)
  - allow same csv mapping for multiple columns
- 1.5.0 (2012/04/02)
  - added cli for craur
  - added `saveToCsvFile($file_path, array $field_mappings)`
  - added `writeToCsvFileHandle($file_handle, array $field_mappings)`
  - fixed csv file test
  - only add csv values, which are not empty
  - added method to generate csv rows out of an object
  - bundled naith as testing framework (<https://github.com/DracoBlue/naith>)
  - allow csv field_mapping even with gaps (e.g. feed[] and feed[].entry.categories[] works now)
  - fixed import mapping with scalar sub values (e.g. issues[].tag[])
- 1.4.1 (2012/03/14)
  - added `make test-constant` (watches for file changes with inotifywait on linux
    or wait_on on mac osx ) and runs tests on change
- 1.4.0 (2012/03/14)
  - added `Craur::createFromCsvFile($file_path, array $field_mappings)`
- 1.3.0 (2012/03/09)
  - added `getWithFilter($path, Callable $filter[, $default_value])`
  - added `getValuesWithFilters($path, array $filters[, array $default_values, $default_value])`
  - prepend the bootstrap file to all test files
  - ignore the test files themself in code coverage
- 1.2.0 (2012/03/06)
  - added extra `$default_value` optional parameter for Craur#getValues
  - added minimum code coverage for the tests to make a successful build
  - initialize the Craur also with just a plain php array
  - added summary for code coverage as text
  - added (disabled) experimental support for clover.xml code coverage files
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
