<?php

require_once (dirname(__FILE__) . '/CraurCsvWriter.php');
require_once (dirname(__FILE__) . '/CraurCsvReader.php');

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
    static function createFromXml($xml_string, $encoding = 'utf-8')
    {
        $xml_string = preg_replace('/[\x1-\x8\xB-\xC\xE-\x1F]/', '', $xml_string);
        
        if ($encoding != 'utf-8')
        {
            $xml_string = iconv($encoding, 'utf-8', $xml_string);
        }
        
        $node = new DOMDocument('1.0', 'utf-8');

        if (empty($xml_string)) 
        {
            throw new Exception('Empty xml string');
        }

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

    /**
     * Create a new `Craur` from a given HTML-string.
     * 
     * @example 
     *     $node = Craur::createFromHtml('<html><head><title>Hans</title></head><body>Paul</body></html>');
     *     assert($node->get('html.head.title') == 'Hans');
     *     assert($node->get('html.body') == 'Paul');
     * 
     * @return Craur
     */
    static function createFromHtml($html_string, $encoding = 'utf-8')
    {
        $html_string = preg_replace('/[\x1-\x8\xB-\xC\xE-\x1F]/', '', $html_string);
        
        if ($encoding != 'utf-8')
        {
            $html_string = iconv($encoding, 'utf-8', $html_string);
        }
        
        $node = new DOMDocument('1.0', 'utf-8');
        
        $is_just_a_fragment = (strpos(strtolower($html_string), '<html') === false) ? true : false;
        
        if ($is_just_a_fragment)
        {
        	$html_string = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=' . $encoding . '"/></head><body>' . $html_string . '</body></html>';
        }
        
        /*
         * FIXME: Can we check if that was enabled in first place?
         */
        libxml_use_internal_errors(true);
        $node->loadHTML($html_string);
        $errors = libxml_get_errors();
        libxml_use_internal_errors(false);


        if (!empty($errors))
        {
            foreach ($errors as $error)
            {
                /* XML_HTML_UNKNOWN_TAG = 801 */
                if ($error->code != 801)
                {
                    throw new Exception('Invalid html (' . trim($error->message) . ', line: ' . $error->line . ', col: ' . $error->column . '): ' . $html_string);
                }
            }
        }
        
        $data = self::convertDomNodeToDataArray($node);
        
        if ($is_just_a_fragment)
        {
        	$data = $data['html']['body'];	
        }
        
        /*
         * We don't need to parse for namespaces here (like in the xml case), 
         * because namespaces are just attributes in html!
         */
        
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
                /*
                 * A html dom node always contains one dom document type child
                 * node with no content (DOMDocumentType#internalSubset is for
                 * example <!DOCTYPE html>). Ignore it!
                 */
                if ($child_node instanceof DOMDocumentType)
                {
                    continue ;
                }
                
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

            if (trim($value) != '')
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
        
        $craur = CraurCsvReader::createFromCsvFileHandle($file_handle, $field_mappings);
        
        fclose($file_handle);
        
        return $craur;   
    }

    /**
     * Will load the first sheet of an xlsx file and fill the objects according to the given `$field_mappings`.
     * 
     * @example
     *     // If the file loooks like this:
     *     // Book Name;Book Year;Author Name
     *     // My Book;2012;Hans
     *     // My Book;2012;Paul
     *     // My second Book;2010;Erwin
     *     $shelf = Craur::createFromExcelFile('fixtures/books.xlsx', array(
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
    static function createFromExcelFile($file_path, array $field_mappings)
    {
        $file_handle = null;
        
        if (!file_exists($file_path))
        {
            throw new Exception('Cannot open file at ' . $file_path);
        }
        
        $row_number = 0;
        
        $current_entry = array();
        
        $entries = array();

        $excel_object = PHPExcel_IOFactory::load($file_path);
        
        $rows = $excel_object->getActiveSheet()->toArray(null,true,true,true);
        
        foreach ($rows as $row_data)
        {
            $row_number++;
            if ($row_number != 1)
            {
                $entries[] = CraurCsvReader::expandPathsIntoArray(array_values($row_data), $field_mappings);
            }
        }
        
        $merged_entries = CraurCsvReader::mergePathEntriesRecursive($entries);
        
        return new Craur($merged_entries);  
    }

    /**
     * Will load the first sheet of an xlsx file and fill the objects according to the given `$field_mappings`.
     * 
     * @example
     *     // If the file loooks like this:
     *     // * books:
     *     //   -
     *     //     name: My Book
     *     //     year: 2012
     *     //     authors:
     *     //       -
     *     //         name: Hans
     *     //         age: 32
     *     //       -
     *     //         name: Paul
     *     //         age: 20
     *     //   -
     *     //     name: My second Book
     *     //     authors:
     *     //       name: Erwin
     *     //       age: 10
     *     $shelf = Craur::createFromYamlFile('fixtures/books.yaml', array());
     *     assert(count($shelf->get('books[]')) === 2);
     *     foreach ($shelf->get('books[]') as $book)
     *     {
     *         assert(in_array($book->get('name'), array('My Book', 'My second Book')));
     *         foreach ($book->get('authors[]') as $author)
     *         {
     *             assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
     *         }
     *     }
     * 
     * @return Craur  
     */
    static function createFromYamlFile($file_path)
    {
        $file_handle = null;
        
        if (!file_exists($file_path))
        {
            throw new Exception('Cannot open file at ' . $file_path);
        }
        
        $array = Symfony\Component\Yaml\Yaml::parse($file_path);
        
        return new Craur($array);  
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
                    $value = call_user_func_array($filter, array($value_without_filter));
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
        if (array_key_exists('@', $this->data))
        {
            return $this->data['@'];
        }

        return '';
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
     * Will store the csv file with the objects content according to the given `$field_mappings`.
     * 
     * @example
     *     $data = array(
     *         'book' => array(
     *             array(
     *                 'name' => 'My Book',
     *                 'year' => '2012',
     *                 'author' => array(
     *                     array('name' => 'Hans'),
     *                     array('name' => 'Paul')
     *                 )
     *             ),
     *             array(
     *                 'name' => 'My second Book',
     *                 'year' => '2010',
     *                 'author' => array(
     *                     array('name' => 'Erwin')
     *                 )
     *             )
     *         )
     *     );
     * 
     *     $shelf = new Craur($data);
     *     $shelf->saveToCsvFile('fixtures/temp_csv_file.csv', array(
     *         'book[].name',
     *         'book[].year',
     *         'book[].author[].name',
     *     ));
     * 
     *     // csv file will look like this now:
     *     // book[].name;book[].year;book[].author[].name
     *     // "My Book";2012;Hans
     *     // "My Book";2012;Paul
     *     // "My second Book";2010;Erwin
     * 
     *     assert(json_encode(array($data)) == Craur::createFromCsvFile('fixtures/temp_csv_file.csv', array(
     *         'book[].name',
     *         'book[].year',
     *         'book[].author[].name',
     *     ))->toJsonString());
     * 
     *     unlink('fixtures/temp_csv_file.csv');
     * 
     * @return void
     */
    public function saveToCsvFile($csv_file_path, array $field_mappings)
    {
        /*
         * Clean the file
         */
        file_put_contents($csv_file_path, '');
        $file_handle = fopen($csv_file_path, 'w');
        
        fputcsv($file_handle, $field_mappings, ';');
        
        $writer = new CraurCsvWriter($this, $field_mappings);
        $writer->writeToCsvFileHandle($file_handle);
        
        fclose($file_handle);
    }
     
}
