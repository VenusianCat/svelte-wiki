<?php

//example of line added for error testing - 
//var_dump(extractMetadataFromWiki(scandir('./content')));

error_reporting(E_ALL);
header('Access-Control-Allow-Origin: http://localhost:5000');
header('Access-Control-Allow-Headers: Content-Type');

// Takes raw data from the request
$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json);

if (isset($data->action)) {

	switch($data->action) {
		case 'save':
			if (!isset($data->id)) die('no file identifier sent');
			$filename = './content/'.$data->id.'.md';
			$fp = fopen($filename, 'w+');
			fwrite($fp, $data->content);
			fclose($fp);
			echo 'data saved in '.$filename;
			break;
		case 'load':
			$filename = './content/'.$data->id.'.md';
			if(file_exists($filename)) {
				echo file_get_contents('./content/'.$data->id.'.md');
			} else {
				echo '404';
			}
			break;
		case 'getIndex':
			echo json_encode(processFilenamesToWikiWords(scandir('./content')));
			break;
		case 'getWikiMetadata':
			echo json_encode(extractMetadataFromWiki(scandir('./content')));
			break;
		default:
			die('No valid action provided in Ajax request.');
	}

}


function get_html_title($html){
    preg_match("/\<title.*\>(.*)\<\/title\>/isU", $html, $matches);
    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return false;
    }
}

function processFilenamesToWikiWords($files) {
	$wikiWords = array();
	foreach($files as $filename) {
		if ($filename !== '.' && $filename !== '..') {
			$temp = substr($filename, 0, -3); //removes the .md
			$wikiWords[] = preg_replace('/([a-z])([A-Z])/', '$1 $2', $temp);
		}
	}
	return $wikiWords;
}

function extractMetadataFromWiki($files) {
	$metadata = array('activeWikiWords' => array(), 'allWikiWords' => array(), 'wikiTags' => array());
	$metadata['activeWikiWords'] = processFilenamesToWikiWords($files);
	$metadata['allWikiWords'] = $metadata['activeWikiWords'];
	//step through and scan each page of the wiki
	foreach($metadata['activeWikiWords'] as $pagename) {
		$filename = './content/'.str_replace(' ', '', $pagename).'.md';
		$pageContents = file_get_contents($filename);
		//find all the wikiWords in the text 
		preg_match_all('/\[\[([A-Za-z ]+)\]\]/m', $pageContents, $matches);
		if($matches[0]) {
			foreach($matches[1] as $match) {
				if (!in_array($match, $metadata['activeWikiWords'])) {
					$metadata['allWikiWords'][] = $match;
				}
			}
		}
		//now find all the tags in the text
		preg_match_all('/{([^|}]+)(|[^}]+)?}/m', $pageContents, $matches);
		//var_dump($matches);
		if($matches[1]) {
			foreach($matches[1] as $match) {
				$metadata['wikiTags'][trim($match)][] = $pagename;
			}
		}
	}
	sort($metadata['allWikiWords'], SORT_NATURAL | SORT_FLAG_CASE);
	//sort the all wiki words array
	
	return $metadata;
}

?>