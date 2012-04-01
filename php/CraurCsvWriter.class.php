<?php

class CraurCsvWriter {
    
    /**
     * @var Craur
     */
    protected $root = null;
    protected $field_mappings = array();
    protected $raw_mapping_keys = array();
    
    public function __construct(Craur $root, array $field_mappings)
    {
        $this->root = $root;
        $this->field_mappings = $field_mappings;
        list($this->raw_mapping_keys) = Craur::getRawMappingAndIdentifiers($this->field_mappings);
    }
    
   /**
     * This function can be used to write the csv directly into a file handle
     * (e.g. STDOUT). It's will be called by `saveToCsvFile`.
     */
    public function writeToCsvFileHandle($file_handle)
    {
        $rows = self::extractAllDescendants($this->root, $this->raw_mapping_keys, $this->field_mappings);
        
        foreach ($rows as $row)
        {
            fputcsv($file_handle, $row, ';');
        }
    }

    /**
     * Only write the direct descendants into a new row.
     * 
     * @example
     *     $craur = new Craur(
     *         array(
     *             'name' => 'My Book',
     *             'year' => '2012',
     *             'categories' => array( // Will be ignored
     *                 'comedy',          
     *                 'fantasy'           
     *             ),
     *             'authors' => array( // Will be ignored
     *                 array('name' => 'Paul'),
     *                 array('name' => 'Erwin')
     *             ),
     *             'pages' => '212'
     *         )
     *     );
     *     
     *     $expected_data = array(
     *         0 => 'My Book',
     *         1 => '2012',
     *         4 => '212'
     *     );
     *     
     *     $result_data = CraurCsvWriter::extractDirectDescendants($craur, array(
     *         'name',
     *         'year',
     *         'categories',
     *         'authors.name',
     *         'pages'
     *     ), array(
     *         'name',
     *         'year',
     *         'categories[]',
     *         'authors[].name',
     *         'pages',
     *     ),'');
     *     
     *     assert(json_encode($expected_data) == json_encode($result_data)); 
     * @return array
     */
    static function extractDirectDescendants(Craur $craur, $raw_mapping_keys, $field_mappings, $prefix = '')
    {
        $row = array();

        foreach ($raw_mapping_keys as $pos => $raw_mapping_key_with_prefix)
        {
            if (substr($raw_mapping_key_with_prefix, 0, strlen($prefix)) == $prefix)
            {
                /*
                 * Get rid of the prefix
                 */
                $raw_mapping_key = substr($raw_mapping_key_with_prefix, strlen($prefix));
                
                if (strpos($raw_mapping_key, '.') === false)
                {
                    /*
                     * Something like: name, age or categories
                     * 
                     * So the $field_mappings[$pos] is something like: book[].name, book[].author[].age or book[].categories[]
                     * which indicates, if we want one or multiple values
                     */
                    if (substr($field_mappings[$pos], -2, 2) !== '[]')
                    {
                        /*
                         * We want one value!
                         */
                        $row[$pos] = $craur->get($raw_mapping_key, '');
                        
                    }
                }
            }
        }
        
        return $row;
    }

