<?php
/**
 * HtmlMinerDocument is a PHP library that can retrieve DOM elements
 * from HTML using CSS selector.
 *
 * PHP version 5
 *
 * @author     Wudi <wudi@wudilabs.org>
 * @copyright  2015 Wudi
 * @license    https://opensource.org/licenses/MIT  The MIT License
 * @link       http://www.wudilabs.org/
 */

class HtmlMinerDocument {
    private $_dom_doc = null;

    private $_root_node = null;

    function __construct($html) {
        $this->_dom_doc = new DOMDocument();
        @$this->_dom_doc->loadHTML($html);

        $this->_root_node = new HtmlMinerNode($this->_dom_doc, $this->_dom_doc);
    }

    public function getRootNode() {
        return $this->_root_node;
    }

    public function findFirst($selector) {
        return $this->_root_node->findFirst($selector);
    }

    public function findAll($selector) {
        return $this->_root_node->findAll($selector);
    }
}

class HtmlMinerNode implements ArrayAccess {
    private $_dom_doc = null;
    private $_dom_node = null;

    private $_single_node_list = null;

    function __construct($dom_doc, $dom_node) {
        $this->_dom_doc = $dom_doc;
        $this->_dom_node = $dom_node;
    }

    public function getDomNode() {
        return $this->_dom_node;
    }

    public function toArray() {
        return self::nodeToArray($this->_dom_node);
    }

    public function findFirst($selector) {
        $this->_makeSingleNodeList();

        return $this->_single_node_list->findFirst($selector);
    }

    public function findAll($selector) {
        $this->_makeSingleNodeList();

        return $this->_single_node_list->findAll($selector);
    }

    public function findAllByGroup($selector) {
        $this->_makeSingleNodeList();

        return $this->_single_node_list->findAllByGroup($selector);
    }

    private function _makeSingleNodeList() {
        if ($this->_single_node_list == null) {
            $this->_single_node_list = new HtmlMinerNodeList($this->_dom_doc, array($this->_dom_node));
        }
    }

    public static function nodeToArray($node) {
        $array = array(
            'name'          => $node->nodeName,
            'attributes'    => array(),
            'text'          => $node->textContent,
            'children'      => self::nodesToArray($node->childNodes)
        );

        if ($node->attributes->length) {
            foreach ($node->attributes as $key => $attr) {
                $array['attributes'][$key] = $attr->value;
            }
        }

        return $array;
    }

    public static function nodesToArray($nodes) {
        $array = array();

        for ($i = 0, $length = $nodes->length; $i < $length; $i++) {
            if ($nodes->item($i)->nodeType == XML_ELEMENT_NODE) {
                array_push($array, self::nodeToArray($nodes->item($i)));
            }
        }

        return $array;
    }

    public function getAttribute($attr_name) {
        if ($this->_dom_node->attributes->length) {
            foreach ($this->_dom_node->attributes as $key => $attr) {
                if ($key == $attr_name) {
                    return $attr->value;
                }
            }
        }

        return null;
    }

    // ArrayAccess

    public function offsetExists($offset) {
        switch ($offset) {
            case 'tagName':
            case 'text':
                return true;
                break;
            default:
                if ($this->getAttribute($offset) !== null) {
                    return true;
                }
                break;
        }

        return false;
    }

    public function offsetGet($offset) {
        switch ($offset) {
            case 'tagName':
                return $this->_dom_node->nodeName;
                break;
            case 'text':
                return $this->_dom_node->textContent;
                break;
            default:
                return $this->getAttribute($offset);
                break;
        }

        return null;
    }

    public function offsetSet($offset, $value) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }

    public function offsetUnset($offset) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }
}

class HtmlMinerNodeList implements SeekableIterator, Countable, ArrayAccess {
    private $_dom_doc = null;
    private $_dom_xpath = null;

    private $_dom_nodes = null;

    private $_position = 0;

    function __construct($dom_doc, $dom_nodes) {
        $this->_dom_doc = $dom_doc;
        $this->_dom_xpath = new DOMXpath($this->_dom_doc);

        $this->_dom_nodes = $dom_nodes;
    }

