<?php
require_once ("codebird.php");
require_once ("config.php");
require_once (__DIR__ . '/vendor/autoload.php');
use Auth0\SDK\Auth0;


function get_twitter_details(){
	if(!isset($_SESSION)) { session_start(); }

	\Codebird\Codebird::setConsumerKey(ADMIN_CONSUMER_KEY, ADMIN_CONSUMER_SECRET);
	$cb = \Codebird\Codebird::getInstance();

	if (isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret'])) {
		// assign access token on each page load
		$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		$reply = (array) $cb->account_verifyCredentials();

		if (isset($reply["errors"]) ){
			//	If the authorization hasn't worked, clear the session variables and start again
			$_SESSION['oauth_token'] = null;
			$_SESSION['oauth_token_secret'] = null;
			$_SESSION['oauth_verify'] = null;
			// send to same URL, without oauth GET parameters
			// header('Location: ' . basename(__FILE__));
			return null;
			die();
		}
		// var_export($reply);
		// die();
		//	Get the user's ID & name
		$id_str = $reply["id_str"];
		$screen_name = $reply["screen_name"];
		// echo "You are {$screen_name} with ID {$id_str}";
		return array($id_str, $screen_name);
	}
	return null;
}

function get_user_details($raw = true) {
	if (null == AUTH0_DOMAIN) {
		return null;
	}
	$auth0 = new Auth0([
		'domain' =>        AUTH0_DOMAIN,
		'client_id' =>     AUTH0_CLIENT_ID,
		'client_secret' => AUTH0_CLIENT_SECRET,
		'redirect_uri' =>  AUTH0_CALLBACK,
		'audience' =>      AUTH0_AUDIENCE,
		'scope' =>        'openid profile',
		'persist_id_token' =>      true,
		'persist_access_token' =>  true,
		'persist_refresh_token' => true,
	]);


	if(!isset($_SESSION)) { session_start(); }
	$userInfo = $auth0->getUser();
	if (!$userInfo) {
		// We have no user info
		return null;
	} else {
		// User is authenticated
		$username = explode("|", $userInfo["sub"]);

		if ($raw) {
			return array(
								$username[0],
								$username[1],
								$userInfo['nickname']
							);
		} else {
			return array(
								htmlspecialchars($username[0]),
								htmlspecialchars($username[1]),
								htmlspecialchars($userInfo['nickname'])
							);
		}

	}
}

function get_edit_key($benchID){
	$hash = crypt($benchID,EDIT_SALT);
	$key = explode("$",$hash)[3];
	return $key;
}

function is_photosphere($filename) {
	//	As per https://stackoverflow.com/a/1578326/1127699
	$file = file_get_contents($filename);
	if (strpos($file, 'UsePanoramaViewer="True"') > 0 ) {
		return true;
	}
	if (strpos($file, 'ProjectionType="equirectangular"') > 0) {
		return true;
	}
	return false;
}

function get_image_location($file)
{
	if (is_file($file)) {
		$img = new \Imagick($file);
		$info = $img->getImageProperties("exif:*");
		$img->clear();

		// $info = exif_read_data($file);
		if ($info !== false) {
				$direction = array('N', 'S', 'E', 'W');
				if (isset($info['exif:GPSLatitude'], $info['exif:GPSLongitude'], $info['exif:GPSLatitudeRef'],
				    $info['exif:GPSLongitudeRef']) &&
					in_array($info['exif:GPSLatitudeRef'], $direction) && in_array($info['exif:GPSLongitudeRef'], $direction)) {

					//	https://stackoverflow.com/questions/19347005/how-can-i-explode-and-trim-whitespace
					$gpsLat = preg_split ('/(\s*,*\s*)*,+(\s*,*\s*)*/',$info['exif:GPSLatitude']);
					$lat_degrees_a = explode('/',$gpsLat[0]);
					$lat_minutes_a = explode('/',$gpsLat[1]);
					$lat_seconds_a = explode('/',$gpsLat[2]);

					$gpsLng = preg_split ('/(\s*,*\s*)*,+(\s*,*\s*)*/',$info['exif:GPSLongitude']);
					$lng_degrees_a = explode('/',$gpsLng[0]);
					$lng_minutes_a = explode('/',$gpsLng[1]);
					$lng_seconds_a = explode('/',$gpsLng[2]);

					$lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
					$lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
					$lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
					$lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
					$lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
					$lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

					$lat = (float) $lat_degrees + ((($lat_minutes * 60) + ($lat_seconds)) / 3600);
					$lng = (float) $lng_degrees + ((($lng_minutes * 60) + ($lng_seconds)) / 3600);
					$lat = number_format($lat, 7);
					$lng = number_format($lng, 7);

					//If the latitude is South, make it negative.
					//If the longitude is west, make it negative
					$lat = $info['exif:GPSLatitudeRef'] == 'S' ? $lat * -1 : $lat;
					$lng = $info['exif:GPSLongitudeRef'] == 'W' ? $lng * -1 : $lng;

					return array(
						'lat' => round($lat,10),
						'lng' => round($lng,10)
					);
				}
		}
	}

	return false;
}

