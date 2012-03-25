<?php

class Craur
{
    protected $data = null;

    /**
     * Create a new `Craur` from a given JSON-string.
     * 
     * @example 
     *     $node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
     *     $authors = $node->get('book.authors[]');
     *     assert(count($authors) == 2);
     * 
     * @return Craur
     */
    static function createFromJson($json_string)
    {
        $data = @json_decode($json_string, true);
        
        if (!$data)
        {
            throw new Exception('Invalid json: ' . $json_string);
        }
        
        return new Craur($data);
    }

    /**
     * Create a new `Craur` from a given XML-string.
     * 
     * @example 
     *     $node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
     *     $authors = $node->get('book.author[]');
     *     assert(count($authors) == 2);
     * 
     * @return Craur
     */
    static function createFromXml($xml_string)
    {
        $node = new DOMDocument('1.0', 'utf-8');
        $is_loaded = $node->loadXML($xml_string, LIBXML_NOCDATA | LIBXML_NOWARNING | LIBXML_NOERROR);
        
        if (!$is_loaded) 
        {
            throw new Exception('Invalid xml: ' . $xml_string);
        }

        $data = self::convertDomNodeToDataArray($node);

        $xpath = new DOMXPath($node);
        $root_node_name = $node->documentElement->nodeName;
        $namespaces = array();
        foreach ($xpath->query('namespace::*') as $namespace_node)
        {
            $namespace_name = $namespace_node->nodeName;
            if ($namespace_name !== 'xmlns:xml')
            {
                $namespaces[$namespace_name] = $namespace_node->nodeValue;
            }
        }
        $namespaces = array_reverse($namespaces, true);
        foreach ($namespaces as $namespace_name => $namespace_uri)
        {
            $data[$root_node_name]['@' . $namespace_name] = $namespace_uri;
        }

        return new Craur($data);
    }

