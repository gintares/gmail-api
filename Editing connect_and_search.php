<?php 

ini_set( 'display_errors', 'On' );   
error_reporting( E_ALL | E_STRICT ); 


include_once __DIR__.'/conf.php';
include_once __DIR__.'/conn.php';

if($pdbg_srch_msg) {  echo '<br> 10_____bsearch_msg='.$bsearch_msg.' &&  $btoken='.$btoken.' && $berr_state='.$berr_state; }

if ( $bsearch_msg &&  $btoken && !$berr_state ) {

        $nextPageToken = -1;
        $resultSizeEstimate = -1;
        $maxResults = 100; // ,maximum 500
        $cnt_search_max = 200; //
        $cnt_search = -1; //permits not more than 1 wrong request  
    
        if($pdbg_srch_msg) { echo '<br> 760_____STARTING SEARCH '; }
        while( !is_null($nextPageToken) && ( $cnt_search <= $resultSizeEstimate ) && ($cnt_search < $cnt_search_max) ) {
                
            $fields = [
                'client_id'=>$client_id,
                'key'=>$api_id,
                'client_secret'=> $client_secret , 
                "scope" => 'https://www.googleapis.com/auth/gmail.readonly',
                "expires_in" => 3000,
                //"verified_email" => true,
                //"access_type" => "offline",
                //'q' => $q,
                'state' => $csrf_state,
                'redirect_uri' => $redirect_uri,  
                'maxResults'=>$maxResults, //500;
            ]; 
            
            $headers = [ 
                    'authorization: '.$token_type.' '.$access_token,
                    "content-type: application/x-www-form-urlencoded",
                    'Cache-Control: no-cache',
            ];
            if(!is_null($nextPageToken) && ($nextPageToken>0) ) {
                $headers['nextPageToken'] = $nextPageToken;
            }
           
            if($pdbg_srch) {  
                echo '<br> fields ='; print_r($fields); 
            }
        //$qs= http_build_query($urla, '', '&amp;');//automatically urlencodes, maybe this causes an error in curl, which 2nd time urlencodes
            $pf = http_build_query_noUrlEnc([ 'arr'=>$fields, 'ga' => [ '0'=>['g'=>'&', 'gcolval'=>'='] ] ]); 
                
            $qs = 'q='.urlencode($q).'&'.$pf;
            if($pdbg_srch_msg) {  echo '<br> 477____qs='.$qs; }
        
            $curl = curl_init( 'https://www.googleapis.com/gmail/v1/users/'.$userId.'/messages?'.$qs ); 
            //$curl = curl_init('https://www.googleapis.com/gmail/v1/users/'.$userId.'/messages');
            curl_setopt_array( $curl, [
                //CURLOPT_URL => 'https://www.googleapis.com/gmail/v1/users/'.$userId.'/messages?'.$qs // "https://myapi.com/api",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                //CURLOPT_POSTFIELDS => $pf,
                CURLOPT_REFERER=>$referer,
                CURLOPT_HTTPHEADER =>$headers,
            ]);
        
            $responsej = curl_exec($curl);
            if($pdbg_srch_msg) { 
                echo '<br> 485___ query response =<pre> '; print_r($responsej);
            }
        
            file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    get-response='.html_entity_decode($responsej), FILE_APPEND  ); 
            $err = curl_error($curl);
            if($pdbg_srch_msg) {  echo '<br>  485___err='; print_r($err); }

            $rsp = json_decode($responsej,true);
            
            if(empty($rsp)) {
                $nextPageToken = null;
            }
            if( !empty($rsp) ) {
                
                $nextPageToken = isset($rsp['nextPageToken']) ? $rsp['nextPageToken'] : null;
                $resultSizeEstimate = isset($rsp['resultSizeEstimate']) ? $rsp['resultSizeEstimate'] : 0;
                
                if($pdbg_srch_msg) {  echo '<br> 992_____resultSizeEstimate='.$resultSizeEstimate.'   $maxResults='.$maxResults; }
      
                //echo '<br> rsp = '; print_r($rsp);
                $msgA = array_key_exists('messages',$rsp) ? $rsp['messages'] : null;
                $nxPgTk =  array_key_exists('nextPageToken',$rsp) ? $rsp['nextPageToken'] : null;
                $resN = array_key_exists('resultSizeEstimate',$rsp) ? $rsp['resultSizeEstimate'] : null;
                
                if($pdbg_srch_msg) { 
                    echo '<br> 914_____COND_msgA = '. ( !empty($msgA) ) ; 
                }
                
                if( !empty($msgA) ) {
                        
                    foreach( $msgA as $ma ) {
                            
                        $cnt_ma++; 
                        $id = $ma['id'];
                        $tid = $ma['threadId'];
                        // 1:1 between id and cnt_mans
        
                        if($pdbg_srch_msg) { echo '<br>############ cnt_ma='.$cnt_ma.'   id='.$id.'   ma='; print_r($ma); }
                        
                        $curl = curl_init('https://www.googleapis.com/gmail/v1/users/'.$userId.'/messages/'.$id); 
                        curl_setopt_array( $curl, [
                            //CURLOPT_URL => 'https://www.googleapis.com/gmail/v1/users/'.$userId.'/messages?'.$qs // "https://myapi.com/api",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            //CURLOPT_POSTFIELDS => $pf,
                            CURLOPT_HTTPHEADER => [ 
                                'authorization: '.$token_type.' '.$access_token,
                                "content-type: application/x-www-form-urlencoded",
                                'Cache-Control: no-cache',
                            ], // CURLOPT_HTTPHEADER
                        ]);
                        
                        $mresponsej = curl_exec($curl);
                        if($pdbg_srch_msg) { 
                            echo '<br>cnt_search='.$cnt_search.'   $cnt_ma='.$cnt_ma.'   id='.$id.'  545___ query mresponse =<pre> '; print_r($mresponsej);
                        }
                    
                        file_put_contents( $request_logFnm, PHP_EOL.'    ##date='.$now.'    get-response='.html_entity_decode($mresponsej), FILE_APPEND  ); 
                        $err = curl_error($curl);
                        
                        if($pdbg_srch_msg) { 
                            echo '<br>cnt_search='.$cnt_search.'   $cnt_ma='.$cnt_ma.'   id='.$id.'  545___err='; print_r($err);
                        }
                        curl_close($curl);
                        
                        if( !empty($mresponsej) ) {
                            $mrspA = json_decode($mresponsej, true);
                            if( $pdbg_srch_msg ) { 
                                echo '<br> $mrspA ='; print_r($mrspA);
                                
                                $ans_body = searchBody([ 'arr'=> $mrspA['payload'] ]);
                                foreach($ans_body as $ab) {
                                    echo '<br> 440____ans_body='; 
                                    if( is_scalar($ab) ) {
                                        echo htmlentities($ab); 
                                    }
                                    else {
                                        print_r($ab);
                                    }
                                } // foreach($ans_body as $ab)
                            }
                        } // if( !empty($mresponsej)
                        
                    } //  foreach( $msgA as $ma 
                
                    
                } // if( !empty($msgA
                
            } // if( !empty($rsp) ) 
        } //  foreach ( $qA as $q
        
} // else if ($bserch_msg1 &&  $btoken && !$berr_state
