<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Richard's Recipe Browser</title>
	<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
	<link rel="manifest" href="site.webmanifest">
	<link rel="stylesheet" href="./global.css" />
</head>
<body>
	

<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

$id = isset($_GET['id']) ? str_replace(' ', '', $_GET['id']) : 'Home';

include_once 'Parsedown.php';

$topNav = getHTMLfromAPI('TopNav');
echo postParseMarkdown($topNav);

function getHTMLfromAPI($id) {
	$Parsedown = new Parsedown();
	// API url (LIVE)
	$url = 'https://richardtammar.com/recipes2/ajax.php';
	// Collection object
	$data = [
	'action' => 'load',
	'id' => $id
	];
	// Initializes a new cURL session
	$curl = curl_init($url);
	// Set the CURLOPT_RETURNTRANSFER option to true
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	// Set the CURLOPT_POST option to true for POST request
	curl_setopt($curl, CURLOPT_POST, true);
	// Set the request data as JSON using json_encode function
	curl_setopt($curl, CURLOPT_POSTFIELDS,  json_encode($data));
	// Set custom headers for RapidAPI Auth and Content-Type header
	/*curl_setopt($curl, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json'
	]);*/
	// Execute cURL request with all previous settings
	$response = curl_exec($curl);
	// Close cURL session
	curl_close($curl);
	$output = $Parsedown->text($response);
	return $output;
}

$output = postParseMarkdown(getHTMLfromAPI($id));

//convert wikilinks to, umm, wikilinks
function postParseMarkdown($s){
	return preg_replace_callback('/\[\[([A-Za-z ]+)\]\]/', 'wikilinks', $s);
}

function wikilinks($match) {
	return '<a class="wikiLink" href="'.$_SERVER['PHP_SELF'].'?id='.$match[1].'">'.$match[1].'</a>';
}

//Convert newlines to, umm, line breaks
$output = preg_replace('/([A-Za-z])\n/', '$1<br>', $output);

echo $output;

?>



</body>
</html>
