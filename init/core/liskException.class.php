<?php
/**
 * CLASS LiskException
 * @package lisk
 *
 */
class LiskException extends Exception
{
	private $showErrorDetails	= false;	//if set to true - shows full exception message
	private $showTrace			= true;     //if set to true shows trace
	
    // Redefine the exception so message isn't optional
    public function __construct($message='Error msg. undefined', $code = 0)
    {
    	//init showErrorDetails
    	GLOBAL $App;
    	$this->showErrorDetails = $App->debug;
    	
        // make sure everything is assigned properly
        parent::__construct($message, $code);
    }
    
    public function ShowError()
    {
    	header('HTTP/1.1 500 Internal Server Error');
    	
        ?>
        <p>&nbsp;</p>
		<table width="500" border="0" align="center" cellpadding="5" cellspacing="1" bgcolor="#999999" >
		  <tr>
		    <td colspan="2" bgcolor="#FF8D71"><strong>Internal error.</strong></td>
		  </tr>
		<?php
		if ($this->showErrorDetails) $this->ShowErrorDetails();
		else $this->ShowErrorNoDetails();
		?>
		</table>
        <?php
        
        if (!$this->showErrorDetails)
        {
	        GLOBAL $App;
	        $App->Destroy();
        }
    }
    
    private function ShowErrorDetails()
    {
    	?>
		  <tr>
		    <td width="1%" nowrap="nowrap" bgcolor="#FFFFFF">error message:</td>
		    <td bgcolor="#FFFFFF"><?php echo $this->getMessage(); ?></td>
		  </tr>
		  <tr>
		    <td bgcolor="#FFFFFF">file:</td>
		    <td bgcolor="#FFFFFF"><?php echo $this->getFile(); ?></td>
		  </tr>
		  <tr>
		    <td bgcolor="#FFFFFF">line:</td>
		    <td bgcolor="#FFFFFF"><?php echo $this->getLine(); ?></td>
		  </tr>
    	<?php
    	
    	if ($this->showTrace) $this->ShowTrace();
    }
    
    private function ShowErrorNoDetails()
    {
    	?>
		  <tr>
		    <td colspan="2" bgcolor="#FFFFFF"><p>An error appeared while rendering this page. Please contact site administrator and specify page url to resolve the problem. </p>
		    <p>page url: <strong><?php echo Navigation::GetCurUrl(); ?></strong></p>
		    </td>
		  </tr>
    	<?php
    }
    
	private function ShowTrace()
	{
	    GLOBAL $App;
		$trace = debug_backtrace();
		
	    $str = '';
	    foreach($trace as $tr)
	    {
	        $file = str_replace('\\','/', @$tr['file']);
	        $cut = strpos($file, $App->sysRoot);
	        if($cut!==false)
	        {
	            $file = substr($file, $cut+strlen($App->sysRoot));
	        }
	    	$str .=
	    		 $file
	    		 . ':' . @$tr['line']
	    		 . ' ' . @$tr['class']
	    		 . @$tr['type'] . $tr['function']
	    		 . '('./*@implode(',', $tr['args']).*/')';
	    	$str .= '<br />';
	    }
	    
	    ?>
		  <tr>
		    <td bgcolor="#FFFFFF">trace:</td>
		    <td bgcolor="#FFFFFF"><?php print_r($str); ?></td>
		  </tr>
	    <?php
	}
}
?>