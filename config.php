<?php

	use Matmaus\Windower;

	//NOTE: expect "Windower" folder to be exactly in root directory
	require_once 'Windower.php';
	$windower_array = [];

	//////////////////////////
	//      SETTINGS        //
	//////////////////////////
	// IF YOU HAVE ANY TROUBLES, LOOK AT EXAMPLES

	//setup database if you need it and you did not configured it yet

	//set URLs an PATHs
	//NOTE: baseUrl, filePath and imgsUrl must be ended with slash or backslash
	$windower_links = [
		"baseUrl"  => "",
		"filePath" => "",
		"editUrl"  => "",
		"imgsUrl"  => "",
		"sheetUrl" => ""
	];

	//set as many windower as you wish
	
