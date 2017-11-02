<?php
error_reporting(E_ERROR | E_PARSE);
require_once('lib/meekrodb.2.3.class.php');

DB::$user = 'root';
DB::$password = 'mysql';
DB::$dbName = 'ChatbotDb';
DB::$host = 'localhost';
DB::$encoding = 'utf8mb4_unicode_ci';

DB::$error_handler = 'sql_error_handler';

function sql_error_handler($params) {
  echo "Error: " . $params['error'] . "<br>\n";
  echo "Query: " . $params['query'] . "<br>\n";
  die; // don't want to keep going if a query broke
}


global $apiurl, $graphapiurl, $page_access_token, $profiledata;
$profiledata = array();

$page_access_token="EAABwV8PucqsBAP0JibtyPAwsUCp2sfBD5t4uNt2WiT4C6ZCIWsLGziFSW8MgJYIQltnBOO4dIJMNQpldntJ6vZCWWgVVY2MR7wYj1rB2vwsIQRa2VBIHdBZBtoISZBSi3sCBOG9v28ogTEJnLaU3fNiPwZAoNYh5LZBbl0zjfPWwZDZD";

$apiurl = "https://graph.facebook.com/v2.6/me/messages?access_token=$page_access_token";

$graphapiurl = "https://graph.facebook.com/v2.6/";

if($_REQUEST['hub_verify_token'] == "chatbotTest1234"){exit($_REQUEST['hub_challenge']);}
if($_REQUEST['chatbotsetup'] == "12345"){setup_bot(); exit();}
if($_REQUEST['chatbotsetupreset'] == "12345"){setup_bot_reset(); exit();}

$input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($input, true)); fclose($fp);}

if(array_key_exists('entry', $input)){fn_process_fbdata($input);}


