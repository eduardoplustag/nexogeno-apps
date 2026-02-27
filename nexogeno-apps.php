<?php
/**
 * Plugin Name: NexoGENO Apps Core
 * Description: Infraestrutura para Apps NexoGENO com descoberta automática e controle por assinatura.
 * Version: 0.1.2
 * Author: NexoGENO | Plustag 
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
require_once NEXOGENO_APPS_CORE . '/storage.php';
require_once NEXOGENO_APPS_CORE . '/python.php';

nexogeno_apps_bootstrap();

if ( is_admin() ) {
	require_once NEXOGENO_APPS_CORE . '/admin.php';
	nexogeno_apps_admin_bootstrap();
}

function nexogeno_apps_activate() {
	nexogeno_apps_discover_and_register();
	nexogeno_apps_register_routes();
	nexogeno_apps_maybe_install_storage();
	flush_rewrite_rules();
}

function nexogeno_apps_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'nexogeno_apps_activate' );
register_deactivation_hook( __FILE__, 'nexogeno_apps_deactivate' );
