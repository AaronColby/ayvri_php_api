<?php

class Ayvri {	

	//Pulled from: https://api.ayvri.com/2.0/activityTypes
	//There may be updates periodically to this list
	const ACTIVITY_AERIAL = "Aerial";
	const ACTIVITY_AIRPLANE = "Airplane";
	const ACTIVITY_ALPINE = "Alpine";
	const ACTIVITY_AVIARY = "Aviary";
	const ACTIVITY_BALLOON = "Balloon";
	const ACTIVITY_BOATING = "Boating";
	const ACTIVITY_CYCLE = "Cycle";
	const ACTIVITY_DRIVE = "Drive";
	const ACTIVITY_DRONE = "Drone";
	const ACTIVITY_EQUESTRIAN = "Equestrian";
	const ACTIVITY_GLIDE = "Glide";
	const ACTIVITY_GROUND = "Ground";
	const ACTIVITY_HANG_GLIDE = "Hang Glide";
	const ACTIVITY_HIKE_GLIDE = "Hike + Glide";
	const ACTIVITY_HIKE = "Hike";
	const ACTIVITY_HORSE_RIDING = "Horse Riding";
	const ACTIVITY_MIXED = "Mixed";
	const ACTIVITY_MOTORCYCLE = "Motorcycle";
	const ACTIVITY_MOUNTAIN_BIKE = "Mountain Bike";
	const ACTIVITY_MOUNTAINEER = "Mountaineer";
	const ACTIVITY_OTHER = "Other";
	const ACTIVITY_PADDLE = "Paddle";
	const ACTIVITY_PARAGLIDE = "Paraglide";
	const ACTIVITY_RUN = "Run";
	const ACTIVITY_SNOWMOBILE = "Snowmobile";
	const ACTIVITY_SURF = "Surf";
	const ACTIVITY_SWIM = "Swim";
	const ACTIVITY_TRAIN = "Train";
	const ACTIVITY_WATERSKI = "Waterski";
	const ACTIVITY_WINDSURF = "Windsurf";
	const ACTIVITY_XC_SKI = "XC Ski";
	
	const SCENE_STAT_SPEED = "speed";
	const SCENE_STAT_AIR_SPEED = "airSpeed";
	const SCENE_STAT_BOAT_SPEED = "boatSpeed";
	const SCENE_STAT_DISTANCE = "distance";
	const SCENE_STAT_ALTITUDE = "altitude"; //Same as elevation... just a different title
	const SCENE_STAT_ALTITUDE_FROM_START = "altitudeFromStart";
	const SCENE_STAT_ELEVATION = "elevation";	//Same as altitude... just a different title
	const SCENE_STAT_GRADIENT = "gradient"; // no clue what this is, seems to be in %
	const SCENE_STAT_CLIMB_RATE = "climbRate";
	const SCENE_STAT_GLIDE_RATIO = "glideRatio"; //doesn't seem to work... just giving a static value of +++
	const SCENE_STAT_LOCAL_TIME = "localTime";
	const SCENE_STAT_GMT_TIME = "gmtTime";
	
	const REQUEST_TYPE_POST = "POST";
	const REQUEST_TYPE_PUT = "PUT";
	
	const AYVRI_BASE_URL = "https://api.ayvri.com/2.0/";
	const AUTH_URL = self::AYVRI_BASE_URL . "auth";
	const ACTIVITY_URL = self::AYVRI_BASE_URL . "activity";
	const SCENE_URL = self::AYVRI_BASE_URL . "scene";
	
	// Seconds to wait for Ayvri servers
	const TIMEOUT_IN_SEC = 60;
	
	// Flag we can check to see if we've logged in.
	private $isAuthorized = false;
	
	// On initialization set the clientId and password
	private $clientId;
	private $password;
	
	// Once logged in we set these variables for subsequent calls
	private $accessToken; // This is the main one that we use to send data to the server after logging in.
	private $refreshToken; // This is used to get a new access token but only after a week, so I'm not sure what the sue case is.
	private $accessTokenExpiresTimestamp = 0; //seconds since unix epoch... timezone independent
	private $tokenType; //no clue what this is used for
	
