<?php 

ini_set( 'display_errors', 'On' );   
error_reporting( E_ALL | E_STRICT ); 

include_once $components.'/helpF.php';


if( $pdbg_post) { echo '<br> POST=<pre>'; print_r($_POST); }
if( $pdbg_get) { echo '<br> GET=<pre>'; print_r($_GET); }

file_put_contents($request_logFnm, PHP_EOL.'###############POST,                     for date='.$now.PHP_EOL.json_encode($_POST).PHP_EOL, FILE_APPEND );
file_put_contents($request_logFnm, PHP_EOL.'###############GET,                     for date='.$now.PHP_EOL.json_encode($_GET).PHP_EOL, FILE_APPEND );

$lpos = file_get_contents($last_post_oauth_stateFnm);
$lposa = json_decode($lpos, true);
echo '<br> lposa=<pre>'; print_r($lposa);
//file_put_contents($request_logFnm, '           $last_post_oauth_state  for date '.$now.PHP_EOL.$lpos.PHP_EOL, FILE_APPEND );

if( empty($_POST) && empty($_GET) ) { 
    
    if( file_exists($tokenFnm) ) {
        
        $tkj = file_get_contents($tokenFnm);
        if( !empty($tkj) ) {
            
            $tkA = json_decode($tkj, true);
           if ($pdbg_get || $pdbg_post) { echo '<br> 164____tkA='; print_r($tkA); }
            
            if( !empty($tkA) ) {
                $access_token = array_key_exists($tkA['access_token']) ? $tkA['access_token'] : null; 
                $refresh_token = array_key_exists($tkA['refresh_token']) ? $tkA['refresh_token'] : null; 
                $token_type = array_key_exists($tkA['token_type']) ? $tkA['token_type'] : null; 
                $refresh_token_expires_in = array_key_exists($tkA['refresh_token_expires_in']) ? $tkA['refresh_token_expires_in'] : null; 
                $expires = array_key_exists($tkA['expires']) ? $tkA['expires'] : null;
                
                $dtexp = date('Y-m-d h:s', $xpires);
                $bm=file_put_contents( $request_logFnm, json_encode(PHP_EOL.'  line140  ##date='.$now.' empty post & get expiration date = '.$dtexp ), FILE_APPEND  );
                
                //if ($pdbg_get || $pdbg_post) { echo '<br> expiration date = '.$dtexp.'   bm='.$bm; }
                
                if( !is_null($expires) && ($expires < $now) ) {
                    $btoken = true;
                    $bnew_code= false; 
                    $bmatched_code = false; 
                    //$bmatched_autho_code = false;
                    $brefresh = false;
                    $berr_state = false;
                    $bm=file_put_contents( $request_logFnm, json_encode(PHP_EOL.'  line140  ##date='.$now.' empty post & get trial to use token which is not expired' ), FILE_APPEND  );
                    
                    if ($pdbg_get || $pdbg_post) {  echo '<br>  line240  ##date='.$now.' empty post & get,  trial to use token which is not expired,   bm='.$bm; }
                } // if( !is_null($expires) && ($expires < $now
            } // if( !empty($tkA) 
            
        } // if( !empty($tkj)
    } // if( file_exists($tokenFnm)
    
    if( !($brefresh ||  $btoken ) ) {
        $btoken = false;
        $bnew_code= true; 
        $bmatched_code = false; 
        //$bmatched_autho_code = false;
        $brefresh = false;
        $berr_state = false;
    }


} // if(  empty($_POST) && empty($_GET)

else if( !empty($_GET)  ) {
    
     if( array_key_exists('code', $_GET) ) {
        $code =  $_GET['code'];
        $bmatched_code = true; 
        $bmatched_autho_code = false;
        $btoken = false; 
        $brefresh = false;
        $bm = file_put_contents($oauthCredFnm, json_encode($_GET) );

        file_put_contents( $request_logFnm, json_encode(PHP_EOL.'  line150  ##date='.$now.'  get='.json_encode($_GET) ), FILE_APPEND  );
         //state is csrf token, anti cross forgery check 
         //you can also cehck the dates betweej requests 
        if( (1===1) && array_key_exists('state', $_GET) && array_key_exists('state', $lposa) && ($_GET['state']===$lposa['state']) ) {
            $berr_state = false;
        }
    } // if( array_key_exists('code', $_GET) ) {
} // if( !empty($_GET)

