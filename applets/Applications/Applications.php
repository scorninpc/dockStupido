<?php

require_once(DOCK_PATH . "/applets/Applications/desktopIni.php");

/**
 * Applet to add applications menu
 */
class applications extends dockStupidoApplet
{

	protected $_categories;
	protected $_applications;
	protected $_execs;
	protected $_icons;

	protected $_menu;
	protected $_language;


	/**
	 *
	 */
	public function doAfterCreateButton($button) 
	{
		// Add the icon
		$this->setIconName("start-here");

		// Parse categories from mate
		$systemCategories = $this->_parseSystemCategories("/etc/xdg/menus/mate-applications.menu");
		
		// Get .desktop from directory
		$desktops = $this->_getDesktops("/usr/share/applications");
		foreach($desktops as $desktop) {
			// Parse desktop ini
			$entry = new desktopIni($desktop, $this->getLanguage());

			if(!$entry->getDisplay()) {
				continue;
			}

			// Get name
			$name = $entry->getName();
			if(!$name) {
				continue;
			}

			// Get icon
			$icon = $entry->getIcon();
			if(!$icon) {
				continue;
			}

			// Categories
			$categories = $entry->getCategories();
			if(!$categories) {
				echo $desktop . "\n";
				continue;
			}
			

			// Loop categories
			foreach($categories as $category)
			{
				if(isset($systemCategories[$category])) {

					$systemCategory = $systemCategories[$category];

					$this->_applications[$systemCategory][$name] = [
						'desktop' => $desktop,
						'name' => $name,
						'icon' => $icon,
					];

					break;
				}
			}

			// array_multisort(array_column($this->_applications, 'key'), SORT_ASC, $this->_applications);
			ksort($this->_applications);
			foreach($this->_applications as $name => $applications) {
				ksort($applications, SORT_NATURAL);
				$this->_applications[$name] = $applications;
			}

		}

		// Create start menu
		$this->_menu = new \GtkMenu();

		// Loop itens adding to menu
		foreach($this->_applications as $name => $applications) {
			// $menu = new \GtkMenu();

			$submenu_item = \GtkMenuItem::new_with_label($name);
			$this->_menu->append($submenu_item);

			$menu = new \GtkMenu();
			foreach($applications as $application) {

				$item = \GtkMenuItem::new_with_label($application['name']);
				$menu->append($item);

				$item->connect("activate", function($button, $application) {

					exec("gtk-launch " . basename($application['desktop'], ".desktop") . " > /dev/null &");

				}, $application);

			}
			
			$submenu_item->set_submenu($menu);
		}

		$this->_menu->show_all();


/*
		die();


			var_dump($array);

			die();
		// Parse desktop file
		die();
		$files = scandir();
		foreach($files as $file) {
			if(($file == ".") || ($file == "..")) {
				continue;
			}

			// Parse ini
			$content = parse_ini_file("/usr/share/applications/" . $file, FALSE, INI_SCANNER_RAW);

			// Category
			$category = $content['Categories'];

			// 
			if(!isset($this->_applications[$category])) {
				$this->_applications[$category] = [];
			}

			// Name
			$name = $content['Name'];
			if(is_array($name)) {
				if(isset($name[$this->getLanguage()])) {
					$name = $name[$this->getLanguage()];
				}
				else {
					$name = current($name);
				}
			}

			// Get exec
			if(isset($content['TryExec'])) {
				$exec = $content['TryExec'];
			}
			else {
				if(!isset($content['TryExec'])) {
					continue;
				}

				$exec = $content['Exec'];
			}

			// Add application
			$this->_applications[$category][$name] = [
				'icon' => "",
				'exec' => $exec
			];
		}









		// // Click on button
		// $button->connect("clicked", function($button) {
		// 	exec("mate-session-save --shutdown-dialog > /dev/null &");
		// });

		// Load applications from .deskfiles
		// ~/.local/share/applications 
		// /usr/share/applications
		$files = scandir("/usr/share/applications");
		foreach($files as $file) {
			if(($file == ".") || ($file == "..")) {
				continue;
			}

			// Parse ini
			$content = parse_ini_file("/usr/share/applications/" . $file, FALSE, INI_SCANNER_RAW);
			
			// Get name
			$name = $content['Name'];
			if(is_array($name)) {
				if(isset($name['pt_BR'])) {
					$name = $name['pt_BR'];
				}
				else {
					$name = current($name);
				}
			}

			// Get exec
			if(isset($content['TryExec'])) {
				$exec = $content['TryExec'];
			}
			else {
				if(!isset($content['TryExec'])) {
					continue;
				}

				$exec = $content['Exec'];
			}

			// Category
			if(isset($content['Categories'])) {

				$category = $content['Categories'];

				echo $category . "\n";

				//
				if(!isset($this->_categories[$category])) {
					$this->_categories[$category] = \GtkMenuItem::new_with_label($category); 
					$this->_menu->append($this->_categories[$category]);




				}


				// 
				$item = \GtkMenuItem::new_with_label($category); 
				$this->_categories[$category]->set_submenu($item);


			}

		}

		// Show
		$this->_menu->show_all();
		**/

		// Add event
		$button->connect("button_press_event", function($button, $event, $menu) {

			$menu->popup_at_pointer($event);
			
		}, $this->_menu);

		// Return button to pack
		return $button;
	}

	/**
	 * 
	 */
	public function configure($dock)
	{ }


	/**
	 *
	 */
	private function _parseSystemCategories($file)
	{
		$content = simplexml_load_file($file);

		$categories = [];

		foreach($content->Menu as $menu) {
			$name = (string)$menu->Name;

			$categories[$name] = $name;

			foreach($menu->Include->And->Category as $include) {
				$categories[(string)$include] = $name;
			}
		}

		return $categories;
	}

	/**
	 *
	 */
	private function _getDesktops($dir)
	{
		$desktops = [];

		$files = scandir($dir);
		foreach($files as $file) {
			if(($file == ".") || ($file == "..") || (substr($file, strpos($file, ".desktop")) != ".desktop")) {
				continue;
			}

			$desktops[] = $dir . "/" . $file;
		}		

		return $desktops;
	}

	

}