<?php

class CraurCsvWriter {
    
    /**
     * @var Craur
     */
    protected $root = null;
    protected $field_mappings = array();
    protected $raw_mapping_keys = array();
    protected $raw_identifier_keys = array();
    
    public function __construct(Craur $root, array $field_mappings)
    {
        $this->root = $root;
        $this->field_mappings = $field_mappings;
        list($this->raw_mapping_keys, $this->raw_identifier_keys) = Craur::getRawMappingAndIdentifiers($this->field_mappings);
    }
    
   /**
     * This function can be used to write the csv directly into a file handle
     * (e.g. STDOUT). It's will be called by `saveToCsvFile`.
     */
    public function writeToCsvFileHandle($file_handle)
    {
        $rows = self::extractPathsFromObject($this->root, $this->raw_mapping_keys, $this->raw_identifier_keys);
        
        /*
         * We will have to fill up all empty cols with empty strings,
         * otherwise the fputcsv would completely leave the value
         * for that col and the entire indention in the csv is broken!
         */
        $empty_row = array_fill(0, count($this->field_mappings), '');
        
        foreach ($rows as $row)
        {
            /*
             * Merge, but preserve keys!
             */
            $row = $row + $empty_row;
            /*
             * We have to ksort now, because the + operator does not
             * fix the order in numeric arrays :(
             */
            ksort($row);
            fputcsv($file_handle, $row, ';');
        }
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
     *      $expected_rows_data = array(
     *          array(
     *              'My Book',
     *              '2012',
     *              'Hans',
     *              '32'
     *          )
     *      );
     * 
     *      assert(json_encode($expected_rows_data) === json_encode(CraurCsvWriter::extractPathsFromObject($entry, $raw_mapping_keys, $raw_identifier_keys)));
     * @return array
     */
    static function extractPathsFromObject(Craur $entry, array $raw_mapping_keys, array $raw_identifier_keys, $prefix = '')
    {
        /*
         * Get rid of the prefix
         */
        foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
        {
            if (substr($raw_mapping_key, 0, strlen($prefix)) == $prefix)
            {
                $raw_mapping_keys[$pos] = substr($raw_mapping_key, strlen($prefix));
            }
            else
            {
                unset($raw_mapping_keys[$pos]);
            }
        }
        
        foreach ($raw_identifier_keys as $pos => $raw_identifier_key)
        {
            if (substr($raw_identifier_key, 0, strlen($prefix)) == $prefix)
            {
                $raw_identifier_keys[$pos] = substr($raw_identifier_key, strlen($prefix));
            }
            else
            {
                unset($raw_identifier_keys[$pos]);
            }
        }

        /*
         * Do the work
         */
        $scalar_values = array();
        
        foreach ($raw_mapping_keys as $pos => $raw_mapping_key)
        {
            if (strpos($raw_mapping_key, '.') === false)
            {
                /*
                 * Something like: name or age
                 */
                $scalar_values[$pos] = (string) $entry->get($raw_mapping_key, '');
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
                elseif ($raw_mapping_key == $raw_identifier_key)
                {
                    $sub_raw_mapping_keys[$pos] = '';
                }
            }
            
            $sub_raw_identifier_keys = array();
            
            foreach ($raw_identifier_keys as $pos => $possible_sub_raw_identifier_key)
            {
                if (substr($possible_sub_raw_identifier_key, 0, strlen($raw_identifier_key) + 1) == $raw_identifier_key . '.')
                {
                    $sub_raw_identifier_key =  substr($possible_sub_raw_identifier_key, strlen($raw_identifier_key) + 1);
                    /*
                     * Only children, not grand children
                     */
                    if (strpos($sub_raw_identifier_key, '.') === false)
                    {
                        $sub_raw_identifier_keys[] = $sub_raw_identifier_key;
                    }
                }
            }
            
            foreach ($entry->get($raw_identifier_key . '[]', array()) as $sub_entry)
            {
                $row = $scalar_values;
 
                foreach ($sub_raw_mapping_keys as $pos => $sub_raw_mapping_key)
                {
                    if (strpos($sub_raw_mapping_key, '.') === false)
                    {
                        if ($sub_raw_mapping_key == '')
                        {
                            $row[$pos] = (string) $sub_entry;
                        }
                        else
                        {
                            $row[$pos] = (string) $sub_entry->get($sub_raw_mapping_key, '');
                        }
                    }
                }
                
                $sub_sub_entries_count = 0;
                foreach ($sub_raw_identifier_keys as $sub_raw_identifier_key)
                {
                    foreach ($sub_entry->get($sub_raw_identifier_key . '[]', array()) as $sub_sub_entry)
                    {
                        $sub_rows = self::extractPathsFromObject($sub_sub_entry, $raw_mapping_keys, $raw_identifier_keys, $raw_identifier_key . '.' . $sub_raw_identifier_key . '.');
                        
                        foreach ($sub_rows as $sub_row_values)
                        {
                            $sub_sub_entries_count++;
                            $sub_row = $row;
                            
                            foreach ($sub_row_values as $pos => $scalar_value)
                            {
                                $sub_row[$pos] = $scalar_value;
                            }
    
                            $rows[] = $sub_row;
                        }
                    }
                }
                
                if (!$sub_sub_entries_count)
                {
                    /*
                     * ok we had no (successful) sub identifiers for this identifier
                     */
                    $rows[] = $row;
                }
            }
        }
        
        if (empty($rows))
        {
            $rows[] = $scalar_values;
        }
        
        return $rows;
    }    
}
