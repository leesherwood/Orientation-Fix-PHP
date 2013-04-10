<?php
/*
 * PHP Orientation Fix (GD) v1.0.0
 * https://github.com/leesherwood/Orientation-Fix-PHP
 *
 * Copyright 2013, "leesherwood" Lee Sherwood
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 *
 * AKA: I couldn't care less what you do with it, 
 *      im just a nice guy and i want to share.
 *      But it would be nice if you left me a little credit :)
 *
 * If you use this, then let me know at: i-played-with-your-git@secure4sure.org
 */

/**
 * This is just a drop of code to seperate the actual working code from something you can use to give it a littletest with, 
 * you can delete this file!
 */ 


require_once 'fix_orientation.php';

$files = glob("images/*");
foreach($files as $file) {
  
  fix_orientation($file);
  
}


?>