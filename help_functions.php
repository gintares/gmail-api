<?php

function  http_build_query_noUrlEnc($gO) {
  
  //mergers array to the string using different glue characters per level given in gO['ga'], 
  //does not url-encode
  //it is used in curl_init function to pass post-fields. They can not be url-encoded, because this gives errors.
    
    $arr = isset($gO['arr']) ? $gO['arr'] : [];
    $ga = isset($gO['ga']) ? $gO['ga'] : []; //glue per level
    $lev = isset($gO['lev']) ? $gO['lev'] : 0;
    $ans =[];
    $g_colval_dft = '=';
    $g_dft = '&';
    $ansf = null; 
    $bdbg_hbqnue = false;
    
    if( isset($gO['bjson']) && $gO['bjson'] ) {
        $ans = json_encode($arr);
    }
    else {
        
        $g = null;
        $levg= $lev;
        while ($levg > -1) {
            $g = isset($ga[$lev]['gcolval']) ? $ga[$lev]['gcolval'] : null;
            $levg--;
            if( !is_null($g) ) { break 1; }
        }
        if ( $bdbg_hbqnue) { echo '<br> g= '.$g; } 
        if(is_null($g)) { $g = $g_colval_dft; }
        
        if( $bdbg_hbqnue ) {
            echo '<br>   double check g= '.$g;
            echo '<br> arr='; print_r($arr); 
        }

        foreach($arr as $k=>$v ){
            if( isset($ga[$lev]['bjson']) && $ga[$lev]['bjson'] ) {
                 $ans[] = $k.$g.json_encode($v); 
            } // // if( $ga[$lev]['gcolval'] === 'json') 
            else {
                if( is_scalar($v)) {
                    $ans[] = $k.$g.$v;
                }
                else if (is_array($v)) {
                    $gOI = $gO;
                    $gOI['arr'] = $v; 
                    $gOI['lev'] = $lev +1; 
                    $t = http_build_query_noUrlEnc($gOI);
                    if( !is_null($t) ) {
                        $ans[] = $t;
                    }
                }
                else {
                    $ans[] = $k.$g.json_encode($v); 
                }
            } // else  
        } // foreach($arr as $k=>$v
    } // else 
    
    if ( $bdbg_hbqnue ) { echo '<br> ans='; print_r($ans); }
    
    if( !empty($ans) ) {
        
        $g = null;
        $levg= $lev;
        while ($levg > -1) {
            $g = isset($ga[$lev]['g']) ? $ga[$lev]['g'] : null;
            $levg--;
            if( !is_null($g) ) { break 1; }
        }
        if(is_null($g)) { $g = $g_dft; }
        $ansf = implode($g,$ans);
    }
    
    if( $bdbg_hbqnue )  {  echo '<br> ansf='; print_r($ansf);  }
    
    return $ansf;
    
}

