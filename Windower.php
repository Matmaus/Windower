<?php

	namespace Matmaus;

	class Windower
	{
		private $id;
		private $templateId;
		private $limit;
		private $database;
		private $fileName;
		private $file;
		private $baseUrl;
		private $editUrl;
		private $sheetUrl;
		private $imgsUrl;
		private $reverse;
		private $last;

		/**
		 * Windower constructor.
		 *
		 * @param int    $id             Unique windower ID
		 * @param int    $templateId     ID of used template
		 * @param int    $limit          OPTIONAL Maximal amount of windows to be showed, NULL means as many windows as is records
		 * @param \PDO   $database       OPTIONAL PDO object of database
		 * @param string $fileName       OPTIONAL filename of file with records
		 * @param array  $windower_links array with URLs
		 * @param bool   $reverse        OPTIONAL if true, text will be showed below, default is false
		 */
		public function __construct($id, $templateId, $limit, $database, $fileName, $windower_links, $reverse = FALSE)
		{
			if (!is_numeric($id))
				die('ID is not a valid number');

			if (!is_numeric($templateId))
				die('Template ID is not a valid number');

			if (!is_numeric($limit) && $limit != NULL)
				die('limit is not a valid number');

			if ($database == NULL && $fileName == NULL)
				die('Nor database neither filename was given');

			if ($database != NULL && $fileName != NULL)
				die('Both database and file was given at the same object');

			if ($fileName != NULL) {
				$this->fileName = $windower_links['filePath'].$fileName;
				$this->file     = file($this->fileName);
				if (!$this->file)
					die('Problem with opening a file');
			}
			if ($database != NULL) {
				$stmt = $database->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'windower_db'");
				if ($stmt->fetchColumn() == 0)
					die("Database is not set correctly");
			}
			if ($reverse != FALSE && $reverse != TRUE)
				die('$reverse can only accept values true or false');

			//get max order_id from table
			if ($database != NULL) {
				$query = $database->prepare("
					SELECT max(order_id) FROM windower WHERE win_id = :id
				");
				$query->execute([
					                'id' => $id
				                ]);
				$maxID      = $query->fetch();
				$this->last = $maxID[0];
			} else {
				foreach ($this->file as $line) {
					if (!ctype_space($line)) {
						$line       = htmlspecialchars($line);
						$arr_item   = explode(';', $line);
						$this->last = $arr_item[0];
					}
				}
			}

			$this->id         = $id;
			$this->templateId = $templateId;
			$this->limit      = $limit;
			$this->database   = $database;
			$this->reverse    = $reverse;
			$this->baseUrl    = filter_var($windower_links['baseUrl'], FILTER_SANITIZE_URL);
			$this->editUrl    = filter_var($windower_links['editUrl'], FILTER_SANITIZE_URL);
			$this->sheetUrl   = filter_var($windower_links['sheetUrl'], FILTER_SANITIZE_URL);
			$this->imgsUrl    = filter_var($windower_links['imgsUrl'], FILTER_SANITIZE_URL);
		}

		/**
		 * @return string
		 */
		public function getBaseUrl()
		{
			return $this->baseUrl;
		}

		/**
		 * @return array
		 */
		public function getFile()
		{
			return $this->file;
		}

		/**
		 * @return int
		 */
		public function getId()
		{
			return $this->id;
		}

		/**
		 * @return \PDO
		 */
		public function getDatabase()
		{
			return $this->database;
		}

		/**
		 * @return int
		 */
		public function getLast()
		{
			return $this->last;
		}

		/**
		 * Get all records from database
		 *
		 * @param $offset
		 *
		 * @return array
		 */
		private function getRecordsFromDatabase($offset = NULL)
		{
			//get all records if nor offset neither limit was set, or if offset is set to -1
			if ($offset == NULL && $this->limit == NULL || $offset == -1)
				$query = $this->database->prepare("SELECT * FROM windower WHERE win_id = :id ORDER BY order_id");
			else {
				if ($offset != NULL && $this->limit == NULL)
					die('Offset is set, but Limit is not');
				else if ($offset == NULL && $this->limit != NULL)
					$query = $this->database->prepare("SELECT * FROM windower WHERE win_id = :id ORDER BY order_id LIMIT :limit");
				else if ($offset != NULL && $this->limit != NULL) {
					$query = $this->database->prepare("SELECT * FROM windower WHERE win_id = :id ORDER BY order_id LIMIT :offset, :limit");
					$query->bindParam(':offset', $offset);
				}
				$query->bindParam(':limit', $this->limit);
			}
			$query->bindParam(':id', $this->id);
			$query->execute();

			if ($query->rowCount())
				$results = $query->fetchAll();
			else
				$results = [];

			return $results;
		}

		/**
		 * Generate HTML code for single edit side window
		 *
		 * @param $arr_item
		 *
		 * @return string
		 */
		private function generateWindow($arr_item)
		{
			//reverse window
			if ($this->reverse) {
				$window = "<div class=\"windower_side\">
									<div class=\"windower_side_body\">
										<a href=\" ".$arr_item[2]." \" class=\"windower_side_link\">
											<img src=\" ".$this->imgsUrl.$arr_item[3]." \" class=\"windower_side_img\">
										</a>
									</div>
									<div class=\"windower_side_top\">
										<h2>".$arr_item[1]."</h2>
									</div>
								</div>
								";
			}   //common window
			else {
				$window = "<div class=\"windower_side\">
									<div class=\"windower_side_top\">
										<h2>".$arr_item[1]."</h2>
									</div>
									<div class=\"windower_side_body\">
										<a href=\" ".$arr_item[2]." \" class=\"windower_side_link\">
											<img src=\" ".$this->imgsUrl.$arr_item[3]." \" class=\"windower_side_img\">
										</a>
									</div>
								</div>
								";
			}

			return $window;
		}

		/**
		 * Generate HTML code for displaying windows
		 *
		 * @param int|null $first First visible window
		 *
		 * @return string
		 */
		public function makeWindow($first = NULL)
		{
			//check if any or both arguments were set
			if ($first != NULL && !is_numeric($first))
				die('makeWindow() has not valid parameter $first');

			//check if is not set offset only
			if ($first != NULL && $this->limit == NULL)
				die('Offset was set, but Limit was not set');

			//begin of HTML
			$windows = "<div class=\"windower_main_".$this->templateId."\">";

			//if database is set
			if ($this->database != NULL) {
				foreach ($this->getRecordsFromDatabase($first) as $arr_item) {
					$windows .= $this->generateWindow($arr_item);
				}
			} //if file is set
			else {
				$maxWindows = $this->limit;

				foreach ($this->file as $line) {
					if (!ctype_space($line)) {
						$line     = htmlspecialchars($line);
						$arr_item = explode(';', $line);

						//if $first is set, skip everything before $first
						if ($first != NULL)
							if ($arr_item[0] < $first)
								continue;

						$windows .= $this->generateWindow($arr_item);

						//limitation
						if ($maxWindows != NULL)
							if (--$maxWindows == 0)
								break;
					}
				}
			}

			return $windows .= "</div>";
		}

		/**
		 * Generate HTML code for header link including style sheets
		 * @return string
		 */
		public function makeSheetsLink()
		{
			return "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->sheetUrl."\">";
		}

		/**
		 * Generate HTML code for edit button
		 * @return string
		 */
		public function makeEditButton()
		{
			return "<a href=\"".$this->editUrl."?id=".$this->id."\" class=\"windower_edit_button_".$this->templateId."\">edit</a>";
		}

		/**
		 * Generate HTML code for single side window
		 *
		 * @param array  $arr_item
		 * @param string $windowerUrl
		 *
		 * @return string
		 */
		private function generateEditWindow($arr_item, $windowerUrl)
		{
			//reverse window
			if ($this->reverse) {
				$window = "<div class=\"windower_side\">
									<div class=\"windower_side_body\">
										<a href=\" ".$this->editUrl."?id=".$this->id."&mv=".$arr_item[0]." \" class=\"windower_side_edit_link\">
											<img src=\"".$windowerUrl."/edit.png\" class=\"windower_side_edit_img\">
										</a>
										<a href=\" ".$this->editUrl."?id=".$this->id."&rm=".$arr_item[0]." \" class=\"windower_side_edit_link\">
											<img src=\"".$windowerUrl."/remove.png\" class=\"windower_side_edit_img\">
										</a>
									</div>
									<div class=\"windower_side_top\">
										<h2>".$arr_item[1]."</h2>
									</div>
								</div>
								";
			}   //common window
			else {
				$window = "<div class=\"windower_side\">
									<div class=\"windower_side_top\">
										<h2>".$arr_item[1]."</h2>
									</div>
									<div class=\"windower_side_body\">
										<a href=\" ".$this->editUrl."?id=".$this->id."&mv=".$arr_item[0]." \" class=\"windower_side_edit_link\">
											<img src=\"".$windowerUrl."/edit.png\" class=\"windower_side_edit_img\">
										</a>
										<a href=\" ".$this->editUrl."?id=".$this->id."&rm=".$arr_item[0]." \" class=\"windower_side_edit_link\">
											<img src=\"".$windowerUrl."/remove.png\" class=\"windower_side_edit_img\">
										</a>
									</div>
								</div>
								";
			}

			return $window;
		}

		/**
		 * Generate HTML code for displaying windows in edit mode
		 *
		 * @param $windowerUrl
		 *
		 * @return string
		 */
		public function makeEditWindow($windowerUrl)
		{
			//$windowerUrl = filter_var($windowerUrl, FILTER_SANITIZE_URL);

			$windows = "<div class=\"windower_main_".$this->templateId."\">";

			//if database is set
			if ($this->database != NULL) {
				foreach ($this->getRecordsFromDatabase(-1) as $arr_item) {
					$windows .= $this->generateEditWindow($arr_item, $windowerUrl);
				}
			} //if file is set
			else {
				foreach ($this->file as $line) {
					if (!ctype_space($line)) {
						$line     = htmlspecialchars($line);
						$arr_item = explode(';', $line);

						$windows .= $this->generateEditWindow($arr_item, $windowerUrl);
					}
				}
			}

			return $windows .= "</div>";
		}

		/**
		 * Generate HTML code for FORM
		 *
		 * @param int|null   $mv
		 * @param array|null $edit
		 *
		 * @return string
		 */
		public function makeEditForm($mv = NULL, $edit = NULL)
		{
			if (!is_numeric($mv) && $mv != NULL)
				die('$mv must be a number');

			$form = "<div>";

			if ($mv != NULL)
				$form .= "<form action=\"".$this->editUrl."?id=".$this->id."&mv=".$mv."\" method=\"post\">";

			else
				$form .= "<form action='' method=\"post\">";

			$form .= "<input type=\"text\" name=\"title\" placeholder=\"Youtube\" value=\"".(($edit != NULL) ? $edit[1] : '')."\">
					<input type=\"text\" name=\"link\" placeholder=\"https://www.youtube.com/\" value=\"".(($edit != NULL) ? $edit[2] : '')."\">
					<input type=\"text\" name=\"img\" placeholder=\"youtube.png\" value=\"".(($edit != NULL) ? $edit[3] : '')."\">
					<input type=\"text\" name=\"order\" value=\"".(($edit != NULL) ? $edit[0] : ($this->last + 1))."\">
					<input type=\"submit\" name=\"submit\" value=\"submit\">
				</form>
			</div>";

			return $form;
		}

		/**
		 * Write all changes to the file
		 *
		 * @param $contents
		 */
		private function writeToFile($contents)
		{
			$contents = implode("\n", $contents);
			$contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $contents);
			file_put_contents($this->fileName, $contents);
			header("Location: ".$this->editUrl."?id=".$this->id);
		}

		/**
		 * Remove record with specified ID, other may be changed too, because of necessary moving
		 *
		 * @param int $index
		 */
		public function removeWindowByIndex($index)
		{
			if (!is_numeric($index))
				die('Index must be a number');

			//if database is set
			if ($this->database != NULL) {
				//delete single row
				$delete_query = $this->database->prepare("
					DELETE FROM windower
    				WHERE order_id = :order_id AND win_id = :id
    				LIMIT 1
					");
				$delete_query->execute([
					                       'order_id' => $index,
					                       'id'       => $this->id
				                       ]);
				//edit indexes of upper rows
				$edit_query = $this->database->prepare("
					UPDATE windower 
					SET order_id = order_id - 1 
					WHERE order_id > :order_id AND win_id = :id
				");
				$edit_query->execute([
					                     'order_id' => $index,
					                     'id'       => $this->id
				                     ]);
				header("Location: ".$this->editUrl."?id=".$this->id);
			} //if file is set
			else {
				foreach ($this->file as $line) {
					$line     = htmlspecialchars($line);
					$arr_item = explode(';', $line);
					if ($arr_item[0] < $index) {
						$contents[ $arr_item[0] ] = $line;
					} else if ($arr_item[0] > $index) {
						$arr_item[0]--;
						$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
						$contents[ $arr_item[0] ] = $line;
					}
				}
				$this->writeToFile($contents);
			}
		}

		/**
		 * Add new record
		 *
		 * @param int    $order
		 * @param string $title
		 * @param string $link
		 * @param string $img
		 */
		public function addNewWindow($order, $title, $link, $img)
		{
			if (!is_numeric($order))
				die('Index must be a number');

			//if database is set
			if ($this->database != NULL) {
				//edit indexes of upper rows
				if ($order <= $this->last) {
					$edit_query = $this->database->prepare("
						UPDATE windower 
						SET order_id = order_id + 1 
				  		WHERE order_id >= :order_id AND win_id = :id
				  		ORDER BY order_id DESC
					");
					$edit_query->execute([
						                     'order_id' => $order,
						                     'id'       => $this->id
					                     ]);
				}

				//add single row
				$add_query = $this->database->prepare("
					INSERT INTO windower (order_id, title, link, img, win_id)
    				VALUES (:order_id, :title, :link, :img, :id)
					");
				$add_query->execute([
					                    'order_id' => $order,
					                    'title'    => $title,
					                    'link'     => $link,
					                    'img'      => $img,
					                    'id'       => $this->id
				                    ]);

				header("Location: ".$this->editUrl."?id=".$this->id);
			} //if file is set
			else {
				$newLine    = $order.";".$title.";".$link.";".$img;
				$not_middle = TRUE;

				foreach ($this->file as $line) {
					$line     = htmlspecialchars($line);
					$arr_item = explode(';', $line);

					// old == new
					if ($arr_item[0] == $order) {
						$not_middle               = FALSE;
						$contents[ $arr_item[0] ] = $newLine;
						$arr_item[0]++;
						$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
						$contents[ $arr_item[0] ] = $line;
					} // old < new
					else if ($arr_item[0] < $order) {
						$contents[ $arr_item[0] ] = $line;
					} // old > new
					else if ($arr_item[0] > $order) {
						$arr_item[0]++;
						$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
						$contents[ $arr_item[0] ] = $line;
					}
				}

				if ($not_middle)
					array_push($contents, $newLine);

				$this->writeToFile($contents);
			}
		}

		/**
		 * Edit record
		 *
		 * @param int    $mv
		 * @param int    $order
		 * @param string $title
		 * @param string $link
		 * @param string $img
		 */
		public function editWindow($mv, $order, $title, $link, $img)
		{
			if (!is_numeric($mv))
				die('Index must be a number');

			//if database is set
			if ($this->database != NULL) {
				if ($mv == $order) {
					//edit single row
					$query = $this->database->prepare("
						UPDATE windower 
						SET title = :title, link = :link, img = :img
    				    WHERE order_id = :order_id AND win_id = :id
    				    LIMIT 1
					");
					$query->execute([
						                'order_id' => $order,
						                'title'    => $title,
						                'link'     => $link,
						                'img'      => $img,
						                'id'       => $this->id
					                ]);
				} else {
					//move editing to position 0
					$queryF = $this->database->prepare("
						UPDATE windower 
						SET order_id = 0
    				    WHERE order_id = :order_id AND win_id = :id
    				    LIMIT 1
					");
					$queryF->execute([
						                 'order_id' => $mv,
						                 'id'       => $this->id
					                 ]);

					//edit indexes of other rows
					if ($mv < $order) {
						$queryS = $this->database->prepare("
							UPDATE windower 
							SET order_id = order_id - 1 
				  		    WHERE order_id > :mv AND order_id <= :new_order AND win_id = :id
						");
					} else if ($mv > $order) {
						$queryS = $this->database->prepare("
							UPDATE windower 
							SET order_id = order_id + 1 
				  		    WHERE order_id < :mv AND order_id >= :new_order AND win_id = :id
				  		    ORDER BY order_id DESC
						");
					}
					try {
						$queryS->execute([
							                 'mv'        => $mv,
							                 'new_order' => $order,
							                 'id'        => $this->id
						                 ]);
					} catch (\PDOException $e) {
						echo $e;
						var_dump($e);
					}
					//move editing to new position
					$queryT = $this->database->prepare("
						UPDATE windower 
						SET order_id = :order_id, title = :title, link = :link, img = :img
    				    WHERE order_id = 0 AND win_id = :id
    				    LIMIT 1
					");
					$queryT->execute([
						                 'order_id' => $order,
						                 'title'    => $title,
						                 'link'     => $link,
						                 'img'      => $img,
						                 'id'       => $this->id
					                 ]);
				}
				header("Location: ".$this->editUrl."?id=".$this->id);
			} //if file is set
			else {
				$editLine = $order.";".$title.";".$link.";".$img;
				$wtfbool  = FALSE;

				foreach ($this->file as $line) {
					$line     = htmlspecialchars($line);
					$arr_item = explode(';', $line);

					// old == new
					if ($mv == $order) {
						if ($arr_item[0] == $order) {
							$contents[ $arr_item[0] ] = $editLine;
						} else {
							$contents[ $arr_item[0] ] = $line;
						}
					} // old < new
					else
						if ($mv < $order) {
							if ($arr_item[0] < $mv) {
								$contents[ $arr_item[0] ] = $line;
							} else if ($arr_item[0] == $mv) {
								;# nothing...
							} else if ($arr_item[0] > $mv && $arr_item[0] <= $order) {
								if ($arr_item[0] == $order) {
									$wtfbool = TRUE;
								}
								$arr_item[0]--;
								$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
								$contents[ $arr_item[0] ] = $line;
							} else if ($arr_item[0] == ($order + 1)) {
								$wtfbool                    = FALSE;
								$contents[ $arr_item[0]-- ] = $editLine;
								$contents[ $arr_item[0] ]   = $line;
							} else {
								$contents[ $arr_item[0] ] = $line;
							}
						} // old > new
						else if ($mv > $order) {
							if ($arr_item[0] < $order) {
								$contents[ $arr_item[0] ] = $line;
							} else if ($arr_item[0] == $order) {
								$contents[ $arr_item[0] ] = $editLine;
								$arr_item[0]++;
								$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
								$contents[ $arr_item[0] ] = $line;
							} else if ($arr_item[0] < $mv) {
								$arr_item[0]++;
								$line                     = $arr_item[0].";".$arr_item[1].";".$arr_item[2].";".$arr_item[3];
								$contents[ $arr_item[0] ] = $line;
							} else if ($arr_item[0] == $mv) {
								;# nothing...
							} else {
								$contents[ $arr_item[0] ] = $line;
							}
						}
				}

				if ($wtfbool)
					array_push($contents, $editLine);

				$this->writeToFile($contents);
			}
		}
	}