	//supporting older versions of PHP I guess :)
	public function Ayvri($clientId, $password){
		self::__construct($clientId, $password);
	}

	/*
		New PHP constructor. Pass in your credentials and we initialize the library but running
		authorization and saving the accessToken.  Generally the accessToken is valid for a week
		so for a normal short run it would be find to use if for a few minutes for several different calls.
		Just need to keep a handle to this library to keep the accessToken.  All calls double check that
		the accessToken is still valid and will try to re-authorize if not.
		
		Params:
			clientId: ayvri account client id
			password: ayvri account password
	*/
	public function __construct($clientId, $password){
		$this->clientId = $clientId;
		$this->password = $password;
		
		$responseObj = $this->authorize();
		if ($responseObj === false){
			error_log(__METHOD__ . ": failed to initialize Ayvri library");
		}
	}	
	
	/** 
	* Check this after creating the Ayvri object, if false then subsequent calls will fail.
	*/
	public function isAuthorized(){
		return $this->isAuthorized;
	}
	
	/**
		Returns true if the current timestamp is less than the access token expiration timestamp,
		false otherwise.
	*/
	public function isAccessTokenValid(){
		if (!isset($this->accessTokenExpiresTimestamp) || $this->accessTokenExpiresTimestamp == 0){
			return false;
		}
		if (time() < $this->accessTokenExpiresTimestamp){
			return true;
		} else {
			return false;
		}
	}	
		
	/**
	 Hits the authorize api end point of ayvri and sets up local vars with the necessary
	 information to run subsequent calls.  Requires that clientId and password have been
	 initialized first.
	 
	 Returns true if we successfully authorize and initialize all variables, false otherwise
	 
	 Return Object from authorize api point:
		$responseObj["access_token"] = ...
		$responseObj["refresh_token"] = ...
		$responseObj["token_type"] = "bearer" //no clue what this is
		$responseObj["expires_in"] = 604800, //seconds... this would be 7 days
	*/
	private function authorize(){
		$data = "grant_type=client_credentials";	
		$userPwd = $this->clientId .":". $this->password;
		$responseObj = $this->doCurl(self::AUTH_URL, self::REQUEST_TYPE_POST, $data, $userPwd);

		if ($responseObj === false || isset($responseObj['error'])){
			error_log(__METHOD__ . ": unable to authorize() ayvri library");
			if (isset($responseObj['error'])){
				error_log(__METHOD__ . ": error message = " . $responseObj['error']);
			}
			$this->accessToken = null;
			$this->refreshToken = null;
			$this->tokenType = null;
			$this->accessTokenExpiresTimestamp = 0;			
			$this->isAuthorized = false;		
			return false;
		} else {
			$this->accessToken = $responseObj["access_token"];
			$this->refreshToken = $responseObj["refresh_token"];
			$this->tokenType = $responseObj["token_type"]; //not sure this is used
			$tokenExpiresInSeconds = $responseObj["expires_in"];
			
			$currTimestamp = time(); //current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)... timezones of your sever won't matter
			$this->accessTokenExpiresTimestamp = $currTimestamp + $tokenExpiresInSeconds;
			$this->isAuthorized = true;			
		}

		return true;	
	}
	
