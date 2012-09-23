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
        
        $entries = array();
        
        while (($row_data = fgetcsv($file_handle, 0, ";")) !== FALSE)
        {
            $row_number++;
            if ($row_number != 1)
            {
                $entries[] = self::expandPathsIntoArray($row_data, $field_mappings);
            }
        }
        
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
                        /*
                         * If we have something like $category => array('fantasy', 'comedy')
                         * we want to add them each by one
                         */
                        if (isset($entry[$object_key][0]))
                        {
                            foreach ($entry[$object_key] as $sub_value)
                            {
                                $result_entry[$object_key][] = $sub_value;                        
                            }
                        }
                        else
                        {
                            /*
                             * If we have something like $name => 'hans' we want to append
                             * it to the previous author names
                             */
                            $result_entry[$object_key][] = $entry[$object_key];                        
                        }
                    }
                }
            }
            foreach ($object_keys as $object_key)
            {
                if (isset($result_entry[$object_key]))
                {
                    /*
                     * If we have no scalar values (NOT "comedy" or "fantasy", but entire book entries)
                     */
                    if (count($result_entry[$object_key]) > 0 && !is_scalar($result_entry[$object_key][0]))
                    {
                        $result_entry[$object_key] = self::mergePathEntriesRecursive($result_entry[$object_key]);                        
                    }
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
     *      $field_mappings = array(
     *          'book[].name',
     *          'book[].year',
     *          'book[].author[].name',
     *          'book[].author[].age'
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
     *      assert(json_encode($expected_entry) === json_encode(CraurCsvReader::expandPathsIntoArray($row_data, $field_mappings)));
     * @return array
     */
    static function expandPathsIntoArray(array $row_data, array $field_mappings, $prefix = '')
    {
        $sub_keys = CraurCsvWriter::getSubKeysForFieldMappingAndPrefix($field_mappings, $prefix);
        $direct_keys = CraurCsvWriter::getDirectKeysForFieldMappingAndPrefix($field_mappings, $prefix);
        
        $entry = array();
        
        foreach ($direct_keys as $pos => $direct_key)
        {
            if (!empty($row_data[$pos]))
            {
                if (empty($direct_key))
                {
                    $entry[] = $row_data[$pos];
                }
                else
                {
                    $entry[$direct_key] = $row_data[$pos];
                }
            }
        }
        
        foreach($sub_keys as $sub_key => $fields)
        {
            /*
             * If we have real sub values (like author[].name and such
             * stuff, we need to do the recursion)
             */
            $sub_prefix = ($prefix === '' ? '' : $prefix) . $sub_key . '[]';

            /*
             * If we don't have something like author[].name and we
             * have just: categories[], we can luckily do an implode
             * on all fields (which are empty) :).
             * 
             * In this case, we just want to (string)-casted value without
             * the dot. Otherwise we need to add a . to the prefix!
             */
            if (implode('', $fields) !== '')
            {
                $sub_prefix .= '.';    
            }
            $entry[$sub_key] = self::expandPathsIntoArray($row_data, $field_mappings, $sub_prefix);  
            if (empty($entry[$sub_key]))
            {
                unset($entry[$sub_key]);
            }
        }
        
        return $entry;
    }
    
}
