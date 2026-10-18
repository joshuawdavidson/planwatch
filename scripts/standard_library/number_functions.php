<?php

/*
STANDARD LIBRARY/
	NUMBER_FUNCTIONS.php

functions that manipulate numbers and number-like things
*/



// ROUND_SIGDIG()
//
// rounds a $number to a certain number of significant digits
//------------------------------------------------------------------------------
function round_sigdig($number,$sigdigs)
{
	return round($number,ceil(0 - log10($number)) + $sigdigs);
}

?>