	/**
		Helper function to create a new activity and upload the track in one go.
	
		$igcFile: path to IGC file on the server
		$activityType: type of activity, use constants in class
		$title: (Optional), title of activity, must be unique to ? account? and doesn't seem to do anything, probably leave as null
		$avatarName: (Optional), name of person for track
		$avatarUrl: (Optional), image url to use for persons track
		$color: (Optional), '#RRGGBB' or 'rgb(R,G,B)' used for track
		$opacity: (Optional), 0-1, doesn't seem to work
		$activityId: (Optional), must be unique, one will be created automatically if not specified. recommend to leave blank
		
		Return: created activityId, should be saved with the track to collate all relevant tracks into a scene.
	*/
	public function uploadNewActivity($igcFile, $activityType, $title = null, $avatarName = null, $avatarUrl = null, $color = null, $opacity = null, $activityId = null){
		$activityResponse = $this->createActivity($activityType, $title, $avatarName, $avatarUrl, $color, $opacity, $activityId);
		
		if ($activityResponse === false || isset($activityResponse["error"])){
			error_log(__METHOD__ . ": Failed while trying to create a new activity");
			if (isset($responseObj['error'])){
				error_log(__METHOD__ . $responseObj['error']);
			}			
			return false;
		} 
		
		//error_log(__METHOD__ . ": create activity response = ");
		//echo var_dump($activityResponse);
		$url = $activityResponse['uploadUrl'];
		$activityId = $activityResponse['activityId'];
		
		$uploadResponse = $this->uploadIgc($url, $igcFile);
		if ($uploadResponse === false){
			error_log(__METHOD__ . ": Failed while trying to create upload for activity $activityId to url " . $url);
			return false;			
		}
		
		return $activityId;
	}
	