function getAttchH($gO) {

  // takes some sent-gmail message as argument, which is fetched from response of gmail-API to read message using id. 
  // ans is new message which is created using fetched message
  // loads images from files, base64 encodes and assigns to ans
  // trims some keys from original message, assigns other keys to ans
  // https://developers.google.com/workspace/gmail/api/guides/uploads
  // https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages.attachments
    
    $bdbg = false;
    $ans=[];
    $arr = isset($gO['arr']) ? $gO['arr'] : [];
    $arrKA = array_keys($arr);
    $nK = isset($gO['nK']) ? $gO['nK'] : null;
    $fnm= isset($gO['filename']) ? $gO['filename'] : null;
    $fA = isset($gO['fA']) ? $gO['fA'] : [];
    $g = isset($gO['g']) ? $gO['g'] : '|'; 
    if( isset($gO['bytesR']) ) {  $bytesR = &$gO['bytesR']; } else { $bytesR = ['n'=>0]; }
    if( isset($gO['sizeR']) ) {  $sizeR = &$gO['sizeR']; } else { $sizeR = ['n'=>0]; }
    if (isset ($gO['bfilesR']) ) { $bfilesR =&$gO['bfilesR']; } else { $bFilesR = [ 'cnt'=>0 ]; }
    
    if( array_key_exists('filename',$arr) ) {
        $fnm = $arr['filename']; 
    }
    
    foreach($arr as $ak=>$av) {

        
        $barr = false;   
        $battch = false; 
        if( is_array($av) && array_key_exists('attachmentId', $av)) { 
            
            
            //$ans[$nk] = $av['attachmentId'];
            //A base64-encoded string.
            if($bdbg) {
                echo '<br> 100______found attachmentId='.$av['attachmentId'];
                echo '<br> 100______fnm='.$fnm. '   COND_fnm='. array_key_exists($fnm, $fA) ;
            }
            
            if( !is_null($fnm) ) {
                if( array_key_exists($fnm, $fA) ) {
                    
                    if($bdbg) {
                        echo '<br>  100______COND_file = ' . file_exists($fA[$fnm]['fnm']).'  '.$fA[$fnm]['fnm'];
                    }
                    
                    if ( file_exists($fA[$fnm]['fnm']) ) {
                        //https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages.attachments
                        $ans[$ak]=[];
                        $fh = file_get_contents( $fA[$fnm]['fnm'] );
                        $flsz = filesize( $fA[$fnm]['fnm'] );
                        //to encod according to RFC 2822:
                        //convert the PNG image to a Base64 string 
                        //and then embed that string within a data URI, which can then be presented as part of a larger RFC 2822-encoded message.
                        $f64 = base64_encode($fh);
                        $size_in_bytes = (int) (strlen(rtrim($f64, '=')) * 3 / 4);
                        
                        $sizeR['n'] += $flsz;
                        $bytesR['n'] += $size_in_bytes;
                        $bfilesR['cnt']++; 
                        
                        //$ans[$ak]['data'] = $f64;
                        $ans[$ak]['size'] = $flsz;
                        $ans[$ak]['raw'] = $f64; // $size_in_bytes;
                        //$ans[$ak]['Content-Type'] = $fA[$fnm]['ctps'];
                        //$ans[$ak]['Content-Disposition'] = 'attachment';
                        //$ans[$ak]['Content-Transfer-Encoding'] = 'base64';
                        $battch = true;
                        
                        if($bdbg) {
                            echo '<br>  $flsz='. $flsz.'   $size_in_bytes='.$size_in_bytes.'  av[size=]'; echo $av['size'];
                            echo '<br> 214____ak='.$ak.'  battch='.$battch.'   barr='.$barr.'    breaking';
                        }
                        break 1; 
                    } else { $barr = true;  }// if(file_exists($fA[$fnm
                    
                } else { $barr = true;  }// if(array_key_exists($fnm, $fA
            } //  if(!is_null($fn
            else { $barr = true;  }
            
        }  // if(array_key_exists('attachmentId', $a
        else if ( is_array($av) ) {
            $barr = true;
        }

        if($bdbg) {  echo '<br> 214____ak='.$ak.'  battch='.$battch.'   barr='.$barr; }
        if(!$battch) {
            
            if($barr ) {
            
                if( array_key_exists('filename',$av) ) {
                    $fnm = $av['filename']; 
                }
                
                if( array_key_exists('size',$av) ) {
                    $bytesR['n'] += $av['size'];
                   //?? $size_in_bytes = (int) (strlen(rtrim($av['size'], '=')) * 3 / 4);
                    if($bdbg) { echo '<br> given size in bytes??  ='.$av['size']; }
                }
                
                
                $nk = $nK.$g.$ak;
                $gOI = ['arr'=>$av, 'nk'=>$nk, 'filename'=>$fnm, 'fA'=>$fA ];
                $gOI['sizeR'] = &$sizeR;
                $gOI['bytesR'] = &$bytesR;
                $gOI['bfilesR'] = [ 'cnt'=>0 ];
                $t = getAttchH($gOI);
                
                if( !empty($t) ) {
                    $ans[$ak]=[];
                    foreach( $t as $tk=>$tv ) {
                        
                        if($bdbg) { echo '<br> setting tk='.$tk.' for array av, '; }
                        if($tk ==='raw') {
                            $ans[$ak][$tk]=$bytesR['n'];
                        }
                        else if($tk ==='sizeEstimate') {
                            if($bdbg) { echo '<br> 219___messahe contains sizeEstimate='.$sizeEstimate.'    my value = '.$sizeR['n'].'   my bytes='.$bytesR['n']; }
                            $ans[$ak][$tk]=$sizeR['n'];
                        }
                        else if (!(array_key_exists ('name', $av) && in_array( $av['name'], ['historyId', 'id', 'threadId', 'labelIds', 'snippet', 'X-Attachment-Id', 'Content-ID','Message-ID' , 'internalDate', 'attachmentId' ] ) ) ) {
                            $ans[$ak][$tk]=$tv;
                            if ($bdbg) { echo '   YES'; }
                        }
                        else {
                            if ($bdbg) { echo '   NO '; }
                        }
                    } /// foreach( $t as $tk=>$tv
                } // if( !empty($t
                 
                if( array_key_exists('sizeEstimate',$t) || array_key_exists('size', $t) ) {
                    //https://developers.google.com/workspace/gmail/api/guides/uploads
                    $ans[$ak]['raw'] = $bytesR['n'];
                }
                
                
                if( empty($ans[$ak]) ) {
                    unset($ans[$ak]);
                }
                
            } // else if (is_array($av
            else {
                //if ( !in_array( $ak, ['historyId', 'id', 'threadId', 'labelIds', 'snippet', 'X-Attachment-Id', 'Content-ID','Message-ID' , 'internalDate', 'attachmentId' ] ) ) ) {
                           
                    $ans[$ak] = $av;
                //}
            }

            
        } //  if(!$battch
        
    } // foreach($arr as $ak=>$av)
    
    return $ans;
}

