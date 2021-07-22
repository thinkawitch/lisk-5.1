<html>
<head>
<title>Net Imaging. View processed image</title>
</head>

<body bgcolor="#A8A8B8">

<p>
  The processed image has been uploaded to the server.<br>
  This script displays it by means of standard HTML tag &lt;img&gt;
</p>

<table border="1" ><tr>
   <td bgcolor="#808080"><img src="<?php echo @$_GET['filename']; ?>" border="0" ></td>
</tr></table>

</body>
</html>