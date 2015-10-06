# HtmlMinerDocument

A PHP library that can retrieve DOM elements from HTML using CSS selector.

## Examples

```php
<?php
include_once 'HtmlMinerDocument.php';

// Find all elements matching the given CSS selectors

$doc = new HtmlMinerDocument(file_get_contents('http://news.163.com/'));

$news_list = $doc->findAll('div.ns-wnews h3 a');

foreach ($news_list as $news) {
    echo "$news[text] ($news[href])\r\n";
}

echo "\r\n";

// Find elements by group

$doc = new HtmlMinerDocument(file_get_contents('http://www.amobbs.com/forum-9892-1.html'));

$threads = $doc
    ->findFirst('table#threadlisttableid')
    ->findAll('tr')
    ->findAllByGroup(array(
        'title'         => 'th a.s',
        'author'        => 'td.by cite a',
        'last_reply'    => 'td.by em span'
    ));

foreach ($threads as $thread) {
    echo $thread['title']['text'];
    echo ' by ' . $thread['author']['text'];
    echo ' (' . $thread['last_reply']['text'] . ')';
    echo "\r\n";
}

?>
```

## License

The MIT License (MIT)

Copyright (c) 2015 Wudi <wudi@wudilabs.org>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
