<?php

namespace Detain\MyAdminPayza;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminPayza
 */
class Plugin {

	public static $name = 'Payza Plugin';
	public static $description = 'Allows handling of Payza emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '//my.interserver.net/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Payza');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Payza', '/../vendor/detain/myadmin-payza-payments/src/Payza.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-payza-payments/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-payza-payments/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-payza-payments/src/abuse.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_radio_setting('Billing', 'Payza', 'payza_enable', 'Enable Payza', 'Enable Payza', PAYZA_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Billing', 'Payza', 'payza_email', 'Email ', 'Email ', (defined('PAYZA_EMAIL') ? PAYZA_EMAIL : ''));
	}

}