    public function findFirst($selector) {
        $new_nodes = array();

        $xpath = HtmlMinerUtil::cssSelectorToXPath($selector);

        foreach ($this->_dom_nodes as $dom_node) {
            $dom_nodes = $this->_dom_xpath->query($xpath, $dom_node);

            foreach ($dom_nodes as $dom_node) {
                if ($dom_node->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                return new HtmlMinerNodeList($this->_dom_doc, array($dom_node));
            }
        }
    }

    public function findAll($selector) {
        $new_nodes = array();

        $xpath = HtmlMinerUtil::cssSelectorToXPath($selector);

        foreach ($this->_dom_nodes as $dom_node) {
            $dom_nodes = $this->_dom_xpath->query($xpath, $dom_node);

            foreach ($dom_nodes as $dom_node) {
                if ($dom_node->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                $new_nodes[] = $dom_node;
            }
        }

        return new HtmlMinerNodeList($this->_dom_doc, $new_nodes);
    }

    public function findAllByGroup($selectors) {
        $grouped_dom_nodes = array();

        foreach ($this->_dom_nodes as $dom_node) {
            $node_group = array();

            foreach ($selectors as $key => $selector) {
                $xpath = HtmlMinerUtil::cssSelectorToXPath($selector);

                $sub_nodes = $this->_dom_xpath->query($xpath, $dom_node);

                foreach ($sub_nodes as $sub_node) {
                    if ($sub_node->nodeType != XML_ELEMENT_NODE) {
                        continue;
                    }

                    $node_group[$key] = $sub_node;

                    break;
                }
            }

            if (count($node_group) == count($selectors)) {
                $grouped_dom_nodes[] = $node_group;
            }
        }

        return new HtmlMinerGroupedNodeList($this->_dom_doc, $grouped_dom_nodes);
    }

    public function getNodeList() {
        $ret = array();

        foreach ($this->_dom_nodes as $dom_node) {
            $ret[] = new HtmlMinerNode($this->_dom_doc, $dom_node);
        }

        return $ret;
    }

    public function getDomNodeList() {
        $ret = array();

        foreach ($this->_dom_nodes as $dom_node) {
            $ret[] = $dom_node;
        }

        return $ret;
    }

    // SeekableIterator

    public function current() {
        return new HtmlMinerNode($this->_dom_doc, $this->_dom_nodes[$this->_position]);
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        $this->_position++;
    }

    public function rewind() {
        $this->_position = 0;
    }

    public function valid() {
        return array_key_exists($this->_position, $this->_dom_nodes);
    }

    public function seek($position) {
        if (!array_key_exists($position, $this->_dom_nodes)) {
            trigger_error('The given position is out of bounds', E_USER_NOTICE);
        }

        $this->_position = $position;
    }

    // Countable

    public function count() {
        return count($this->_dom_nodes);
    }

    // ArrayAccess

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_dom_nodes);
    }

    public function offsetGet($offset) {
        return new HtmlMinerNode($this->_dom_doc, $this->_dom_nodes[$offset]);
    }

    public function offsetSet($offset, $value) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }

    public function offsetUnset($offset) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }
}

class HtmlMinerGroupedNodeList implements SeekableIterator, Countable, ArrayAccess {
    private $_dom_doc = null;

    private $_grouped_dom_nodes = null;

    private $_position = 0;

    function __construct($dom_doc, $grouped_nodes) {
        $this->_dom_doc = $dom_doc;
        $this->_grouped_dom_nodes = $grouped_nodes;
    }

    public function node($key) {
        return $this->_grouped_dom_nodes[$key];
    }

    // SeekableIterator

    public function current() {
        return new HtmlMinerNodeList($this->_dom_doc, $this->_grouped_dom_nodes[$this->_position]);
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        $this->_position++;
    }

    public function rewind() {
        $this->_position = 0;
    }

    public function valid() {
        return array_key_exists($this->_position, $this->_grouped_dom_nodes);
    }

    public function seek($position) {
        if (!array_key_exists($position, $this->_grouped_dom_nodes)) {
            trigger_error('The given position is out of bounds', E_USER_NOTICE);
        }

        $this->_position = $position;
    }

    // Countable

    public function count() {
        return count($this->_grouped_dom_nodes);
    }

    // ArrayAccess

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_grouped_dom_nodes);
    }

    public function offsetGet($offset) {
        return new HtmlMinerNodeList($this->_dom_doc, $this->_grouped_dom_nodes[$offset]);
    }

    public function offsetSet($offset, $value) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }

    public function offsetUnset($offset) {
        trigger_error('The ' . __CLASS__ . ' object cannot be modified', E_USER_NOTICE);
    }
}


