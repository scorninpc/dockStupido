<?php 

/**
 * @TODOS
 * 
 * - Animate icon when a new icon was added to the dock (like a appear with zoom or fade)
 * - Load config from file
 * - Add padding/margin on start and end of dock (interface->space size on start and end)
 * - When change active window, look for position of window. If active window are up side the dock, hide it
 */

// Define a path to application directory
defined("DOCK_PATH") || define("DOCK_PATH", dirname(__FILE__));
defined("DOCK_CONFIG_PATH") || define("DOCK_CONFIG_PATH", getenv("HOME") . "/.config/dockStupido");

// Include the library 
require_once(DOCK_PATH . "/dockStupidoApplet.php");

// Init GTK
\Gtk::init();

/**
 * 
 */
class dockStupido
{
	public $widgets;
	public $title;
	public $width;
	public $height;
	private $_config;
	public $language;
	public $direction;

	const DOCK_SIDE_TOP = 0;
	const DOCK_SIDE_LEFT = 1;
	const DOCK_SIDE_RIGHT = 2;
	const DOCK_SIDE_BOTTOM = 3;

	const DOCK_POSITION_FILL = 0;
	const DOCK_POSITION_CENTER = 1;
	const DOCK_POSITION_LOCATED = 2;


	/**
	 *
	 */
	public function __construct()
	{

		// $pid = pcntl_fork();
		// if($pid == -1)  {
		// 	die("Couldn't fork()!");
		// }
		// else if ($pid) {
		// 	exit(0);
		// }

		// posix_setsid();






		$this->language = file_get_contents(getenv("HOME") . "/.config/user-dirs.locale");
		
		// $todo Load config from file
		$this->_config1 = [
			'interface' => [
				'icon_size' => 24,											// Size of icon
				'transparent' => TRUE,										// Transparent or gtk theme
				'space' => 8,												// Space between icons
				'side' => \dockStupido::DOCK_SIDE_LEFT,						// Side
				'position' => \dockStupido::DOCK_POSITION_LOCATED,			// Position
				'position_location' => 100,									// Position porcentagem of screen (for example: place at 10% from screen)
				'reserve' => FALSE,											// Reserve desktop area
			],
			'debug' => FALSE,												// Open GTK Debug window
		];

		// Load config
		$this->loadConfig();


		// Verifica a direção
		switch ($this->_config['interface']['side']) {
			case \dockStupido::DOCK_SIDE_LEFT:
			case \dockStupido::DOCK_SIDE_RIGHT:
				$this->height = NULL;
				if($this->_config['interface']['position'] == \dockStupido::DOCK_POSITION_FILL) {
					$this->height = 1080;
				}
				$this->width = 36;
				$this->direction = \GtkOrientation::VERTICAL;
				break;
			
			case \dockStupido::DOCK_SIDE_TOP:
			case \dockStupido::DOCK_SIDE_BOTTOM:
				$this->width = NULL;
				if($this->_config['interface']['position'] == \dockStupido::DOCK_POSITION_FILL) {
					$this->width = 1920;
				}
				$this->height = 36;
				$this->direction = \GtkOrientation::HORIZONTAL;
				break;
		}
		
		// 
		$this->title = "dockStupido";

		//
		$this->interface();

		// Stylish dock
		$this->styleDock();

		// Show the window
		$this->widgets['dock']->show_all();

		// Recupera os tamanhos da tela
		$this->screen_width = exec("xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$1}'");
		$this->screen_height = exec("xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$2}'");
		
		// Recupera o window id
		$this->pid = exec("xdotool search --name '^" . $this->title . "\$'");

		// Dock it
		$this->dockIt();
		
		// Move dock
		\Gtk::timeout_add(100, function() {
			$this->dockMove();
			return FALSE;
		});

		// Load applets
		$this->loadApplets();

		// Loop
		\Gtk::main();
	}

	/**
	 *
	 */
	public function interface()
	{

		$this->widgets['dock'] = new \GtkWindow(\Gtk::WINDOW_TOPLEVEL);
		$this->widgets['dock']->set_default_size($this->width, $this->height);
		$this->widgets['dock']->set_decorated(FALSE);
		$this->widgets['dock']->set_title($this->title);
		$this->widgets['dock']->set_keep_above(TRUE);
		if($this->getConfig()['debug']) {
			$this->widgets['dock']->set_interactive_debugging(TRUE);
		}

		// Add container
		$this->widgets['layout'] = new \GtkBox($this->direction);
		$this->widgets['layout']->set_spacing($this->getConfig()['interface']['space']);

		$this->widgets['dock']->add($this->widgets['layout']);
	}

	/**
	 *
	 */
	public function loadApplets()
	{
		// @todo Get this applets from config file, who will save applets list
		$this->_config1['applets'] = [
			['name'=>"taskList", 'config'=>[]],
			['name'=>"shutdown", 'config'=>[]],
		];

		foreach($this->_config['applets'] as $index => $applet) {
			$appletName = $applet['name'];
	
			// Create applet object
			require_once(DOCK_PATH . "/applets/" . $appletName . "/" . $appletName . ".php");				
			$object = new $appletName($applet['config'], $this);
			$button = $object->createButton();
			$this->widgets['layout']->pack_start($button, FALSE, FALSE);
			$this->widgets['dock']->show_all();
			$object->doAfterShow();

			$this->_config['applets'][$index]['object'] = $object;
		}
	}

	/**
	 *
	 */
	public function getConfig()
	{
		return $this->_config;
	}