function tweet_bench($benchID, $mediaURLs=null, $inscription=null,
                     $latitude=null, $longitude=null, $license=null,
                     $user_provider=null, $user_name=null){
	//	Send Tweet
	\Codebird\Codebird::setConsumerKey(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
	$cb = \Codebird\Codebird::getInstance();
	$cb->setToken(OAUTH_ACCESS_TOKEN, OAUTH_TOKEN_SECRET);

	//	Add the image
	if(null!=$mediaURLs){

		$media_ids = array();

		foreach ($mediaURLs as $file) {
			// upload all media files
			$reply = $cb->media_upload(['media' => $file]);
			// and collect their IDs
			$media_ids[] = $reply->media_id_string;
		}
		$media_ids = implode(',', $media_ids);
	}

	//	Tweet length is now 280
	$length = 280;

	//	Tweet will end with "℅ @twittername"
	if ("twitter" == $user_provider) {
		$from = "℅ @{$user_name}";
	} else {
		//	Might use this for Github / Facebook names in future
		$from = "";
	}

	//	Remove the length of the items and the newline character
	$length = $length - mb_strlen($from) - 1;
	$length = $length - mb_strlen($license) - 1;
	//	Remove URL
	$length = $length - 24 - 1;
	//	Two more for luck :-)
	$length = $length - 2;

	//	Truncate the inscription
	$tweet_inscription = mb_substr($inscription, 0, $length);
	if (mb_strlen($inscription) > $length) {
		$tweet_inscription .= "…";
	}

	$domain = $_SERVER['SERVER_NAME'];

	$params = [
		'status'    => "{$tweet_inscription}\nhttps://{$domain}/bench/{$benchID}\n{$license}\n{$from}",
		'lat'       => $latitude,
		'long'      => $longitude,
		'media_ids' => $media_ids,
		'weighted_character_count' => 'true'
	];
	$reply = $cb->statuses_update($params);
}

// function toot_bench($benchID, $mediaFiles=null, $inscription=null, $license=null){
//
// 	//	Send Tweet
// 	$mastodon_api = new Mastodon_api();
// 	$mastodon_api->set_url(MASTODON_INSTANCE);
// 	$mastodon_api->set_token(MASTODON_ACCESS_TOKEN,'bearer');
//
// 	//	Add the image
// 	if(null!=$mediaFiles){
//
// 		$media_ids = array();
//
// 		foreach ($mediaFiles as $file) {
// 			// upload all media files
// 			$reply =  $mastodon_api->media($file);
// 			var_export($reply);
//
// 			// and collect their IDs
// 			$media_ids[] = $reply["html"]["id"];
// 		}
// 	}
//
// 	//	Toot length is 500 - this gives us overhead for link, licence, and metadata
// 	$length = 400;
//
// 	$toot_inscription = mb_substr($inscription, 0, $length);
// 	if (mb_strlen($inscription) > $length) {
// 		$toot_inscription .= "…";
// 	}
//
// 	$domain = $_SERVER['SERVER_NAME'];
//
// 	$params = [
// 		'status'    => "{$toot_inscription}\nhttps://{$domain}/bench/{$benchID}\n{$license}",
// 		'media_ids' => $media_ids,
// 	];
// 	$reply = $mastodon_api->post_statuses($params);
// }

//	Defaults to a view of the UK
function get_map_javascript($lat = "54.5", $long="-4", $zoom = "5") {
	$mapbox = MAPBOX_API_KEY;
	$mapJavaScript = <<<EOT
<script>
	var attribution = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors, ' +
		'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
		'Imagery © <a href="https://mapbox.com">Mapbox</a>';

	var grayscale = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/outdoors-v10/tiles/256/{z}/{x}/{y}?access_token={$mapbox}', {
		minZoom: 2,
		maxZoom: 18,
		attribution: attribution,
		id: 'mapbox.light'
	});

	var satellite = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v10/tiles/256/{z}/{x}/{y}?access_token={$mapbox}', {
			minZoom: 2,
			maxZoom: 18,
			attribution: attribution,
			id: 'mapbox.satellite'
		});

	var map = L.map('map');

	map.on("load", function () {
		if (window.location.hash != "") {
			if(window.location.hash.indexOf("/") > -1)
			{
				var hashArray = window.location.hash.substr(1).split("/");
				if(hashArray.length >= 2)
				{
					var hashLat = hashArray[0];
					var hashLng = hashArray[1];
					var hashZoom = 16; if(hashArray[2] != void 0){hashZoom = hashArray[2];}
					map.setView([hashLat, hashLng], hashZoom);
				}
			}
		}
	});

	map.setView([{$lat}, {$long}], {$zoom});

	var baseMaps = {
		"Map View": grayscale,
		"Satellite View": satellite
	};

	grayscale.addTo(map);

	L.control.layers(baseMaps).addTo(map);

	var markers = L.markerClusterGroup({
		maxClusterRadius: 29
	});
