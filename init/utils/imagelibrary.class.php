<?php
/**
 * CLASS ImageLibrary
 * @package lisk
 *
 */

class ImageLibrary
{

	function __construct()
	{
		GLOBAL $App;
		$App->Load('filesystem', 'utils');
	}
	
	private function Debug($name, $value, $error=null)
	{
	    GLOBAL $Debug, $App;
		if ($App->debug) $Debug->AddDebug('IL', $name, $value, $error);
	}

	/**
	 * @return boolean
	 * @param string $src Source image file
	 * @param string $dst Destination image file
	 * @param int $dst_w Destination image width
	 * @param int $dst_h Destination image height
	 * @param boolean $only_minimize If true, then image will not be enlarged.
	 * @param boolean $useCrop
	 * @desc Resize image.
	*/
	public function Resize($src, $dst, $dst_w, $dst_h, $only_minimize=true, $useCrop=false)
	{
		GLOBAL $App,$FileSystem;
		
		$this->Debug('Resize', $dst.' '.$dst_w.'x'.$dst_h);

		if (file_exists($dst)) $FileSystem->DeleteFile($dst);
			
		// 3.0 - updated
		if ($dst_w < 1 || $dst_h < 1)
		{
			$FileSystem->CopyFile($src, $dst, '0666');
			return true;
		}

		$file_info = getimagesize($src);
		$src_w = $file_info[0];
		$src_h = $file_info[1];
		$src_type = $file_info[2]; // file type

		$origDstW = $dst_w;
		$origDstH = $dst_h;
		list ($dst_w, $dst_h) = $this->GetResizeSize($src_w, $src_h, $dst_w, $dst_h, $useCrop);

		// only minimize
		if ($only_minimize && $dst_w > $src_w && $dst_h > $src_h)
		{
			$FileSystem->CopyFile($src, $dst, '0666');
			return true;
		}

		switch ($App->imageLibType)
		{
			case 1:
				// if Gif is unsupported just copy
				if (!$this->CheckType($src_type))
				{
					$FileSystem->CopyFile($src, $dst, '0666');
					return false;
				}

				$src_im = $this->CreateFromFile($src, $src_type);
				$dst_im = imagecreate($dst_w,$dst_h);
				imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
				$this->OutputToFile($dst_im, $dst, $src_type);
				$FileSystem->ChangeMode($dst);
				break;

			case 2:
				if (!$this->CheckType($src_type))
				{
					$FileSystem->CopyFile($src, $dst, '0666');
					return false;
				}
				$src_im = $this->CreateFromFile($src, $src_type);
				$dst_im = imagecreatetruecolor($dst_w,$dst_h);
				imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
				$this->OutputToFile($dst_im, $dst, $src_type);
				
				if ($useCrop)
				{
					$ox = intval(($dst_w-$origDstW)/2);
					$oy = intval(($dst_h-$origDstH)/2);
					
					$this->Debug('Crop', $dst.' '.$ox.'x'.$oy);
					
					$imgCropped = imagecreatetruecolor($origDstW, $origDstH);
					$file_info = getimagesize($dst);
					$dstType = $file_info[2]; // file type
					$imgResized = $this->CreateFromFile($dst, $dstType);
					imagecopyresampled($imgCropped, $imgResized, 0, 0, $ox, $oy, $origDstW,$origDstH, $origDstW, $origDstH);
					$this->OutputToFile($imgCropped, $dst, $src_type);
				}
				
				$FileSystem->ChangeMode($dst);
				break;

			case 3:
				$FileSystem->CopyFile($src, $dst, '0666');
				$cmd = $App->imageMagickPath."mogrify -resize {$dst_w}x{$dst_h} $dst";
				$res = `$cmd 2>&1`;
				
				if ($useCrop)
				{
					$ox = intval(($dst_w-$origDstW)/2);
					$oy = intval(($dst_h-$origDstH)/2);
					if ($ox>=0) $ox = "+{$ox}";
					if ($oy>=0) $oy = "+{$oy}";
					
					$this->Debug('Crop', $dst.' '.$ox.'x'.$oy);
					
					$cmd = $App->imageMagickPath."mogrify -crop {$origDstW}x{$origDstH}{$ox}{$oy} $dst";
					$res = `$cmd 2>&1`;
				}
				
				break;
		}

		return true;
	}