class HtmlMinerUtil {
    private static $_cache = array();

    // from https://github.com/tj/php-selector/blob/master/selector.inc
    public static function cssSelectorToXPath($selector) {
        if (array_key_exists($selector, self::$_cache)) {
            return self::$_cache[$selector];
        }

        $selector_to_convert = $selector;

        // remove spaces around operators
        $selector = preg_replace('/\s*>\s*/', '>', $selector);
        $selector = preg_replace('/\s*~\s*/', '~', $selector);
        $selector = preg_replace('/\s*\+\s*/', '+', $selector);
        $selector = preg_replace('/\s*,\s*/', ',', $selector);
        $selectors = preg_split('/\s+(?![^\[]+\])/', $selector);

        foreach ($selectors as &$selector) {
            // ,
            $selector = preg_replace('/,/', '|descendant-or-self::', $selector);
            // input:checked, :disabled, etc.
            $selector = preg_replace('/(.+)?:(checked|disabled|required|autofocus)/', '\1[@\2="\2"]', $selector);
            // input:autocomplete, :autocomplete
            $selector = preg_replace('/(.+)?:(autocomplete)/', '\1[@\2="on"]', $selector);
            // input:button, input:submit, etc.
            $selector = preg_replace('/:(text|password|checkbox|radio|button|submit|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/', 'input[@type="\1"]', $selector);
            // foo[id]
            $selector = preg_replace('/(\w+)\[([_\w-]+[_\w\d-]*)\]/', '\1[@\2]', $selector);
            // [id]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)\]/', '*[@\1]', $selector);
            // foo[id=foo]
            $selector = preg_replace('/\[([_\w-]+[_\w\d-]*)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selector);
            // [id=foo]
            $selector = preg_replace('/^\[/', '*[', $selector);
            // div#foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\#([_\w-]+[_\w\d-]*)/', '\1[@id="\2"]', $selector);
            // #foo
            $selector = preg_replace('/\#([_\w-]+[_\w\d-]*)/', '*[@id="\1"]', $selector);
            // div.foo
            $selector = preg_replace('/([_\w-]+[_\w\d-]*)\.([_\w-]+[_\w\d-]*)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selector);
            // .foo
            $selector = preg_replace('/\.([_\w-]+[_\w\d-]*)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selector);
            // div:first-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):first-child/', '*/\1[position()=1]', $selector);
            // div:last-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):last-child/', '*/\1[position()=last()]', $selector);
            // :first-child
            $selector = str_replace(':first-child', '*/*[position()=1]', $selector);
            // :last-child
            $selector = str_replace(':last-child', '*/*[position()=last()]', $selector);
            // :nth-last-child
            $selector = preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selector);
            // div:nth-child
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selector);
            // :nth-child
            $selector = preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selector);
            // :contains(Foo)
            $selector = preg_replace('/([_\w-]+[_\w\d-]*):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selector);
            // >
            $selector = preg_replace('/>/', '/', $selector);
            // ~
            $selector = preg_replace('/~/', '/following-sibling::', $selector);
            // +
            $selector = preg_replace('/\+([_\w-]+[_\w\d-]*)/', '/following-sibling::\1[position()=1]', $selector);
            $selector = str_replace(']*', ']', $selector);
            $selector = str_replace(']/*', ']', $selector);
        }

        // ' '
        $selector = implode('/descendant::', $selectors);
        $selector = 'descendant-or-self::' . $selector;
        // :scope
        $selector = preg_replace('/(((\|)?descendant-or-self::):scope)/', '.\3', $selector);
        // $element
        $sub_selectors = explode(',', $selector);

        foreach ($sub_selectors as $key => $sub_selector) {
            $parts = explode('$', $sub_selector);
            $sub_selector = array_shift($parts);

            if (count($parts) && preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                $results = $matches[0];
                $results[] = str_repeat('/..', count($results) - 2);
                $sub_selector .= implode('', $results);
            }

            $sub_selectors[$key] = $sub_selector;
        }

        $xpath = implode(',', $sub_selectors);

        self::$_cache[$selector_to_convert] = $xpath;

        return $xpath;
    }
}

?>
