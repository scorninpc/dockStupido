<?php


/**
 * Lib
 */
abstract class dockStupidoApplet
{
	private $_config;
	private $_dock;
	private $_button;

	/**
	 * 
	 */
	public function __construct($config, $dock)
	{
		$this->_config = $config;
		$this->_dock = $dock;

		$this->doBeforeCreateButton();
	}

	/**
	 * 
	 */
	public function createButton()
	{
		$this->_button = new \GtkButton();
	
		switch ($this->getDock()->getConfig()['interface']['side']) {
			case dockStupido::DOCK_SIDE_TOP:
				$direction_class = "place_top";
				break;
			case dockStupido::DOCK_SIDE_RIGHT:
				$direction_class = "place_right";
				break;
			case dockStupido::DOCK_SIDE_BOTTOM:
				$direction_class = "place_bottom";
				break;
			case dockStupido::DOCK_SIDE_LEFT:
				$direction_class = "place_left";
				break;
		}

		// Add css style 
		$style_context = $this->_button->get_style_context();
		$style_context->add_provider($this->getCssProvider(), 600);
		$style_context->add_class($direction_class);

		// Create hookable
		$this->_button = $this->doAfterCreateButton($this->_button);

		// Return button to dock pack
		return $this->_button;
	}

	/**
	 * Hook called before the configure
	 */
	public function doBeforeCreateButton()
	{ }

	/**
	 * Hook called after the configure
	 */
	public function doAfterCreateButton($button)
	{
		return $button;
	}

	/**
	 * Hook called after show_all
	 */
	public function doAfterShow()
	{ }

	/**
	 *
	 */
	public function setIconName($name)
	{
		$image = \GtkImage::new_from_icon_name($name, \GtkIconSize::DIALOG);
		$image->set_pixel_size($this->_dock->getConfig()['interface']['icon_size']);
		$this->_button->set_image($image);
	}

	/**
	 * Get the config
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/**
	 * Set the config
	 */
	public function setConfig($config)
	{
		$this->_config = $config;

		$this->_dock->saveConfig();
	}

	/**
	 * Get the dock bar
	 */
	public function getDock()
	{
		return $this->_dock;
	}

	/**
	 * Get language
	 */
	public function getLanguage()
	{
		return $this->_dock->language;
	}

	/**
	 * Get default css provider
	 */
	public function getCssProvider()
	{
		$css_provider = new \GtkCssProvider();
		$ret = $css_provider->load_from_data("
			.image-button {
				background: transparent;
				border: none;
				padding: 0;
				transition: 0.2s linear;
				border-radius: 0;
				-gtk-icon-shadow: 0px 0px 3px rgba(0,0,0,0.30);
			}
			.image-button:hover {
				transition: 0.2s linear;
			}

			/********
			 * TOP
			 *********/
			.image-button.place_top {
				margin-bottom: 0px;
			}
			.image-button.place_top:hover {
				margin-bottom: -10px;
			}
			.image-button.place_top.marked {
				border-top: 2px rgba(111, 97, 97, 0.8) solid;
			}
			.image-button.place_top.actived {
				border-top: 2px rgba(12, 192, 247, 0.8) solid;
			}

			/********
			 * BOTTOM
			 *********/
			.image-button.place_bottom {
				margin-top: 0px;
			}
			.image-button.place_bottom:hover {
				margin-top: -10px;
			}
			.image-button.place_bottom.marked {
				border-bottom: 2px rgba(111, 97, 97, 0.8) solid;
			}
			.image-button.place_bottom.actived {
				border-bottom: 2px rgba(12, 192, 247, 0.8) solid;
			}

			/********
			 * RIGHT
			 *********/
			.image-button.place_right {
				margin-left: 0px;
			}
			.image-button.place_right:hover {
				margin-left: -10px;
			}
			.image-button.place_right.marked {
				border-right: 2px rgba(111, 97, 97, 0.8) solid;
			}
			.image-button.place_right.actived {
				border-right: 2px rgba(12, 192, 247, 0.8) solid;
			}

			/********
			 * LEFT
			 *********/
			.image-button.place_left {
				margin-right: 0px;
			}
			.image-button.place_left:hover {
				margin-right: -10px;
			}
			.image-button.place_left.marked {
				border-left: 2px rgba(111, 97, 97, 0.8) solid;
			}
			.image-button.place_left.actived {
				border-left: 2px rgba(12, 192, 247, 0.8) solid;
			}

		");

		if(!$ret) {
			throw new Exception("Cannot create GtkCssProvider", 1);
		}

		return $css_provider;
	}

}