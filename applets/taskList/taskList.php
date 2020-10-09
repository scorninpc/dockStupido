<?php

/**
 * Applet to add simple launchers
 */
class taskList extends dockStupidoApplet
{
	protected $tasksArea;
	protected $taskButtons;
	protected $taskMenus;
	protected $pinnedButtons;
	protected $direction;
	protected $direction_class;

	/**
	 * 
	 */
	public function doBeforeCreateButton()
	{
		switch ($this->getDock()->getConfig()['interface']['side']) {
			case dockStupido::DOCK_SIDE_TOP:
				$this->direction_class = "place_top";
				break;
			case dockStupido::DOCK_SIDE_RIGHT:
				$this->direction_class = "place_right";
				break;
			case dockStupido::DOCK_SIDE_BOTTOM:
				$this->direction_class = "place_bottom";
				break;
			case dockStupido::DOCK_SIDE_LEFT:
				$this->direction_class = "place_left";
				break;
		}
	}

	/**
	 * 
	 */
	public function doAfterCreateButton($button)
	{
		// Create a new area for multiple buttons
		$this->tasksArea = new \GtkBox($this->getDock()->direction);
		$this->tasksArea->set_spacing($this->getDock()->getConfig()['interface']['space']);

		// Load pinneds
		$pinned = $this->getConfig()['pinned'];
		$pinned = [];
		foreach($pinned as $desktopFile) {
			$content = parse_ini_file($desktopFile, FALSE, INI_SCANNER_RAW);

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

			// Create the button
			$this->pinnedButtons[$name] = new \GtkButton();
			$image = \GtkImage::new_from_icon_name($content['Icon'], \GtkIconSize::DIALOG);
			$image->set_pixel_size(24);
			$this->pinnedButtons[$name]->set_image($image);
			$this->pinnedButtons[$name]->set_tooltip_text($name);
			$this->pinnedButtons[$name]->connect('clicked', function($button, $content, $desktopFile) {

				// Exec
				if(isset($content['TryExec'])) {
					$exec = $content['TryExec'];
				}
				else {
					$exec = $content['Exec'];
				}
				
				exec("gtk-launch " . basename($desktopFile, ".desktop") . " > /dev/null &");

			}, $content, $desktopFile);

			// Add css style 
			$style_context = $this->pinnedButtons[$name]->get_style_context();
			$style_context->add_provider($this->getCssProvider(), 600);
			$style_context->add_class($this->direction_class);

			// Create the menu
			// $this->taskMenus[$id] = new \GtkMenu();
			// 	$menu_item = \GtkCheckMenuItem::new_with_label("Manter no dock"); 
			// 	$menu_item->connect("activate", [$this, "taskMenu_clicked"], 0, $window);
			// 	$this->taskMenus[$id]->append($menu_item);

			// Pack, show and place docker to new locate
			$this->tasksArea->pack_start($this->pinnedButtons[$name], TRUE, TRUE);
			$this->tasksArea->show_all();
			
		}

		// Return the new Widget
		return $this->tasksArea;
	}

