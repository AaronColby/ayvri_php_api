<?php
//Example controller to use the ayvri library

require_once __DIR__ . DIRECTORY_SEPARATOR . "ayvri_lib.php";

$CLIENT_ID = "<YOUR ID>";
$ACCOUNT_ID = "<ACCOUNT ID>";
$PASSWORD = "<YOUR PWD>";

$URL_ROOT = "<YOUR WEB SERVER ROOT>";
$LOCAL_ROOT = "<LOCAL WEB HOST ROOT>";

$ayvriLib = new Ayvri($CLIENT_ID, $PASSWORD);

if (!$ayvriLib->isAuthorized()){
	echo "Not authorized";
	return;
}
$avatarUrl = $URL_ROOT  . "api/avatar_api.php?user_id=9";
$avatarUrl2 = $URL_ROOT  . "api/avatar_api.php?user_id=265";
$avatarUrl3 = $URL_ROOT  . "api/avatar_api.php?user_id=39";
$avatarUrl4 = $URL_ROOT  . "api/avatar_api.php?user_id=55";

$backgroundImg = $URL_ROOT  . "api/avatar_api.php?image=ayvri_start.jpg";
$startBtn = $URL_ROOT  . "api/avatar_api.php?image=ayvri_start_btn.png";
echo "$avatarUrl<br>";
echo "$avatarUrl2<br>";
echo "$avatarUrl3<br>";
echo "$avatarUrl4<br>";


$filePath = $LOCAL_ROOT . "/tracklogs/1/20/211/Aaron Price.9.200.igc";
$filePath2 = $LOCAL_ROOT . "/tracklogs/1/20/211/Patrick Joyce.265.231.igc";
$filePath3 = $LOCAL_ROOT . "/tracklogs/1/20/211/Peter Hill.39.230.igc";
$filePath4 = $LOCAL_ROOT . "/tracklogs/1/20/211/Jai Pal Khalsa.55.229.igc";
//$activityType, $title = null, $avatarName = null $avatarUrl = null, $color = null, $opacity = null, $activityId = null){
$activityId = $ayvriLib->uploadNewActivity($filePath, Ayvri::ACTIVITY_PARAGLIDE, null, "Aaron", $avatarUrl);
$activityId2 = $ayvriLib->uploadNewActivity($filePath2, Ayvri::ACTIVITY_PARAGLIDE, null, "Patrick", $avatarUrl2);
$activityId3 = $ayvriLib->uploadNewActivity($filePath3, Ayvri::ACTIVITY_PARAGLIDE, null, "Peter", $avatarUrl3);
$activityId4 = $ayvriLib->uploadNewActivity($filePath4, Ayvri::ACTIVITY_PARAGLIDE, null, "Jay", $avatarUrl4);

$stats = array(Ayvri::SCENE_STAT_LOCAL_TIME, 
			   Ayvri::SCENE_STAT_ALTITUDE, 
			   Ayvri::SCENE_STAT_SPEED, 
			   Ayvri::SCENE_STAT_CLIMB_RATE);
$defaultStatsCount = 4;
$autoplay = false;
$defaultSpeed = 32;
$defaultTargetDistance = 5000;
$title = null; //"Foobar Title";
$sceneId = null;

$activities = array($activityId, $activityId2, $activityId3, $activityId4);
if ($activityId !== false) {
	$result = $ayvriLib->createScene($activities, $title, $sceneId, 
									$stats, $defaultStatsCount, 
									$autoplay, $defaultSpeed , $defaultTargetDistance);
	if ($result === false){
		echo "failed to create scene";
	} else if (isset($result['sceneUrl'])) {
		echo $result['sceneUrl'];
	} else {
		echo var_dump($result);
	}
}
?>