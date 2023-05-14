<?php

include '../bluesky.php';

$text = "Sometimes I have a hard time coming up with creative placeholder text. I hardly try anymore.";

$embed = array(
    'type' => 'image',
    'uri'  => '/winners-dont-do-drugs.jpg',
    'alt'  => 'The legend, Michael Knight, sitting on the hood of Kit giving teh best-ever thumbs-up.',
);

$bluesky = new Bluesky;
$bluesky->setAccount('../account.json');
$response = $bluesky->post( $text, $embed );

print_r($response);