function genEfE($gO) {

  // takes some sent-gmail message as argument, which is fetched from response of gmail-API to read message using id. 
  // ans is new message which is created using fetched message

  // trims some keys from original message, assigns other keys to ans
  // adds headers to ans
  // https://developers.google.com/workspace/gmail/api/guides/uploads
  // https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages.attachments
  
    $bdbg = false;
    $ans=[];
    $arr = isset($gO['arr']) ? $gO['arr'] : [];
    $to = isset($gO['to']) ? $gO['to'] : [];
    $username = isset($gO['username']) ? $gO['username'] : '';
    $userId = isset($gO['userId']) ? $gO['userId'] : 'ginwork10@gmail.com'; 
    //$lev = isset($gO['lev']) ? $gO['lev'] : 0; 
   // $doKA = ['body', 'parts', 'headers'];
    $arrKA = array_keys($arr);
    
    $hda_dft = ['MIME-Version', 'Subject', 'Content-Type' ];
    
    $pl = array_key_exists('payload',$arr) ? $arr['payload'] : null;
    //$mimeType = array_key_exists('mimeType',$pl) ? $pl['mimeType'] : null;
    $hda = array_key_exists('headers',$pl) ? $pl['headers'] : null; 
    //$body = array_key_exists('body',$pl) ? $pl['body'] : null;
    //$parts = array_key_exists('body',$pl) ? $pl['parts'] : null;
    //$snippet = array_key_exists('snippet',$arr) ? $arr['snippet'] : null;
    
    $ans = $arr; 
    
    $ans['payload']['headers']=[];
    $hda_dft = ['MIME-Version', 'Subject', 'Content-Type'];
    foreach($hda_dft as $hk) {
        if(array_key_exists($hk,$hda)) {
            $hv = $hda[$hk];
            $ans['payload']['headers'][$hk]=$hv;
        }
    }
    $ans['payload']['headers']['Date'] = date('D, d M Y h:i:s O', time() );  //Wed, 30 Apr 2025 15:51:23 -0400
    $ans['payload']['headers']['From'] = $username.' '.$userId;
    $ans['payload']['headers']['To'] = $to;
    //$ans['headers']['Content-Type: multipart/mixed; boundary="000000000000bff2ab06340664b3"']
    
    return $ans; 
    
} // function genEfE($gO)


function randso( $n, $b='alphanumCL' ) {
    $ans='';
    $alphanumS = 'qwe1rt2yu3io4pl5kj6hg7fd8sa9zx0cvbnm';
    $alphanumSL = strlen($alphanum)-1;
    for( $i=0; $i<$n; $i++ ) {
        $j = rand(0,$alphanumSL);
        $ans .= $alphanulS[$i];
    } // for( $i=0; $i<$n; $i++
    return $ans;
} // function randso( $n, $b='alphanumCL' 

            
function prfields($fields) {
    
    $ans = [];
    foreach( $fields as $fk=>$fv ) {
        if( is_scalar($fv) ) {
            if( $fk!=='data' ) {
                $ans[$fk]=$fv;
            }
            else {
                $ans[$fk] = $fk;
            }
        } // if( is_scalar($fv)
        else {
            $ans[$fk] = prfields($fv);
        }
    } // foreach( $fields as $fk=>$fv
    
    return $ans;
    
} //function prfields($fields)
