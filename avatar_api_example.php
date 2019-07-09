<?php

$SERVER_ROOT_PATH = dirname(__DIR__) . "/";
$AVATARS_FOLDER = "avatars/";
$IMAGES_FOLDER = "images/ayvri_images/";
$DEFAULT_AVATARS_FOLDER = $AVATARS_FOLDER . "defaults/";

/*
Sample return header to write out an image
	HTTP/1.1 200 OK
	Date: Fri, 07 Jun 2019 04:46:50 GMT
	Server: Apache
	Upgrade: h2,h2c
	Connection: Upgrade, Keep-Alive
	Last-Modified: Mon, 23 Jul 2018 03:27:09 GMT
	Accept-Ranges: bytes
	Content-Length: 2745
	Cache-Control: max-age=31536000
	Expires: Sat, 06 Jun 2020 04:46:50 GMT
	X-Endurance-Cache-Level: 2
	Keep-Alive: timeout=5, max=75
	Content-Type: image/jpeg
*/


//https://xcdemon.com/api/avatar_api.php?user_id=9
//https://xcdemon.com/api/avatar_api.php?image=foobar.jpg

$userId = -1;
$doImageGet = false;
$imageNm;
if (isset($_GET['user_id'])){
	$userId = $_GET['user_id'];
} else if (isset($_GET['image'])){
	$doImageGet = true;
	$imageNm = $_GET['image'];
}

$filepath;
if ($doImageGet){
	$filepath = $SERVER_ROOT_PATH . $IMAGES_FOLDER . "$imageNm";
} else {
	//echo $userId;
	$filepath = getAvatarFilePathOrDefault($userId);
	//echo $filepath;
}

//exit;
if (!file_exists($filepath)){
	echo "not file found";
	//Todo: maybe have ultimate default?
	exit;
}
header('Content-type: image/jpeg;');
header("Access-Control-Allow-Origin: *");
header("Content-Length: " . filesize($filepath));
readfile($filepath);
exit;

function getAvatarFilePathOrDefault($user_id){
	$filepath = getAvatarFilePath($user_id);
	if ($filepath === false){
		$filepath = getDefaultAvatarFilePath($user_id);
	}		
	return $filepath;
}

function getAvatarUrl($user_id){
	return getAvatar($user_id, true);
}

function getAvatarFilePath($user_id){
	return getAvatar($user_id, false);    
}

function getAvatar($user_id, $do_get_url, $do_add_timestamp = false){
	global $SERVER_ROOT_PATH;
	global $AVATARS_FOLDER;
	$folder_id = floor($user_id / 100); 
	$avatar_path = $SERVER_ROOT_PATH . $AVATARS_FOLDER . "$folder_id/$user_id.jpg";
	$avatar_url  = $AVATARS_FOLDER . "$folder_id/$user_id.jpg";
	if (!file_exists($avatar_path)){
		return false;
	} else {
		if ($do_get_url) {
			if ($do_add_timestamp) {
				return $avatar_url . '?=' . filemtime($avatar_path);
			} else {
				return $avatar_url;
			}
		} else {
			return $avatar_path;
		}
	}        
}

function getDefaultAvatarUrl($user_id){
	return getDefaultAvatar($user_id, true);
}

function getDefaultAvatarFilePath($user_id){
	return getDefaultAvatar($user_id, false);    
}    

function getDefaultAvatar($user_id, $do_get_url){
	global $SERVER_ROOT_PATH;
	global $DEFAULT_AVATARS_FOLDER;
	$default_id = $user_id % 100;
	$default_avatar_path = $SERVER_ROOT_PATH . $DEFAULT_AVATARS_FOLDER . "$default_id.jpg";
	$default_avatar_url  = $DEFAULT_AVATARS_FOLDER . "$default_id.jpg";
	if (!file_exists($default_avatar_path)){
		return false;
	} else {
		if ($do_get_url) {
			return $default_avatar_url;
		} else {
			return $default_avatar_path;
		}
	}        
}    

?>