<?php

// ACTION 1: Add your FB page token within the quotes below

$page_access_token="EAABwV8PucqsBAP0JibtyPAwsUCp2sfBD5t4uNt2WiT4C6ZCIWsLGziFSW8MgJYIQltnBOO4dIJMNQpldntJ6vZCWWgVVY2MR7wYj1rB2vwsIQRa2VBIHdBZBtoISZBSi3sCBOG9v28ogTEJnLaU3fNiPwZAoNYh5LZBbl0zjfPWwZDZD";

// ACTION 2:
// visit this link from your browser:
// http://localhost/windows_curl.php?curlcall=1234

// If the result is like this:
// Array ( [success] => 1 )
// It means you have successfully completed this step

// DO Not Edit below this line.

$subscribeurl = "https://graph.facebook.com/v2.6/me/subscribed_apps?access_token=$page_access_token";
if($_REQUEST['curlcall'] == "1234"){curl_tofb($subscribeurl); exit();}


//######################################
function curl_tofb($apiurl)
{

$ch = curl_init($apiurl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$jresult = json_decode($result, true);
print_r($jresult);
//return $jresult;
}
//######################################
