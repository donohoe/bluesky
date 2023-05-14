<?php
/*

Basic Blueksky client for simple posts
v0.0.3 May 13th 2023

https://github.com/donohoe/blueksy

*/

if (!class_exists('Bluesky')):

	class Bluesky {

		public function initialize() {
			$this->debug = false;
			$this->account = $this->getAccount();
			$this->credentials = $this->getCredentials();
		}

    /*  Pass in your setup and credentials */
        public function setAccount( $account_file ){
            $this->account_file = $account_file;
			if (file_exists($this->account_file )) {
				return true;
			}
            return false;
        }

		private function getAccount(){
			$account = array();
			if (file_exists($this->account_file )) {
				$account = json_decode( file_get_contents($this->account_file), true);
			}
			return $account;
		}

		private function getCredentials() {
			$credentials = array();
			$request = $this->createSession();
			if (isset($request['credentials'])) {
				$credentials = $request['credentials'];
			}
			return $credentials;
		}

	/*
		Post
		Allow you to post a message with text, link, or images.
		For now text content is required
	*/
		public function post( $text = '', $embed = array('type' => 'none') ) {

            $this->initialize();

			if (empty($text)) {
				return array( 'status' => 'no content' );
			}

			$embed_type = 'none';
			if (isset($embed['type'])){
				$embed_type = $embed['type'];
				unset($embed['type']);
			}

			$service_path = 'xrpc/com.atproto.repo.createRecord';
			$body = $this->create_post_request( $text );
			$result = array();

			switch ($embed_type) {
				case 'link':
					$body = $this->add_link($body, $embed);
					break;
				case 'image':
					$body = $this->add_image($body, $embed);
					break;
			}

			$post_fields = json_encode( $body );
			$response = $this->request( $service_path, $post_fields );

			$result = array(
				'status' => 'success',
				'debug'  => $response,
				'post_fields'  => $post_fields
			);

			return $result;
		}

	/*
		Add Link
		This is a link element, and not a link within the message text.
		You cannot post both a link and an image, its one or the other.
		https://github.com/pfefferle/wordpress-share-on-bluesky/blob/92589df9242063ba2a64b63d9472c5c3eb57c0f8/share-on-bluesky.php#L270
	*/
		private function add_link($body, $embed) {

            if (isset($embed['thumb'])) {

                $thumb = '' . $embed['thumb'];
                $blob = $this->uploadImage( array('uri' => $thumb ) );
                unset($embed['thumb']);

                if ($blob['blob']['ref']) {
                    $ref_link = $blob['blob']['ref']['$link'];
                    $embed['thumb'] = array(
                        "mimeType" => $blob['blob']['mimeType'],
                        "cid"      => $ref_link
                    );
                }
            }

			$body['record']['embed'] = array(
				'$type'    => 'app.bsky.embed.external',
				'external' => $embed
			);
			return $body;
		}

		private function add_image($body, $embed) {

			$blob = $this->uploadImage( $embed );

			if ($this->debug) {
				print "\nBlob:\n";
				print_r( $blob );
			}

			if (isset($blob['error'])) {
				return array( 
					'status' => 'did not upload image',
					'debug'  => $blob
				);
			}

		/*
			Helpful links:
			https://github.com/bluesky-social/atproto/issues/763#issue-1655837257
			https://github.com/ShinoharaTa/AozoraWebClient/blob/89c74401da5d655ae136d8b3e8c641d33878cce7/docs/sample.md?plain=1#L126
		*/
			$ref_link = $blob['blob']['ref']['$link'];

			$body['record']['embed'] = array(
				'$type'  => 'app.bsky.embed.images',
				'images' => []
			);

			$img = array(
				'alt' => $embed['alt'],
				'image' => array(
					"mimeType" => $blob['blob']['mimeType'],
					"cid" => $ref_link,
				)
			);

			$body['record']['embed']['images'][] =  $img ;

			if ($this->debug) {
				print "\nJSON:\n";
				print json_encode( $body, JSON_OBJECT_AS_ARRAY + JSON_PRETTY_PRINT );
				print "\nBody:\n";
				print_r( $body );
			}

			return $body;
		}

	/*
		Add Text

		This manages basic body construction
		For text, it will handle any (markdown) links and convert them into facets.
	*/
		private function create_post_request($text) {

			$text   = strip_tags($text);
			$facets = array();

			preg_match_all('/\[(.*?)\]\((.*?)\)/', $text, $elements, PREG_OFFSET_CAPTURE);
			array_shift($elements);

			if (!empty($elements)) {

				$text_plain = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
					return $matches[1];
				}, $text);

				for ($i=0; $i<count($elements[0]); $i++) {

					$label = $elements[0][$i][0];
					$link  = $elements[1][$i][0];
					$pos_start = strpos($text_plain, $label);
					$pos_end   = $pos_start + strlen($label);

					$facet = array(
						'features' =>[],
						'index' => array(
							'byteStart' => $pos_start,
							'byteEnd'   => $pos_end
						)
					);

					$facet['features'][] = array(
						'uri'   => $link,
						'$type' => 'app.bsky.richtext.facet#link'
					);

					$facets[] = $facet;
				}

				if (!empty($facet)) {
					$text = $text_plain;
				}
			} else {

			/* If no markdonw links detected, then lets look for plain links only, and convert them to facets */

				$url_pattern = '/(((http|https)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(\:[0-9]+)?(\/\S*)?/';
				preg_match_all($url_pattern, $text, $elements, PREG_OFFSET_CAPTURE);

				if (isset($elements[0]) && !empty($elements[0])) {
					$el = $elements[0];
					for ($i=0; $i<count($el); $i++) {

						$link      = $el[$i][0];
						$pos_start = strpos($text, $link);
						$pos_end   = $pos_start + strlen($link);

						$facet = array(
							'features' =>[],
							'index' => array(
								'byteStart' => $pos_start,
								'byteEnd'   => $pos_end
							)
						);
						$facet['features'][] = array(
							'uri'   => $link,
							'$type' => 'app.bsky.richtext.facet#link'
						);

						$facets[] = $facet;
					}
				}
			}

			$did = $this->credentials['did'];

			$body = array(
				'collection' => 'app.bsky.feed.post',
				'did'        => $did,
				'repo'       => $did,
				'record'     => array(
					'$type'     => 'app.bsky.feed.post',
					'text'      => $text,
					'createdAt' => gmdate( 'c' )
				),
			);

			if (!empty($facets)) {
				$body['record']['facets'] = $facets;
			}

			return $body;
		}

	/*	
		Upload image
	*/

		private function uploadImage($image){
			$blob = array();

			$service_path = 'xrpc/com.atproto.repo.uploadBlob';
			$post_headers = array();

			$ext = pathinfo( $image['uri'], PATHINFO_EXTENSION);
			switch($ext) {
				case 'jpg':
				case 'jpeg':
					$post_headers[] = 'content-type: image/jpeg';
					break;			
				case 'png':
					$post_headers[] = 'content-type: image/png';
					break;
			}

			$data = fopen ($image['uri'], 'rb');
			$size = filesize ($image['uri']);
			$post_fields = fread ($data, $size);
			fclose ($data);

			$blob = $this->request( $service_path, $post_fields , $post_headers );

			return $blob;
		}

	/*
        Authenticate with Bluesky
    */
		public function createSession() {

			$result = array();
			$service_path = 'xrpc/com.atproto.server.createSession';
			$body = array(
				'identifier' => $this->account['bluesky_identifier'],
				'password'   => $this->account['bluesky_password'],
			);

			$post_fields = json_encode( $body );
			$response = $this->request( $service_path, $post_fields );

			if (
				   ! empty( $response['accessJwt'] )
				&& ! empty( $response['refreshJwt'] )
				&& ! empty( $response['did'] )
			) {
				$credentials = array(
					'identifier'  => $this->account['bluesky_identifier'],
					'domain'      => $this->account['bluesky_domain'],
					'access_jwt'  => $response['accessJwt'],
					'refresh_jwt' => $response['refreshJwt'],
					'did'         => $response['did'],
				);

				$result = array(
					'status'      => 'success',
					'credentials' => $credentials,
					'debug'       => $response
				);

			} else {
				$result = array(
					'status' => 'failure',
					'debug'  => $response
				);
			}

			return $result;
		}

	/*
		request

		Make a direct request to bluesky network
	*/
		public function request( $service_path, $post_fields, $post_headers = array() ){

			$response = array();

			$service_url = $this->account['bluesky_domain'] . $service_path;
			$wp_version  = 0.3;
			$wp_url      = 'https://donohoe.dev';
			$user_agent  = 'BSkyPHP/' . $wp_version . '; ' . $wp_version . '; ActivityPub';

			$headers = array(
				'Accept: */*',
				'Accept-Language: en-US,en;q=0.9',
				'Dnt: 1',
				'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
			);

			if ($service_path !== 'xrpc/com.atproto.repo.uploadBlob') {
				$headers[] = 'Content-Type: application/json';
			}

			if ($service_path !== 'xrpc/com.atproto.server.createSession') {
				$headers[] = 'Authorization: Bearer ' . $this->credentials['access_jwt'];
			}

		//	Any additional custom headers
			foreach($post_headers as $h){
				$headers[] = $h;
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $service_url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields );
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$result = curl_exec($ch);

			$error = false;
			if (curl_errno($ch)) {
				$error = 'Error:' . curl_errno($ch) . "-" .curl_error($ch);
			}

			curl_close($ch);

			if ($error) {
				$response = array(
					'status' => $error,
					'debug' => array(
						'service_path' => $service_path, 
						'post_fields'  => $post_fields
					)
				);
			} else {
				$response = json_decode($result, true);
			}

			return $response;
		}
	}

endif;