//#####################################
function fn_process_fbdata($input){
    foreach ($input['entry'] as $k=>$v) {

        foreach ($v['messaging'] as $k2=>$v2) {
          if(array_key_exists('postback', $v2)){
                fn_command_processpostback($v2['sender']['id'], $v2['postback']['payload']);
          }

          if(array_key_exists('text', $v2['message']) && !array_key_exists('app_id', $v2["message"])){
                if(array_key_exists('quick_reply', $v2['message'])){
                 fn_command_processquickreply($v2['sender']['id'], $v2['message']['text'], $v2['message']['quick_reply']['payload']);
                }
                else{
                fn_command_processtext($v2['sender']['id'], $v2['message']['text']);
                }

             if(array_key_exists('attachments', $v2['message'])){
               foreach ($v2['message']['attachments'] as $k3=>$v3) {
                    if($v3['type'] == 'image' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processimage($v2['sender']['id'], $v3['payload']['url']);
                    }
                    if($v3['type'] == 'location' && !array_key_exists('app_id', $v2["message"])){
                        fn_command_processlocation($v2['sender']['id'], $v3);
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
  global $apiurl, $graphapiurl, $page_access_token, $profiledata;

  if(count($profiledata) == 0)
  {
      $profiledata = DB::queryFirstRow("select * from fbprofile WHERE fid = $senderid");

      if(is_null($profiledata))
      {
          $profiledata = send_curl_cmd('', $graphapiurl.$senderid.'?access_token='.$page_access_token);
          $profiledata['fid'] = $senderid;
          $profiledata['firstseen'] = time();
          DB::insert('fbprofile', $profiledata);
      }
  }

 //          $profiledata = send_curl_cmd('', $graphapiurl.$senderid.'?access_token='.$page_access_token);
 // foreach ($profiledata as $k => $v) {
 //   $j .= $k.": ".$v."\r\n";
 // }
fn_command_sentiments($senderid, $cmdtext);
if(stripos($cmdtext, "Hi hello hey") == true or 0 === stripos($cmdtext,'Hi')  or 0 === stripos($cmdtext, 'Hello'))
{
  send_text_message($senderid, "Hello ".$profiledata["first_name"]."! ");
}
elseif(stripos($cmdtext, "have other questions") == true or 0 === stripos($cmdtext, 'Other'))
{
  otherQuestionsTemp($senderid);
}
elseif(stripos($cmdtext, "add courses") == true or stripos($cmdtext, "add a course") == true or stripos($cmdtext, "drop a course") == true or 0 === strpos($cmdtext,'add or drop course')  or 0 === strpos($cmdtext, 'add or drop courses'))
{
  send_text_message($senderid, "You can add or drop a course using Memorial Self Service. If you are eligible to enrol in the course and course is disabled, you can register for the course using a Course Change Form available at department office. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "audit a course") == true or stripos($cmdtext, "audit course") == true or stripos($cmdtext, "audit courses") == true or 0 === strpos($cmdtext,'audit course'))
{
  send_text_message($senderid, "In order to audit any course, an individual must receive permission from the instructor in that course and the head of the academic unit in which the course is offered. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "enrolment verification") == true or stripos($cmdtext, "letter of enrolment") == true or stripos($cmdtext, "enrolment confirmation") == true or stripos($cmdtext, "enrolment") == true or 0 === strpos($cmdtext,'enrolment'))
{
  send_text_message($senderid, "You can request an enrolment verification letter via Memorial Self Service. Login using your student number and PIN, then click on the link to the Registration Menu, and follow the instructions labelled `Request Enrolment Verification`. Please type in `okay` to continue.");
}
elseif(stripos($cmdtext, "apply to graduate") == true or stripos($cmdtext, "apply for graduation") == true or stripos($cmdtext, "apply for graduate") == true or stripos($cmdtext, "graduation application") == true or 0 === strpos($cmdtext,'apply to graduate'))
{
  send_text_message($senderid, "You can submit an application from Memorial Self Service. Go to Student Menu, select `graduation` then `apply to graduate`. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "apply to convocation") == true or stripos($cmdtext, "apply for convocation") == true or stripos($cmdtext, "convocation") == true or stripos($cmdtext, "convocation help") == true or 0 === strpos($cmdtext,'apply for convocation'))
{
  send_text_message($senderid, "You can submit an application from Memorial Self Service. Go to Student Menu, select `graduation` then `Register to attend convocation`. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "graduation help") == true or stripos($cmdtext, "graduation") == true or stripos($cmdtext, "graduation related questions") == true or stripos($cmdtext, "graduation questions") == true or 0 === strpos($cmdtext,'graduation'))
{
  send_text_message($senderid, "Please send an email to `jjewison@mun.ca`. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "TA ") == true or stripos($cmdtext, "teaching assistant") == true or stripos($cmdtext, "teaching assistantship") == true or stripos($cmdtext, "mandatory teaching assistant") == true or 0 === strpos($cmdtext,'teaching assistant'))
{
  send_text_message($senderid, "You can apply for teaching assistant position in the beginning of the semester. It is not necessary to take a course for teaching assistant in each semester. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "pay my tuition") == true or stripos($cmdtext, "tuition") == true or stripos($cmdtext, "fee") == true or stripos($cmdtext, "fees") == true or stripos($cmdtext, "pay my fee") == true or 0 === strpos($cmdtext,'graduation'))
{
  send_text_message($senderid, "The Cashier's Office, AA-1023, is responsible for collection of all student fees and charges. For more information, visit the site https://www.mun.ca/finance/sections/cashiers_office/cashofficefaqs.php#Q1. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "trancript") == true or stripos($cmdtext, "my transcript") == true or stripos($cmdtext, "transcripts") == true or 0 === stripos($cmdtext,'transcript'))
{
  send_text_message($senderid, "You can request your transcript through Memorial Self Service. Go to `student menu`, click on `Academic Information Menu`, then you can submit a request for an `Official Transcript` or you can download an unofficial transcript. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "grades") == true or stripos($cmdtext, "my grades") == true or stripos($cmdtext, "grade") == true or 0 === stripos($cmdtext,'grade'))
{
  send_text_message($senderid, "You can view your term grades through Memorial Self Service. Go to `student menu`, click on `Academic Information Menu`, then you can select `view term grades`. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "tax form") == true or stripos($cmdtext, "tax forms") == true or 0 === stripos($cmdtext,'tax forms') or stripos($cmdtext, "tax slip") == true or stripos($cmdtext, "tax slips") == true)
{
  send_text_message($senderid, "You can download your tax forms through Memorial Self Service. Go to `employee services`, click on `tax forms`, then you can download the tax forms. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "payroll") == true or stripos($cmdtext, "salary") == true or 0 === stripos($cmdtext,'pay') or stripos($cmdtext, "funding") == true or stripos($cmdtext, "pay") == true)
{
  send_text_message($senderid, "You can view your pay through Memorial Self Service. Go to `employee services`, click on `pay information` to view your pay stubs. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "payroll deduction") == true or stripos($cmdtext, "payroll") == true or 0 === stripos($cmdtext,'payroll deduction'))
{
  send_text_message($senderid, "You can apply for payroll deduction through Memorial Self Service. Go to `employee services`, click on `payroll deduction` to submit your request. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "visa") == true or stripos($cmdtext, "immigration") == true or 0 === stripos($cmdtext,'visa'))
{
    visaCall($senderid);
}
elseif(stripos($cmdtext, "study permit") == true or stripos($cmdtext, "study visa") == true or stripos($cmdtext, "student visa") == true or stripos($cmdtext, "student permit") == true or 0 === stripos($cmdtext,'study permit'))
{
  send_text_message($senderid, "If you have received your `letter of acceptance` from MUN, then you are eligible to apply for study permit from cic website. \r\n You can apply for study permit extension before 2-3 months of your study permit expiry, you have check your eligibility from cic website and gather the required documents to apply. \r\n If your study permit has expired and you have submitted the request for extension then you should inform `International Students office`. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "coop permit") == true or stripos($cmdtext, "coop visa") == true or stripos($cmdtext, "co-op visa") == true or stripos($cmdtext, "co-op permit") == true or 0 === stripos($cmdtext,'coop permit'))
{
  send_text_message($senderid, "If you have completed your core courses then you can request a letter from coop office. Then you can apply for Co-op permit from cic website. Please type in `okay` to continue");
}
elseif(stripos($cmdtext, "work permit") == true or stripos($cmdtext, "work visa") == true or stripos($cmdtext, "open work permit") == true or 0 === stripos($cmdtext,'work permit'))
{
  send_text_message($senderid, "If you have received `letter of completion` from the school of gradute studies, then you can apply for post graduate work permit from cic website. Please type in `okay` to continue");
}
elseif($cmdtext == "send button template"){
    sendtemplate_btn($senderid);
}
elseif($cmdtext == "send generic template"){
    sendtemplate_generic($senderid);
}
elseif(strpos($cmdtext, "okay") == true or $cmdtext == "Okay" or $cmdtext == "okay" or strpos($cmdtext, "ok") == true or 0 === strpos($cmdtext,'ok') or 0 === strpos($cmdtext,'Ok')) {
    moreHelpTemp($senderid);
}
elseif(strpos($cmdtext, "more help") == true or $cmdtext == "more help" or strpos($cmdtext, "help") == true or 0 === strpos($cmdtext,'help') or 0 === strpos($cmdtext,'Help')) {
    sendtemplate_carousel($senderid);
}
elseif($cmdtext == "send quickreplytext"){
    sendtemplate_quickreplytext($senderid);
}
elseif($cmdtext == "send quickreplyimage"){
    sendtemplate_quickreplyimage($senderid);
}
elseif($cmdtext == "send quickreplytemplate"){
    sendtemplate_quickreplytemplate($senderid);
}
elseif($cmdtext == "send image"){
    sendfile_tofb($senderid, "image", "https://aa5bd365.ngrok.io/files/sampleimage.gif");
}
elseif($cmdtext == "send audio"){
    sendfile_tofb($senderid, "audio", "https://aa5bd365.ngrok.io/files/sampleaudio.mp3");
}
elseif($cmdtext == "send video"){
    sendfile_tofb($senderid, "video", "http://www.sample-videos.com/video/mp4/720/big_buck_bunny_720p_1mb.mp4");
}
elseif($cmdtext == "send receipt"){
    sendfile_tofb($senderid, "file", "https://aa5bd365.ngrok.io/files/payment-receipt.pdf");
}
elseif($cmdtext == "name?"){
    send_text_message($senderid, "My name is Chatbot!");
}
//default message
else{
    send_text_message($senderid, "Please type `help` for assistance.");
  }

}
//#####################################
function send_curl_cmd($data, $url){

//Encode the array into JSON.
if($data != ""){$jsonDataEncoded = json_encode($data);}

$ch = curl_init($url);
if($data != ""){curl_setopt($ch, CURLOPT_POST, 1);curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);} //Attach our encoded JSON string to the POST fields.
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$jresult = json_decode($result, true);


return $jresult;
}
//######################################
function sendtemplate_generic($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$buttons[] = array("type" => "postback", "title"=> "Buy Now", "payload" => "Bot_Order_32");
$buttons[] = array("type" => "postback", "title"=> "Save for Later", "payload" => "Bot_Order_Save_32");
$buttons[] = array("type" => "phone_number", "title"=> "Contact", "payload" => "+18008291040");

$elements[] = array("title" => "Awesome Product #1", "subtitle"=> "It has these great qualities, would be useful!",
                    "image_url" => "https://aa5bd365.ngrok.io/files/i1.jpg", "item_url" => "http://google.com/", 'buttons' => $buttons);

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'generic';
$sendmsg->message->attachment->payload->elements = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function sendtemplate_quickreplytext($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$reply[] = array("content_type" => "text", "title"=> "Pepperoni", "payload" => "Bot_Order_Pepperoni");
$reply[] = array("content_type" => "text", "title"=> "Mushroom", "payload" => "Bot_Order_Mushroom");
$reply[] = array("content_type" => "text", "title"=> "Onion", "payload" => "Bot_Order_Onion");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->text = 'Pick your Pizza Topping below!';
$sendmsg->message->quick_replies = $reply;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

############################################################
function quickReplyRegister($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$reply[] = array("content_type" => "text", "title"=> "Okay", "payload" => "Okay");
$reply[] = array("content_type" => "text", "title"=> "Thank you", "payload" => "ThankYou");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->text = 'Please reply!';
$sendmsg->message->quick_replies = $reply;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

//######################################
function sendtemplate_quickreplyimage($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;
send_text_message($senderid, "Please select a topping for your pizza");

$reply[] = array("content_type" => "text", "title"=> "Pepperoni", "payload" => "Bot_Order_Pepperoni");
$reply[] = array("content_type" => "text", "title"=> "Mushroom", "payload" => "Bot_Order_Mushroom");
$reply[] = array("content_type" => "text", "title"=> "Onion", "payload" => "Bot_Order_Onion");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'image';
$sendmsg->message->attachment->payload->url = 'https://aa5bd365.ngrok.io/files/pizza1.jpg';
$sendmsg->message->quick_replies = $reply;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//#####################################
function fn_command_processquickreply($senderid, $replytext, $cmdtext)
{
global $apiurl, $graphapiurl, $page_access_token, $profiledata;

if(count($profiledata) == 0)
{
    $profiledata = DB::queryFirstRow("select * from fbprofile WHERE fid = $senderid");

    if(is_null($profiledata))
    {
        $profiledata = send_curl_cmd('', $graphapiurl.$senderid.'?access_token='.$page_access_token);
        $profiledata['fid'] = $senderid;
        $profiledata['firstseen'] = time();
        DB::insert('fbprofile', $profiledata);
    }
}


// send_text_message($senderid, "Ok ".$profiledata["first_name"]."! \r\n".$replytext.': '.$cmdtext);
moreHelpTemp($senderid);


}
//######################################
function sendtemplate_quickreplytemplate($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

send_text_message($senderid, "Please select an option to proceed:");

$reply[] = array("content_type" => "text", "title"=> "Cancel", "payload" => "Bot_Order_Cancel");
$reply[] = array("content_type" => "text", "title"=> "StartOver", "payload" => "Bot_Order_StartOver");


$buttons[] = array("type" => "postback", "title"=> "Buy Now", "payload" => "Bot_Order_32");
$buttons[] = array("type" => "postback", "title"=> "Save for Later", "payload" => "Bot_Order_Save_32");
$buttons[] = array("type" => "phone_number", "title"=> "Contact Seller", "payload" => "+18008291040");

$elements[] = array("title" => "Awesome Product #1",
                    "image_url" => "https://aa5bd365.ngrok.io/files/i1.jpg",  'buttons' => $buttons);


$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'generic';
$sendmsg->message->attachment->payload->elements = $elements;
$sendmsg->message->quick_replies = $reply;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function sendtemplate_carousel($senderid)
{
  global $apiurl, $graphapiurl, $page_access_token;

  $buttons[] = array("type" => "phone_number", "title" => "Call", "payload" => "+18008291040");
  $buttons[] = array("type" => "web_url", "title" => "Memorial University", "url" => "https://www.mun.ca");
  $elements[] = array("title" => "Antonina Kolokolova", "subtitle" => "Associate Professor at the Department of Computer Science.",
                      "image_url" => "http://www.cs.mun.ca/~kol/images/kol-Coffeeshop-colette-cropped.jpg", "item_url" => "http://www.cs.mun.ca/~kol/", 'buttons' => $buttons);
  $elements[] = array("title" => "Administration Staff", "subtitle"=> "List of Administration Staff. You can reach out to anyone from the list.",
                      "image_url" => "https://www.mun.ca/marcomm/brand/standards/logos/MUN_Logo_RGB.png", "item_url" => "http://www.mun.ca/regoff/contact/staff.php#adminstaff", 'buttons' => $buttons);
  $elements[] = array("title" => "School of Grad Studies Staff", "subtitle"=> "List of SGS Staff. You can reach out to anyone from the list.",
                      "image_url" => "https://www.google.ca/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0ahUKEwiSy-agiZzXAhUK0IMKHQWJAA8QjRwIBw&url=http%3A%2F%2Fwww.mun.ca%2Fbiophysics%2Fmubs%2Fbps_symposium%2F&psig=AOvVaw1yZ9MFqcl02Uk9p05y15mC&ust=1509580899901713", "item_url" => "https://www.mun.ca/sgs/contacts/sgscontacts.php", 'buttons' => $buttons);

  $sendmsg = new stdClass();
  $sendmsg->recipient->id = $senderid;
  $sendmsg->message->attachment->type = 'template';
  $sendmsg->message->attachment->payload->template_type = 'generic';
  $sendmsg->message->attachment->payload->elements = $elements;

  $res = send_curl_data_tofb($sendmsg);

  $fp = fopen("logfbdata.txt","a");
  if( $fp == false ){ echo "file creation failed";}
  else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function sendfile_tofb($senderid, $filetype, $fileurl)
{
global $apiurl, $graphapiurl, $page_access_token;
$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = $filetype;
$sendmsg->message->attachment->payload->url = $fileurl;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function send_curl_data_tofb($sendmsg, $fburl, $dowhat = 1)
{
global $apiurl;
if($fburl == "") {$fburl = $apiurl;}
$jsonDataEncoded = json_encode($sendmsg);

$ch = curl_init($fburl);
if($dowhat == 2)
{
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
}
else
{
curl_setopt($ch, CURLOPT_POST, 1);
}

curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded); //Attach our encoded JSON string to the POST fields.
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$jresult = json_decode($result, true);
return $jresult;
}
//######################################
function setup_bot()
{
global $apiurl, $graphapiurl, $page_access_token;
$sendmsg = new stdClass();
$sendmsg->setting_type = "greeting";
$sendmsg->greeting->text = "Welcome to our Awesome Page. Our helpful chatbot will guide you through the process.";
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token);

    print_r($res);

$sendmsg = new stdClass();
$sendmsg->setting_type = "call_to_actions";
$sendmsg->thread_state = "new_thread";
$sendmsg->call_to_actions[] = array("payload" => "Get Started!");
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token);

print_r($res);

$sendmsg = new stdClass();
$sendmsg->setting_type = "call_to_actions";
$sendmsg->thread_state = "existing_thread";
$elements[] = array("type" => "postback", "title"=> "Help", "payload" => "Bot_Help");
$elements[] = array("type" => "postback", "title"=> "Show Cart", "payload" => "Bot_Cart");
$elements[] = array("type" => "postback", "title"=> "My Orders", "payload" => "Bot_Orders");
$elements[] = array("type" => "web_url", "title"=> "Visit Website", "url" => "http://google.com/");
$sendmsg->call_to_actions = $elements;
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token);
$jsonDataEncoded = json_encode($sendmsg);



print_r($res);

}
//#####################################
function fn_command_processpostback($senderid, $cmdtext)
{
global $apiurl, $graphapiurl, $page_access_token, $profiledata;

if(count($profiledata) == 0)
{
    $profiledata = DB::queryFirstRow("select * from fbprofile WHERE fid = $senderid");

    if(is_null($profiledata))
    {
        $profiledata = send_curl_cmd('', $graphapiurl.$senderid.'?access_token='.$page_access_token);
        $profiledata['fid'] = $senderid;
        $profiledata['firstseen'] = time();
        DB::insert('fbprofile', $profiledata);
    }
}

if($cmdtext == "Get Started!"){
    send_text_message($senderid, "Hi ".$profiledata["first_name"]."!, How can i help you today?");
}
elseif($cmdtext == "registration"){
registrationCall($senderid);
}
elseif($cmdtext == "graduation"){
graduationCall($senderid);
}
elseif($cmdtext == "applyGraduate"){
    send_text_message($senderid, "You can submit an application from Memorial Self Service. Go to Student Menu, select `graduation` then `apply to graduate`. Please type in `okay` to continue");
}
elseif($cmdtext == "convocation"){
  send_text_message($senderid, "You can submit an application from Memorial Self Service. Go to Student Menu, select `graduation` then `Register to attend convocation`. Please type in `okay` to continue");
}
elseif($cmdtext == "moreQuestions"){
    send_text_message($senderid, "Please send an email to `jjewison@mun.ca`. Please type in `okay` to continue");
}
elseif($cmdtext == "visa"){
visaCall($senderid);
}
elseif($cmdtext == "studypermit"){
    send_text_message($senderid, "If you have received your `letter of acceptance` from MUN, then you are eligible to apply for study permit from cic website. \r\n You can apply for study permit extension before 2-3 months of your study permit expiry, you have check your eligibility from cic website and gather the required documents to apply. \r\n If your study permit has expired and you have submitted the request for extension then you should inform `International Students office`. Please type in `okay` to continue");
}
elseif($cmdtext == "cooppermit"){
  send_text_message($senderid, "If you have completed your core courses then you can request a letter from coop office. Then you can apply for Co-op permit from cic website. Please type in `okay` to continue");
}
elseif($cmdtext == "workpermit"){
  send_text_message($senderid, "If you have received `letter of completion` from the school of gradute studies, then you can apply for post graduate work permit from cic website. Please type in `okay` to continue");
}
elseif($cmdtext == "employee"){
empCall($senderid);
}
elseif($cmdtext == "grades"){
gradesCall($senderid);
}
elseif($cmdtext == "grades"){
  send_text_message($senderid, "You can view your term grades through Memorial Self Service. Go to `student menu`, click on `Academic Information Menu`, then you can select `view term grades`. Please type in `okay` to continue");
}
elseif($cmdtext == "transcript"){
  send_text_message($senderid, "You can request your transcript through Memorial Self Service. Go to `student menu`, click on `Academic Information Menu`, then you can submit a request for an `Official Transcript` or you can download an unofficial transcript. Please type in `okay` to continue");
}
elseif($cmdtext == "taxforms"){
  send_text_message($senderid, "You can download your tax forms through Memorial Self Service. Go to `employee services`, click on `tax forms`, then you can download the tax forms. Please type in `okay` to continue");
}
elseif($cmdtext == "payinfo"){
  send_text_message($senderid, "You can view your pay through Memorial Self Service. Go to `employee services`, click on `pay information` to view your pay stubs. Please type in `okay` to continue");
}
elseif($cmdtext == "payroll"){
  send_text_message($senderid, "You can apply for payroll deduction through Memorial Self Service. Go to `employee services`, click on `payroll deduction` to submit your request. Please type in `okay` to continue");
}
elseif($cmdtext == "AddDropCourse"){
  send_text_message($senderid, "You can add or drop a course using Memorial Self Service. If you are eligible to enrol in the course and course is disabled, you can register for the course using a Course Change Form available at department office. Please type in `okay` to continue");
}
elseif($cmdtext == "auditCourse"){
  send_text_message($senderid, "In order to audit any course, an individual must receive permission from the instructor in that course and the head of the academic unit in which the course is offered. Please type in `okay` to continue");
}
elseif($cmdtext == "enrolment"){
   send_text_message($senderid, "You can request an enrolment verification letter via Memorial Self Service. Login using your student number and PIN, then click on the link to the Registration Menu, and follow the instructions labelled `Request Enrolment Verification`. Please type in `okay` to continue.");
}
elseif($cmdtext == "Yes!"){
    sendtemplate_btn2($senderid);
}
elseif($cmdtext == "No!"){
    send_text_message($senderid, "It was nice chatting with you, Bye!");
}
else{
  moreHelpTemp($senderid);
}

}
//######################################
function moreHelpTemp($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Yes", "payload" => "Yes!");
$elements[] = array("type" => "postback", "title"=> "No", "payload" => "No!");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'Do you need anything else?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

//######################################
function setup_bot_reset()
{
global $apiurl, $graphapiurl, $page_access_token;
$sendmsg = new stdClass();
$sendmsg->setting_type = "greeting";
$sendmsg->greeting->text = " ";
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token, 1);

print_r($res);


$sendmsg = new stdClass();
$sendmsg->setting_type = "call_to_actions";
$sendmsg->thread_state = "new_thread";
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token, 2);

print_r($res);


$sendmsg = new stdClass();
$sendmsg->setting_type = "call_to_actions";
$sendmsg->thread_state = "existing_thread";
$res = send_curl_data_tofb($sendmsg, $graphapiurl.'/me/thread_settings?access_token='.$page_access_token, 2);
$jsonDataEncoded = json_encode($sendmsg);

print_r($res);

}

//######################################
function otherQuestionsTemp($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Employee Services", "payload" => "employee");
$elements[] = array("type" => "postback", "title"=> "Grades", "payload" => "grades");
$elements[] = array("type" => "postback", "title"=> "Tax Forms", "payload" => "taxforms");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'How can I help you?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function sendtemplate_btn($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Registration", "payload" => "registration");
$elements[] = array("type" => "postback", "title"=> "Graduation", "payload" => "graduation");
$elements[] = array("type" => "postback", "title"=> "Visa", "payload" => "visa");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'How can I help you? (type `other` for more questions)';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function sendtemplate_btn2($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Registration", "payload" => "registration");
$elements[] = array("type" => "postback", "title"=> "Graduation", "payload" => "graduation");
$elements[] = array("type" => "postback", "title"=> "Visa", "payload" => "visa");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'How can I help you?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
//######################################
function registrationCall($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Add or Drop Courses", "payload" => "AddDropCourse");
$elements[] = array("type" => "postback", "title"=> "Audit a Course", "payload" => "auditCourse");
$elements[] = array("type" => "postback", "title"=> "Enrolment Letter", "payload" => "enrolment");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'What registration related help can I offer you today?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
##################################################
function graduationCall($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Apply to graduate", "payload" => "applyGraduate");
$elements[] = array("type" => "postback", "title"=> "Convocation", "payload" => "convocation");
$elements[] = array("type" => "postback", "title"=> "More questions", "payload" => "moreQuestions");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'What graduation related questions do you have?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}
##################################################
function gradesCall($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Request Transcript", "payload" => "transcript");
$elements[] = array("type" => "postback", "title"=> "View term grades", "payload" => "termgrades");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'You can view your grades or request your trancript?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

##################################################
function empCall($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Pay Info", "payload" => "payinfo");
// $elements[] = array("type" => "postback", "title"=> "TA Info", "payload" => "tainfo");
$elements[] = array("type" => "postback", "title"=> "Payroll Deduction", "payload" => "payroll");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'How can I help you with employee services?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

##################################################
function visaCall($senderid)
{
global $apiurl, $graphapiurl, $page_access_token;

$elements[] = array("type" => "postback", "title"=> "Study Permit", "payload" => "studypermit");
$elements[] = array("type" => "postback", "title"=> "Co-op Work Permit", "payload" => "cooppermit");
$elements[] = array("type" => "postback", "title"=> "Open Work Permit", "payload" => "workpermit");

$sendmsg = new stdClass();
$sendmsg->recipient->id = $senderid;
$sendmsg->message->attachment->type = 'template';
$sendmsg->message->attachment->payload->template_type = 'button';
$sendmsg->message->attachment->payload->text = 'What visa related help can I offer you today?';
$sendmsg->message->attachment->payload->buttons = $elements;

$res = send_curl_data_tofb($sendmsg);

$fp = fopen("logfbdata.txt","a");
if( $fp == false ){ echo "file creation failed";}
else{fwrite($fp,print_r($res, true)); fclose($fp);}
}

//#####################################
function fn_command_processlocation($senderid, $data)
{

$j  = $data['title']."\r\n";
$j .= "Latitude: ".$data['payload']["coordinates"]["lat"]."\r\n";
$j .= "Longitude: ".$data['payload']["coordinates"]["long"]."\r\n";

send_text_message($senderid, $j);
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
