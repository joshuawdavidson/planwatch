<?php
/*
TEXTFUNCTIONS.php

holds all the text manipulating functions that used
to be in essential.php so that essential.php is easier
to work with


functions:
str WrapWords(str)
str removeEvilAttributes(str)
str removeEvilTags(str)
str smart_nl2br(str)
str _remove_linebreaks(str) * only a callback for smart_nl2br
arr cleanlist(arr)
str planwatch_fixlinks(str)
*/



// WRAPWORDS() ////////////////////////////////////////////////////////////////////////////
// a wordwrap() mod
//------------------------------------------------------------------------------
function WrapWords($str)
{
 $str=preg_replace("/([^\s\/<>]{60})/","\\1\n",$str);
 $str=wordwrap($str);
 return $str;
}

// REMOVEEVILATTRIBUTES() /////////////////////////////////////////////////////////////////
// removes style and class attributes from tags. used by plan_read_web()
// from http://www.php.net/strip-tags, by Tony Freeman and tREXX
//------------------------------------------------------------------------------
function removeEvilAttributes($tagSource)
{
	$stripAttrib="' (style|class)=\"(.*?)\"'i";
//	$stripAttrib="''i";
	$tagSource=stripslashes($tagSource);
	$tagSource=preg_replace($stripAttrib,'', $tagSource);
	return $tagSource;
}

// REMOVEEVILTAGS() /////////////////////////////////////////////////////////////////
// removes problematic tags. used by plan_read_web(). calls removeEvilAttributes()
// from http://www.php.net/strip-tags, by Tony Freeman and tREXX
//------------------------------------------------------------------------------
function removeEvilTags($source)
{
	$allowedTags='<a><br /><br/><br><b><h1><h2><h3><h4><i>' .
		'<img><li><ol><p><strong><em>' .
		'<div><span><acronym><strike>' .
		'<u><ul><area><map><blockquote><sub><dl><dd>';
	$source = strip_tags($source, $allowedTags);
	return preg_replace('/<(.*?)>/ie', "'<'.removeEvilAttributes('\\1').'>'", $source);
}



// SMART_NL2BR() //////////////////////////////////////////////////////////////////////////
//
// a smarter version of nl2br that doesn't put linebreaks
// inside HTML tags.
//------------------------------------------------------------------------------
function smart_nl2br($inputstring)
{
	$output=preg_replace_callback("/<style.*style>/s",'_remove_linebreaks',$inputstring);
	$output=preg_replace_callback("/<.*?".">/s",'_remove_linebreaks',$output);
	$output=str_replace(array("\r\n","\n\r","\r"),"\n",$output);
	$output_paragraphs=explode("\n\n",$output);
	foreach($output_paragraphs as $paragraph)
	{
		if(!strstr($paragraph,'<div')
			&& !strstr($paragraph,'<table')
			&& !strstr($paragraph,'<style')
			&& !strstr($paragraph,'</style')
			&& !strstr($paragraph,'</div')
			&& !strstr($paragraph,'</table')
			)
			$paragraph="<p>$paragraph</p>";
		else $paragraph=$paragraph;
		$content.=nl2br($paragraph);
	}

	return $content;
}

// just a callback function for smart_nl2br. not
// useful otherwise.
function _remove_linebreaks($item)
{
	foreach($item as $i=>$thing)
	{
		$item[$i]=str_replace(array("\n","\r"),' ',$item[$i]);
	}

return implode('',$item);
}




