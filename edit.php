<?php
	//CONFIG
	$windowerUrl = '........Windower';      // set full URL
	require_once "..........config.php";    // set full PATH

	foreach ($windower_array as $item) {
		if ($item->getId() == $_GET['id']) {
			$windower_item = $item;
		}
	}

	//safe check for correct value of position
	if (isset($_POST['submit'])) {
		if ($_POST['order'] == 0)
			die('You can not set position to 0');
		else if (isset($_GET['mv']) && $_POST['order'] > $windower_item->getLast())
			die('You can not set position to value bigger than count of windows');
		else if ($_POST['order'] > $windower_item->getLast() + 1)
			die('You can not set position to value bigger than count of windows + 1');
	}

	if (isset($_GET['mv'])) {
		if ($windower_item->getDatabase() != NULL) {
			$query = $windower_item->getDatabase()->prepare("
					SELECT * FROM windower
    				WHERE order_id = :id
    				LIMIT 1
					");
			$query->bindParam(':id', $_GET['mv'], PDO::PARAM_INT);
			$query->execute();

			if ($query->rowCount())
				$edit = $query->fetch();
			else
				die('Could not select records from database');
		} else {
			foreach ($windower_item->getFile() as $line) {
				$line     = htmlspecialchars($line);
				$arr_item = explode(';', $line);
				if ($arr_item[0] == $_GET['mv']) {
					$edit = $arr_item;
				}
			}
		}
	}

	//REMOVE
	if (isset($_GET['rm']))
		$windower_item->removeWindowByIndex($_GET['rm']);

	//EDIT
	else if (isset($_GET['mv']) && isset($_POST['submit']))
		$windower_item->editWindow($_GET['mv'], $_POST['order'], $_POST['title'], $_POST['link'], $_POST['img']);

	//ADD
	else if (isset($_POST['submit']))
		$windower_item->addNewWindow($_POST['order'], $_POST['title'], $_POST['link'], $_POST['img']);
?>
<!DOCTYPE html>
<html>
<head>
	<title>VPN</title>
	<link rel="stylesheet" type="text/css" href="<?= $windower_links['sheetUrl']; ?>">
</head>
<body>
	<h1>HOME</h1>
	<?php
		echo $windower_item->makeEditWindow($windowerUrl);
		echo $windower_item->makeEditForm((isset($_GET['mv']) ? $_GET['mv'] : NULL), ((isset($edit)) ? $edit : NULL));
	?>
	<a href="<?= $windower_item->getBaseUrl(); ?>">home</a>
</body>
</html>
