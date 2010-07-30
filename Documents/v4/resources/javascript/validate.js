/* 
modifications for planwatch.org by jwdavidson

ORIGINAL CODE
http://developer.apple.com/internet/webcontent/validation.html

Copyright 2001 by Apple Computer, Inc., All Rights Reserved.

 You may incorporate this Apple sample code into your own code
 without restriction. This Apple sample code has been provided "AS IS"
 and the responsibility for its operation is yours. You may redistribute
 this code, but you are not permitted to redistribute it as
 "Apple sample code" after having made changes. */

/* whole form */
function checkWholeForm(theForm) {
    var why = "";
    why += checkEmail(theForm.email.value);
    why += checkPassword(theForm.password.value);
    why += checkUsername(theForm.username.value);
    why += checkRealname(theForm.real_name.value);
    if (why != "") {
       alert(why);
       return false;
    }
return true;
}

/* email */

function checkEmail (strng) {
var error="";
if (strng == "") {
   error = "You didn't enter an email address.\n";
}

    var emailFilter=/^.+@.+\..{2,3}$/;
    if (!(emailFilter.test(strng))) { 
       error = "Please enter a valid email address.\n";
    }
    else {
//test email for illegal characters
       var illegalChars= /[\(\)\<\>\,\;\:\\\"\[\]]/
         if (strng.match(illegalChars)) {
          error = "The email address contains illegal characters.\n";
       }
    }
return error;    
}


// password - between 6-8 chars, uppercase, lowercase, and numeral

function checkPassword (strng) {
var error = "";
if (strng == "") {
   error = "You didn't enter a password.\n";
}

    var illegalChars = /[\W_]/; // allow only letters and numbers
    
    if ((strng.length < 6) || (strng.length > 8)) {
       error = "The password is the wrong length.\n";
    }
    else if (illegalChars.test(strng)) {
      error = "The password contains illegal characters.\n";
    } 
return error;    
}    


// username - 4-10 chars, uc, lc, and underscore only.

function checkUsername (strng) {
var error = "";
if (strng == "") {
   error = "You didn't enter a username.\n";
}


    var illegalChars = /\W/; // allow letters, numbers, and underscores
    if (illegalChars.test(strng)) {
    error = "The username contains illegal characters.\n";
    }
return error;
}

// username - 4-10 chars, uc, lc, and underscore only.

function checkRealname (strng) {
var error = "";
if (strng == "") {
   error = "You didn't enter a real name.\n";
}


    var illegalChars = /[^\w ]/; // allow letters, numbers, and spaces
    if (illegalChars.test(strng)) {
    error = "Your real name contains illegal characters.\n";
    }
return error;
}

function advanced_toggle()
{
	var advanced_div = document.getElementById('advanced');
	var adv_link_div = document.getElementById('advanced_link');
	var sim_link_div = document.getElementById('simple_link');

	if (advanced_div.style.display == 'none')
	{
		advanced_div.style.display='block';
		sim_link_div.style.display='inline';
		adv_link_div.style.display='none';
	}
	else
	{
		adv_link_div.style.display='inline';
		advanced_div.style.display='none';
		sim_link_div.style.display='none';
	}
}
