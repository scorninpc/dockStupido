<?php 

// Define a path to application directory
defined("DOCK_PATH") || define("DOCK_PATH", dirname(__FILE__));

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
	public $direction;

	/**
	 *
	 */
	public function __construct()
	{

		$this->_config = [
			'interface' => [
				'space' => 8,						// Espaço entre os icones
				'location' => "left",				// Posição do dock (top, right, bottom, left)
				'fill' => "center", 				// Preenche toda a tela, "center" ou porcentagem da tela
				'reserve' => FALSE,					// Reserva a area?
			]
		];

		// Verifica a direção
		if(($this->_config['interface']['location'] == "left") || ($this->_config['interface']['location'] == "right")) {
			$this->height = NULL;
			if($this->_config['interface']['fill'] === TRUE) {
				$this->height = 1080;
			}
			$this->width = 36;
			$this->direction = \GtkOrientation::VERTICAL;
		}
		else {
			$this->width = NULL;
			if($this->_config['interface']['fill'] === TRUE) {
				$this->width = 1920;
			}
			$this->height = 36;
			$this->direction = \GtkOrientation::HORIZONTAL;
		}

		$this->title = "dockStupido";

		$this->interface();

		// Stylish dock
		$this->styleDock();

		// Show the window
		$this->widgets['dock']->show_all();

		// Recupera os tamanhos da tela
		$this->screen_width = exec("xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$1}'");
		$this->screen_height = exec("xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$2}'");
		
		// Recupera o window id
		$this->pid = exec("xdotool search --name '^dockStupido\$'");

		// Dock it
		$this->dockIt();
		$this->dockMove();

		// Load applets
		$this->loadApplets();

		// Loop
		Gtk::main();
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
		// $this->widgets['dock']->set_interactive_debugging(TRUE);

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
		$this->applets = [
			['name'=>"taskList", 'config'=>[]],
			['name'=>"shutdown", 'config'=>[]],
		];

		foreach($this->applets as $applet) {
			$appletName = $applet['name'];
	
			// Create applet object
			require_once(DOCK_PATH . "/applets/" . $appletName . "/" . $appletName . ".php");				
			$object = new $appletName($applet['config'], $this);
			$button = $object->createButton();
			$this->widgets['layout']->pack_start($button, FALSE, FALSE);
			$this->widgets['dock']->show_all();
			$object->doAfterShow();
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
		// Deixa transparent
		$screen = $this->widgets['dock']->get_screen();
		$visual = $screen->get_rgba_visual();
		$this->widgets['dock']->set_visual($visual);
		// $this->widgets['dock']->set_app_paintable(TRUE);

		$css_provider = new \GtkCssProvider();
		$css_provider->load_from_data("
			window {
				padding-bottom: 0px;
				margin-left: -20px;
			}
		");

		// Add css style 
		$style_context = $this->widgets['dock']->get_style_context();
		$style_context->add_provider($css_provider, 600);
	}

	/**
	 *
	 */
	public function dockIt()
	{
		$position_left = 0;
		$position_top = 0;

		if($this->_config['interface']['location'] == "top") {
			$left = 0;
			$right = 0;
			$top = $this->height;
			$bottom = 0;
			$left_start_y = 0;
			$left_end_y = 0;
			$right_start_y = 0;
			$right_end_y = 0;
			$top_start_x = $position_left;
			$top_end_x = $position_left+$this->width;
			$bottom_start_x = 0;
			$bottom_end_x = 0;

			$position_top = $this->screen_height - $this->height;
			$position_left = 0;
		}
		else if($this->_config['interface']['location'] == "bottom") {
			$left = 0;
			$right = 0;
			$top = 0;
			$bottom = $this->height;
			$left_start_y = 0;
			$left_end_y = 0;
			$right_start_y = 0;
			$right_end_y = 0;
			$top_start_x = 0;
			$top_end_x = 0;
			$bottom_start_x = $position_left;
			$bottom_end_x = $position_left+$this->width;

			$position_top = $this->screen_height - $this->height;
			$position_left = 0;

		}

		// @fix struct correct area for 2 monitors
		else if($this->_config['interface']['location'] == "right") {
			$left = 0;
			$right = 0;
			$top = 0;
			$bottom = 0;
			$left_start_y = $this->screen_width - $this->width;
			$left_end_y = $this->screen_width;
			$right_start_y = 0;
			$right_end_y = 0;
			$top_start_x = 0;
			$top_end_x = 0;
			$bottom_start_x = 0;
			$bottom_end_x = $this->height;

			$position_top = 0;
			$position_left = $this->screen_width - $this->width;
		}

		// @fix struct correct area for 2 monitors
		else if($this->_config['interface']['location'] == "left") {

			$current_dock_size = $this->widgets['dock']->get_size();

			$position_top = ($this->screen_height / 2) - ($current_dock_size['height'] / 2);
			$position_left = 0;

			$left = $this->width;
			$right = 0;
			$top = 0;
			$bottom = 0;
			$left_start_y = 0;
			$left_end_y = 0;
			$right_start_y = 0;
			$right_end_y = 0;
			$top_start_x = 0;
			$top_end_x = 0;
			$bottom_start_x = 0;
			$bottom_end_x = 0;
		}

		// Faz virar um dock
		$commands = [
			"win=\$(xdotool search --name '^dockStupido\$');",
			"WIDTH=" . $this->width . "; HEIGHT=" . $this->height . ";",
			"SCREEN_WIDTH=" . $this->screen_width . ";",
			"SCREEN_HEIGHT=" . $this->screen_height . ";",
			// "SCREEN_WIDTH=\$(xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$1}');",
			// "SCREEN_HEIGHT=\$(xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$2}');",
			"xdotool windowunmap --sync \$win;",
			// "xdotool windowsize --sync \$win \$WIDTH \$HEIGHT;",
			// "xdotool windowmove --sync \$win " . $position_left . " "  . $position_top . ";",
			// "xdotool windowmove --sync \$win \$((\$SCREEN_WIDTH - \$WIDTH)) \$((\$SCREEN_HEIGHT - \$HEIGHT));",
			"xprop -id \"\${win}\" -format _NET_WM_WINDOW_TYPE 32a -set _NET_WM_WINDOW_TYPE \"_NET_WM_WINDOW_TYPE_DOCK\";",
			// "xprop -id \"\${win}\" -format _NET_WM_STRUT_PARTIAL 32cccccccccccc -set _NET_WM_STRUT_PARTIAL \"0,0,0,\${HEIGHT},0,0,0,0,0,\${WIDTH},0,0\"",,
		];

		// May reserve screen area?
		if($this->_config['interface']['reserve']) {
			$commands[] = "xprop -id \"\${win}\" -format _NET_WM_STRUT_PARTIAL 32cccccccccccc -set _NET_WM_STRUT_PARTIAL \"" . $left . "," . $right . "," . $top . "," . $bottom . "," . $left_start_y . "," . $left_end_y . "," . $right_start_y . "," . $right_end_y . "," . $top_start_x . "," . $top_end_x . "," . $bottom_start_x . "," . $bottom_end_x . "\"";
		}

		exec(implode(" ", $commands));
	}

	/**
	 *
	 */
	public function dockMove()
	{
		$current_dock_size = $this->widgets['dock']->get_size();

		if(($this->_config['interface']['location'] == "left") || ($this->_config['interface']['location'] == "right")) {
			
			if(is_int($this->_config['interface']['fill'])) {
				$position_top = (int)(($this->screen_height * $this->_config['interface']['fill']) / 100);

				// @todo Verify if dock exploring screen. Calc the position for do dock visible
				// if(($position_top + $current_dock_size['height']) > $this->screen_height) {
				// 	$position_top = $this->screen_height - $current_dock_size['height'];
				// }
			}
			else if($this->_config['interface']['fill'] == "center") {
				$position_top = ($this->screen_height / 2) - ($current_dock_size['height'] / 2);
			}

			$position_left = 0;
			if($this->_config['interface']['location'] == "right") {
				$position_left = $this->screen_width - $this->width;
			}
		}
		else {

			if(is_int($this->_config['interface']['fill'])) {
				$position_left = (int)(($this->screen_width * $this->_config['interface']['fill']) / 100);
			}
			else if($this->_config['interface']['fill'] == "center") {
				$position_left = ($this->screen_width / 2) - ($current_dock_size['width'] / 2);
			}

			$position_top = 0;
			if($this->_config['interface']['location'] == "bottom") {
				$position_top = $this->screen_height - $this->height;
			}

		}

		// Move o dock
		// $commands = [
		// 	"xdotool windowmove --sync " . $this->pid . " " . $position_left . " "  . $position_top . ";",
		// ];
		// exec(implode(" ", $commands));
		$this->widgets['dock']->move($position_left, $position_top);

		$this->widgets['dock']->show_all();
	}

	/**
	 *
	 */
	public function dockHide()
	{
		$progress_width = $this->width;
		Gtk::timeout_add(100, function() {
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
}

new dockStupido();








die();




/**
 * Tests with PHP-GTK 3 to create a simple GTK dock / panel
 *
 * https://github.com/scorninpc/php-gtk3
 *
 * Just download the appimage from release, make it executable, and run ./php-gk3-ARCH-VERSION.appimage dockStupido.php
 */

$title = "dockStupido";
$width = 1920;
$height = 24;

$width = 300;
$height = 300;

// Cria a janela
// $dock = new \GtkWindow(\Gtk::WINDOW_POPUP);
$dock = new \GtkWindow(\Gtk::WINDOW_TOPLEVEL);
$dock->set_default_size($width, $height);
$dock->set_decorated(FALSE);
$dock->set_title($title);
// $dock->set_interactive_debugging(TRUE);


// Create a css provider
$css_provider = new GtkCssProvider();
$ret = $css_provider->load_from_data("
	.image-button {
		background: transparent;
		border: none;
		padding: 0;
		min-width: 40px;
		min-height: 40px;
	}

	.image-button image {
		-gtk-icon-transform: scale(1.4);
	}
");

// Adiciona a imagem
// $image = \GtkImage::new_from_file("./twitter.png");
$layout = new \GtkLayout(new \GtkAdjustment(), new \GtkAdjustment());
// $layout->put($image, 0, 00);

// Adiciona um botão qualquer
$button = \GtkButton::new_from_icon_name("filezilla", \GtkIconSize::DIALOG);
$style_context = $button->get_style_context();
$style_context->add_provider($css_provider, 600);
$layout->put($button, 80, 130);

// Adiciona um botão qualquer
$button = \GtkButton::new_from_icon_name("pgadmin3", \GtkIconSize::DIALOG);
$layout->put($button, 120, 130);

// $button_context = $button->get_style_context();
// $button_context->add_class("class_test");

// Deixa transparent
$screen = $dock->get_screen();
$visual = $screen->get_rgba_visual();
$dock->set_visual($visual);
$dock->set_app_paintable(TRUE);

// Adiciona o css
// $css_provider = new GtkCssProvider();
// $ret = $css_provider->load_from_data("label {color: red; font-size: 35px; padding: 30px; }");
// if(!$ret) {
// 	die("Css Error\n");
// }
// $ret->add_provider_for_screen($screen);

// Mostra a janela
$dock->add($layout);
$dock->show_all();

// Faz virar um dock
$commands = [
	"win=\$(xdotool search --name '^dockStupido\$');",
	"WIDTH=" . $width . "; HEIGHT=" . $height . ";",
	"SCREEN_WIDTH=\$(xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$1}');",
	"SCREEN_HEIGHT=\$(xrandr -q | head -n2 | tail -n1 | awk '{print \$4}' | awk -F'[+]' '{print \$1}' | awk -F'[x]' '{print \$2}');",
	"xdotool windowunmap --sync \$win;",
	"xdotool windowsize --sync \$win \$WIDTH \$HEIGHT;",
	"xdotool windowmove --sync \$win \$((\$SCREEN_WIDTH - \$WIDTH)) \$((\$SCREEN_HEIGHT - \$HEIGHT));",
	"xprop -id \"\${win}\" -format _NET_WM_WINDOW_TYPE 32a -set _NET_WM_WINDOW_TYPE \"_NET_WM_WINDOW_TYPE_DOCK\";",
	"xprop -id \"\${win}\" -format _NET_WM_STRUT_PARTIAL 32cccccccccccc -set _NET_WM_STRUT_PARTIAL \"0,0,0,\${HEIGHT},0,0,0,0,0,\${WIDTH},0,0\"",
	"",
];
exec(implode(" ", $commands));

// Loop
\Gtk::main();