// PLANWATCH_FIXLINKS()
//
// this function adjusts links for nonstandard hosts and
// session IDs
//------------------------------------------------------------------------------
function planwatch_fixlinks($page)
{
	$page=str_replace("url(/","url($_SERVER[WEB_ROOT]/",$page);
	$page=str_replace("='/","='$_SERVER[WEB_ROOT]/",$page);
	$page=str_replace("='http://$_SERVER[HTTP_HOST]/","='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/",$page);
	$page=str_replace("$_SERVER[WEB_ROOT]$_SERVER[WEB_ROOT]","$_SERVER[WEB_ROOT]",$page);
	$page=str_replace("$_SERVER[WEB_ROOT]$_SERVER[FILE_ROOT]","$_SERVER[FILE_ROOT]",$page);
	$page=str_replace("='/graphics","='$_SERVER[WEB_ROOT]/resources/graphics",$page);
	
	if (strlen($sid=$_SERVER['SESSION_ID']) > 10)
	{
		$notintextarea=TRUE;
		$page_a=explode("href='",$page);
		foreach($page_a as $i=>$link)
		{
			$notintextarea=TRUE;
			if (strstr($page_a[$i-1],"</textarea")) $notintextarea=TRUE;
			if (strstr($link,"<textarea")) $notintextarea=FALSE;
			$linkend=strpos($link,"'");
			if ($linkend > 0 && $i>0)
			{
				$linkreplace=substr($link,0,$linkend);
				if ((strstr($linkreplace,'planw') || $linkreplace[0]=='/') && $notintextarea) $link=str_replace($linkreplace."'","href='$linkreplace/sid=$sid'",$link);
				else $link="href='$link";
				$page_a[$i]=$link;
			}
		}
		$page=implode('',$page_a);
	
		$page=str_replace("</form>","<input type='hidden' name='sid' value='$_SERVER[SESSION_ID]'/></form>",$page);
		$page=str_replace("</FORM>","<input type='hidden' name='sid' value='$_SERVER[SESSION_ID]'/></form>",$page);
	}
	$page=str_replace("a ' ","a href=''",$page);
	
return $page;
}


// HIGHLIGHT_PHP()
//
// this function adjusts links for nonstandard hosts and
// session IDs
//
// from php.net/include/layout.inc
// modified by joshuawdavidson@gmail.com
//------------------------------------------------------------------------------
function highlight_php($filename, $return = TRUE)
{
	$_SERVER['STOPWATCH']['highlight_begin']=array_sum(explode(' ',microtime()));
	// depends on PHP > 4.2.0
	$highlighted=highlight_file($filename,TRUE);
	$highlighted_lines=explode("<br />",$highlighted);
	unset($highlighted);
	foreach($highlighted_lines as $i=>$highlighted_line)
	{
		$line_number=$i+1;
		if (strlen($line_number)==1) $line_number="...$line_number";
		if (strlen($line_number)==2) $line_number="..$line_number";
		if (strlen($line_number)==3) $line_number=".$line_number";
	
		if (strstr($highlighted_line,'function'))
		{
			$highlighted_line=preg_replace('/function\s+<.+>(\w+)<.+>\(/',"function </span><a name='\\1' style='font-weight: bold; color: #0000BB;'>\\1</a><span>(",$highlighted_line);
		}

		$highlighted.="<a class='line_number' id='line".($i+1)."' name='".($i+1)."'>$line_number:</a> ".$highlighted_line."<br/>\n";
	}
 
	// Fix output to use CSS classes and wrap well
	$highlighted = '<div class="phpcode">' . str_replace(
		array(
			'&nbsp;',
			'<br />',
			'<font color="',		// for PHP 4
			'</font>',
			"\n ",
			'  '
		),
		array(
			' ',
			"<br />\n",
			'<span style="color: ',
			'</span>',
			"\n&nbsp;",
			'&nbsp; '
		),
		$highlighted
	) . '</div>';
  
	$highlighted=str_replace('style="color: #007700"','class="dots"',$highlighted);
	$highlighted=str_replace('style="color: #DD0000"','class="strings"',$highlighted);
	$highlighted=str_replace('style="color: #0000BB"',"class='keys'",$highlighted);

	$_SERVER['STOPWATCH']['highlight_end']=array_sum(explode(' ',microtime()));
	if ($return) { return $highlighted; }
	else { echo $highlighted; }
}


