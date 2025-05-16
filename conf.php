<?php


ini_set( 'display_errors', 'On' );   
error_reporting( E_ALL | E_STRICT ); 

$err= false;
$response = 'none';

//function to perform
$bsearch_msg=true; //search for email
//example queries to use in gmail search
    $q = 'is:sent ExCel after:2025/01/24 before: 2026/01/24';
    $q = 'in:inbox  after:2025/4/3 before:2025/5/6'; 

$bsend=false; //send email, to be done 


//files and directories
$components = __DIR__.'/components';
$logs = __DIR__.'/logs';
$authcodes = __DIR__.'/authcodes';

$tokenFnm = $authcodes.'/token.json';
$oauthCredFnm = $authcodes.'/oauthCred.json';
$last_post_oauth_stateFnm = $authcodes.'/last_post_oauth_state.json';
$request_logFnm = $logs.'/request_log';

//authorisation variables
$bnew_code = false; //get new $access_token and $refresh_token via POST, because it does nto exists, or expired. Saved in $tokenFnm
$bmatched_code = false;
$btoken = false; //becomes true if json encoded token is accepted by google-OAuth, or GET requests returns new token
$brefresh = false;
 
$berr_code = false;
$berr_state = true; //assumes forgery attack, unless proven opposite. In requests user sends own token and checks if it arrived

$authuserN = 6; ////this is the number in google url, if there are many google accounts, it is possible to check in gmail -- https://mail.google.com/mail/u/6

//OAuth 2.0 credentials from the Google API Console.  
$client_id = '157970476482-ll36d4qvpftkm7u01pfehcpbl8vgd8rh.apps.googleusercontent.com';
$client_secret = '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'; 
$api_id = 'AIzaSyAJ4Vx55n2GLndBVCivnEvmlSgOISkRvcA'; //key

$redirect_uri = 'https://uveik.com/connect_and_search'; 
$referer = $redirect_uri;
$csrf_state =  md5( uniqid(mt_rand(), true) );
//  $_SESSION['state'] = $state; //or write to some file
$now = time();
$userId = 'ginstat4@gmail.com';


if( $bsend ) {
    $scope = 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.compose https://www.googleapis.com/auth/gmail.send'; 
}
else if ( $bsearch_msg ) {
    $scope = 'https://www.googleapis.com/auth/gmail.readonly'; //array_merge('+',[scope1, scope1]) 
}




//debuggin variables
$pdbg_get=true;
$pdbg_post=true;
$pdbg_srch=true;
$pdbg_srch_msg=true;
$pdbg_new = true;



