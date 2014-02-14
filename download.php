<?PHP

/* Script Variables */
// Lots of output, saves requests to a local file.
$debugMode = false; 
// Initially, you should set this to something like "-2 years". Once you have all day, change this to "-48 hours" or so to pull incremental data
$TimeBackPull = "-2 years";

/* RETS Variables */
require("PHRets_CREA.php");
$RETS = new PHRets();
$RETSURL = "http://data.crea.ca/Login.svc/Login";
$RETSUsername = "";
$RETSPassword = "";
$RETS->Connect($RETSURL, $RETSUsername, $RETSPassword);
$RETS->AddHeader("RETS-Version", "RETS/1.7.2");
$RETS->AddHeader('Accept', '/');
$RETS->SetParam('compression_enabled', true);
$RETS_PhotoSize = "LargePhoto";
$RETS_LimitPerQuery = 100;
if($debugMode /* DEBUG OUTPUT */)
{
	//$RETS->SetParam("catch_last_response", true);
	$RETS->SetParam("debug_file", "/var/web/CREA_Anthony.txt");
	$RETS->SetParam("debug_mode", true);
}

function downloadPhotos($listingID)
{
	global $RETS, $RETS_PhotoSize, $debugMode;
	
	if(!$downloadPhotos)
	{
		if($debugMode) error_log("Not Downloading Photos");
		return;
	}

	$photos = $RETS->GetObject("Property", $RETS_PhotoSize, $listingID, '*');
	
	if(!is_array($photos))
	{
		if($debugMode) error_log("Cannot Locate Photos");
		return;
	}

	if(count($photos) > 0)
	{
		$count = 0;
		foreach($photos as $photo)
		{
			if(
				(!isset($photo['Content-ID']) || !isset($photo['Object-ID']))
				||
				(is_null($photo['Content-ID']) || is_null($photo['Object-ID']))
				||
				($photo['Content-ID'] == 'null' || $photo['Object-ID'] == 'null')
			)
			{
				continue;
			}
			
			$listing = $photo['Content-ID'];
			$number = $photo['Object-ID'];
			$destination = $listingID."_".$number.".jpg";
			$photoData = $photo['Data'];
			
			/* @TODO SAVE THIS PHOTO TO YOUR PHOTOS FOLDER
			 * Easiest option:
			 * 	file_put_contents($destination, $photoData);
			 * 	http://php.net/function.file-put-contents
			 */
			 
			$count++;
		}
		
		if($debugMode)
			error_log("Downloaded ".$count." Images For '".$listingID."'");
	}
	elseif($debugMode)
		error_log("No Images For '".$listingID."'");
	
	// For good measure.
	if(isset($photos)) $photos = null;
	if(isset($photo)) $photo = null;
}

/* NOTES
 * With CREA, You have to ask the RETS server for a list of IDs.
 * Once you have these IDs, you can query for 100 listings at a time
 * Example Procedure:
 * 1. Get IDs (500 Returned)
 * 2. Get Listing Data (1-100)
 * 3. Get Listing Data (101-200)
 * 4. (etc)
 * 5. (etc)
 * 6. Get Listing Data (401-500)
 *
 * Each time you get Listing Data, you want to save this data and then download it's images...
 */
 
error_log("-----GETTING ALL ID's-----");
$DBML = "(LastUpdated=" . date('Y-m-d', strtotime($TimeBackPull)) . ")";
$params = array("Limit" => 1, "Format" => "STANDARD-XML", "Count" => 1);
$results = $RETS->SearchQuery("Property", "Property", $DBML, $params);
$totalAvailable = $results["Count"];
error_log("-----".$totalAvailable." Found-----");
if(empty($totalAvailable) || $totalAvailable == 0)
	error_log(print_r($RETS->GetLastServerResponse(), true));	
for($i = 0; $i < ceil($totalAvailable / $RETS_LimitPerQuery); $i++)
{
	$startOffset = $i*$RETS_LimitPerQuery;
	
	error_log("-----Get IDs For ".$startOffset." to ".($startOffset + $RETS_LimitPerQuery).". Mem: ".round(memory_get_usage()/(1024*1024), 1)."MB-----");
	$params = array("Limit" => $RETS_LimitPerQuery, "Format" => "STANDARD-XML", "Count" => 1, "Offset" => $startOffset);
	$results = $RETS->SearchQuery("Property", "Property", $DBML, $params);			
	foreach($results["Properties"] as $listing)
	{
		$listingID = $listing["@attributes"]["ID"];
		if($debugMode) error_log($listingID);
	
		/* @TODO Handle $listing array. Save to Database? */
		
		/* @TODO Uncomment this line to begin saving images. Refer to function at top of file */
		//downloadPhotos($listingID);
	}
}

$RETS->Disconnect();

/* This script, by default, will output something like this:

Connecting to RETS as '[YOUR RETS USERNAME]'...
-----GETTING ALL ID's-----
-----81069 Found-----
-----Get IDs For 0 to 100. Mem: 0.7MB-----
-----Get IDs For 100 to 200. Mem: 3.7MB-----
-----Get IDs For 200 to 300. Mem: 4.4MB-----
-----Get IDs For 300 to 400. Mem: 4.9MB-----
-----Get IDs For 400 to 500. Mem: 3.4MB-----
*/

?>
