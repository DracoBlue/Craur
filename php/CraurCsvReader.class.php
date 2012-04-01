<?php

class CraurCsvReader
{

    /**
     * Internal method to create a craur object from a given file handle (e.g. STDIN)
     */
    static function createFromCsvFileHandle($file_handle, array $field_mappings)
    {
        $row_number = 0;
        
        $current_entry = array();
        
        list($raw_mapping_keys, $raw_identifier_keys) = self::getRawMappingAndIdentifiers($field_mappings);

        $entries = array();
        
        while (($row_data = fgetcsv($file_handle, 23085, ";")) !== FALSE)
        {
            $row_number++;
            if ($row_number != 1)
            {
                $entries[] = self::expandPathsIntoArray($row_data, $raw_mapping_keys, $raw_identifier_keys);
            }
        }
        
        $merged_entries = self::mergePathEntriesRecursive($entries);
        
        return new Craur($merged_entries);   
    }
    
    /**
     * Generates the raw mapping and raw identifiers for a given set of field mappings.
     * 
     * @private
     */
    static function getRawMappingAndIdentifiers(array $field_mappings)
    {
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

        $raw_identifier_keys_with_holes = array_values($raw_identifier_keys);
        
        /*
         * $raw_identifier_keys_with_holes is now just: array('book', 'book.city.place')
         */
        
        /*
         * Sometimes we are missing a subkey (like book.city, thus we need to be sure
         * we have them all)
         */
        $raw_identifier_keys = array();
        foreach ($raw_identifier_keys_with_holes as $raw_identifier_key)
        {
            $current_path = array();
            foreach (explode('.', $raw_identifier_key) as $key_part)
            {
                $current_path[] = $key_part;
                $raw_identifier_keys[] = implode('.', $current_path);
            }
        }
        
        $raw_identifier_keys = array_unique($raw_identifier_keys);
        
        /*
         * $raw_identifier_keys is now just: array('book', 'book.city', 'book.city.place')
         */

        return array($raw_mapping_keys, $raw_identifier_keys);
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
     *      $merged_entries = CraurCsvReader::mergePathEntriesRecursive($entries);
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
     * @private
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
     *      assert(json_encode($expected_entry) === json_encode(CraurCsvReader::expandPathsIntoArray($row_data, $raw_mapping_keys, $raw_identifier_keys)));
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
    
}
