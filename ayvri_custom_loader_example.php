<?php
require_once  "./ayvri_lib.php";

//For detailed failures check your PHP default error log, common errors are when activities fail to load and therefore doesn't supply a valid
//activity id for the scene.  Also be aware of subsequent runs without updating the scene name.

$CLIENT_ID = "";
$ACCOUNT_ID = "";
$PASSWORD = "";
$NEW_LINE = "\n";
$IGC_PATH = "./igc_folder/*.igc";
$title = "Title of Scene"; //Must be unique, can only be used once, if you mess up on creation you'll have to change this title.
$activityIdFile = "lookup.v1.txt"; //In this example the files have a unique ID that I match to the activity created.  That way if the load fails I can grab the activity IDs that were already created instead of uploading activities multiple times on a failed load.


$activityIds = array();
$existingIds = array();
//Check if there is an activity file and load all ids from that file to avoid uploading duplicate activities
$lineNum = 1;
if (file_exists($activityIdFile)){
	$handle = fopen($activityIdFile, "r");
	if ($handle) {
		while (($line = fgets($handle)) !== false) {
			if (isset($line) && !empty($line)){
				$lineParts = explode("-",trim($line));
				if (count($lineParts) == 2){
					$userId = $lineParts[0];
					$activityId = $lineParts[1];
					$existingIds[$userId] = $activityId;
					array_push($activityIds, $activityId);
				} else {
					echo "Ignoring bad line on $lineNum $NEW_LINE";
				}
			} else {
				echo "Ignoring blank line on $lineNum $NEW_LINE";
			}
			$lineNum++;
		}

		fclose($handle);
	} else {
		// error opening the file.
	} 	
}

//Create a new Ayvri session
$ayvriLib = new Ayvri($CLIENT_ID, $PASSWORD);

if (!$ayvriLib->isAuthorized()){
	echo "Not authorized";
	return;
}

//Get array of all IGC files
$files = glob($IGC_PATH);

$fileCount = count($files);
$fileNum = 0;
foreach ($files as $filename) {
	//This section is unique to my example, the filename has a user name and id associated with it that I want to parse out for creating 
	//the activity, I also use the id as a way to get a unique avatar from my server.
	$fileNum++;
	$basename = basename($filename);
	$fileParts = explode(".",$basename);
	$name = $fileParts[0];
	$id = $fileParts[count($fileParts)-2]; //last index == igc, 2nd to last is pilot id
	if (isset($existingIds[$id])){
		echo "$fileNum of $fileCount: $filename - Skipping existing activity $NEW_LINE";
		continue;
	} 
	$avatarUrl = "https://yourimageserver.com/api/avatar_api.php?default_id=$id";

	//Stripping off extraneous text if it exists.
	$name = str_replace('LiveTrack ', '', $name);
	$name = str_replace('LiveTrack_', '', $name);
	
	//I like to do full first name and initial for last name when displaying names on the replay to keep text to a minimum.
	$nameParts = preg_split("/[\s_]+/", $name); //split on all white space and underscores
	//$nameParts = explode("_", $name);
	$avatarName = $nameParts[0];
	if (isset($nameParts[1]) && !empty($nameParts[1])){
		$avatarName .= " " . $nameParts[1][0];
	} else if (isset($nameParts[2]) && !empty($nameParts[2])){
		$avatarName .= " " . $nameParts[2][0];
	}
    echo "$fileNum of $fileCount: Loading $filename - $avatarName - $id: size " . filesize($filename) . $NEW_LINE;
	
	//All the magic here for uploading the activity.
	$activityId = $ayvriLib->uploadNewActivity($filename, Ayvri::ACTIVITY_PARAGLIDE, null, $avatarName, $avatarUrl);
	
	echo "\t activity_id = $activityId". $NEW_LINE;
	array_push($activityIds, $activityId);
	//Save the id/activity mapping to our file in case we need to re-run this script.
	file_put_contents($activityIdFile, "$id-$activityId\n", FILE_APPEND);
}

//Scene variables
$stats = array(Ayvri::SCENE_STAT_LOCAL_TIME, 
			   Ayvri::SCENE_STAT_ALTITUDE, 
			   Ayvri::SCENE_STAT_SPEED, 
			   Ayvri::SCENE_STAT_CLIMB_RATE);
$defaultStatsCount = 4;
$autoplay = false;
$defaultSpeed = 32;
$defaultTargetDistance = 5000;
$sceneId = null;
$shareImg = "share.jpg";
$shareUrl = "https://yourimageserver.com/api/avatar_api.php?image=$shareImg";

//echo var_dump($activityIds);
//exit;

if (count($activityIds) > 0) {
	$result = $ayvriLib->createScene($activityIds, $title, $sceneId, 
									$stats, $defaultStatsCount, 
									$autoplay, $defaultSpeed , $defaultTargetDistance,
									$shareUrl);
	if ($result === false){
		echo "failed to create scene" . $NEW_LINE;
	} else if (isset($result['sceneUrl'])) {
		echo $result['sceneUrl'] . $NEW_LINE;
	} else {
		echo var_dump($result) . $NEW_LINE;
	}
} else {
	echo "no activity ids found" . $NEW_LINE;
}
?>