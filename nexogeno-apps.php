<?php
/**
 * Plugin Name: Nexogeno Apps Core
 * Description: Infraestrutura para Apps Nexogeno com descoberta automática e controle por assinatura.
 * Version: 0.1.0
 * Author: Nexogeno
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEXOGENO_APPS_ROOT', __DIR__ );
define( 'NEXOGENO_APPS_CORE', NEXOGENO_APPS_ROOT . '/core' );

require_once NEXOGENO_APPS_CORE . '/helpers.php';
require_once NEXOGENO_APPS_CORE . '/registry.php';
require_once NEXOGENO_APPS_CORE . '/router.php';
require_once NEXOGENO_APPS_CORE . '/access.php';
require_once NEXOGENO_APPS_CORE . '/cache.php';

nexogeno_apps_bootstrap();

function nexogeno_apps_activate() {
	nexogeno_apps_discover_and_register();
	nexogeno_apps_register_routes();
	flush_rewrite_rules();
}

function nexogeno_apps_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'nexogeno_apps_activate' );
register_deactivation_hook( __FILE__, 'nexogeno_apps_deactivate' );
