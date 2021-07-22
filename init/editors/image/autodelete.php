<?php
/*-----------------------------------------------------------------
 This script can be used to delete downloaded files automatically.

 It reads the file passed by the URL parameter "filename",
 outputs the file data as the response, and erases the file.
 
 To delete original user's images automatically open 'edit.php'
 and point this script as the value of the applet's parameter "load"
 instead passing the image's URL directly, for example:
 
 <param name="load" value="autodelete.php?filename=<? echo $filename ?>" >
 
 Attention !
   Be careful if you wish to modify this script.
   It produces binary response and changes HTTP headers,
   so no one character (including space or new line) may appear
   before and after the script brackets in this file.

-----------------------------------------------------------------*/

$filename = @$_GET['filename'];

if (false !== ($fd = fopen($filename, "rb")))
{
    // read the file data
    $data = fread($fd, filesize($filename));
    fclose($fd);
    
    // send it to the browser
    header("Content-type: image/jpeg");
    header("Content-Disposition: inline; filename=$filename;");
    print $data;

    // erase the file
    unlink($filename);
}
else
{
    echo "Can't open file $filename";
}
?>