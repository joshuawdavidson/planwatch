<?php

//dl('imap.so');
$server_ref="{localhost:110/pop3}INBOX";
$pop_user="submit@planwatch.org";
$pop_pass="23mac4x";

$mbox = imap_open ($server_ref, $pop_user, $pop_pass);

$check = imap_check ($mbox);

imap_close ($mbox);

$overview = imap_fetch_overview($mbox,"1:".$check->Nmsgs,0);
 
if(is_array($overview)) {
        reset($overview);
        while( list($key,$val) = each($overview)) {
                print     $val->msgno
                . " - " . $val->date
                . " - " . $val->subject
                . "\n";
        }
}
 
imap_close($mbox);

