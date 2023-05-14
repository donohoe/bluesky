<?php

include '../bluesky.php';

$text = "Bluesky does not have its own oembed service so you have full control over preview experience";
$card = array(
    'uri'         => 'https://github.com/donohoe/blueksy',
    'title'       => 'A simple (and very basic) PHP library for Bluesky',
    'description' => 'This minimal PHP client allows you to post text, links, and images to Bluesky',
);
$thumbnail = '/social-card.jpg';

// Card to be Embedded

$embed = array(
    'type'        => 'link',
    'uri'         => $card['uri'],
    'title'       => $card['title'],
    'description' => $card['description'],
    'thumb'       => $thumbnail
);


$bluesky = new Bluesky;
$bluesky->setAccount('../account.json');
$response = $bluesky->post( $text, $embed );

print_r($response);
