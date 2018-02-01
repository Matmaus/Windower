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
	//NOTE: baseUrl, basePath and imgsUrl must be ended with slash or backslash
	$windower_links = [
		"baseUrl"  => "",
		"basePath" => "",
		"editUrl"  => "...edit.php",
		"imgsUrl"  => "",
		"sheetUrl" => "...windower_style.css"
	];

	//set as many windower as you wish
	
