<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * River Display Settings Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   SwiftRiver - http://github.com/ushahidi/Swiftriver_v2
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Controller_River_Display extends Controller_River_Settings {
	
	/**
	 * @return	void
	 */
	public function action_index()
	{
		$this->template->header->title = $this->river->river_name.' ~ '.__('Display Settings');
		
		$this->active = 'display';
		$this->settings_content = View::factory('pages/river/settings/display');
		$this->settings_content->river = $this->river;
		
		$session = Session::instance();		
		if ($this->request->method() == "POST")
		{
			try
			{
				$this->river->river_name = $this->request->post('river_name');
				$this->river->river_public = $this->request->post('river_public');
				$this->river->default_layout = $this->request->post('default_layout');
				$this->river->save();
				
				// Force refresh of cached rivers
		        Cache::instance()->delete('user_rivers_'.$this->user->id);
				
				// Redirect to the new URL with a success messsage
				$session->set("messages", array(__("Display settings were saved successfully.")));
				$this->request->redirect($this->river->get_base_url().'/settings/display');
			}
			catch (ORM_Validation_Exception $e)
			{
				$this->settings_content->errors = $e->errors('validation');
			}
			catch (Database_Exception $e)
			{
				$this->settings_content->errors = array(
					__("A river with the name ':name' name already exists", 
						array(":name" => $this->request->post('river_name'))
					));
			}
		}
		
		// Check for messages
		$this->settings_content->messages = $session->get('messages');
		$session->delete('messages');
	}
	
}