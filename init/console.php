<?php

session_start();
$actionPost = isset($_POST['action']) ? trim($_POST['action']) : null;
$actionGet = isset($_GET['action']) ? trim($_GET['action']) : null;

switch ($actionPost)
{
	case 'login':
		Login($_POST['password']);
		header('location: ?');
		break;
}

function Login($password)
{
	if ($password == 'developer')
	{
		$_SESSION['console'] = true;
	}
}

function Auth()
{
	if (isset($_SESSION['console']) && $_SESSION['console'] !== true) return false;
	else return true;
}


function show($values)
{
	echo "<pre>";
	print_r($values);
	echo "</pre>";
}


if (!Auth())
{
?>
<table width="300" border="0" cellpadding="5" cellspacing="1" bgcolor="#333333">
<form method="post">
  <tr>
    <td bgcolor="#CCCCCC"><input name="password" type="password" id="password" style="width:100%" /></td>
    <td width="1%" bgcolor="#CCCCCC"><input name="action" type="hidden" id="action" value="login" />
    <input type="submit" name="Submit" value="Enter" /></td>
  </tr>
  </form>
</table>
<?php
    die("Auth required");
}
else
{
?>
<a href="?action=info">PHP Info</a> | <a href="?action=sql">SQL console</a> | <a href="?action=highlight">Code Highlight</a>
<hr>
<?php
}

switch ($actionGet)
{
	case 'info':
		phpinfo();
		break;
		
	case 'sql':
		SqlConsole();
		break;
		
	case 'highlight':
		CodeHighlight();
		break;
}

function CodeHighlight() {
?>
<form id="form1" name="form1" method="post" action="">
  <p>
    <textarea name="code" cols="80" rows="10"></textarea>
</p>
  <p>
    <input type="submit" name="Submit" value="Highlight" />
    *code must starts with &lt;? or &lt;?php
</p>
</form>

<?php

$code = isset($_POST['code']) ? trim(stripslashes($_POST['code'])) : null;
highlight_string($code);
}

function SqlConsole()
{
    
    define('HOST', @$_POST['host']);
	define('USER', @$_POST['user']);
	define('PASSWORD', @$_POST['password']);
	define('DBNAME', @$_POST['db']);
	
	$sql = isset($_POST['sql']) ? trim($_POST['sql']) : null;
	
	?>
  <table width="600" border="0" cellpadding="10" cellspacing="0">
<form id="form1" name="form1" method="post" action="">
  <tr>
    <td nowrap="nowrap">host:
      <input name="host" type="text" id="host" value="<?php echo HOST ?>" />
      DB:
      <input name="db" type="text" id="db" value="<?php echo DBNAME ?>" />
      user:
      <input name="user" type="text" id="user" value="<?php echo USER ?>" />
      password:
      <input name="password" type="text" id="password" value="<?php echo PASSWORD ?>" /></td>
  </tr>
  <tr>
      <td><textarea name="sql" cols="70" rows="6" id="sql"><?php echo $sql ?></textarea></td>
    </tr>
    <tr>
      <td><label>
        <input type="submit" name="Submit" value="Submit" />
      </label></td>
    </tr>
 </form> </table>

	<?php
	
	if ($sql != '')
	{
        mysql_connect(HOST, USER, PASSWORD) or die ('Could not connect');
			
		$res = mysql_select_db(DBNAME);

		if (!$res)
		{
			echo 'Databse set error.';
			exit;
		}
		
		$res = mysql_query($sql);
		if(!$res)
		{
			$error = mysql_error();
			echo "Error. ".$error;
		}
		
		$sql_type = strtolower(substr(trim($sql), 0, 5));
		$return = array();
		if ($sql_type == 'inser')
		{
			echo "New record inserted. Id=". mysql_insert_id();
		}
		elseif ($sql_type == 'selec' || $sql_type == 'show ')
		{
			//fetch result
			while (false !== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
			{
				$return[] = $row;
			}

			show($return);
		}
		
		echo 'Done.';
	}
}
?>