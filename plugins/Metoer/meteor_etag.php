<?php
/* evercookie, by samy kamkar, 09/20/2010
 *  http://samy.pl : code@samy.pl
 *
 * This is the server-side ETag software which tags a user by 
 * using the Etag HTTP header, as well as If-None-Match to check
 * if the user has been tagged before.
 *
 * -samy kamkar
 */

if (!function_exists('getallheaders')) 
{
    function getallheaders() 
    {
        foreach ($_SERVER as $name => $value) 
        {
            if (substr($name, 0, 5) == 'HTTP_') 
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// we don't have a cookie, so we're not setting it
if (!$_COOKIE["evercookie_etag"])
{
	// read our etag and pass back
    $headers = getallheaders();
	echo $headers['If-None-Match'];

	exit;
}

// set our etag
header('Etag: ' . $_COOKIE["evercookie_etag"]);
echo $_COOKIE["evercookie_etag"];
?>