	/**
	 *
	 */
	public function styleDock()
	{

		// make transparent
		if($this->_config['interface']['transparent']) {
			$screen = $this->widgets['dock']->get_screen();
			$visual = $screen->get_rgba_visual();
			$this->widgets['dock']->set_visual($visual);
			$this->widgets['dock']->set_app_paintable(TRUE);
		}

		// Add css provider do dock window
		$css_provider = new \GtkCssProvider();
		$css_provider->load_from_data("
			window {
				padding-bottom: 0px;
				margin-left: -20px;
			}
		");
		$style_context = $this->widgets['dock']->get_style_context();
		$style_context->add_provider($css_provider, 600);
	}

	/**
	 *
	 */
	public function dockIt()
	{
		$top = 0;
		$right = 0;
		$bottom = 0;
		$left = 0;

		// Get side
		switch($this->_config['interface']['side']) {
			case \dockStupido::DOCK_SIDE_TOP:
				$top = $this->height;
				break;
			case \dockStupido::DOCK_SIDE_LEFT:
				$left = $this->width;
				break;
			case \dockStupido::DOCK_SIDE_RIGHT:
				$right = $this->width;
				break;
			case \dockStupido::DOCK_SIDE_BOTTOM:
				$bottom = $this->height;
				break;
		}

		// create the dock
		$commands = [
			"xprop -id " . $this->pid . " -format _NET_WM_WINDOW_TYPE 32a -set _NET_WM_WINDOW_TYPE \"_NET_WM_WINDOW_TYPE_DOCK\";",
		];

		// May reserve screen area?
		if($this->_config['interface']['reserve']) {
			$commands[] = "xprop -id " . $this->pid . " -format _NET_WM_STRUT 32cccccccccccc -set _NET_WM_STRUT \"" . $left . "," . $right . "," . $top . "," . $bottom . "\"";
		}

		// Dock
		exec(implode(" ", $commands));
	}

	/**
	 *
	 */
	public function dockMove()
	{
		$current_dock_size = $this->widgets['dock']->get_size();

		$position_left = 0;
		$position_top = 0;

		switch($this->_config['interface']['side']) {
			
			case \dockStupido::DOCK_SIDE_LEFT:
			case \dockStupido::DOCK_SIDE_RIGHT:

				switch ($this->_config['interface']['position']) {
					case \dockStupido::DOCK_POSITION_CENTER:
						$position_top = ($this->screen_height / 2) - ($current_dock_size['height'] / 2);
						break;
					
					case \dockStupido::DOCK_POSITION_LOCATED:
						$position_top = (int)(($this->screen_height * $this->_config['interface']['position_location']) / 100);
						if(($position_top + $current_dock_size['height']) > $this->screen_height) {
							$position_top = $this->screen_height - $current_dock_size['height'] - $this->_config['interface']['space'];
						}
						break;
				}

				if($this->_config['interface']['side'] == \dockStupido::DOCK_SIDE_RIGHT) {
					$position_left = $this->screen_width - $this->width;
				}

				break;

			case \dockStupido::DOCK_SIDE_BOTTOM:
			case \dockStupido::DOCK_SIDE_TOP:

				switch ($this->_config['interface']['position']) {
					case \dockStupido::DOCK_POSITION_CENTER:
						$position_left = ($this->screen_width / 2) - ($current_dock_size['width'] / 2);
						break;
					
					case \dockStupido::DOCK_POSITION_LOCATED:
						$position_left = (int)(($this->screen_width * $this->_config['interface']['position_location']) / 100);
						if(($position_left + $current_dock_size['width']) > $this->screen_width) {
							$position_left = $this->screen_width - $current_dock_size['width'] - $this->_config['interface']['space'];
						}
						break;
				}

				if($this->_config['interface']['side'] == \dockStupido::DOCK_SIDE_BOTTOM) {
					$position_top = $this->screen_height - $this->height;
				}

				break;
		}

		// Move the dock
		$this->widgets['dock']->move($position_left, $position_top);
		$this->widgets['dock']->show_all();
	}

	/**
	 *
	 */
	public function dockHide()
	{
		$progress_width = $this->width;
		\Gtk::timeout_add(100, function() {
			global $progress_width;

			// Verify if all finished and stop propagation
			if($progress_width < 0) {
				return FALSE;
			}

			$this->widgets['dock']->move($progress_width, NULL);
			$progress_width -= 2;

			

			// Tell to continue calling
			return TRUE;
		});
	}

	/**
	 *
	 */
	public function saveConfig()
	{
		if(!is_dir(DOCK_CONFIG_PATH)) {
			mkdir(DOCK_CONFIG_PATH);
		}

		// Array to save
		$config = $this->_config;

		// Loop applets
		$appletsConfig = [];
		foreach($this->_config['applets'] as $index => $applet) {
			$appletsConfig[] = [
				'name' => $applet['name'],
				'config' => $this->_config['applets'][$index]['object']->getConfig()
			];
		}
		$config['applets'] = $appletsConfig;


		// Save file
		file_put_contents(DOCK_CONFIG_PATH . "/config.json", json_encode($config));
	}

	/**
	 *
	 */
	public function loadConfig()
	{
		if(!is_dir(DOCK_CONFIG_PATH)) {
			mkdir(DOCK_CONFIG_PATH);
		}

		// Load file
		$this->_config = json_decode(file_get_contents(DOCK_CONFIG_PATH . "/config.json"), TRUE);
	}
}

new \dockStupido();