	public function ImageCut($name1, $name2, $left, $top, $width, $height)
	{
		GLOBAL $App, $FileSystem;

		if ($name1 == $name2) return true;

		if ($left == 0 && $top == 0 && $width == '' && $height == '')
		{
			$FileSystem->CopyFile($name1, $name2);
			return true;
		}

		$file_info = getimagesize($name1);
		$file_type = $file_info[2];

		$srcX = $left;
		$srcY = $top;
		$dstX = 0;
		$dstY = 0;
		$srcW = $width;
		$srcH = $height;
		$dstW = $width;
		$dstH = $height;
		
		switch ($App->imageLibType)
		{
		    case 1:
		        if (!$this->CheckType($file_type))
		        {
    				copy($name1, $name2);
    				return false;
    			}
    			$src_im = $this->CreateFromFile($name1, $file_type);
    			$dst_im = imagecreate($dstW, $dstH);
    			imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    			$this->OutputToFile($dst_im, $name2, $file_type);
    			chmod($name2, 0666);
		        break;
		        
		    case 2:
		        if (!$this->CheckType($file_type))
		        {
    				copy($name1, $name2);
    				return false;
    			}
    			$src_im = $this->CreateFromFile($name1, $file_type);
    			$dst_im = imagecreatetruecolor($dstW, $dstH);
    			imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    			$this->OutputToFile($dst_im, $name2, $file_type);
    			chmod($name2, 0666);
		        break;
		        
		    case 3:
		       	/*copy($name1, $name2);
    			chmod($name2, 0666);
    			$cmd = JPEG_UTIL_PATH."mogrify -crop ".$dstW."x".$dstH." $srcX $srcY ".$name2;
    			$res = `$cmd 2>&1`;
    			//print $cmd." - ".$res.BR;*/
    			if (!$this->CheckType($file_type))
    			{
    				copy($name1, $name2);
    				return false;
    			}
    			$src_im = $this->CreateFromFile($name1, $file_type);
    			$dst_im = imagecreate($dstW, $dstH);
    			imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    			$this->OutputToFile($dst_im, $name2, $file_type);
    			chmod($name2, 0666);
		        break;
		}

		return true;
	}

	/**
	 *  Old code, need to be refactored
	 */
	public function ImageQuality($name1, $name2, $quality)
	{
		if ($name1 == $name2) return true;

		$file_info = getimagesize($name1);
		$srcW = $file_info[0];
		$srcH = $file_info[1];
		$file_type = $file_info[2];

		if ($file_type != 2) return false;

		if (JPEG_LIB == 1)
		{
			$src_im = imagecreatefromjpeg($name1);
			$dst_im = imagecreate($srcW,$srcH);
			imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $srcW, $srcH, $srcW, $srcH);
			imagejpeg($dst_im, $name2, $quality);
			chmod($name2, 0666);
		}

