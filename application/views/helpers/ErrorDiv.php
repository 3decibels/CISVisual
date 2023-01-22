<?php

class My_View_Helper_ErrorDiv
{
	public function errorDiv($errors, $element)
	{
		$output = '';

		if (isset($errors[$element]))
		{
			$output .= "<div class=\"error\">\n";
			foreach ($errors[$element] as $error)
				$output .= "Error: " . htmlspecialchars($error) . "<br />\n";
			$output .= "</div>\n";
		}

		return $output;
	}
}

?>
