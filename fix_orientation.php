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
 * The main function that does the actual job
 * 
 * Pass a string representing the image path and filename such as /var/www/images/image.jpg
 * And it will change the name of the image to include "orig." (for the sake of not deleting your image if something goes wrong)
 * and then create a new image with the same filename, with the orientation fixed
 * The return bool is not informative and should only be used to to tell your script that there is a proper orientated image available (or not)
 * If you want to know why it failed (theres many reason) then you need to make the return values for informative, or use exceptions.     
 *   
 * @param string the path to the file including the file iteself (absolute path would be advised)
 * @return bool true if successful, false if not
 */  
function fix_orientation($fileandpath) {

  // Does the file exist to start with?
  if(!file_exists($fileandpath))
    return false;
  
  // Get all the exif data from the file
  $exif = read_exif_data($fileandpath, 'IFD0');
   
  // If we dont get any exif data at all, then we may as well stop now
  if(!$exif || !is_array($exif))
    return false;
  
  // I hate case juggling, so we're using loweercase throughout just in case
  $exif = array_change_key_case($exif, CASE_LOWER);
  
  // If theres no orientation key, then we can give up, the camera hasn't told us the 
  // orientation of itself when taking the image, and i'm not writing a script to guess at it!
  if(!array_key_exists('orientation', $exif)) 
    return false;
  
  // Gets the GD image resource for loaded image
  $img_res = get_image_resource($fileandpath);
  
  // If it failed to load a resource, give up
  if(is_null($img_res))
    return false;
  
  // The meat of the script really, the orientation is supplied as an integer, 
  // so we just rotate/flip it back to the correct orientation based on what we 
  // are told it currently is 
  switch($exif['orientation']) {
    
    // Standard/Normal Orientation (no need to do anything, we'll return true as in theory, it was successful)
    case 1: return true; break;
    
    // Correct orientation, but flipped on the horizontal axis (might do it at some point in the future)
    case 2: 
      $final_img = imageflip($img_res, IMG_FLIP_HORIZONTAL);
    break;
    
    // Upside-Down
    case 3: 
      $final_img = imageflip($img_res, IMG_FLIP_VERTICAL);
    break;
    
    // Upside-Down & Flipped along horizontal axis
    case 4:  
      $final_img = imageflip($img_res, IMG_FLIP_BOTH);
    break;
    
    // Turned 90 deg to the left and flipped
    case 5:  
      $final_img = imagerotate($img_res, -90, 0);
      $final_img = imageflip($img_res, IMG_FLIP_HORIZONTAL);
    break;
    
    // Turned 90 deg to the left
    case 6: 
      $final_img = imagerotate($img_res, -90, 0);
    break;
    
    // Turned 90 deg to the right and flipped
    case 7: 
      $final_img = imagerotate($img_res, 90, 0);
      $final_img = imageflip($img_res,IMG_FLIP_HORIZONTAL);
    break;
    
    // Turned 90 deg to the right
    case 8: 
      $final_img = imagerotate($img_res, 90, 0); 
    break;
    
  }
  
  // If theres no final image resource to output for whatever reason, give up
  if(!isset($final_img))
    return false;
  
  //-- rename original (very ugly, could probably be rewritten, but i can't be arsed at this stage)
  $parts = explode("/", $fileandpath);
  $oldname = array_pop($parts);
  $path = implode('/', $parts);
  $oldname_parts = explode(".", $oldname);
  $ext = array_pop($oldname_parts);
  $newname = implode('.', $oldname_parts).'.orig.'.$ext;
  
  rename($fileandpath, $path.'/'.$newname);
  
  // Save it and the return the result (true or false)
  $done = save_image_resource($final_img,$fileandpath);
  
  return $done;

}

/**
 * Simple function which takes the filepath, grabs the extension and returns the GD resource for it
 * Not fool-proof nor the best, but it does the job for now 
 */ 
function get_image_resource($file) {
    
    $img = null;
    $p = explode(".", strtolower($file));
    $ext = array_pop($p);
    switch($ext) {
      
      case "png":
        $img = imagecreatefrompng($file);
        break;
      
      case "jpg":
      case "jpeg":
        $img = imagecreatefromjpeg($file);
        break;
      case "gif":
        $img = imagecreatefromgif($file);
        break;
    
    }  
       
    return $img;
    
}

/**
 * Saves the final image resource to the given location
 * As above it works out the extension and bases its output command on that, not fool proof, but works nonetheless
 */  
function save_image_resource($resource, $location) {
  
    $success = false;
    $p = explode(".", strtolower($location));
    $ext = array_pop($p);
    switch($ext) {
      
      case "png":
        $success = imagepng($resource,$location);
        break;
      
      case "jpg":
      case "jpeg":
        $success = imagejpeg($resource,$location);
        break;
      case "gif":
        $success = imagegif($resource,$location);
        break;
    
    } 
    
    return $success;
  
}


/**
 * My Dev environment is not the latest on this machine. which is lucky as i never bothered to check if imageflip needed a specific version
 * ( been using imagerotate for ages and assumed imageflip would surely be included to )
 * But as it turns out, you need a fairly recent version of PHP for imageflip, so i cobbled together a quick replacement function for those that don't have it
 */   
if(!function_exists('imageflip')) {

  // These are the same constants so this script should be upgrade safe, the values are different no doubt, but that won't hurt!
  define("IMG_FLIP_HORIZONTAL", 1);
  define("IMG_FLIP_VERTICAL", 2);
  define("IMG_FLIP_BOTH", 3);
  
  /**
   * Simple function that takes a gd image resource and the flip mode, and uses rotate 180 instead to do the same thing... Simples!
   */     
  function imageflip($resource, $mode) {
      
      if($mode == IMG_FLIP_VERTICAL || $mode == IMG_FLIP_BOTH)
        $resource = imagerotate($resource, 180, 0);
      
      if($mode == IMG_FLIP_HORIZONTAL || $mode == IMG_FLIP_BOTH)
        $resource = imagerotate($resource, 90, 0);
         
      return $resource;
      
  }
  
}


?>