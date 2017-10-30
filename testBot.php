<?php
error_reporting(E_ERROR | E_PARSE);

global $apiurl, $graphapiurl, $page_access_token;

$page_access_token="EAABwV8PucqsBAP0JibtyPAwsUCp2sfBD5t4uNt2WiT4C6ZCIWsLGziFSW8MgJYIQltnBOO4dIJMNQpldntJ6vZCWWgVVY2MR7wYj1rB2vwsIQRa2VBIHdBZBtoISZBSi3sCBOG9v28ogTEJnLaU3fNiPwZAoNYh5LZBbl0zjfPWwZDZD";

$apiurl = "https://graph.facebook.com/v2.6/me/messages?access_token=$page_access_token";

$graphapiurl = "https://graph.facebook.com/v2.6/";

if($_REQUEST['hub_verify_token'] == "chatbotTest1234"){exit($_REQUEST['hub_challenge']);}

$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($input, true)); fclose($fp);}

if(array_key_exists('entry', $input)){fn_process_fbdata($input);}


//#####################################
function fn_process_fbdata($input){
    foreach ($input['entry'] as $k=>$v) {

        foreach ($v['messaging'] as $k2=>$v2) {

           if(array_key_exists('message', $v2)){
             if(array_key_exists('text', $v2['message']) && !array_key_exists('app_id', $v2["message"])){
                 fn_command_processtext($v2['sender']['id'], $v2['message']['text']);
             }

             if(array_key_exists('attachments', $v2['message'])){
               foreach ($v2['message']['attachments'] as $k3=>$v3) {
                    if($v3['type'] == 'image' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processimage($v2['sender']['id'], $v3['payload']['url']);
                    }
                    if($v3['type'] == 'audio' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processaudio($v2['sender']['id'], $v3['payload']['url']);
                    }
                    if($v3['type'] == 'video' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processvideo($v2['sender']['id'], $v3['payload']['url']);
                    }
                    if($v3['type'] == 'file' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processfile($v2['sender']['id'], $v3['payload']['url']);
                    }
               }
             }
           }


        }
    }

}

//#####################################
function fn_command_processtext($senderid, $cmdtext)
{
fn_command_sentiments($senderid, $cmdtext);
if($cmdtext == "Hi"){
    send_text_message($senderid, "Hi there!");
}
elseif($cmdtext == "name?"){
    send_text_message($senderid, "My name is Chatbot!");
}
//default message
else{
    send_text_message($senderid, "I am still learning...");
  }

}
//#####################################
function fn_command_sentiments($senderid, $cmdtext)
{
include("lib/emoji.php");

$j = '';
$results = array();
preg_match_all('/./u', $cmdtext, $results);
$htmlarr = $results[0];
$emarr = array_keys($GLOBALS['emoji_maps']['names']);
$emojichars = array_intersect($emarr, $htmlarr);

if(count($emojichars) > 0)
{
    foreach($emojichars as $k=>$v){
        $j .= $k.': '.$GLOBALS['emoji_maps']['names'][$v]."\r\n";
    }
     send_text_message($senderid, $j);
}

}

//#####################################
function fn_command_processimage($senderid, $cmdtext)
{

if(strpos($cmdtext, ".png") !== false){
    send_text_message($senderid, "Its a PNG image");
}
elseif(strpos($cmdtext, ".jpg") !== false){
    send_text_message($senderid, "Its a JPG image");
}
elseif(strpos($cmdtext, ".gif") !== false){
    send_text_message($senderid, "Its a GIF image");
}
else{
    send_text_message($senderid, "Hmm.. nice image");
}
}
//#####################################
function fn_command_processaudio($senderid, $cmdtext)
{
send_text_message($senderid, "Hey! That's a nice Song!");
}
//#####################################
function fn_command_processvideo($senderid, $cmdtext)
{
send_text_message($senderid, "Hey! That's a nice Video!");
}
//#####################################
function fn_command_processfile($senderid, $cmdtext)
{
send_text_message($senderid, "Processing your Order details from this file.");
}
//#########################################

function send_text_message($senderid, $msg){
global $apiurl;

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->text = $msg;

//Encode the array into JSON.
$jsonDataEncoded = json_encode($sendmsg);

$ch = curl_init($apiurl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded); //Attach our encoded JSON string to the POST fields.
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$jresult = json_decode($result, true);


}
//#####################################

?>
