<?
/*
* pop3.php
*
* @(#) $Header: /home/mlemos/cvsroot/PHPlibrary/pop3.php,v 1.6 2002/01/12 02:39:57 mlemos Exp $
*
*/

class pop3_class
{
var $hostname="";
var $port=110;
var $quit_handshake=0;

/* Private variables - DO NOT ACCESS */

var $connection=0;
var $state="DISCONNECTED";
var $greeting="";
var $must_update=0;
var $debug=0;
var $next_token="";

/* Private methods - DO NOT CALL */

Function Tokenize($string,$separator="")
{
if(!strcmp($separator,""))
{
$separator=$string;
$string=$this->next_token;
}
for($character=0;$character<strlen($separator);$character++)
{
if(GetType($position=strpos($string,$separator[$character]))=="integer")
$found=(IsSet($found) ? min($found,$position) : $position);
}
if(IsSet($found))
{
$this->next_token=substr($string,$found+1);
return(substr($string,0,$found));
}
else
{
$this->next_token="";
return($string);
}
}

Function OutputDebug($message)
{
echo $message,"n";
}

Function GetLine()
{
for($line="";;)
{
if(feof($this->connection))
return(0);
$line.=fgets($this->connection,100);
$length=strlen($line);
if($length>=2
&& substr($line,$length-2,2)=="rn")
{
$line=substr($line,0,$length-2);
if($this->debug)
$this->OutputDebug("< $line");
return($line);
}
}
}

Function PutLine($line)
{
if($this->debug)
$this->OutputDebug("> $line");
return(fputs($this->connection,"$linern"));
}

Function OpenConnection()
{
if($this->hostname=="")
return("2 it was not specified a valid hostname");
if($this->debug)
$this->OutputDebug("Connecting to ".$this->hostname." ...");
if(($this->connection=fsockopen($this->hostname,$this->port,&$error))==0)
{
switch($error)
{
case -3:
return("-3 socket could not be created");
case -4:
return("-4 dns lookup on hostname "$hostname" failed");
case -5:
return("-5 connection refused or timed out");
case -6:
return("-6 fdopen() call failed");
case -7:
return("-7 setvbuf() call failed");
default:
return($error." could not connect to the host "".$this->hostname.""");
}
}
return("");
}

Function CloseConnection()
{
if($this->debug)
$this->OutputDebug("Closing connection.");
if($this->connection!=0)
{
fclose($this->connection);
$this->connection=0;
}
}

/* Public methods */

/* Open method - set the object variable $hostname to the POP3 server address. */

Function Open()
{
if($this->state!="DISCONNECTED")
return("1 a connection is already opened");
if(($error=$this->OpenConnection())!="")
return($error);
$this->greeting=$this->GetLine();
if(GetType($this->greeting)!="string"
|| $this->Tokenize($this->greeting," ")!="+OK")
{
$this->CloseConnection();
return("3 POP3 server greeting was not found");
}
$this->Tokenize("<");
$this->must_update=0;
$this->state="AUTHORIZATION";
return("");
}

/* Close method - this method must be called at least if there are any
messages to be deleted */

Function Close()
{
if($this->state=="DISCONNECTED")
return("no connection was opened");
if($this->must_update
|| $this->quit_handshake)
{
if($this->PutLine("QUIT")==0)
return("Could not send the QUIT command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get quit command response");
if($this->Tokenize($response," ")!="+OK")
return("Could not quit the connection: ".$this->Tokenize("rn"));
}
$this->CloseConnection();
$this->state="DISCONNECTED";
return("");
}

/* Login method - pass the user name and password of POP account.  Set
$apop to 1 or 0 wether you want to login using APOP method or not.  */

Function Login($user,$password,$apop)
{
if($this->state!="AUTHORIZATION")
return("connection is not in AUTHORIZATION state");
if($apop)
{
if(!strcmp($this->greeting,""))
return("Server does not seem to support APOP authentication");
if($this->PutLine("APOP $user ".md5("<".$this->greeting.">".$password))==0)
return("Could not send the APOP command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get APOP login command response");
if($this->Tokenize($response," ")!="+OK")
return("APOP login failed: ".$this->Tokenize("rn"));
}
else
{
if($this->PutLine("USER $user")==0)
return("Could not send the USER command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get user login entry response");
if($this->Tokenize($response," ")!="+OK")
return("User error: ".$this->Tokenize("rn"));
if($this->PutLine("PASS $password")==0)
return("Could not send the PASS command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get login password entry response");
if($this->Tokenize($response," ")!="+OK")
return("Password error: ".$this->Tokenize("rn"));
}
$this->state="TRANSACTION";
return("");
}

/* Statistics method - pass references to variables to hold the number of
messages in the mail box and the size that they take in bytes.  */

Function Statistics($messages,$size)
{
if($this->state!="TRANSACTION")
return("connection is not in TRANSACTION state");
if($this->PutLine("STAT")==0)
return("Could not send the STAT command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get the statistics command response");
if($this->Tokenize($response," ")!="+OK")
return("Could not get the statistics: ".$this->Tokenize("rn"));
$messages=$this->Tokenize(" ");
$size=$this->Tokenize(" ");
return("");
}

/* ListMessages method - the $message argument indicates the number of a
message to be listed.  If you specify an empty string it will list all
messages in the mail box.  The $unique_id flag indicates if you want
to list the each message unique identifier, otherwise it will
return the size of each message listed.  If you list all messages the
result will be returned in an array. */

Function ListMessages($message,$unique_id)
{
if($this->state!="TRANSACTION")
return("connection is not in TRANSACTION state");
if($unique_id)
$list_command="UIDL";
else
$list_command="LIST";
if($this->PutLine("$list_command $message")==0)
return("Could not send the $list_command command");
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get message list command response");
if($this->Tokenize($response," ")!="+OK")
return("Could not get the message listing: ".$this->Tokenize("rn"));
if($message=="")
{
for($messages=array();;)
{
$response=$this->GetLine();
if(GetType($response)!="string")
return("Could not get message list response");
if($response==".")
break;
$message=intval($this->Tokenize($response," "));
if($unique_id)
$messages[$message]=$this->Tokenize(" ");
else
$messages[$message]=intval($this->Tokenize(" "));
}
return($messages);
}
else
{
$message=intval($this->Tokenize(" "));
return(intval($this->Tokenize(" ")));
}
}

/* RetrieveMessage method - the $message argument indicates the number of
a message to be listed.  Pass a reference variables that will hold the
arrays of the $header and $body lines.  The $lines argument tells how
many lines of the message are to be retrieved.  Pass a negative number
if you want to retrieve the whole message. */

Function RetrieveMessage($message,$headers,$body,$lines)
{
if($this->state!="TRANSACTION")
return("connection is not in TRANSACTION state");
if($lines<0)
{
$command="RETR";
$arguments="$message";
}
else
{
$command="TOP";
$arguments="$message $lines";
}
