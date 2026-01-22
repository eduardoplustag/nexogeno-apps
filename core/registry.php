<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_registry_bootstrap() {
	add_action( 'init', 'nexogeno_apps_discover_and_register', 1 );
}

function nexogeno_apps_registry_get_all() {
	return isset( $GLOBALS['nexogeno_apps_registry'] ) ? $GLOBALS['nexogeno_apps_registry'] : array();
}

function nexogeno_apps_registry_get( $app_id ) {
	$apps = nexogeno_apps_registry_get_all();
	return isset( $apps[ $app_id ] ) ? $apps[ $app_id ] : null;
}

function nexogeno_apps_registry_set( $app_id, $config ) {
	if ( ! isset( $GLOBALS['nexogeno_apps_registry'] ) ) {
		$GLOBALS['nexogeno_apps_registry'] = array();
	}

	$GLOBALS['nexogeno_apps_registry'][ $app_id ] = $config;
}

function nexogeno_apps_discover_and_register() {
	$app_files = nexogeno_apps_discover_app_files();
	$app_files = apply_filters( 'nexogeno_apps_app_files', $app_files );

	foreach ( $app_files as $app_file ) {
		$config = include $app_file;
		$config = apply_filters( 'nexogeno_apps_raw_config', $config, $app_file );

		$normalized = nexogeno_apps_normalize_config( $config, $app_file );
		$errors     = nexogeno_apps_validate_config( $normalized );

		if ( ! empty( $errors ) ) {
			do_action( 'nexogeno_apps_invalid_config', $normalized, $errors, $app_file );
			continue;
		}

		nexogeno_apps_registry_set( $normalized['id'], $normalized );
	}

	do_action( 'nexogeno_apps_registered', nexogeno_apps_registry_get_all() );
}

function nexogeno_apps_discover_app_files() {
	$apps_root = rtrim( NEXOGENO_APPS_ROOT, '/' ) . '/apps';
	$pattern   = $apps_root . '/*/app.php';
	$files     = glob( $pattern );

	if ( ! is_array( $files ) ) {
		return array();
	}

	sort( $files );

	return $files;
}