else if( !empty($_POST) ) {
    //if( array_key_exists('authorization_code', $_POST) ) {
    
    $access_token = array_key_exists($_POST['access_token']) ? $_POST['access_token'] : null; 
    $refresh_token = array_key_exists($_POST['refresh_token']) ? $_POST['refresh_token'] : null; 
    $token_type = array_key_exists($_POST['token_type']) ? $_POST['token_type'] : null; 
    $refresh_token_expires_in = array_key_exists($_POST['refresh_token_expires_in']) ? $_POST['refresh_token_expires_in'] : null; 
    $_POST['expires'] = $now  + $refresh_token_expires_in - 100;
    $bm=file_put_contents($tokenFnm, $_POST);

    //$authorization_code = $_POST['authorization_code'];
    //You can only make ONE CALL when you get the initial token (used for an access token call). If that fails, you must get another token from the previous leg.
    //$bmatched_autho_code = true;
    $bmatched_code = true;
    $btoken = true;
    $brefresh = false;
    $bnew_code  = false;
    $bm = file_put_contents($oauthCodeCredFnm, json_encode($_POST) );
    file_put_contents( $request_logFnm, json_encode(PHP_EOL.'  line150  ##date='.$now.'  get='.json_encode($_GET) ), FILE_APPEND  );
     //state is csrf token, anti cross forgery check 
     //you can also cehck the dates betweej requests 
    if( (1===1) && array_key_exists('state', $_POST) && array_key_exists('state', $lposa) && ($_GET['state']!=$lposa['state']) ) {
        $berr_state = true;
    }
    else {
         $berr_state = false;
        echo '<br> ERROR, states does not match in authorisation '; 
    }
} // if( !empty($_POST

$bm=file_put_contents( $request_logFnm, json_encode(PHP_EOL.'  line160  ##date='.$now.'    berr_code='.$berr_code.'   bnew_code='.$bnew_code.'  $matched_code='. $bmatched_code.'  btoken='.$btoken.'  brefresh='.$brefresh ), FILE_APPEND  ); 

if ($pdbg_get || $pdbg_post || $pdbg_new ) { echo '<br>  line160  ##date='.$now.'    berr_code='.$berr_code.'   bnew_code='.$bnew_code.'  $matched_code='. $bmatched_code.'  btoken='.$btoken.'  brefresh='.$brefresh.'  bm='.$bm; } 


if( $bnew_code ) { 
    
    $lposa = [ 'state'=>$csrf_state, 'start_time'=>$now, 'getp'=>'authcode', 'scope'=>$scope ]; 
    $lposj = json_encode($lposa);
    $bm = file_put_contents($last_post_oauth_stateFnm, $lposj);
    //if ( $pdbg_new ) { echo '<br> bm='.$bm.'   $lposj='. $lposj; }
    
    $fields = [
        "scope" => $scope, 
        "response_type" => 'code', 
        //'response_type'=> 'token',
        //"grant_type" => "authorization_code", 
        //"code"=>$code, 
        "access_type" => 'offline', 
        "state" => $csrf_state,
        "redirect_uri" => $redirect_uri,  //Callback URL: This is the redirect URL you specified in your Google Cloud Console. //  redirect_uri=https://YOUR_APP/callback&
        "client_id" => $client_id,
        //"client_secret" => $client_secret,
    ]; // fields 
    //$pf = http_build_query( $fields, '', '&amp;' );//automatically urlencodes, maybe this causes an error in curl, which 2nd time urlencodes
    $pf = http_build_query_noUrlEnc([ 'arr'=>$fields, 'ga' => [ '0'=>['g'=>'&', 'gcolval'=>'='] ] ]); 
    
    if( $pdbg_new  ) { 
        echo '<br> fields='; print_r($fields); 
        echo '<br> pf='.$pf;
    }
    file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    pf='.$pf, FILE_APPEND ); 
    
    $curl = curl_init("https://accounts.google.com/o/oauth2/v2/auth?authuser=".$authuserN); //this is the number in google url, if there are many google accounts, it is possible to check in gmail -- https://mail.google.com/mail/u/6
    curl_setopt_array( $curl, [
        //CURLOPT_URL => "https://accounts.google.com/o/oauth2/v2/auth?authuser=1" //the same as in curl_init
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        //curl authomatically encodes postfields 
        CURLOPT_POSTFIELDS => $pf,
        CURLOPT_HTTPHEADER => [
            "content-type: application/x-www-form-urlencoded",
            //'Cache-Control: no-cache',
        ],
    ]);
    
    $response = curl_exec($curl);
    if( $pdbg_new  ) { echo '<br> response = '; print_r($response); }
    
    file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    auth-response='.html_entity_decode($response), FILE_APPEND  ); 
    $err = curl_error($curl);
   curl_close($curl);

} //if( $bnew_code 

