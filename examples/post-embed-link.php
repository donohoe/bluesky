<?php

include '../bluesky.php';

$text = "Bluesky does not have its own oembed service so you have full control over preview experience";

$embed = array(
    'type'        => 'link',
    'uri'         => 'https://github.com/donohoe/blueksy',
    'title'       => 'A simple (and very basic) PHP library for Bluesky',
    'description' => 'This minimal PHP client allows you to post text, links, and images to Bluesky',
    'thumb'       => '../examples/social-card.jpg'
);

$bluesky = new Bluesky;
$bluesky->setAccount('../account.json');
$response = $bluesky->post( $text, $embed );

print_r($response);
