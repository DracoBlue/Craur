<?php

class Craur
{
    protected $data = null;

    static function createFromJson($json_string)
    {
        return new Craur(json_decode($json_string, true));
    }

    static function createFromXml($xml_string)
    {
        $node = new DOMDocument('1.0', 'utf-8');
        $node->loadXML($xml_string, LIBXML_NOCDATA);

        $data = self::convertDomNodeToDataArray($node);

        if (!is_array($data))
        {
            $data = array('@' => $data);
        }

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
            if (!isset($data[$root_node_name]))
            {
                $data[$root_node_name] = array();
            }
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

                if (isset($data[$key]))
                {
                    if (!is_array($data[$key]) || !isset($data[$key][0]))
                    {
                        $data[$key] = array($data[$key]);
                    }
                    $data[$key][] = $attribute_node->nodeValue;
                }
                else
                {
                    $data[$key] = $attribute_node->nodeValue;
                }
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

    protected function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function getValues(array $paths_map, array $default_values = array())
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
                $values[$value_key] = $this->get($path);
            } 
        }
        
        return $values;   
    }
    
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

    public function __toString()
    {

        if (isset($this->data['@']))
        {
            return $this->data['@'];
        }

        throw new Exception('Cannot convert to string, since value is missing!');
    }

    public function toJsonString()
    {
        return json_encode($this->data);
    }

    public function toXmlString()
    {
        return $this->convertNodeDataToXml($this->data);
    }

    protected function convertNodeDataToXml($data)
    {
        if (!is_array($data))
        {
            return htmlspecialchars($data);
        }

        if (is_array($data) && isset($data['@']) && count($data) === 1)
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

}