    /**
     * Write all (including the direct descendants) into multiple rows.
     * 
     * @example
     *     $craur = new Craur(
     *         array(
     *             'name' => 'My Book',
     *             'year' => '2012',
     *             'authors' => array(
     *                 array('name' => 'Paul', 'age' => '30'),
     *                 array('name' => 'Erwin', 'age' => '20'),
     *             ),
     *             'categories' => array(
     *                 'comedy',
     *                 'fantasy'
     *             ),
     *             'pages' => '212'
     *         )
     *     );
     *     
     *     $expected_data = array(
     *         array(
     *             'My Book',
     *             '2012',
     *             'Paul',
     *             '30',
     *             'comedy',
     *             '212'
     *         ),
     *         array(
     *             'My Book',
     *             '2012',
     *             'Erwin',
     *             '20',
     *             'fantasy',
     *             '212'
     *         )
     *     );
     *     
     *     $result_data = CraurCsvWriter::extractAllDescendants($craur, array(
     *         'name',
     *         'year',
     *         'authors.name',
     *         'authors.age',
     *         'categories',
     *         'pages'
     *     ), array(
     *         'name',
     *         'year',
     *         'authors[].name',
     *         'authors[].age',
     *         'categories[]',
     *         'pages',
     *     ),'');
     * 
     *     assert(json_encode($expected_data) == json_encode($result_data));
     * 
     * @return array
     */
    static function extractAllDescendants(Craur $craur, $raw_mapping_keys, $field_mappings, $prefix = '', $field_mapping_prefix = '')
    {
        $scalar_values = self::extractDirectDescendants($craur, $raw_mapping_keys, $field_mappings, $prefix);
        $sub_keys = array();

        /*
         * Get rid of the prefix and fetch all possible sub_keys with valid position:
         * 
         * e.g. $sub_keys[author][3] => 'name' and $sub_keys[author][4] => 'age'
         */
        foreach ($raw_mapping_keys as $pos => $raw_mapping_key_with_prefix)
        {
            if (substr($raw_mapping_key_with_prefix, 0, strlen($prefix)) == $prefix)
            {
                $raw_mapping_key = substr($raw_mapping_key_with_prefix, strlen($prefix));
                $raw_field_mapping = substr($field_mappings[$pos], strlen($field_mapping_prefix));
                if (strpos($raw_field_mapping, '[]') !== false)
                {
                    /*
                     * Something like: author.name, author.age or author.cities
                     * 
                     * So the $field_mappings[$pos] is something like: book[].author[].name,
                     * book[].author[].age or book[].author[].cities 
                     * which indicates, if we want one or multiple values
                     */
                    $sub_key_root = substr($raw_field_mapping, 0, strpos($raw_field_mapping, '[]'));
                    $sub_key_value = substr($raw_field_mapping, strpos($raw_field_mapping, '[]') + 3);
                    
                    if (!isset($sub_keys[$sub_key_root]))
                    {
                        $sub_keys[$sub_key_root] = array();
                    }
                    $sub_keys[$sub_key_root][$pos] = $sub_key_value;
                    /*
                     * Default value must be empty, if we have none!
                     */
                    $scalar_values[$pos] = '';
                }
            }
        }

        /* 
         * $sub_keys looks like this now: array(
         *     'author' => array(
         *         3 => 'name',
         *         4 => 'age'
         *     ),
         *     'categories' => array(
         *         5 => ''
         *     )
         * )
         */

        $rows = array();

        $sub_entries = array();
        foreach($sub_keys as $sub_key => $fields)
        {
            $depth_offset = 0;
            
            /*
             * $sub_key is something like 'author' or 'categories'
             */
            foreach ($craur->get($sub_key . '[]', array()) as $sub_entry)
            {
                if (implode('', $fields) === '')
                {
                    /*
                     * If we don't have something like author[].name and we
                     * have just: categories[], we can luckily do an implode
                     * on all fields (which are empty) :).
                     * 
                     * In this case, we just want to (string)-casted value
                     */
                    $sub_row = array();
                    foreach ($fields as $pos => $_)
                    {
                        $sub_row[$pos] = (string) $sub_entry;
                    }
                    $sub_rows = array($sub_row);
                }
                else
                {
                    /*
                     * If we have real sub values (like author[].name and such
                     * stuff, we need to do the recursion)
                     */
                    $sub_prefix = ($prefix === '' ? '' : $prefix) . $sub_key . '.';
                    $sub_field_mapping_prefix = ($field_mapping_prefix === '' ? '' : $field_mapping_prefix) . $sub_key . '[].';
                    $sub_rows = self::extractAllDescendants($sub_entry, $raw_mapping_keys, $field_mappings, $sub_prefix, $sub_field_mapping_prefix);
                }
                
                foreach ($sub_rows as $depth => $sub_row)
                {
                    /*
                     * We don't have anything in this row (at $depth_offset) yet,
                     * thus we create a new row
                     */
                    if (!isset($sub_entries[$depth_offset]))
                    {
                        $sub_entries[$depth_offset] = $scalar_values;
                    }
                    
                    /*
                     * Now copy all values of that sub_row to the current row
                     * at this depth
                     */
                    foreach ($sub_row as $pos => $value)
                    {
                        $sub_entries[$depth_offset][$pos] = $value;
                    }
                    
                    /*
                     * Fix numeric key sort, otherwise json_encode creates {"1":"value2", "0": "value1"}
                     */
                    ksort($sub_entries[$depth_offset]);
                    $depth_offset++;
                }
            }
        }
        
        /*
         * If we don't have any sub_entries, we just need to return the scalar values as the only
         * item of the array
         */
        if (empty($sub_entries))
        {
            return array($scalar_values);
        }
        
        /*
         * The scalar values are in each row, so let's return the sub_entries without any further modifications
         */
        return $sub_entries;
    }

}