	/**
	createActivity(): creates the activity which can then have an tracklog uploaded for it..
	Returns: a JSON object with many values (as below), but the main one we need is the uploadUrl
	
	Response Object	
	{ 
		["accountId"]=> string(7) "xcdemon" 
		["activityId"]=> string(20) "c3cf2d16bee97466dd30" 
		["title"]=> NULL 
		["includedInScene"]=> array(0) { } 
		["activityType"]=> string(9) "Paraglide" 
		["elevationCorrectionType"]=> int(2) 
		["private"]=> bool(true) 
		["stats"]=> array(0) { } 
		["focusable"]=> bool(true) 
		["show"]=> bool(true) 
		["uploadUrl"]=> string(1183) "https://unprocessed-tracks.s3.amazonaws.com/xcdemon/c3cf2d16bee97466dd30.896220?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIAWGKE4ABBXMJPJ4YE%2F20190606%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20190606T094646Z&X-Amz-Expires=900&X-Amz-Security-Token=AgoJb3JpZ2luX2VjEAEaCXVzLWVhc3QtMSJHMEUCIQD5GvJo5msmw1HkCdUBR5FeSGtElrT0PrYp%2B6g3kx5ocgIgdCvE0duRKeljMt6OBsV6jHfm34NlN7XJVJ2h%2FHmnMwQqpAIIKhAAGgw0MjU4ODMwNzQ2MjciDFWBxQA4so6IZRt5xyqBAslZW%2BHDvxQ4qX%2FQ2wOpd%2FQhrokv6NhDNQ6nBKCrausyoQrV8XZvuQGBvgUCTDwvc2wJ2Drsl4AydkXSwrLnfeofGUPLVQpYTUbengIerz3vepG2kFhNeD0yPV7yi0xZ29%2B0eLMy6rhgTkI9GQd6OLfEIyu%2BRJgLFVOoahHNSl3Ak%2BpCcM5bNWwdmHOq6zFUDotc1DyGwW%2FQTkeVYWvJGEBtcBmYSQ1wUnH0BtmrjynsBAwjPNcQRfWJjHb9Z6kASZ%2BYl46g3rsYcjy4fVgRAAOEuMvv%2B1pEdb9i9ohT8KvtGsd0BeE56QGAIiCpZQGgzEQphNqGL1Z8GoKkhsc76J2oMNqc4%2BcFOrQB5gC2aFyrwb6rknicA9IeOACFqk%2FAtQhJWP%2Fim054iVOwGPG5%2BZ54RID8PJBMmAyLGk63%2FwH0NsFl0LdIJMj0kzLttEVQ2gwAj33rBDvJJWHaUFhHzcpmI2R4Lo3iW7XJ852PW7F9KOdu0FqGDqbM2TzISS39riOILpQ%2FcQJbDJaNtqUDr33TZAm2hYW%2BBvRlhaBk7thsMmQqsXdZHi%2FtJC9puFGMSM4TXtQWssALkTamp%2FBD&X-Amz-Signature=3536f2afde4633686000b011c63c9c7ea84812e144605b8fe95967643d0c6bad&X-Amz-SignedHeaders=host" 
	}	

		$activityType: must be one of the defined ACTIVITY_XYZ constants in the class
		$title: title of the activity, must be unique, probably best not to use.
		$avatarName: name of the person doing the activity
		$avatarUrl: url to an image of the person doing the activity, need to make sure that you have an image server that adds the header:
			"Access-Control-Allow-Origin: *"
		$color: either '#RRGGBB' or 'rgb(R,G,B)', values should be 00 -> FF or 0->255 (i.e. #FFFFFF = white  OR rgb(0,0,0) = Black
		$opacity: From 0 to 1
		$activityId: not sure if there is any benefit to using this, must be unique, cannot reuse.  Better to let the system create one for you.
	*/
	private function createActivity($activityType, $title = null, $avatarName = null, $avatarUrl = null, $color = null, $opacity = null, $activityId = null){
		if (!$this->isAuthorized() || !$this->isAccessTokenValid()){
			if (!$this->authorize()){
				error_log(__METHOD__ . ": Ayvri library not initialized to run createActivity()");
				return false;
			}
		}
		
		if (!isset($activityType)){
			error_log(__METHOD__ . ": activityType not set");
			return false;
		} 
		/* probably don't want this... overly restrictive
		else if (!isset($this->ACTIVITY_LOOKUP[$activityType])){
			error_log(__METHOD__ . ": activityType not a known type: '" . $activityType . "'");
			return false;
		}*/
		
		$data = array("activityType" => $activityType);
		
		//Everything after is optional
		if (isset($title)){
			$data["title"] = $title;
		}
		if (isset($activityId)){
			$data["activityId"] = $activityId;
		}		
		if (isset($color)){
			if (!preg_match("/#[0-9a-fA-F]{6}/", $color) &&
				!preg_match("/rgb\(\d{1,3},\d{1,3},\d{1,3}\)/", $color)) {
				error_log(__METHOD__ . ": color not provided in expected format [#RRGGBB | rgb(R,G,B)]: '" . $color . "'");
				return false;
			}
			$data["color"] = $color;
		}
		if (isset($opacity)){
			if ($opacity > 1 || $opacity < 0){
				error_log(__METHOD__ . ": opacity outside of valid range (0-1): '" . $opacity . "'");
				return false;
			}
			$data["opacity"] = $opacity;
		}		
		
		if (isset($avatarName) || isset($avatarUrl)){
			$avatarObj = array();
			if (isset($avatarName)){
				$avatarObj["name"] = $avatarName;
			}		
			if (isset($avatarUrl)){
				$avatarObj["image"] = $avatarUrl;
			}	
			$data["avatar"] = $avatarObj;
		}
		
		$data_string = json_encode($data);
		//echo "$data_string<br>";
		$responseObj = $this->doCurl(self::ACTIVITY_URL, self::REQUEST_TYPE_POST, $data_string);
		
		if ($responseObj === false ){
			error_log(__METHOD__ . ": failed on CURL to create an activity");
		} else if (isset($responseObj['error'])){
			error_log(__METHOD__ . ": Error creating an activity with message = " . $responseObj['error']);
			return false;			
		}

		return $responseObj;		
	}
	
