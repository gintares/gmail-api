<?php

//return the beginning of email text body 
function searchBody($gO=[]) {
    
    $bdbg_seaB = false;
    $ans = [];
    $base = '/customers/3/e/6/cwylctg8j/webroots/42cf782f/ga4/gmfu/gmfu1';
    $arr = isset($gO['arr']) ? $gO['arr'] : [];
    $subsSize = isset($gO['approxSize']) ? $gO['approxSize'] : 1000;
    $bmarr_cnt_max = isset($gO['bmarr_cnt_max']) ? $gO['bmarr_cnt_max'] : 2; //$bmarr_cnt_max=1 meanss it is enough one answer,
    if(isset($gO['bmR'])) { $bmR = &$gO['bmR']; } else { $bmR = [ 'cnt'=>0 ]; } // counts the text/html answers in deeper fcution calls. Break any iteration if there is an answer-count-match >=$bmarr_cnt_max
    
    $bmime = array_key_exists('mimeType', $arr) && in_array($arr['mimeType'], ['text/html', 'text/plain']); 
    if($bdbg_seaB ) {
        echo '<br> bmime='.$bmime.'  arr='; print_r($arr); 
    }
    
    if( $bmime && array_key_exists('body',$arr) && array_key_exists('size',$arr['body']) && array_key_exists('data', $arr['body']) && ($arr['body']['size']>0) ) {
        
        //[name] => Content-Transfer-Encoding
        //[value] => quoted-printable
        $data = trim($arr['body']['data']);
        //$data =str_replace(' ','+',$data);//https://www.php.net/manual/en/function.base64-decode.php
        if($bdbg_seaB ) {
            echo '<br>22_____ data='.$data;
        }
        
        $ud = str_replace( ['-','_','.'], ['+','/', '='],  substr($data, 0,256)  );
        $data_dec = base64_decode( $ud );
        
        $bnext_dochtml_start = preg_match('/^\<\!DOCTYPE html/',$data_dec);
        if($bdbg_seaB ) {
            echo '<br>......22_____ $bnext_dochtml_start='.$bnext_dochtml_start.'    mimeType='.$arr['mimeType'];
            echo '<br> $data_dec[0:40]'. htmlentities(substr($data_dec, 0, 40 ) ); 
        }
        
        if( !$bnext_dochtml_start && ($arr['mimeType']==='text/plain') ) {
            $data = trim($arr['body']['data']);
            $data_raw = '';
            $i = -1;
            $imax = ceil(strlen($data)/256);
            //$ss = 0;
            //$sl = 300; //step of chunks;
            $bnext_cnt_raw=0;
            while ( ($bnext_cnt_raw<2) || $bend || ($i<$imax) ) {
               $i++;
               $ud = str_replace(['-','_','.'], ['+','/', '='],  substr($data, $i*256,256)  );
               $data_dec = base64_decode( $ud ); 
               $bnext_raw = preg_match_all( '/[.]+/',$data_dec, $ma, PREG_OFFSET_CAPTURE ); //some condition, like to match the first two sentences.
               $ss = 0;
               if( $bnext_raw ) { 
                   foreach( $ma[0] as $mk=>$mv ) {
                       $pos = $mv[1];
                       //$data_raw .= substr($data_dec, $ss, ($pos-$ss) ); //add only to the  matched character, i.e. sentence stop
                       //$ss += $pos; 
                       $bnext_cnt_raw++; 
                       if( $bnext_cnt_raw >= $bmarr_cnt_max ) { 
                           $data_raw .= substr($data_dec, $ss, ($pos-$ss) ); //take text up to 2nd stop 
                           break 2;  
                       } 
                   } // foreach( $ma[1] as $mk=>$mv
                   if( $bnext_cnt_raw < $bmarr_cnt_max ) { 
                       $data_raw  .= $data_dec; //take all text  
                   }
               } // if( $bnext_raw
               else {
                   $data_raw .=  $data_dec; //add substrings until the condition-above is matched 
               } // else
            } // while ( ($bnext_cnt_raw<2) || $bend || ($i<$imax
            $data_raw = preg_replace('/[\s+\n+\t+]+/',' ', $data_raw);
        } // if( !$bnext_dochtml_start && ($pv['mimeType']==='text/plain
       
        else if ( $bnext_dochtml_start &&  $arr['mimeType']==='text/html' ) {
            
            $data = trim($arr['body']['data']);
            //decode all data
            $bnext_cnt_raw=0;
            $data_dechtml = '';
            $data = trim($data); 
            $imax = ceil(strlen($data)/256);
            for ($i=0; $i < $imax ; $i++ ) {
                //More exactly, you should use “-” instead of “+” and “_” instead of “/”.
                $ud = str_replace(['-','_','.'], ['+','/', '='], substr($data, $i*256,256)  );
                $data_dechtml .= base64_decode( $ud ); 
            }
            $internalErrors = libxml_use_internal_errors(true);
            // htmlParseEntityRef: no name in Entity
            // An unescaped "&" somewhere in the HTML and replace "&" with &amp. It will replace the single ampersand with "&amp" but current "&amp" will still remain the same.
            // https://stackoverflow.com/questions/14648442/domdocumentloadhtml-warning-htmlparseentityref-no-name-in-entity
            $data_dechtml = preg_replace('/&(?!amp)/', '&amp;', $data_dechtml );
            try {
                $doc = new DOMDocument('1.0', 'UTF-8');
                $doc->loadHTML($data_dechtml); //("<html><body>Test<br></body></html>");
                libxml_clear_errors();
                //libxml_use_internal_errors($internalErrors);

                    
                $body = $doc->getElementsByTagName('body');
                $datatxt = '';
                foreach ( $body as $bd ) {
                    $datatxt .= $bd->textContent. PHP_EOL;
                }
                $data_dec = preg_replace('/[\s+\n+\t+]+/',' ', $datatxt);
                //$doc->saveHTML();
                //file_put_contents( $base .'/data_dechtml.php', $data_dechtml );
                //ile_put_contents( $base .'/data_dechtml.php', $datatxt );
                //ob_start();
                //    include $base .'/data_dechtml.php';
                //    //$data_dec = ob_get_contents();
                //$data_dec = ob_get_clean();
                //$data_dec = strip_tags($data_dec);
                //ob_end_clean();
                //echo '<br> 60_____data_dec='. htmlentities($data_dec); 
                
               //some condition, like to match the first two sentences.
                $ss = 0;
                $bnext_cnt_raw = 0;
                $bnext_ma = true; 
                $data_raw ='';
                $data_decL = strlen($data_dec);
                while ( $bnext_ma && ( $bnext_cnt_raw >= $bmarr_cnt_max ) && ($ss <$data_decL) ) { 
                    
                    $bnext_ma = preg_match( '/[.]+/',$data_dec, $ma, PREG_OFFSET_CAPTURE ); 
                    if($bdbg_seaB ) {
                        echo '<br> ......22_____ bnext_ma='. $bnext_ma.'  ma='.json_encode($ma); 
                    }
                    
                    if($bnext_ma ) {
                        if( ($ss <$data_decL) ) {
                            foreach( $ma as $mk=>$mv ) {
                                $pos = $mv[1];
                                $data_raw .= substr($data_dec, $ss, ($pos-$ss+1) ); //add only to the  matched character, i.e. sentence stop
                                $ss += $pos + 1; 
                            } // foreach( $ma[1] as $mk=>$mv
                        }
                        else {
                            break 1; 
                        }
                        $bnext_cnt_raw++; 
                        if( $bnext_cnt_raw >= $bmarr_cnt_max ) { break 1; } 
                        $data_dec  = substr($data_dec, ($pos +1) );
                    }
                } // if( $bnext_raw
            }
            catch (Exception $e) {
                echo '<br> error e='; print_r($e);
            }
            
        } // else if ( $bnext_dochtml_start &&  $pv['mimeType']==='text/html 
        if($bdbg_seaB ) {
            
            echo '<br> ......22_____fina data_raw='.htmlentities($data_raw);
        }
        
        //$data_raw= preg_replace('/[\s+\n+\t+]+/',' ', $data_raw); 
        $ans[] = $data_raw; 
        $bmR['cnt']++; 
        //if($bmR['cnt'] >= $bmarr_cnt_max) { break 1; } // $bmarr_cnt_max=1 meanss it is enough one answer, 
       
    } // if( $bmime && array_key_exists('body',$arr) && array_key_exists('size',$arr['body']) && array_key_exists('data', $arr['body']) && ($arr['body']['si
    else if ( array_key_exists('parts',$arr) ) {
        
        $parts = $arr['parts']; 
        foreach( $parts as $pk=>$pv ) {
            $bmime_text = array_key_exists('mimeType', $pv) && in_array($pv['mimeType'], [ 'text/plain']); 
            if($bmime_text) {
                $parts=[];
                $parts[$pk]=$pv;
                break 1; 
            }
        }
        
        foreach( $parts as $pk=>$pv ) {

            $bmime = array_key_exists('mimeType', $pv) && in_array($pv['mimeType'], ['text/html', 'text/plain']); 
            if($bdbg_seaB ) {
                echo '<br> bmime='.$bmime.'   pv='; print_r($pv);
            }
            
            if( $bmime && array_key_exists('body',$pv) && array_key_exists('size',$pv['body']) && array_key_exists('data', $pv['body']) && ($pv['body']['size']>0) ) {
            
                //[name] => Content-Transfer-Encoding
                //[value] => quoted-printable
                $data = trim($pv['body']['data']);
                if($bdbg_seaB ) {
                    echo '<br> data='.$data;
                }
                
                $ud = str_replace(['-','_','.'], ['+','/', '='], substr($data, 0,256)  );
                $data_dec = base64_decode( $ud );
                $bnext_dochtml_start = preg_match('/^\<\!DOCTYPE html/',$data_dec);
                
                if($bdbg_seaB ) {
                    echo '<br>......155_____ $bnext_dochtml_start='.$bnext_dochtml_start.'    mimeType='.$arr['mimeType'];
                }
                 
                if( !$bnext_dochtml_start && ($pv['mimeType']==='text/plain') ) {
                    $data = trim($pv['body']['data']);
                    $bnext_cnt_raw=0;
                    $data_raw = '';
                    $i = -1;
                    $imax = ceil(strlen($data)/256);
                    //$ss = 0;
                    //$sl = 300; //step of chunks;
                    $bnext_cnt_raw=0;
                    while ( ($bnext_cnt_raw<2) && ($i<$imax) ) {
                       $i++;
                       $ud = str_replace(['-','_','.'], ['+','/','='],  substr($data, $i*256,256)  );
                       $data_dec = base64_decode( $ud ); 
                       $bnext_raw = preg_match_all( '/[.]+/',$data_dec, $ma, PREG_OFFSET_CAPTURE ); //some condition, like to match the first two sentences.
                       $ss = 0;
                       if( $bnext_raw ) { 
                           //echo '<br> 214 ma='; print_r($ma); 
                           foreach( $ma[0] as $mk=>$mv ) {
                               $pos = $mv[1];
                               //$data_raw .= substr($data_dec, $ss, ($pos-$ss) ); //add only to the  matched character, i.e. sentence stop
                               //$ss += $pos; 
                               $bnext_cnt_raw++; 
                               if( $bnext_cnt_raw >= $bmarr_cnt_max ) { 
                                   $data_raw .= substr($data_dec, $ss, ($pos-$ss) ); //take text up to 2nd stop 
                                   break 2;  
                               } 
                           } // foreach( $ma[1] as $mk=>$mv
                           if( $bnext_cnt_raw < $bmarr_cnt_max ) { 
                               $data_raw  .= $data_dec; //take all text  
                           }
                       } // if( $bnext_raw
                       else {
                           $data_raw .=  $data_dec; //add substrings until the condition-above is matched 
                       } // else
                    } // while ( ($bnext_cnt_raw<2) || $bend || ($i<$imax
                } // if( !$bnext_dochtml_start && ($pv['mimeType']==='text/plain
                else if ( $bnext_dochtml_start &&  $pv['mimeType']==='text/html' ) {
                    $data = $pv['body']['data'];
                    //decode all data
                    $bnext_cnt_raw=0;
                    $data_dechtml = '';
                    $data = trim($data); 
                    $imax = ceil(strlen($data)/256);
                    for ($i=0; $i < $imax ; $i++ ) {
                        //More exactly, you should use “-” instead of “+” and “_” instead of “/”.
                        $ud = str_replace(['-','_','.'], ['+','/','='],  substr($data, $i*256,256)  );
                        $data_dechtml .= base64_decode( $ud ); 
                    }
                    $internalErrors = libxml_use_internal_errors(true);
                    // htmlParseEntityRef: no name in Entity
                    // An unescaped "&" somewhere in the HTML and replace "&" with &amp. It will replace the single ampersand with "&amp" but current "&amp" will still remain the same.
                    // https://stackoverflow.com/questions/14648442/domdocumentloadhtml-warning-htmlparseentityref-no-name-in-entity
                    $data_dechtml = preg_replace('/&(?!amp)/', '&amp;', $data_dechtml );
                    try {
                        $doc = new DOMDocument('1.0', 'UTF-8');
                        $doc->loadHTML($data_dechtml); //("<html><body>Test<br></body></html>");
                        libxml_clear_errors();
                        //libxml_use_internal_errors($internalErrors);
                        $body = $doc->getElementsByTagName('body');
                        $datatxt = '';
                        foreach ($body as $bd) {
                            $datatxt .= $bd->textContent. PHP_EOL;
                        }
                        $data_dec = preg_replace('/[\s+\n+\t+]+/',' ', $datatxt);

                        //$imax = ceil(strlen($data)/256);
                        //for ($i=0; $i < $imax ; $i++ ) {
                        //    $data_dechtml .= base64_decode( substr($data, $i*256,256) ); 
                        //}
                        //$doc = new DOMDocument();
                        //$doc->loadHTML($data_dechtml); //("<html><body>Test<br></body></html>");
                        //$doc->saveHTML();
                        //file_put_contents( $base .'/data_dechtml.php', $data_dechtml );
                        //file_put_contents( $base .'/data_dechtml.php', $doc->saveHTML() );
                        //ob_start();
                        //    include $base .'/data_dechtml.php';
                            //$data_dec = ob_get_contents();
                        //$data_dec = ob_get_clean();
                        //$data_dec = strip_tags($data_dec);
                        //ob_end_clean();
                        //echo '<br> 60_____data_dec='. htmlentities($data_dec); 
                        
                       //some condition, like to match the first two sentences.
                        $ss = 0;
                        $bnext_cnt_raw = 0;
                        $bnext_ma = true; 
                        $data_raw ='';
                        $data_decL = strlen($data_dec);
                        while ( $bnext_ma && ( $bnext_cnt_raw >= $bmarr_cnt_max ) && ($ss <$data_decL) ) { 
                            
                            $bnext_ma = preg_match( '/[.]+/',$data_dec, $ma, PREG_OFFSET_CAPTURE ); 
                            if($bdbg_seaB ) {
                                echo '<br> ......22_____ bnext_ma='. $bnext_ma.'  ma='.json_encode($ma); 
                            }
                            
                            if($bnext_ma ) {
                                if( ($ss <$data_decL) ) {
                                    foreach( $ma as $mk=>$mv ) {
                                        $pos = $mv[1];
                                        $data_raw .= substr($data_dec, $ss, ($pos-$ss+1) ); //add only to the  matched character, i.e. sentence stop
                                        $ss += $pos + 1; 
                                    } // foreach( $ma[1] as $mk=>$mv
                                }
                                else {
                                    break 1; 
                                }
                                $bnext_cnt_raw++; 
                                if( $bnext_cnt_raw >= $bmarr_cnt_max ) { break 1; } 
                                $data_dec  = substr($data_dec, ($pos +1) );
                            }
                        } // while ( $bnext_ma && ( $bnext_cnt_
                    }
                    catch (Exception $e) {
                        echo '<br> error e='; print_r($e);
                    }
                        
                        
                } // else if ( $bnext_dochtml_start &&  $pv['mimeType']==='text/html 
                
                if($bdbg_seaB ) {
                    echo '<br> ......174_____fina data_raw='.htmlentities($data_raw);
                }
                
                //$data_raw= preg_replace('/[\s+\n+\t+]+/',' ', $data_raw); 
                $ans[] = $data_raw; 
                $bmR['cnt']++; 
                if($bmR['cnt'] >= $bmarr_cnt_max) { break 1; } // $bmarr_cnt_max=1 meanss it is enough one answer, 
  
            } // if( $bmime && array_key_exists('body',$pv) && array_key_exists('size',$pv['body']) && array_key_exists('data', $pv['bo
            else if ( array_key_exists('parts',$pv) ) {
                $gOI=$gO;
                $gOI['arr']=$pv['parts'];
                $tsb = searchBody($gOI);
                $ans = array_merge($ans,$tsb);
            }
        } // foreach( $parts as $pk=>$pv 
    } // else if ( array_key_exists('parts',$arr
    else {
        foreach( $arr as $ak=>$av ) {
            if ( array_key_exists('parts',$av) ) {
                $bmarr = [ 'cnt'=>0 ]; 
                $gOI=$gO;
                $gOI['arr']=$av;
                $gOI['bmR'] = &$bmarr; 
                $tsb = searchBody($gOI);
                if(!empty($tsb)) {
                    $ans = array_merge($ans,$tsb);
                    if($bmarr['cnt'] >= $bmarr_cnt_max) { break 1; } // $bmarr_cnt_max=1 meanss it is enough one answer, 
                }
            }
        }
    }
    
    return $ans; 
} // function searchBody($gO=


