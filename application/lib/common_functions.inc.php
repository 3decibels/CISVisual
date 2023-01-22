<?php

//Returns the MIME type of a file using PECL Fileinfo
function mime_type($filename)
{
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $filename);
	finfo_close($finfo);
	return $mimetype;
}

//Function to strip slashes off of an array of data
function strip_array($array)
{
	foreach ($array as $key => $value)
	{
		if (is_array($value))
		{
			$array[$key] = strip_array($value);
		}
		else
		{
			$array[$key] = stripslashes($value);
		}
	}
	return $array;
}

?>
