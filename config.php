<?php

	use Matmaus\Windower;

	//expect "Windower" file to be exactly in root file
	require_once 'Windower.php';
	$windower_array = [];

	//      SETTINGS        //

	//set URLs an PATHs
	$windower_links = [
		"baseUrl"  => "http://localhost/myPage/",
		"filePath" => "C:\\dir\\www\\myPage\\",
		"editUrl"  => "http://localhost/myPage/Windower/edit.php",
		"imgsUrl"  => "http://localhost/myPage/",
		"sheetUrl" => "http://localhost/myPage/Windower/windower_style.css"
	];

	//set as many windower as you wish
	