function  http_build_query_noUrlEnc($gO) {
    
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


function addImgFH($gO) {
    
    $ans = [];
    $fA = isset($gO['fA']) ? $gO['fA'] : [];
    $boundary = isset($gO['boundary']) ? $gO['boundary'] : [];
    $nl = isset($gO['nl']) ? $gO['nl'] : '\r\n'; 
    
    if( !empty($fA) ) {
    
        foreach ( $fA as $fk=>$fv ) {

            $ans[$fk] = [];
            $fnm = $fv['fnm'];
            $ctps = $fv['ctps'];
            
            echo '<br> 100______fnm='.$fnm. '   COND_fnm='. array_key_exists($fnm, $fA) ;
            
            if( !is_null($fnm) ) {
           
                echo '<br>  100______COND_file = ' . file_exists($fnm).'   '.$fnm;
                if( file_exists($fnm) ) {
                    //https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.messages.attachments
                    $fh = file_get_contents($fnm);
                    //to encod according to RFC 2822:
                    //convert the PNG image to a Base64 string 
                    //and then embed that string within a data URI, which can then be presented as part of a larger RFC 2822-encoded message.
                    $f64 = base64_encode($fh);
                    $size_in_bytes = (int) ( strlen(rtrim($f64, '=')) * 3 / 4 );
                    
$ans[$fk]=<<<HERE

    $boundary
    Content-Type: $ctps; name="$fk" 
    Content-Disposition: attachment; filename="$fk" 
    Content-Transfer-Encoding: base64
    size: $size_in_bytes
    data: $fh
    
HERE;

if($fk==='Easter_forukis.png') {
    echo '<br>119______addImgFH,  debugging ans['.$fk.'] = '.$ans[$fk];
}
                    
                    /* $ans[$fk] = '
                    '.$boundary.$nl.'
                    data: '.$fh.$nl.'
                    size: '.$size_in_bytes.$nl.'
                    Content-Type: '.$ctps.'; name="'.$fk.'" '.$nl.'
                    Content-Disposition: attachment; filename="'.$fk.'" '.$nl.'
                    Content-Transfer-Encoding: base64'.$nl; */
                    //$ans[$fk]['name'] = $fk;
                    //$ans$fk[]['Content-Type:  ']=$ctps;
                    //$ans[$fk]['data'] = $f64;
                    //$ans[$fk]['size'] = $size_in_bytes;
                    //$ans[$fk]['Content-Transfer-Encoding: '] = 'base64'; 
                    
                } // if(file_exists($fA[$fnm
            } //  if(!is_null($fn
            
        } //  foreach ( $fA as $fk=>$fv 
    } // if( !empty($fA)
    
    return $ans;

} // function addImgFH($gO)

function getAttchH($gO) {
    
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
                        /*
                        if( $tk==='X-Attachment-Id' ) {
                            $tv = 'f_'.randso(9);
                        }
                        else if ($tk ==='Content-ID' ) {
                            $tv = '\u003c'.randso(11).'\u003e';
                        }
                        else if ($tk==='Message-ID') {
                            $tv = '\u003c'.randso(51).'@mail.gmail.com\u003e';
                        }
                        else if ($tk==='id') {
                            $tv = $id;
                        }
                        else if ($tk==='id') {
                            $tv = $id;
                        }
                        else if ($tk==='id') {  
                            $tv = $id;
                        }  */
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
    ;//$parts = array_key_exists('body',$pl) ? $pl['parts'] : null;
    $snippet = array_key_exists('snippet',$arr) ? $arr['snippet'] : null;
    
    /*
    $ans['payload']=[];
    $ans['snippet'] = $snippet;
    
    if( array_key_exists('body',$arr) ) {
        $ans['body'] = $arr['body'];
    }
    if( array_key_exists('mimeType',$pl) ) {
        $ans['mimeType'] = $pl['mimeType'];
    }
    if( array_key_exists('body',$pl) ) {
        $ans['payload']['body'] = $pl['body'];
    }
    if( array_key_exists('parts',$pl) ) {
        $ans['payload']['parts'] = $pl['parts'];
    }
    if( array_key_exists('filename',$pl) ) {
        $ans['payload']['filename'] = $pl['filename'];
    } */
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
    
    //does not work
    //foreach( $arrKA as $k ) {
        //if( is_integer($k) ) {
            //$gOI=$arr[$nk];
            //$t = genEfE($gOI);
            //$ans[]= $t; 
       //     $ans[] = $akk[$k];
        //} // foreach( $arrKA_next as $nk
    //} // if( !empty($arrKA_next
    
    return $ans; 
    
} // function genEfE($gO)

function genEfE2($gO) {
    
    $ans=[];
    $arr = isset($gO['arr']) ? $gO['arr'] : [];
    $to = isset($gO['to']) ? $gO['to'] : [];
    $username = isset($gO['username']) ? $gO['username'] : '';
    $userId = isset($gO['userId']) ? $gO['userId'] : 'ginwork10@gmail.com'; 
    //$referer = isset($gO['referer']) ? $gO['referer'] : null; 
    //$token_type = isset($gO['token_type']) ? $gO['token_type'] : null;
    //$access_token = isset($gO['access_token'] ? $gO['access_token'] : null; 
    //$lev = isset($gO['lev']) ? $gO['lev'] : 0; 
   // $doKA = ['body', 'parts', 'headers'];
    $arrKA = array_keys($arr);
    
    $hda_dft = ['MIME-Version', 'Subject', 'Content-Type' ];
    
    $pl = array_key_exists('payload',$arr) ? $arr['payload'] : null;
    //$mimeType = array_key_exists('mimeType',$pl) ? $pl['mimeType'] : null;
    $hda = array_key_exists('headers',$pl) ? $pl['headers'] : null; 
    //$body = array_key_exists('body',$pl) ? $pl['body'] : null;
    ;//$parts = array_key_exists('body',$pl) ? $pl['parts'] : null;
    $snippet = array_key_exists('snippet',$arr) ? $arr['snippet'] : null;
    
    $ans['snippet'] = $snippet;
    if( array_key_exists('body',$arr) ) {
        $ans['body'] = $arr['body'];
    }
    if( array_key_exists('mimeType',$pl) ) {
        $ans['mimeType'] = $pl['mimeType'];
    }
    if( array_key_exists('body',$pl) ) {
        $ans['body'] = $pl['body'];
    }
    if( array_key_exists('parts',$pl) ) {
        $ans['parts'] = $pl['parts'];
    }
    if( array_key_exists('filename',$pl) ) {
        $ans['filename'] = $pl['filename'];
    }
    
    $ans["payload"] = [];
    $ans["payload"]['headers'] = [];
    $hda_dft = ['MIME-Version', 'Subject', 'Content-Type'];
    foreach( $hda_dft as $hk ) {
        if( array_key_exists($hk,$hda) ) {
            $hv = $hda[$hk];
            $ans["payload"]['headers'][$hk] = $hv;
        } // if( array_key_exists($hk,$hda
    } // foreach( $hda_dft as $hk
    $ans["payload"]['headers']['Date'] = date('D, d M Y h:i:s O', time() );  //Wed, 30 Apr 2025 15:51:23 -0400
    $ans["payload"]['headers']['From'] = $username.' '.$userId;
    $ans["payload"]['headers']['To'] = $to;
    //if( !is_null($referer) ) { $ans["payload"]['headers']['Referer'] = $referer; }
    //if( !is_null($token_type) && !is_null($access_token) ) { $ans["payload"]['headers']['Authorization'] =  $token_type.' '.$access_token; 
    //$ans['headers']['Content-Type: multipart/mixed; boundary="000000000000bff2ab06340664b3"']
    
    //does not work
    //foreach( $arrKA as $k ) {
        //if( is_integer($k) ) {
            //$gOI=$arr[$nk];
            //$t = genEfE($gOI);
            //$ans[]= $t; 
       //     $ans[] = $akk[$k];
        //} // foreach( $arrKA_next as $nk
    //} // if( !empty($arrKA_next
    
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

    
function cleanFH($gO) {
    
    $pat = isset($gO['pat']) ? $gO['pat'] : null;
    $spycFnm = isset($gO['spycFnm']) ? $gO['spycFnm'] : null;

    if( !is_null($pat) && !is_null($spycFnm) ) {
        
    
        include_once $spycFnm;
        $spy = new Spyc();
        
        $fA = glob( $pat );
        $dA = glob( $pat, GLOB_ONLYDIR );
        echo '<br> fA='; print_r($fA);
        
        if( !empty($fA) ) {
            foreach( $fA as $f ) {
                if( !in_array($f, $dA) ) {
                    $pa = pathinfo($f);
                    echo '<br> 860_____pa='; print_r($pa);
                    if($pa['extension']==='json') {
                        file_put_contents($f,'[]');
                    }
                    else if ($pa['extension']==='yaml') {
                        $es = $spy->YAMLDump([]);
                        file_put_contents($f,$es);
                    }
                } // if( !in_array($f, $dA
            } // foreach( $fA as $f 
        } //   if( !empty($fA)
        
        if(!empty($dA)) {
            foreach($dA as $dir) {
                $gOI=$gO;
                $gOI['pat']= $dir.'/*'; 
                cleanFH($gOI);
            }
        }
        
    } // if( !is_null($pat)
} // function cleanFH($gO

/* does not work
// https://www.php.net/manual/en/function.glob.php
function rglob($pattern) {
    yield from glob($pattern);
    foreach (glob(dirname($pattern) . "/*", GLOB_ONLYDIR) as $dir) {
        yield from rglob("$dir/" . basename($pattern));
    }
} */