	/**
		uploadIgc(): uploads an IGC for an already created activity.
		Returns XML on error or 200 response code if successful
		Sample error response
		<?xml version="1.0" encoding="UTF-8"?>
		<Error>
			<Code>AuthorizationQueryParametersError</Code>
			<Message>Query-string authentication version 4 requires the X-Amz-Algorithm, X-Amz-Credential, X-Amz-Signature, X-Amz-Date, X-Amz-SignedHeaders, and X-Amz-Expires parameters.</Message>
			<RequestId>2F5DF80C3BABE57D</RequestId>
			<HostId>uLrCyi1cAuAWVBrLrajrVz6ftf1Q56Fj4pvNcj9JTrTtU0a3vtGsmu2HqnzpKhnlJeZs+xRw+84=</HostId>
		</Error>
	*/	
	private function uploadIgc($url, $igcPath){
		if (!$this->isAuthorized() || !$this->isAccessTokenValid()){
			if (!$this->authorize()){
				error_log(__METHOD__ . ": Ayvri library not initialized to run createActivity()");
				return false;
			}
		}
		
		if (!file_exists($igcPath)){
			error_log(__METHOD__ . ": Could not find file to upload: '" . $igcPath ."'");
			return false;
		}
		
		$data = file_get_contents($igcPath);
		//True or false... if an error check logs
		$responseObj = $this->doCurl($url, self::REQUEST_TYPE_PUT, $data);
		return $responseObj;
	}
	
