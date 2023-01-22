function setFocus()
{
	for (i = 0; i < document.walkthrough.walkSubmit.length; i++)
	{
		if (document.walkthrough.walkSubmit[i].checked)
		{
			input = document.walkthrough.walkSubmit[i].value;
		}
	}
	if ('text' == input)
	{
		document.getElementById('submitText').style.display = 'block';
		document.getElementById('submitFile').style.display = 'none';
	}
	else if ('file' == input)
	{
		document.getElementById('submitText').style.display = 'none';
		document.getElementById('submitFile').style.display = 'block';
	}
}