</script>
EOT;

	echo $mapJavaScript;
}

function get_exif_html($filename) {

	try {
		$img = new \Imagick($filename);
		$exif = $img->getImageProperties();
		$img->clear();
	} catch (\Exception $e) {
		return "";
	}

	if (array_key_exists("exif:DateTime", $exif)) {
		$dateHTML = exif_date_to_timestamp($exif["exif:DateTime"]);
	} else if (array_key_exists("exif:DateTimeOriginal", $exif)) {
		$dateHTML = exif_date_to_timestamp($exif["exif:DateTimeOriginal"]);
	} else if (array_key_exists("exif:DateTimeDigitized", $exif)) {
		$dateHTML = exif_date_to_timestamp($exif["exif:DateTimeDigitized"]);
	} else if (array_key_exists("exif:GPSDateStamp", $exif)) {
		$dateHTML = exif_date_to_timestamp($exif["exif:GPSDateStamp"]);
	} else {
		$dateHTML = null;
	}

	//	Get the make and model
	$makeHTML = "";
	if (array_key_exists("exif:Make", $exif)) {
		$makeHTML = ucwords($exif["exif:Make"]);
	}
	if (array_key_exists("exif:Model", $exif)) {
		$makeHTML .= " " . $exif["exif:Model"];
	}

	//	Format the text
	// if ($makeHTML != "") {
	// 	$makeHTML = " | " . $makeHTML;
	// }

	$exifHTML = "{$dateHTML}&nbsp;{$makeHTML}";

	return $exifHTML;
}

function exif_date_to_timestamp($date) {
	//	Take the first 10 characters e.g. 2017:08:25 and turn it into a date
	$datestring = ( substr( str_replace(":", "-", $date) ,0, 10 ));
	$datetime = new DateTime($datestring);
	$time = $datetime->format('c');
	$human = $datetime->format('jS') . " " . $datetime->format('F') . " " . $datetime->format('Y');
	$dateHTML = "<time datetime=\"{$time}\">{$human}</time>";
	return $dateHTML;
}

function get_path_from_hash($sha1, $full = true) {
	$directory = substr($sha1,0,1);
	$subdirectory = substr($sha1,1,1);
	$photo_path = "photos/".$directory."/".$subdirectory."/";

	if($full) {
		return $photo_path.$sha1.".jpg";
	}

	return $photo_path;
}

function get_location_from_name($location) {
	//	https://api.opencagedata.com/geocode/v1/json?q=Oxford&key=
	$geocode_api_key = OPENCAGE_API_KEY;
	$location = urlencode($location);
	$geocodeAPI = "https://api.opencagedata.com/geocode/v1/json?q={$location}&no_annotations=1&key={$geocode_api_key}";
	$options = array(
		'http'=>array(
			'method'=>"GET",
			'header'=>"User-Agent: OpenBenches.org\r\n"
		)
	);

	$context = stream_context_create($options);
	$locationJSON = file_get_contents($geocodeAPI, false, $context);
	$locationData = json_decode($locationJSON);
	try {
		$lat = $locationData->results[0]->geometry->lat;
		$lng = $locationData->results[0]->geometry->lng;
	} catch (Exception $e) {
		$loc = var_export($locationData);
		error_log("Caught $e - $loc");
		return "";
	}

	return "{$lat}/{$lng}";
}