		if (JPEG_LIB == 2)
		{
			$src_im = imagecreatefromjpeg($name1);
			$dst_im = imagecreatetruecolor($srcW,$srcH);
			imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $srcW, $srcH, $srcW, $srcH);
			imagejpeg($dst_im, $name2, $quality);
			chmod($name2, 0666);
		}

		if (JPEG_LIB == 3)
		{
			copy($name1, $name2);
			chmod($name2, 0666);
			//$cmd = JPEG_UTIL_PATH."mogrify -resize ".$srcW."x".$srcH." -quality $quality ".$name2;
			$cmd = JPEG_UTIL_PATH."mogrify -resize ".$srcW."x".$srcH." ".$name2;
			$res = `$cmd 2>&1`;
			//print $cmd." - ".$res;
		}

		return true;
	}

	public function GetResizeSize($width1, $height1, $width2, $height2, $useCrop=false)
	{
		// 3.0 updated
		$k1 = $width1/$height1;
		$k2 = $width2/$height2;

		$q = $k1/$k2;
		
		/**
		 * esli useCrop - to nado resaizit' bolshe polozhennogo,
		 * dlia togo, chtoby potom viresat' centr fiksirovannogo razmera
		 */

		if ($k1 >= 1)
		{

			if ($q >= 1)
			{
				$width = $width2;
				$height = $width/$k1;
				
				if ($useCrop)
				{
					$height = $height2;
					$width = $width2*$q;
				}
				
			}
			else
			{
				$width = $width2*$q;
				$height = $height2;
				
				if ($useCrop)
				{
					$width = $width2;
					$height = $width/$k1;
				}
			}

		}
		else
		{

			if ($q >= 1)
			{
				$height = $height2/$q;
				$width = $height*$k1;
				
				if ($useCrop)
				{
					$height = $height2;
					$width = $height*$k1;
				}
				
			}
			else
			{
				$height = $height2;
				$width = $height*$k1;
				
				if ($useCrop)
				{
					$height = $height2/$q;
					$width = $height*$k1;
				}
			}
			
		}

		return array(round($width), round($height));
	}
	
	
	public function CreateFromFile($file, $type)
	{
		switch ($type)
		{
			case 1:		return imagecreatefromgif($file); break; //GIF
			case 2:		return imagecreatefromjpeg($file); break; //JPG
			case 3:		return imagecreatefrompng($file); break; //PNG
			case 4:		break; //SWF
			case 5:		break; //PSD
			case 6:		break; //BMP
			case 7:		break; //TIFF(intel byte order)
			case 8:		break; //TIFF(motorola byte order)
			case 9:		break; //JPC
			case 10:	break; //JP2
			case 11:	break; //JPX
			case 12:	break; //JB2
			case 13:	break; //SWC
			case 14:	break; //IFF
		}
		return null;
	}


	public function OutputToFile($im, $file, $type)
	{
	    $this->Debug('gd / output to file', $file);
	    
		switch ($type)
		{
			case 1:		imagegif($im, $file); break; //GIF
			case 2:		imagejpeg($im, $file); break; //JPG
			case 3:		imagepng($im, $file); break; //PNG
			case 4:		break; //SWF
			case 5:		break; //PSD
			case 6:		break; //BMP
			case 7:		break; //TIFF(intel byte order)
			case 8:		break; //TIFF(motorola byte order)
			case 9:		break; //JPC
			case 10:	break; //JP2
			case 11:	break; //JPX
			case 12:	break; //JB2
			case 13:	break; //SWC
			case 14:	break; //IFF
		}
	}


	/**
	 * @return boolean True - if given image type supported
	 * @param int $type Image type
	 * @desc Check if given image type supported by image library.
	*/
	function CheckType($type)
	{
		GLOBAL $App;
		// 3.0 updated
		switch ($type)
		{
			case 1:		return ($App->imageLibType == 3 || (@imagetypes() & IMG_GIF)); //GIF
			case 2:		return ($App->imageLibType == 3 || (@imagetypes() & IMG_JPG)); //JPG
			case 3:		return ($App->imageLibType == 3 || (@imagetypes() & IMG_PNG)); //PNG
			/*case 4:		break; //SWF
			case 5:		break; //PSD
			case 6:		break; //BMP
			case 7:		break; //TIFF(intel byte order)
			case 8:		break; //TIFF(motorola byte order)
			case 9:		break; //JPC
			case 10:	break; //JP2
			case 11:	break; //JPX
			case 12:	break; //JB2
			case 13:	break; //SWC
			case 14:	break; //IFF*/
		}
		return false;
	}


	/**
	 * @return string Image type name (GIF, JPG, PNG etc.)
	 * @param int $type Image type.
	 * @desc Get image type description.
	*/
	function GetTypeName($type)
	{
		switch ($type)
		{
			case 1:		return 'GIF'; break; //GIF
			case 2:		return 'JPG'; break; //JPG
			case 3:		return 'PNG'; break; //PNG
			case 4:		return 'SWF'; break; //SWF
			case 5:		return 'PSD'; break; //PSD
			case 6:		return 'BMP'; break; //BMP
			case 7:		return 'TIFF'; break; //TIFF(intel byte order)
			case 8:		return 'TIFF'; break; //TIFF(motorola byte order)
			case 9:		break; //JPC
			case 10:	break; //JP2
			case 11:	break; //JPX
			case 12:	break; //JB2
			case 13:	break; //SWC
			case 14:	break; //IFF
		}

		return '';
	}

}

$GLOBALS['ImageLibrary'] = new ImageLibrary();

?>