	/**
		createScene(): for an array of activityIds creates a scene which can be shared via a single link
		Returns the following JSON object: 
		{ 
			["media"]=> array(0) { } 
			["stats"]=> array(0) { } 
			["loading"]=> array(0) { } 
			["segments"]=> array(0) { } 
			["exitScreen"]=> array(0) { } 
			["hideElements"]=> array(0) { } 
			["sceneId"]=> string(20) "f433bbd27da2a779972c" 
			["accountId"]=> string(7) "xcdemon" 
			["accountType"]=> string(3) "api" 
			["live"]=> bool(false) 
			["private"]=> bool(true) 
			["activities"]=> array(1) { 
				[0]=> array(12) { 
					["show"]=> bool(true) 
					["stats"]=> array(0) { } 
					["focusable"]=> bool(true) 
					["activityType"]=> string(9) "Paraglide" 
					["includedInScene"]=> array(0) { } 
					["elevationCorrectionType"]=> int(2) 
					["activityId"]=> string(20) "0aeba5b3cfb6feb93d9a" 
					["activityIdx"]=> int(896244) 
					["accountId"]=> string(7) "xcdemon" 
					["live"]=> bool(false) 
					["private"]=> bool(false) 
					["processed"]=> bool(false) 
				} 
			} 
			["created"]=> string(24) "2019-06-06T10:24:00.000Z" 
			["version"]=> int(0) 
			["sceneUrl"]=> string(52) "https://ayvri.com/scene/xcdemon/f433bbd27da2a779972c" 
			["embedUrl"]=> string(52) "https://ayvri.com/embed/xcdemon/f433bbd27da2a779972c" 
			["muxerEndpoint"]=> string(66) "https://yjv32gt469.execute-api.us-east-1.amazonaws.com/production/" 
		}
	Input:
		activityIds: array of ids
		title: (optional)... must be unique, cannot re-use a title, if no sceneId is set then the sceneId will default to this as well.
		sceneId: (optional) created if not used, recommended?		
		shareImg => url: Set an image to be used by social networks when your Scene is shared.
		loading - loading image: // not working at the moment thought
			{ 
				backGround : imageurl, //image used as background before users presses play. Not used if set to AutoPlay
				startImg : imageurl //an image to use as the play button on top of the background image
			} 
		defaultSpeed: number | optional - a multiple of real-time
		defaultTargetDistance: number | optional - the distance (zoom) the camera will follow an activity through the scene (meters from target)
		autoplay: boolean | optional - Scene can be set to autoplay when loaded, so the loading page which not be seen. not working when set to false... probably why loading image isn't working
		stats: ["speed","airSpeed","boatSpeed","distance","altitude","gradient","climbrate","glideRatio","elevation","altitude","altitudeFromStart","localTime","gmtTime"]
		defaultStatsVisible: number | optional, default = 1		
			
		distanceMarkers - ?
			"distanceMarkers": {"frequency": 5, "color": "#4286f4"}}					
		
		// Not implemented at this time
		exitScreen - array of images/links to show at end
			image - required
			link - required
			"exitScreen":[{"image": "https"://url.to/image", "link":"https://url.to/your-page"}] 		
		
		segments:
			"segments": [{"time":"2017-11-03T12:11:00Z", "target": "activity":, "targetId":"uniqueTargetId", "speed": 300, "targetDistance": 2000}, {"time":"2017-11-03T12:18:00Z", "target": "activity":, "targetId":"uniqueTargetId", "speed": 100, "targetDistance": 1200}]
		
		media: array of media items
			 "media": [{"mediaType": "photo", "source": "https://url.to/image-file", "target": { "activityId": "activityId_1", "time": "2017-06-02T12:11:00Z"} }]
			
	*/	
	public function createScene($activityIds, $title = null, $scenedId = null, 
								$statsArray = null, $defaultVisibleStats = null, 
								$autoplay = null,
								$defaultSpeed = null, $defaultTargetDistance = null,
								$shareImgUrl = null, $backgroundImgUrl = null, $startImgUrl = null,
								$distanceMarkerFrequency = null, $distanceMarkerColor = "#FFFFFF"){
		if (!$this->isAuthorized() || !$this->isAccessTokenValid()){
			if (!$this->authorize()){
				error_log(__METHOD__ . ": Ayvri library not initialized to run createActivity()");
				return false;
			}
		}
		
		if (!isset($activityIds) || !is_array($activityIds) || count($activityIds) == 0){
			error_log(__METHOD__ . ": no activity ids found");
			return false;
		}
		$activitiesArray = array();
		foreach ($activityIds as $activityId){
			$tmpArray = array("activityId" => $activityId);
			array_push($activitiesArray, $tmpArray);
		}	
		$data = array("activities" => $activitiesArray);
		
		//Optional arguments:
		if (isset($title)){
			$data["title"] = $title;
		}	
		
		if (isset($scenedId)){
			$data["scenedId"] = $scenedId;
		}				
		
		if (isset($statsArray) && count($statsArray) > 0){
			$stats = array();
			foreach ($statsArray as $tmpStat){
				array_push($stats, $tmpStat);
			}
			$data["stats"] = $stats;
			//$data["stats"] = implode("|", $statsArray);
		}
		
		if (isset($defaultVisibleStats) && is_numeric($defaultVisibleStats) &&  $defaultVisibleStats > 0){
			$data["defaultStatsVisible"] = round($defaultVisibleStats); // make sure it's a whole number
		}
		
		if (isset($autoplay)){
			$data["autoplay"] = $autoplay ? true : false;
		}
		
		if (isset($defaultSpeed) && is_numeric($defaultSpeed) &&  $defaultSpeed > 0){
			$data["defaultSpeed"] = round($defaultSpeed); // make sure it's a whole number
		}		
		
		if (isset($defaultTargetDistance) && is_numeric($defaultTargetDistance) &&  $defaultTargetDistance > 0){
			$data["defaultTargetDistance"] = round($defaultTargetDistance); // make sure it's a whole number
		}		

		if (isset($shareImgUrl)){
			$data["shareImg"] = $shareImgUrl;
		}
		
		if (isset($backgroundImgUrl) || isset($startImgUrl)){
			$loadingArray = array();
			if (isset($backgroundImgUrl)){
				$loadingArray["background"] = $backgroundImgUrl;
			}
			if (isset($startImgUrl)){
				$loadingArray["startImg"] = $startImgUrl;
			}		
			$data["loading"] = $loadingArray;
		}
		
		if (isset($distanceMarkerFrequency) && is_numeric($distanceMarkerFrequency) && $distanceMarkerFrequency > 0){
			$data["distanceMarkers"] = array(
				"frequency" => round($distanceMarkerFrequency),
				"color" => $distanceMarkerColor
			);
		}
		
		$data_string = json_encode($data);
		
		//echo "$data_string<br>";
		
		$responseObj = $this->doCurl(self::SCENE_URL, self::REQUEST_TYPE_POST, $data_string);
		
		if ($responseObj === false ){
			error_log(__METHOD__ . ": Error creating a scene, check the error logs for more details.");
			return false;
		} else if (isset($responseObj['error'])){
			error_log(__METHOD__ . ": Error creating a scene, message = " . $responseObj['error']);
			return false;			
		}
		
		return $responseObj;			
	}
	
