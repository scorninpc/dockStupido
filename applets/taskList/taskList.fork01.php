<?php

/**
 * Applet to add simple launchers
 */
class taskList extends dockStupidoApplet
{
	protected $tasksArea;
	protected $taskButtons;

	/**
	 * 
	 */
	public function doBeforeCreateButton()
	{

	}

	/**
	 * 
	 */
	public function doAfterCreateButton($button)
	{
		$this->tasksArea = new \GtkBox(\GtkOrientation::HORIZONTAL);

		$this->tasksArea->set_spacing($this->getDock()->getConfig()['interface']['space']);

		return $this->tasksArea;
	}

	public function doAfterShow()
	{
		$screen = WnckScreen::get_default();

		$screen->connect("application-closed", function($screen, $window=NULL) {

		});
		
		$screen->connect("window-opened", function($screen, $window=NULL) {
			
			// var_dump($screen);
			// var_dump($window);

			if($window->get_window_type() == \WnckWindow::NORMAL) {
				// echo "(" . $window->get_name() . ")" . $window->get_window_type() . "\n";

				// Recupera o grupo da janela
				$group = $window->get_class_group();
				$xid = $group->get_id();

				// Add the icon
				$buff = $group->get_icon();
				$buff = $buff->scale_simple(24, 24, \GdkInterpType::HYPER);
				$image = \GtkImage::new_from_pixbuf($buff);

				// Verifica se o grupo ja existe
				if(isset($this->taskButtons[$xid])) {

				}
				else {

					// echo $group->get_name() . " (" . $group->get_id() . ")" . "\n";
					// var_dump($xid);

					// Cria o botão
					$this->taskButtons[$xid] = new \GtkButton();
					$this->taskButtons[$xid]->set_image($image);
					$this->taskButtons[$xid]->set_tooltip_text($group->get_name());

					// Add css style 
					$style_context = $this->taskButtons[$xid]->get_style_context();
					$style_context->add_provider($this->getCssProvider(), 600);

					if(rand(0, 100) % 2 == 0)
						$style_context->add_class("actived");
					else 
					$style_context->add_class("marked");

					// 
					$this->tasksArea->pack_start($this->taskButtons[$xid], TRUE, TRUE);
				}

				$this->tasksArea->show_all();
			}


		});
	}

}