	/**
	 *
	 */
	public function doAfterShow()
	{
		$screen = WnckScreen::get_default();

		// On change window active state
		$screen->connect("active-window-changed", function($screen, $window) {

			// Get previous actived window
			$id = $window->get_xid();
			if(isset($this->taskButtons[$id])) {
				// Add css style 
				$style_context = $this->taskButtons[$id]->get_style_context();
				$style_context->remove_class("actived");
			}

			// Get the active window
			$active = $screen->get_active_window();
			if($active != NULL) {
				$id = $active->get_xid();
				if(isset($this->taskButtons[$id])) {
					// Add css style 
					$style_context = $this->taskButtons[$id]->get_style_context();
					$style_context->add_class("actived");
				}
			}
		});

		// On some window close
		$screen->connect("window-closed", function($screen, $window) {
			// Recupera os dados da janela
			$id = $window->get_xid();
			if(isset($this->taskButtons[$id])) {
				$this->taskButtons[$id]->destroy();
				$this->tasksArea->show_all();

				// Resize and re-place the dock
				switch($this->getDock()->getConfig()['interface']['side']) {
					case \dockStupido::DOCK_SIDE_LEFT:
					case \dockStupido::DOCK_SIDE_RIGHT:
						$this->getDock()->widgets['dock']->resize($this->getDock()->width, 1);
						break;
					case \dockStupido::DOCK_SIDE_TOP:
					case \dockStupido::DOCK_SIDE_BOTTOM:
						$this->getDock()->widgets['dock']->resize(1, $this->getDock()->height);
						break;
				}
				
				
				// Move dock
				Gtk::timeout_add(100, function() {
					$this->getDock()->dockMove();
					return FALSE;
				});
			}
		});

		// On wew window open
		$screen->connect("window-opened", function($screen, $window) {
			
			if($window->get_window_type() == \WnckWindow::NORMAL) {
				
				// Recupera os dados da janela
				$id = $window->get_xid();
				$buff = $window->get_icon();
				$name = $window->get_name();

				// Get desktop file
				$desktopFile = $this->getDesktopFile($name);

				// Verify if .desktop exists
				if(file_exists($desktopFile)) {
					// Verify if 


					$canpin = TRUE;
				}
				else {
					$canpin = FALSE;
				}






				// Faz o hook para mudança de nome
				$window->connect("name-changed", function($group, $window) {
					$id = $window->get_xid();
					$this->taskButtons[$id]->set_tooltip_text($window->get_name());
				}, $window);

				// Scale the icon and add to GtkImage
				$buff = $buff->scale_simple(24, 24, \GdkInterpType::HYPER);
				$image = \GtkImage::new_from_pixbuf($buff);

				// Create the button
				$this->taskButtons[$id] = new \GtkButton();
				$this->taskButtons[$id]->set_image($image);
				$this->taskButtons[$id]->set_tooltip_text($window->get_name());

				// Add the click event
				$this->taskButtons[$id]->connect("clicked", [$this, "taskButton_clicked"], $window);
				$this->taskButtons[$id]->connect("button_press_event", [$this, "taskButton_buttonpress"], $window);

				// Add css style 
				$style_context = $this->taskButtons[$id]->get_style_context();
				$style_context->add_provider($this->getCssProvider(), 600);
				$style_context->add_class($this->direction_class);
				$style_context->add_class("marked");

				// Verify if is actived
				if($window->is_active()) {
					$style_context->add_class("actived");
				}
				
				// Create the menu
				$this->taskMenus[$id] = new \GtkMenu();
					$menu_item = \GtkCheckMenuItem::new_with_label("Manter no dock"); 
					$menu_item->connect("activate", [$this, "taskMenu_clicked"], 0, $window);
					$this->taskMenus[$id]->append($menu_item);

					$menu_item = \GtkMenuItem::new_with_label("Fechar"); 
					$menu_item->connect("activate", [$this, "taskMenu_clicked"], 1, $window);
					$this->taskMenus[$id]->append($menu_item);


				$this->taskMenus[$id]->show_all();

				// Pack, show and place docker to new locate
				$this->tasksArea->pack_start($this->taskButtons[$id], TRUE, TRUE);
				$this->tasksArea->show_all();

				// Move dock
				Gtk::timeout_add(100, function() {
					$this->getDock()->dockMove();
					return FALSE;
				});
			}


		});
	}

	/**
	 *
	 */
	public function taskButton_buttonpress($button, $event, $window)
	{
		$id = $window->get_xid();

		if($event->button->button == 3) {
			if(isset($this->taskMenus[$id])) {
				$this->taskMenus[$id]->popup_at_pointer($event);
			}
		}
	}

	/**
	 *
	 */
	public function taskButton_clicked($button, $window)
	{
		// Recupera os dados da janela
		$id = $window->get_xid();

		// Verify if button exists
		if(isset($this->taskButtons[$id])) {

		
			// Verify if actived
			if($window->is_active()) {
				$window->minimize();
			}
			else {
				$window->activate(time());
			}
		}
	}

	/**
	 *
	 */
	public function taskMenu_clicked($menu, $item, $window)
	{
		// Manter no dock
		if($item == 0) {
			// Recupera o grupo
			$group = $window->get_class_group();
			$desktopFile = $this->getDesktopFile($group->get_name());
			if(!$desktopFile) {
				echo "\n\nCannot find .desktop file";
				return FALSE;
			}

			// Recupera os fixos
			$config = $this->getConfig();

			// Verifica se está ativando ou desativando
			if($menu->get_active()) {

				// Adiciona
				$config['pinned'][] = $desktopFile;

			}
			else {

				// Remove
				foreach($config['pinned'] as $index => $pinned) {
					if($pinned == $desktopFile) {
						unset($config['pinned'][$index]);
						break;
					}
				}

			}

			//
			$this->setConfig($config);
		}

		// Fechar
		else if($item == 1) {
			$window->close(time());
		}
	}

	/**
	 *
	 */
	public function getDesktopFile($name)
	{
		$desktopFile = exec("grep -RHEi '(Generic)?Name(\[.*\])?=" . $name . "' ~/.local/share/applications /usr/share/applications | sed -n 1p");
		if(strlen($desktopFile) == 0) {
			return FALSE;
		}

		$desktopFile = substr($desktopFile, 0, strpos($desktopFile, ":"));
		
		return $desktopFile;
	}

}