	/**
		Helper function to do the CURL requests to the Ayvri servers.
		$url: end point to hit
		$requestType: currently only REQUEST_TYPE_POST or REQUEST_TYPE_PUT, use the class constants
		$data: data pre-encoded for whatever the server needs
		$userPwd: only set if we are doing a user authorization, otherwise we will default to using the auth token.
	*/
	private function doCurl($url, $requestType, $data, $userPwd = null){		
		if ($requestType != self::REQUEST_TYPE_POST && $requestType != self::REQUEST_TYPE_PUT ){
			error_log(__METHOD__ . ": unknown request type '". $requestType."'");
			return false;
		}
		$ch = curl_init();	

		$headers = array();
		if ($requestType == self::REQUEST_TYPE_POST) {
			curl_setopt($ch, CURLOPT_POST, 1);
			$headers = array(
				"Accept:application/json",
				"Content-Type: application/json",
				"Content-Length: " . strlen($data)
			);
			if (!isset($userPwd)){
				array_push($headers, "Authorization:".$this->accessToken);
			}
		} else if ($requestType == self::REQUEST_TYPE_PUT){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			//Don't do authorization for the put, it's in the URL so putting anything in the headers will break it.
			$headers = array(
				"Accept:*/*",
				"Content-Length: " . strlen($data)
			);			
		}
		
		if (isset($userPwd)){
			//We are doing authorization so supply user/pwd
			curl_setopt($ch, CURLOPT_USERPWD, $userPwd);
		} 
		
		curl_setopt($ch, CURLOPT_URL, $url);				
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true); //used with curl_getinfo below for debugging		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //not sure what this does
		curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_IN_SEC); //could be refactored to constants package, global var, or similar
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //Setting this last can be important it seems, as other options may over-ride headers?	
		
		$response = curl_exec($ch);
		//error_log(__METHOD__ . ": $response");
		$responseObj = false;

		//Debugging stuff
		//$information = curl_getinfo($ch); //gets everything that was sent and returned
		//echo var_dump($information);				
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //CURLINFO_RESPONSE_CODE newer
		if (curl_errno($ch)) { 
			error_log(__METHOD__ . ": Curl Error: " . curl_error($ch));
		} else {				
			if ($requestType == self::REQUEST_TYPE_PUT){
				if ($httpCode != 200){
					error_log(__METHOD__ . ": Curl PUT Error ($httpCode): " . $response);
				} else {
					$responseObj = true;
				}
			} else {
				$responseObj = json_decode($response, true);
			}
		}
		
		// close cURL resource, and free up system resources
		curl_close($ch);
		return $responseObj;			
	}
	
	public function getAccessToken(){
		return $this->accessToken;
	}
	
	public function getRefreshToken(){
		return $this->refreshToken;
	}

	public function getTokenType(){
		return $this->tokenType;
	}

	public function getAccessTokenExpiresTimestamp(){
		return $this->accessTokenExpiresTimestamp;
	}	
		
	/**
	 Helper to handle errors and log in class as needed
	*/ 
	private function handleError($resultsArray){
		if (!isset($resultsArray)){
			return "No result for Ayvri";
		}
		if (!is_array($resultsArray)){
			return "Unknown Ayvri data: " . $resultsArray;
		}
		//"{"status":"ERROR","message":"api002.controllers.Api002$ApiException: Problem getting file"}"
		if(array_key_exists("status", $resultsArray) && strcmp("ERROR", $resultsArray["status"]) == 0
			&& array_key_exists("message", $resultsArray)){
			return $resultsArray["message"];
		} else {
			return "Unknown Doarama Error.";
		}				
	}

}
?>