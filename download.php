<?PHP

use PHRETS\Configuration;
use PHRETS\Http\Client;
use PHRETS\Session;

function __autoload($class_name)
{
    include str_replace("\\", "/", $class_name) . '.php';
}

/* Script Variables */
// Lots of output, saves requests to a local file.
$debugMode = false; 
// @TODO Set this to something like "-2 years" to get all listings, then choose "-2 days" for on-going updates, etc.
$TimeBackPull = "-2 days";
date_default_timezone_set('America/New_York');

/* Do Not Edit Zone ------------------- */
$RETS_LimitPerQuery = 100;
/* END Do Not Edit Zone --------------- */

$config = new PHRETS\Configuration;
$config = Configuration::load([
    'login_url' => 'http://data.crea.ca/Login.svc/Login',
    'username' => 'VJ4ExvOz6mLNP6d1kq0aG22r',
    'password' => 'dwdmTV2hZ6GqtJaqskADWwc0',
    'user_agent' => 'MyUserAgent/1.0', // @TODO Make this yours or remove it
    'rets_version' => '1.7.2'
]);
$config->setLoginUrl('http://data.crea.ca/Login.svc/Login')
        ->setUsername('VJ4ExvOz6mLNP6d1kq0aG22r')
        ->setPassword('dwdmTV2hZ6GqtJaqskADWwc0')
        ->setRetsVersion('1.7.2');
		
$rets = new \PHRETS\Session($config);

if($debugMode /* DEBUG OUTPUT */)
{
	$log = new \Monolog\Logger('PHRETS');
	$log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
	$rets->setLogger($log);
}

$connect = $rets->Login();

//$RETS->SetParam('compression_enabled', true);
//$RETS_PhotoSize = "LargePhoto";

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
 
echo("-----GETTING ALL ID's-----");
$results = $rets->Search('Property', 'Property', '(LastUpdated='.date('Y-m-d', strtotime($TimeBackPull)).')', ['Limit' => 3, 'Format' => 'STANDARD-XML', 'Count' => 1]);
print_r($results);

$rets->Disconnect();