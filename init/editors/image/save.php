<?php

define ('ROOT_PATH',	'../../../');
require_once("../../../init/init.php");


$filename = urldecode(@$_REQUEST['filename']);

error_reporting(E_ALL); // force the PHP engine to report all errors

// check if the request contains the uploaded image.
if (isset($_FILES['image']['tmp_name']))
{
	// this is the name of the temporal file created by the PHP engine
	$temp_name = $_FILES['image']['tmp_name'];

    	// The script stores the file to the current directory
    	// and uses the file name suggested by the applet.
		// $filename = $_FILES['image']['name'];

		// but you can assign any file name and destination directory,
    	// for example: $filename = "/images/myfile.jpg";

    	// Try to copy the temporal file to the permanent one.
    	if (copy($temp_name,$filename))
    	{
    	    // Ask the applet to open "view.php" which displays the resulting image.
			// echo "#SETDOCTAG=_blank\n"; // in new window (optinally)
			// echo "#SHOWDOCUMENT=view.php?filename=".urlencode($filename)."\n";
    	}
    	else
    	{
            echo "#SHOWMESSAGE=Can't copy the temporal uploaded file $temp_name to $filename\n";
    	}
}
else
{
	echo("#SHOWMESSAGE=Image is not uploaded\n");
}
?>
