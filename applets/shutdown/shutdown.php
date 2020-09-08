<?php

/**
 * Applet to add a shutdown dialog
 */
class shutdown extends dockStupidoApplet
{

	/**
	 *
	 */
	public function doAfterCreateButton($button) 
	{
		// Add the icon
		$this->setIconName("system-shutdown");
		
		// Click on button
		$button->connect("clicked", function($button) {
			exec("mate-session-save --shutdown-dialog > /dev/null &");
		});

		// Return button to pack
		return $button;
	}

	/**
	 * 
	 */
	public function configure($dock)
	{ }

}