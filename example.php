<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>HtmlMinerDocument Example</title>
</head>
<body>
<pre>
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
</pre>
</body>
</html>