function get_place_name($latitude, $longitude) {
	$geocode_api_key = OPENCAGE_API_KEY;

	$reverseGeocodeAPI = "https://api.opencagedata.com/geocode/v1/json?q={$latitude}%2C{$longitude}&no_annotations=1&key={$geocode_api_key}";
	$options = array(
		'http'=>array(
			'method'=>"GET",
			'header'=>"User-Agent: OpenBenches.org\r\n"
		)
	);

	$context = stream_context_create($options);
	$locationJSON = file_get_contents($reverseGeocodeAPI, false, $context);
	$locationData = json_decode($locationJSON);
	try {
		$address = $locationData->results[0]->formatted;
	} catch (Exception $e) {
		$loc = var_export($locationData);
		error_log("Caught $e - $loc");
		return "";
	}

	return $address;
}

function save_image($file, $media_type, $benchID, $userID) {
	$filename = $file['name'];
	$file =     $file['tmp_name'];

	//	Not needed. This is checked in add.php
	// if (get_image_location($file) == false) {
	// 	return "<h3>No GPS tags in: {$filename}</h3>";
	// }

	if (duplicate_file($file)) {
		return "<h3>Duplicate image: {$filename}</h3>";
	}

	//	Check to see if this has the right EXIF tags for a photosphere
	if (is_photosphere($file)) {
		$media_type = "360";
	} else if ("360" == $media_type){
		//	If it has been miscategorised, remove the media type
		$media_type = null;
	}

	$sha1 = sha1_file($file);
	$photo_full_path = get_path_from_hash($sha1, true);
	$photo_path      = get_path_from_hash($sha1, false);

	//	Move media to the correct location
	if (!is_dir($photo_path)) {
		mkdir($photo_path, 0777, true);
	}
	$moved = move_uploaded_file($file, $photo_full_path);

	$dimensions = get_image_dimensions($sha1);

	if (null != $dimensions) {
		$width = $dimensions["width"];
		$height = $dimensions["height"];
	} else {
		$width  = null;
		$height = null;
	}

	//	Add the media to the database
	if ($moved){
		$mediaID = insert_media($benchID, $userID, $sha1, "CC BY-SA 4.0", null, $media_type, $width, $height);
		return true;
	} else {
		return("<h3>Unable to move {$filename} to {$photo_full_path} - bench {$benchID} user {$userID} media {$media_type}</h3>");
		die();
	}
}

function duplicate_file($filename) {
	$sha1 = sha1_file($filename);
	$photo_full_path = get_path_from_hash($sha1, true);

	//	Does this photo already exit?
	if(file_exists($photo_full_path)){
		return true;
	}
	return false;
}

function get_image_cache($size=IMAGE_DEFAULT_SIZE, $filter=IMAGE_DEFAULT_FILTER) {
	//	Generate a prefix for the cached image. Can be thumbnailed. https://docs.cloudimage.io/go/cloudimage-documentation/en/operations/
	return IMAGE_CACHE_PREFIX . "{$size}/$filter/" . $_SERVER['SERVER_NAME'];
}

function prepare_search_query($q) {
	$q = str_replace(" ","[[:space:]]*", $q);
	$quoteTranslation=array("\"" => "[\"“”]", "“" => "[\"“]", "”" => "[\"”]", "'" => "['‘’]", "‘" => "['‘]", "’" => "['’]");
	$q = strtr($q,$quoteTranslation);
	return $q;
}

function get_image_dimensions($sha1) {
	try {
		$imagick = new \Imagick(realpath(get_path_from_hash($sha1)));
	} catch (Exception $e) {
		error_log("Image error! {$sha1} - {$e}" , 0);
		return null;
	}
	$height = $imagick->getImageHeight();
	$width  = $imagick->getImageWidth();
	$imagick->clear();

	$image_dimensions = array();

	$image_dimensions["width"]  = $width;
	$image_dimensions["height"] = $height;

	return $image_dimensions;
}

function license_to_icon($shortName){
	//	The exception which doesn't fit
	if ("CC Zero" == $shortName){
		return "cc-zero.svg";
	}

	//	Remove the version
	$version = array("1.0","2.0","3.0","4.0");
	$shortName = str_replace($version, "", $shortName);

	//	Lower case
	$shortName = strtolower($shortName);
	//	Remove trailing space
	$shortName = rtrim($shortName);
	//	Replace space
	$shortName = str_replace(" ","-",$shortName);
	//	Add file type
	return $shortName . ".svg";
}
