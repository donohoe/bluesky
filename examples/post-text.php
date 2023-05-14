<?php

include '../bluesky.php';

// Most URLs should automaticlly and converted to links
//$text = "Just test posting via API in PHP... you can find the source here: https://github.com/donohoe/blueksy";

//  Markdown links are supported
$text = "Just test posting via API in PHP... you can find the source [here](https://github.com/donohoe/blueksy)";

$bluesky = new Bluesky;
$bluesky->setAccount('../account.json');
$response = $bluesky->post( $text );

print_r($response);