// Exchange Authorization code for an access token and a refresh token. 
else if( ($bmatched_code && !$berr_state) || $brefresh ) {
    
    $lposa = [ 'state'=>$csrf_state, 'start_time'=>$now, 'getp'=>'accesstoken', 'scope'=>$scope ]; 
    $lposj = json_encode($lposa);
    $bm=file_put_contents($last_post_oauth_stateFnm, $lposj);
    
    //HTTP request
        //Upload URI, for media upload requests:
        //POST https://gmail.googleapis.com/upload/gmail/v1/users/{userId}/messages/send
        
        //Metadata URI, for metadata-only requests:
        //POST https://gmail.googleapis.com/gmail/v1/users/{userId}/messages/send
    
        //Upload URI, for media upload requests:
        //POST https://gmail.googleapis.com/upload/gmail/v1/users/{userId}/drafts
        
        //Metadata URI, for metadata-only requests:
        //POST https://gmail.googleapis.com/gmail/v1/users/{userId}/drafts


    $fields = [
        //exchange code to the access_token
        //"scope=" => $scope, 
        //"response_type" => 'code', 
        "grant_type" => "authorization_code", 
        "code"=>$code, 
        // does not work: "access_type" => 'offline', 
        "state=" => $csrf_state,
        "redirect_uri" => $redirect_uri, //Callback URL: This is the redirect URL you specified in your Google Cloud Console. //  redirect_uri=https://YOUR_APP/callback&
        "client_id" => $client_id,
        "client_secret" => $client_secret,
        //Callback URL: This is the redirect URL you specified in your Google Cloud Console. 
    ]; // fields 
    
    if($brefresh) {
        //exchange refresh_token to new access_token
        $fields["grant_type"] = "refresh_token";
        $fields['refresh_token'] = $refresh_token;
        unset($fields['code']);
    }
    //$pf = http_build_query( $fields, '', '&amp;' );//automatically urlencodes , maybe this causes an error in curl, which 2nd time urlencodes
    $pf = http_build_query_noUrlEnc([ 'arr'=>$fields, 'ga' => [ '0'=>['g'=>'&', 'gcolval'=>'='] ] ]); 
    echo '<br> qs='.$pf; 
    $bm=file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    pf='.$pf, FILE_APPEND ); 
    
    //$curl = curl_init("https://oauth2.googleapis.com/token");
    $curl = curl_init("https://www.googleapis.com/oauth2/v4/token");
    curl_setopt_array( $curl, [
        //CURLOPT_URL => "https://accounts.google.com/o/oauth2/v2/auth?authuser=1" //the same as in curl_init
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, 
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_VERBOSE => true,
        //curl authomatically encodes postfields 
        CURLOPT_POSTFIELDS => $pf, //"scope=".$scope."&response_type=code&access_type=offline&state=".$csrf_state."&redirect_uri=".$redirect_uri_auth.'&client_id='.$client_id,
        CURLOPT_HTTPHEADER => [ 
             //    "authorization: code ".$code,
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $responsej = curl_exec($curl);
    //echo '<br> line400___responsej =<pre> '; print_r($responsej);
    
    $bm=file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    post for authorisation code -response='.html_entity_decode($responsej), FILE_APPEND  ); 
    //echo '<br> line400___bm='.$bm;
    
    $response = json_decode($responsej, true);
    //echo '<br> line400___response = '; print_r($response);
    
    if( !empty($response) && is_array($response) ) {
        $access_token = array_key_exists('access_token',$response) ? $response['access_token'] : null; 
         echo '<br>  line4000  $access_token-'. $access_token;
        $refresh_token = array_key_exists('refresh_token', $response) ? $response['refresh_token'] : null; 
        $token_type = array_key_exists('token_type', $response) ? $response['token_type'] : null; 
        $refresh_token_expires_in = array_key_exists('refresh_token_expires_in', $response) ? $response['refresh_token_expires_in'] : null; 
        //expires_in  --- lazy to implement , for access_token
        //           $dtexp = date('Y-m-d h:s', $xpires);
        if( !is_null($access_token) ) { 
            $btoken = true; 
            $bnew_code= false; 
            $bmatched_code = false; 
            //$bmatched_autho_code = false;
            echo '<br>  line4000 in case !is_null access_token XX'; 
        }
        if(!is_null( $refresh_token_expires_in ) ) {
            $response['expires'] = $now  + $refresh_token_expires_in - 100;
            $bm  = file_put_contents($tokenFnm, $response);
            echo '<br> file_put_contents (tokenFnm... line400___bm='.$bm;
        }
        
    } // !empty($response

    $err = curl_error($curl);
   //curl_close($curl);    
}

$bm=file_put_contents( $request_logFnm, PHP_EOL.'  line400  ##date='.$now.'  btoken='.$btoken, FILE_APPEND  ); 



