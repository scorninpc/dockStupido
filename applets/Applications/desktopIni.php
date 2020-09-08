<?php


class desktopIni
{
	private $ini;
	private $entry;

	public function __construct($file, $lang)
	{
		$this->_lang = $lang;

		$this->ini = $this->_parse_desktop_file($file);
		$this->entry = $this->ini['Desktop Entry'];
	}

	public function getDisplay()
	{
		if(!isset($this->entry['NoDisplay'])) {
			return TRUE;
		}
		elseif(strtolower($this->entry['NoDisplay']) == "false") {
			return TRUE;
		}

		return FALSE;
	}

	public function getName()
	{
		if(isset($this->entry["Name[" . $this->_lang . "]"])) {
			return $this->entry["Name[" . $this->_lang . "]"];
		}
		elseif($this->entry['Name']) {
			return $this->entry['Name'];
		}
		else {
			return FALSE;
		}
	}

	public function getCategories()
	{
		if(!isset($this->entry['Categories'])) {
			return FALSE;
		}

		$categories = $this->entry['Categories'];

		return explode(";", $categories);
	}


	/**
	 * Parse ini desktop file correctly
	 * @thanks to robsongehl @ https://forum.dokuwiki.org/d/2067-tip-how-to-solve-problem-with-parse-ini-file-disabled
	 */
	private function _parse_desktop_file($file, $commentchar=';')
	{
		$array1 = file($file);
		$section = '';
		for ($line_num = 0; $line_num <= sizeof($array1); $line_num++) {
			if(!isset($array1[$line_num])) {
				continue;
			}

			$filedata = $array1[$line_num];
			$dataline = trim($filedata);
			$firstchar = substr($dataline, 0, 1);
			if (($firstchar != $commentchar) && ($dataline != '')) {
				// It's an entry (not a comment and not a blank line)
				if ($firstchar == '[' && substr($dataline, -1, 1) == ']') {
					// It's a section
					$section = (substr($dataline, 1, -1));
				}
				else {
					// It's a key...
					$delimiter = strpos($dataline, '=');
					if ($delimiter > 0) {
						// ...with a value
						$key = (trim(substr($dataline, 0, $delimiter)));
						$array2[$section][$key] = '';
						$value = trim(substr($dataline, $delimiter + 1));
						while (substr($value, -1, 1) == '\\') {
							// ...value continues on the next line
							$value = substr($value, 0, strlen($value)-1);
							$array2[$section][$key] .= stripcslashes($value);
							$line_num++;
							$value = trim($array1[$line_num]);
						}

						$array2[$section][$key] .= stripcslashes($value);
						$array2[$section][$key] = trim($array2[$section][$key]);
						if (substr($array2[$section][$key], 0, 1) == '"' && substr($array2[$section][$key], -1, 1) == '"') {
							$array2[$section][$key] = substr($array2[$section][$key], 1, -1);
						}
					}
					else {
						//...without a value
						$array2[$section][(trim($dataline))]='';
					}
				}
			}
			else {
				//It's a comment or blank line.  Ignore.
			}
		}
		return $array2;
	}
}