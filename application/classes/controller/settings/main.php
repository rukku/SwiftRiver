<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Main Settings Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package	   SwiftRiver - http://github.com/ushahidi/Swiftriver_v2
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Controller_Settings_Main extends Controller_Swiftriver {
	
	// Active settings menu
	protected $active;

	/**
	 * Access privileges for this controller and its children
	 */
	public $auth_required = 'admin';
	
	/**
	 * @return	void
	 */
	public function before()
	{
		// Execute parent::before first
		parent::before();
		
		$this->template->content = View::factory('pages/settings/layout')
			->bind('active', $this->active)
			->bind('settings_content', $this->settings_content);
	}
	
	/**
	 * List all the available settings
	 *
	 * @return  void
	 */
	public function action_index()
	{
		$this->template->header->title = __('Application Settings');
		$this->settings_content = View::factory('pages/settings/main')
		    ->bind('action_url', $action_url);

		$this->active = 'main';	
		$action_url = URL::site('settings/main/manage');
		
		// Setting items
		$settings = array(
			'site_name' => '',
			'site_locale' => '',
			'public_registration_enabled' => '',
			'anonymous_access_enabled' => '',
			'river_active_duration' => '',
			'river_expiry_notice_period' => ''
		);

		if ($this->request->post())
		{
			// Setup validation for the application settings
			$validation = Validation::factory($this->request->post())
				->rule('site_name', 'not_empty')
				->rule('site_locale', 'not_empty')
				->rule('river_active_duration', 'not_empty')
				->rule('river_active_duration', 'digit')
				->rule('river_expiry_notice_period', 'not_empty')
				->rule('river_expiry_notice_period', 'digit')
				->rule('form_auth_token', array('CSRF', 'valid'));
			
			if ($validation->check())
			{
				// Set the setting key values
				$settings = array(
					'site_name' => $this->request->post('site_name'),
					'site_locale' => $this->request->post('site_locale'),
					'public_registration_enabled' => $this->request->post('public_registration_enabled') == 1,
					'anonymous_access_enabled' => $this->request->post('anonymous_access_enabled') == 1,
					'river_active_duration' => $this->request->post('river_active_duration'),
					'river_expiry_notice_period' => $this->request->post('river_expiry_notice_period')
				);

				// Update the settings
				Model_Setting::update_settings($settings);
				
				$this->settings_content->set('messages', 
					array(__('The site settings have been updated.')));
			}
			else
			{
				$this->settings_content->set('errors', $validation->errors('user'));
			}
		}
		
		$this->settings_content->settings = Model_Setting::get_settings(array_keys($settings));
	}

}