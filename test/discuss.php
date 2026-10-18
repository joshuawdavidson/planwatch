<?php
/*
DISCUSS.php

a test of the discussion/trackback/reference web

this will extend planworld.getText and maybe create
a new planworld.getDiscussion or something like that.

my discussion view will probably also be pretty DHTML heavy.
*/

$block_one="
This is block one of the discussion. It is about weevils. Weevils are fun.
So are baseball bats, and even snowshoes. My cat is trying to walk on my
keyboard now, and that's not really related. But soon, he will begin eating
my mouse (hah!) and then we will all be in real trouble.
";

$block_two="
block one writes about !josh:weevils! and that's pretty interesting. He also mentions
!josh:baseball bats! and snowshoes. But most of all, he talks about his cat
eating his !josh:mouse!, which is damn funny if you ask me.
";

function parse_discuss_links($plan)
{
    global $block_one;
    preg_match_all("/!(\S+):([^!]+)!/",$plan,$matches);
    foreach($matches[2] as $i=>$matchterm)
    {
       preg_match_all("/[\s\.]([^\.]*$matchterm"."[^\.]*)[\s\.]/",$block_one,$matches_b[$matchterm]);
       $plan=str_replace($matches[0][$i],"<a href='#' title='".implode(' ',$matches_b[$matchterm][1])."'>$matchterm</a>",$plan);
    }
echo $block_one."<hr>".$plan;
}

parse_discuss_links($block_two);

?>
