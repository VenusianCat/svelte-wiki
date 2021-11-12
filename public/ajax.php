<?php

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
			$wikiWords[] = substr($filename, 0, -3);
		}
	}
	return $wikiWords;
}

?>