    static function convertDomNodeToDataArray(DomNode $node)
    {
        $data = array();
        $values = array();
        $has_value = false;

        if ($node->hasChildNodes())
        {
            foreach ($node->childNodes as $child_node)
            {
                if ($child_node->nodeType === XML_TEXT_NODE)
                {
                    $has_value = true;
                    $values[] = $child_node->nodeValue;
                }
                else
                {
                    $key = $child_node->nodeName;

                    if (isset($data[$key]))
                    {
                        if (!is_array($data[$key]) || !isset($data[$key][0]))
                        {
                            $data[$key] = array($data[$key]);
                        }
                        $data[$key][] = self::convertDomNodeToDataArray($child_node);
                    }
                    else
                    {
                        $data[$key] = self::convertDomNodeToDataArray($child_node);
                    }
                }
            }
        }

        if ($node->hasAttributes())
        {
            foreach ($node->attributes as $attribute_node)
            {
                $key = '@' . $attribute_node->nodeName;
                $data[$key] = $attribute_node->nodeValue;
            }
        }

        if ($has_value)
        {
            $value = implode('', $values);

            if (trim($value))
            {
                if (empty($data))
                {
                    $data = $value;
                }
                else
                {
                    $data['@'] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Will load the csv file and fill the objects according to the given `$field_mappings`.
     * 
     * @example
     *     // If the file loooks like this:
     *     // Book Name;Book Year;Author Name
     *     // My Book;2012;Hans
     *     // My Book;2012;Paul
     *     // My second Book;2010;Erwin
     *     $shelf = Craur::createFromCsvFile('fixtures/books.csv', array(
     *         'book[].name',
     *         'book[].year',
     *         'book[].author[].name',
     *     ));
     *     assert(count($shelf->get('book[]')) === 2);
     *     foreach ($shelf->get('book[]') as $book)
     *     {
     *         assert(in_array($book->get('name'), array('My Book', 'My second Book')));
     *         foreach ($book->get('author[]') as $author)
     *         {
     *             assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
     *         }
     *     }
     * 
     * @return Craur  
     */
    static function createFromCsvFile($file_path, array $field_mappings)
    {
        $file_handle = null;
        
        if (file_exists($file_path))
        {
            $file_handle = fopen($file_path, "r");
        }
        
        if (!$file_handle)
        {
            throw new Exception('Cannot open file at ' . $file_path);
        }
        
        $row_number = 0;
        
        $current_entry = array();
        
        $raw_mapping_keys = array();
        $raw_identifier_keys = array();
        
        /*
         * $field_mappings is: array('book[].name', 'book[].year', 'book[].author[].name', 'book[].author[].age')
         */
        foreach ($field_mappings as $field_mapping)
        {
            /*
             * $field_mapping is something like: book[].author[].name
             */
            $last_brackets_pos = strrpos($field_mapping, '[]');
            if ($last_brackets_pos !== false)
            {
                $raw_identifier_key = substr($field_mapping, 0, $last_brackets_pos);
                $raw_identifier_key = str_replace('[]', '', $raw_identifier_key);
                /*
                 * $raw_identifier_key is now just: book.author
                 */
                $raw_identifier_keys[$raw_identifier_key] = $raw_identifier_key;
            }
            
            $raw_mapping_keys[] = str_replace('[]', '', $field_mapping);
        }
        
        /*
         * $raw_mapping_keys is now: array('book.name', 'book.year', 'book.author.name', 'book.author.age')
         */
        
        $raw_identifier_keys = array_values($raw_identifier_keys);
        /*
         * $raw_identifier_keys is now just: array('book', 'book.author')
         */
        
        $entries = array();
        
        while (($row_data = fgetcsv($file_handle, 23085, ";")) !== FALSE)
        {
            $row_number++;
            if ($row_number != 1)
            {
                $entries[] = self::expandPathsIntoArray($row_data, $raw_mapping_keys, $raw_identifier_keys);
            }
        }
        
        fclose($file_handle);
        
        $merged_entries = self::mergePathEntriesRecursive($entries);
        
        return new Craur($merged_entries);   
    }

    /**
     * This function is used internally to merge the entries from
     * expandPathsIntoArray into a proper form.
     * 
     * @private
     * 
     * @example
     *      $entries = array(
     *          array(
     *              'book' => array(
     *                  'name' => 'My Book',
     *                  'year' => 2012,
     *                  'author' => array(
     *                      'name' => 'Hans',
     *                      'age' => '32'
     *                  )
     *              )
     *          ),
     *          array(
     *              'book' => array(
     *                  'name' => 'My Book',
     *                  'year' => 2012,
     *                  'author' => array(
     *                      'name' => 'Paul',
     *                      'age' => '20'
     *                  )
     *              )
     *          ),
     *          array(
     *              'book' => array(
     *                  'name' => 'My second Book',
     *                  'year' => 2010,
     *                  'author' => array(
     *                      'name' => 'Erwin',
     *                      'age' => '10'
     *                  )
     *              )
     *          )
     *      );
     *      $merged_entries = Craur::mergePathEntriesRecursive($entries);
     *      assert(count($merged_entries) === 1);
     *      assert(json_encode(array(
     *          'book' => array(
     *              array(
     *                  'name' => 'My Book',
     *                  'year' => 2012,
     *                  'author' => array(
     *                      array(
     *                          'name' => 'Hans',
     *                          'age' => '32'
     *                      ),
     *                      array(
     *                          'name' => 'Paul',
     *                          'age' => '20'
     *                      )
     *                  )
     *              ),
     *              array(
     *                  'name' => 'My second Book',
     *                  'year' => 2010,
     *                  'author' => array(
     *                      array(
     *                          'name' => 'Erwin',
     *                          'age' => '10'
     *                      )
     *                  )
     *              )
     *          )
     *      )) === json_encode($merged_entries[0]));
     * 
     * @return array
     */
    static function mergePathEntriesRecursive(array $entries)
    {
        $merged_entries = array();
        
        $object_keys = array();
        $scalar_keys = array();
        
        foreach ($entries as $entry)
        {
            foreach ($entry as $key => $value)
            {
                if (is_scalar($value))
                {
                    $keys[$key] = $key;
                }
                else
                {
                    $object_keys[$key] = $key;
                }
            }
        }
        
        /*
         * $object_keys looks now like this: array('author')
         * $scalar_keys looks now like this: array('name', 'year')
         */
        
        foreach ($entries as $entry)
        {
            $primary_keys = array();
            foreach ($entry as $primary_key => $primary_value)
            {
                if (is_scalar($primary_value))
                {
                    $primary_keys[$primary_key] = $primary_value;
                }
            }
            
            /*
             * $primary_key_as_string is now like this: ::hans::2012
             */
            $primary_key_as_string = '::' . implode('::', $primary_keys);
            
            if (!isset($merged_entries[$primary_key_as_string]))
            {
                $merged_entries[$primary_key_as_string] = array(
                    'primary_keys' => $primary_keys,
                    'values' => array()
                );
            }
            
            $merged_entries[$primary_key_as_string]['values'][] = $entry;
        }
        
        $result_entries = array();
        
        foreach ($merged_entries as $entries_with_same_primary_key)
        {
            $result_entry = $entries_with_same_primary_key['primary_keys'];
            foreach ($object_keys as $object_key)
            {
                $result_entry[$object_key] = array();           
            }
            
            foreach ($entries_with_same_primary_key['values'] as $entry)
            {
                foreach ($object_keys as $object_key)
                {
                    if (isset($entry[$object_key]))
                    {
                        $result_entry[$object_key][] = $entry[$object_key];                        
                    }
                }
            }
            foreach ($object_keys as $object_key)
            {
                if (isset($result_entry[$object_key]))
                {
                    $result_entry[$object_key] = self::mergePathEntriesRecursive($result_entry[$object_key]);                        
                }
            }
            $result_entries[] = $result_entry;
        }
        
        return $result_entries;
    }

    /**
     * This function is used internally to map a csv row into an object.
     * 
     * @example
     *      $row_data = array(
     *          'My Book',
     *          2012,
     *          'Hans',
     *          '32'
     *      );
     *      $raw_mapping_keys = array(
     *          'book.name',
     *          'book.year',
     *          'book.author.name',
     *          'book.author.age'
     *      );
     *      $raw_identifier_keys = array(
     *          'book',
     *          'book.author'
     *      );
     *      $expected_entry = array(
     *         'book' => array(
     *             'name' => 'My Book',
     *             'year' => 2012,
     *              'author' => array(
     *                  'name' => 'Hans',
     *                  'age' => '32'
     *              )
     *          )
     *      );
     * 
     *      assert(json_encode($expected_entry) === json_encode(Craur::expandPathsIntoArray($row_data, $raw_mapping_keys, $raw_identifier_keys)));
     * @return array
     */
    static function expandPathsIntoArray(array $row_data, array $raw_mapping_keys, array $raw_identifier_keys)
    {
        $entry = array();
        
        $entry_sub_entries = array();
        
        /*
         * First of all, extract all direct keys (no . in the name).
         * 
         * e.g.: name, age
         */
        foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
        {
            if (array_key_exists($pos, $row_data))
            {
                if (strpos($raw_mapping_key, '.') !== false)
                {
                    /*
                     * Something like: book.author
                     */
                    $entry_sub_entries[$raw_mapping_key] = $row_data[$pos];
                }
                else
                {
                    /*
                     * Something like: name
                     */
                    $entry[$raw_mapping_key] = $row_data[$pos];
                }
            }
        }
        
        /*
         * $entry looks now like this: array('name' => 'My Book', 'year' => 2012);
         * $entry_sub_entries looks now like this: array('author.name' => 'hans', 'author.age' => 32);
         */
        
        /*
         * Now retrieve all data which is stored within an element (e.g. the name in author.name)
         */
        foreach ($raw_identifier_keys as $raw_identifier_key)
        {
            if (strpos($raw_identifier_key, '.') !== false)
            {
                /*
                 * We have a key like book.author.name, will be handled after recursion!
                 */
                continue ;
            }
            $sub_raw_identifier_keys = array();
            $sub_raw_mapping_keys = array();
            $sub_raw_data = array();
            $sub_pos = 0;
            foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
            {
                if (array_key_exists($pos, $row_data))
                {
                    if (substr($raw_mapping_key, 0, strlen($raw_identifier_key) + 1) == $raw_identifier_key . '.')
                    {
                        $sub_raw_mapping_keys[] = substr($raw_mapping_key, strlen($raw_identifier_key) + 1);
                        if (strlen($row_data[$pos]) > 0)
                        {
                            $sub_raw_data[$sub_pos] = $row_data[$pos];
                        }
                        $sub_pos++;
                    }
                }
            }
            foreach ($raw_identifier_keys as $sub_raw_identifier_key)
            {
                if (substr($sub_raw_identifier_key, 0, strlen($raw_identifier_key) + 1) == $raw_identifier_key . '.')
                {
                    $sub_raw_identifier_keys[] = substr($sub_raw_identifier_key, strlen($raw_identifier_key) + 1);
                }
            }
            
            if (empty($sub_raw_data))
            {
                continue ;
            }
            
            $entry[$raw_identifier_key] = self::expandPathsIntoArray($sub_raw_data, $sub_raw_mapping_keys, $sub_raw_identifier_keys);
        }
        
        return $entry;
    }
    

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Return multiple values at once. If a given path is not set, one can use
     * the `$default_values` array to specify a default. If a path is not set
     * and no default value is given an exception will be thrown.
     * 
     * @param {String[String]} $paths_map A map of values `$paths_map[$key_in_values]=$path_in_craur`
     * @param {String[String]} $default_values A map of default values `$paths_map[$key_in_values]=$default_value`
     * @param {mixed} $default_value A value which can be used as default if even the `$default_values` do not have a key for the path
     * 
     * @example
     *     $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
     * 
     *     $values = $node->getValues(
     *         array(
     *             'name' => 'book.name',
     *             'book_price' => 'price',
     *             'first_author' => 'book.authors'
     *         ),
     *         array(
     *             'book_price' => 20
     *         )
     *     );
     * 
     *     assert($values['name'] == 'MyBook');
     *     assert($values['book_price'] == '20');
     *     assert($values['first_author'] == 'Hans');
     * 
     * @return $values[String][]
     */
    public function getValues(array $paths_map, array $default_values = array(), $default_value = null)
    {
        $values = array();
        
        foreach ($paths_map as $value_key => $path)
        {
            if (array_key_exists($value_key, $default_values))
            {
                /*
                 * Yay, we have a default value!
                 */
                $values[$value_key] = $this->get($path, $default_values[$value_key]);
            }
            else
            {
                if (func_num_args() < 3)
                {
                     /*
                     * If we have no default_value parameter supplied
                     */
                    $values[$value_key] = $this->get($path);
                }
                else
                {
                    $values[$value_key] = $this->get($path, $default_value);
                }
                
            } 
        }
        
        return $values;   
    }

    /**
     * Works similar to `Craur#getValues`, but can use a callable as filter
     * object. Before returning the value, the function evaluates
     * `$filter($value)` and returns this instead.
     * 
     * If the `$filter` throws an exception, the value won't be added to the
     * result.
     * 
     * @example
     *     $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
     *
     *     $values = $node->getValuesWithFilters(
     *         array(
     *             'name' => 'book.name',
     *             'book_price' => 'price',
     *             'first_author' => 'book.authors'
     *         ),
     *         array(
     *             'name' => 'strtolower',
     *             'first_author' => 'strtoupper',
     *         ),
     *         array(
     *             'book_price' => 20
     *         )
     *     );
     *     
     *     assert($values['name'] == 'mybook');
     *     assert($values['book_price'] == '20');
     *     assert($values['first_author'] == 'HANS');
     * 
     * @return $values[String][]
     */ 
    public function getValuesWithFilters(array $paths_map, array $filters, array $default_values = array(), $default_value = null)
    {
        $values = array();
        
        foreach ($paths_map as $value_key => $path)
        {
            $has_filter = array_key_exists($value_key, $filters);
            
            if ($has_filter)
            {
                $filter = $filters[$value_key];
                if (array_key_exists($value_key, $default_values))
                {
                    /*
                     * Yay, we have a default value!
                     */
                    $values[$value_key] = $this->getWithFilter($path, $filter, $default_values[$value_key]);
                }
                else
                {
                    if (func_num_args() < 4)
                    {
                         /*
                         * If we have no default_value parameter supplied
                         */
                        $values[$value_key] = $this->getWithFilter($path, $filter);
                    }
                    else
                    {
                        $values[$value_key] = $this->getWithFilter($path, $filter, $default_value);
                    }
                }
            }
            else
            {
                if (array_key_exists($value_key, $default_values))
                {
                    /*
                     * Yay, we have a default value!
                     */
                    $values[$value_key] = $this->get($path, $default_values[$value_key]);
                }
                else
                {
                    if (func_num_args() < 4)
                    {
                         /*
                         * If we have no default_value parameter supplied
                         */
                        $values[$value_key] = $this->get($path);
                    }
                    else
                    {
                        $values[$value_key] = $this->get($path, $default_value);
                    }
                } 
            }
        }
        
        return $values;   
    }
        
    /**
     * Returns the value at a given path in the object. If the given path does
     * not exist and an explicit `$default_value` is set: the `$default_value`
     * will be returned. 
     * 
     * @param {String} $path The path to the value (e.g. `book.name` or `book.authors[]`)
     * @param {mixed} $default_value The default value, which will be returned if the path has no value
     * 
     * @example
     *     $node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');
     * 
     *     $book = $node->get('book');
     *     assert($book->get('name') == 'MyBook');
     *     assert($book->get('price', 20) == 20);
     * 
     *     $authors = $node->get('book.authors[]');
     *     assert(count($authors) == 2);
     * 
     * @return mixed
     */
    public function get($path, $default_value = null)
    {
        $current_node = $this->data;

        $return_multiple = false;

        if (substr($path, -2) === '[]')
        {
            $return_multiple = true;
            $path = substr($path, 0, strlen($path) - 2);
        }

        /*
         * 1. Find the data for the path
         */
        foreach (explode('.', $path) as $part)
        {
            if (is_array($current_node) && !isset($current_node[$part]) && isset($current_node[0]))
            {
                /*
                 * We have a non associative array, maybe we want just the
                 * first element?
                 */
                $current_node = $current_node[0];
            }
            
            if (!isset($current_node[$part]))
            {
                if (func_num_args() < 2)
                {
                    /*
                     * If we have no default_value parameter supplied
                     */
                    throw new Exception('Path not found: ' . $path);
                }
                
                return $default_value;            
            }
            $current_node = $current_node[$part];
        }

        /*
         * 2. Now return the value
         */

        if (!$return_multiple)
        {
            /*
             * If we expect just one value!
             */
            if (is_array($current_node) && empty($current_node))
            {
                if (func_num_args() < 2)
                {
                     /*
                     * If we have no default_value parameter supplied
                     */
                    throw new Exception('Path not found: ' . $path);
                }
                
                return $default_value;            
            }

            if (is_array($current_node) && isset($current_node[0]))
            {
                /*
                 * We have something like
                 *
                 *    current_node = [
                 *        "value",
                 *        "value2"
                 *    ]
                 *
                 * let's use the first value!
                 */
                $current_node = $current_node[0];
            }

            /*
             * It's no array, so let's return it
             */
            if (!is_array($current_node))
            {
                return $current_node;
            }

            /*
             * Associative array - let's return it as Craur node!
             */
            return new Craur($current_node);
        }

        /*
         * If we expect multiple values!
         */
        if (is_array($current_node) && empty($current_node))
        {
            return array();
        }

        if (is_array($current_node) && isset($current_node[0]))
        {
            /*
             * Return each value!
             */
            $results = array();

            foreach ($current_node as $result_data)
            {
                if (!is_array($result_data))
                {
                    $result_data = array('@' => $result_data);
                }
                $results[] = new Craur($result_data);
            }

            return $results;
        }

        /*
         * It's no array yet, because it's just one element. Let's return
         * an array with one item!
         */
        if (is_array($current_node))
        {
            return array(new Craur($current_node));
        }

        return array($current_node);
    }

    /**
     * Works similar to `Craur#get`, but can use a callable as filter object.
     * Before returning the value, the function evaluates `$filter($value)`
     * and returns this instead.
     * 
     * If the `$filter` throws an exception, the value won't be added to the
     * result.
     * 
     * @example
     *     function isACheapBook(Craur $value)
     *     {
     *         if ($value->get('price') > 20)
     *         {
     *             throw new Exception('Is no cheap book!');
     *         }
     *         return $value;
     *     }
     *     
     *     $node = Craur::createFromJson('{"books": [{"name":"A", "price": 30}, {"name": "B", "price": 10}, {"name": "C", "price": 15}]}');
     *     $cheap_books = $node->getWithFilter('books[]', 'isACheapBook');
     *     assert(count($cheap_books) == 2);
     *     assert($cheap_books[0]->get('name') == 'B');
     *     assert($cheap_books[1]->get('name') == 'C');
     * 
     * @return mixed
     */
    public function getWithFilter($path, $filter, $default_value = null)
    {
        $has_default_value = (func_num_args() > 2);

        $return_multiple = false;

        if (substr($path, -2) === '[]')
        {
            $return_multiple = true;
            $path = substr($path, 0, strlen($path) - 2);
        }
        
        if (!is_callable($filter))
        {
            throw new Exception('Cannot use ' . gettype($filter) . ' as filter, only callables allowed!');
        }
        
        try
        {
            $values_without_filter = $this->get($path . '[]');
            $values = array();
            foreach ($values_without_filter as $value_without_filter)
            {
                try 
                {
                    $value = $filter($value_without_filter);
                    if (!$return_multiple)
                    {
                        return $value;
                    }
                    
                    $values[] = $value;
                }
                catch (Exception $exception)
                {
                    /*
                     * Ok, no match!
                     */
                }
            }
            
            if ($return_multiple)
            {
                return $values;
            }
            
            throw new Exception('No element for this path found (after filtering)');
        }
        catch (Exception $exception)
        {
            if ($has_default_value)
            {
                return $default_value;
            }
            throw new Exception('Path not found!');
        }
    }

    public function __toString()
    {

        if (isset($this->data['@']))
        {
            return $this->data['@'];
        }

        throw new Exception('Cannot convert to string, since value is missing!');
    }

    /**
     * Return the object as a json string. Can be loaded from `Craur::createFromJson`.
     * 
     * @return {String}
     */
    public function toJsonString()
    {
        return json_encode($this->data);
    }

    /**
     * Return the object as a xml string. Can be loaded from `Craur::createFromXml`.
     * 
     * @return {String}
     */
    public function toXmlString()
    {
        return $this->convertNodeDataToXml($this->data);
    }

    protected function convertNodeDataToXml(array $data)
    {
        if (isset($data['@']) && count($data) === 1)
        {
            return htmlspecialchars($data['@']);
        }

        $result_buffer = array();

        foreach ($data as $key => $value)
        {
            if (substr($key, 0, 1) === '@')
            {
                /*
                 * Ok, just an attribute (we made them in a recursion before
                 * this element)
                 */
                if ($key === '@')
                {
                    /*
                     * Nice, we finally have the value:
                     */
                    $result_buffer[] = htmlspecialchars($value);
                }
                
                continue;
            }

            if (is_array($value) && isset($value[0]))
            {
                /*
                 * Multiple elements!
                 */
                foreach ($value as $sub_value)
                {
                    $tmp_data = array();
                    $tmp_data[$key] = $sub_value;
                    $result_buffer[] = $this->convertNodeDataToXml($tmp_data);
                }

                continue;
            }

            $has_inner_value = false;

            $result_buffer[] = '<' . htmlspecialchars($key);

            $attributes = array();
            $has_also_non_attributes = false;

            if (is_array($value))
            {
                foreach ($value as $sub_key => $sub_value)
                {
                    if (substr($sub_key, 0, 1) == '@' && strlen($sub_key) > 1)
                    {
                        $result_buffer[] = ' ' . substr($sub_key, 1) . '="' . htmlspecialchars($sub_value) . '"';
                    }
                    else
                    {
                        $has_also_non_attributes = true;
                    }
                }
            }
            else
            {
                $has_also_non_attributes = true;
            }

            if (!$has_also_non_attributes)
            {
                $result_buffer[] = '/>';
                continue;
            }

            $result_buffer[] = '>';

            /*
             * Just one!
             */
            if (is_array($value))
            {
                /*
                 * Multiple elements!
                 */
                $result_buffer[] = $this->convertNodeDataToXml($value);
            }
            else
            {
                $result_buffer[] = htmlspecialchars($value);
            }

            $result_buffer[] = '</' . htmlspecialchars($key) . '>';
        }

        return implode('', $result_buffer);
    }

    /**
     * This function is used internally to map a csv row into an object.
     * 
     * @example
     *      $entry = new Craur(array(
     *         'book' => array(
     *             'name' => 'My Book',
     *             'year' => '2012',
     *              'author' => array(
     *                  'name' => 'Hans',
     *                  'age' => '32'
     *              )
     *          )
     *      ));
     *      $raw_mapping_keys = array(
     *          'book.name',
     *          'book.year',
     *          'book.author.name',
     *          'book.author.age'
     *      );
     *      $raw_identifier_keys = array(
     *          'book',
     *          'book.author'
     *      );
     *      $expected_row_data = array(
     *          'My Book',
     *          '2012',
     *          'Hans',
     *          '32'
     *      );
     * 
     *      assert(json_encode($expected_row_data) === json_encode(Craur::extractPathsFromObject($entry, $raw_mapping_keys, $raw_identifier_keys)));
     * @return array
     */
    static function extractPathsFromObject(Craur $entry, array $raw_mapping_keys, array $raw_identifier_keys)
    {
        $scalar_values = array();
        
        foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
        {
            if (strpos($raw_mapping_key, '.') === false)
            {
                /*
                 * Something like: name or age
                 */
                $scalar_values[$pos] = (string) $entry->get($raw_mapping_key);
            }
        }
        
        $rows = array();
        
        foreach ($raw_identifier_keys as $raw_identifier_key)
        {
            if (strpos($raw_identifier_key, '.') !== false)
            {
                /*
                 * We have a key like book.author, will be handled after recursion!
                 */
                continue ;
            }
            
            $sub_raw_mapping_keys = array();
            
            foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
            {
                if (substr($raw_mapping_key, 0, strlen($raw_identifier_key) + 1) == $raw_identifier_key . '.')
                {
                    $sub_raw_mapping_keys[$pos] = substr($raw_mapping_key, strlen($raw_identifier_key) + 1);
                }
            }
            
            
            foreach ($entry->get($raw_identifier_key . '[]') as $sub_entry)
            {
                $row = array();
                
                foreach ($scalar_values as $pos => $scalar_value)
                {
                    $row[$pos] = $scalar_value;
                }
                
                foreach ($sub_raw_mapping_keys as $pos => $sub_raw_mapping_key)
                {
                    $row[$pos] = (string) $sub_entry->get($sub_raw_mapping_key);
                }
                
                $rows[] = $row;
            }
        }
        
        if (empty($rows))
        {
            $rows[] = $scalar_values;
        }
        
        return $rows;
    }    
}
