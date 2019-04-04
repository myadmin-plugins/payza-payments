<?php

namespace Detain\MyAdminPayza;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminPayza
 */
class Plugin
{
	public static $name = 'Payza Plugin';
	public static $description = 'Allows handling of Payza based Payments through their Payment Processor/Payment System.';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
			if (has_acl('client_billing')) {
			}
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_page_requirement('pay_balance_payza', '/../vendor/detain/myadmin-payza-payments/src/pay_balance_payza.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_radio_setting(_('Billing'), _('Payza'), 'payza_enable', _('Enable Payza'), _('Enable Payza'), PAYZA_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(_('Billing'), _('Payza'), 'payza_email', _('Email'), _('Email'), (defined('PAYZA_EMAIL') ? PAYZA_EMAIL : ''));
	}
}