// VAR_DUMP_RET()
//
// captures var_dump output and returns it as a string
//
// from edwardzyang at thewritingpot dot com
// in comments on http://php.net/var_dump
//------------------------------------------------------------------------------
function var_dump_ret($mixed = null) {
	ob_start();
	var_dump($mixed);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

#################################################
####
#### smart_trim
#### This function trims a string to a specified length.
#### Words are separated by space characters, and they are not
#### chopped if possible.
#### 
#### @package smart_trim
#### @author  Michael Gauthier <mike@silverorange.com>
#### silverorange
#### labs.silverorange.com
#### 
#### Copyright (c) 2003, silverorange Inc.
#### All rights reserved.
#### 
#### Redistribution and use in source and binary forms, with or without modification,
#### are permitted provided that the following conditions are met:
#### 
####     * Redistributions of source code must retain the above copyright notice, this
####       list of conditions and the following disclaimer.
####     * Redistributions in binary form must reproduce the above copyright notice,
####       this list of conditions and the following disclaimer in the documentation
####       and/or other materials provided with the distribution.
####     * Neither the name of silverorange Inc. nor the names of its contributors may
####       be used to endorse or promote products derived from this software without
####       specific prior written permission.
#### 
#### THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
#### ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#### WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
#### IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
#### INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
#### BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
#### DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
#### LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
#### OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
#### OF THE POSSIBILITY OF SUCH DAMAGE.
#### 
#################################################

/**
 * Trim the string.
 *
 * @param  string  $text        The line to trim.
 * @param  integer $max_len     The maximum length of the trimmed line.
 *                              This ignores the length of the characters
 *                              that indicate trimming has occured.
 * @param  boolean $trim_middle Trimming takes place in the middle of the line
 *                              iff true. Otherwise, the line is trimmed at the
 *                              end. Defaults to false.
 * @param  string  $trim_chars  Characters to use to indicate trimming has
 *                              occured. Defaults to '...'.
 *
 * @return string               The trimmed line of text.
 */
function smart_trim($text, $max_len, $trim_middle = false, $trim_chars = '...')
{
	$text = trim($text);

	if (strlen($text) < $max_len) {

		return $text;

	} elseif ($trim_middle) {

		$hasSpace = strpos($text, ' ');
		if (!$hasSpace) {
			/**
			 * The entire string is one word. Just take a piece of the
			 * beginning and a piece of the end.
			 */
			$first_half = substr($text, 0, $max_len / 2);
			$last_half = substr($text, -($max_len - strlen($first_half)));
		} else {
			/**
			 * Get last half first as it makes it more likely for the first
			 * half to be of greater length. This is done because usually the
			 * first half of a string is more recognizable. The last half can
			 * be at most half of the maximum length and is potentially
			 * shorter (only the last word).
			 */
			$last_half = substr($text, -($max_len / 2));
			$last_half = trim($last_half);
			$last_space = strrpos($last_half, ' ');
			if (!($last_space === false)) {
				$last_half = substr($last_half, $last_space + 1);
			}
			$first_half = substr($text, 0, $max_len - strlen($last_half));
			$first_half = trim($first_half);
			if (substr($text, $max_len - strlen($last_half), 1) == ' ') {
				/**
				 * The first half of the string was chopped at a space.
				 */
				$first_space = $max_len - strlen($last_half);
			} else {
				$first_space = strrpos($first_half, ' ');
			}
			if (!($first_space === false)) {
				$first_half = substr($text, 0, $first_space);
			}
		}

		return $first_half.$trim_chars.$last_half;

	} else {

		$trimmed_text = substr($text, 0, $max_len);
		$trimmed_text = trim($trimmed_text);
		if (substr($text, $max_len, 1) == ' ') {
			/**
			 * The string was chopped at a space.
			 */
			$last_space = $max_len;
		} else {
			/**
			 * In PHP5, we can use 'offset' here -Mike
			 */
			$last_space = strrpos($trimmed_text, ' ');
		}
		if (!($last_space === false)) {
			$trimmed_text = substr($trimmed_text, 0, $last_space);
		}
		return remove_trailing_punctuation($trimmed_text).$trim_chars;

	}

}

/**
 * Strip trailing punctuation from a line of text.
 *
 * @param  string $text The text to have trailing punctuation removed from.
 *
 * @return string       The line of text with trailing punctuation removed.
 */
function remove_trailing_punctuation($text)
{
	return preg_replace("'[^a-zA-Z_0-9]+$'s", '', $text);
}

?>