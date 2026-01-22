<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_install_storage() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'nexogeno_apps';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (\n"
		. "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n"
		. "app_id varchar(191) NOT NULL DEFAULT '',\n"
		. "event_type varchar(32) NOT NULL DEFAULT '',\n"
		. "event_status varchar(32) NOT NULL DEFAULT '',\n"
		. "user_id bigint(20) unsigned NOT NULL DEFAULT 0,\n"
		. "subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,\n"
		. "ip_address varchar(45) NOT NULL DEFAULT '',\n"
		. "user_agent varchar(255) NOT NULL DEFAULT '',\n"
		. "message text NULL,\n"
		. "payload longtext NULL,\n"
		. "created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
		. "PRIMARY KEY  (id),\n"
		. "KEY app_id (app_id),\n"
		. "KEY event_type (event_type),\n"
		. "KEY user_id (user_id),\n"
		. "KEY subscription_id (subscription_id),\n"
		. "KEY created_at (created_at)\n"
		. ") {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

function nexogeno_apps_get_storage_table() {
	global $wpdb;

	return $wpdb->prefix . 'nexogeno_apps';
}

function nexogeno_apps_log_event( $app_id, $event_type, $args = array() ) {
	global $wpdb;

	$app_id = sanitize_key( $app_id );
	$event_type = sanitize_key( $event_type );

	if ( ! $app_id || ! $event_type ) {
		return false;
	}

	$defaults = array(
		'status' => '',
		'user_id' => 0,
		'subscription_id' => 0,
		'ip_address' => '',
		'user_agent' => '',
		'message' => '',
		'payload' => null,
		'created_at' => current_time( 'mysql' ),
	);

	$args = array_merge( $defaults, is_array( $args ) ? $args : array() );

	$status = sanitize_key( $args['status'] );
	$user_id = (int) $args['user_id'];
	$subscription_id = (int) $args['subscription_id'];
	$ip_address = $args['ip_address'] ? sanitize_text_field( $args['ip_address'] ) : nexogeno_apps_get_client_ip();
	$user_agent = $args['user_agent'] ? sanitize_text_field( $args['user_agent'] ) : nexogeno_apps_get_user_agent();
	$ip_address = $ip_address && filter_var( $ip_address, FILTER_VALIDATE_IP ) ? $ip_address : '';
	$ip_address = substr( $ip_address, 0, 45 );
	$user_agent = substr( $user_agent, 0, 255 );
	$message = is_string( $args['message'] ) ? $args['message'] : '';
	$message = $message ? sanitize_text_field( $message ) : '';

	$payload = $args['payload'];
	if ( null !== $payload && ! is_string( $payload ) ) {
		$payload = wp_json_encode( $payload );
	}

	$table_name = nexogeno_apps_get_storage_table();

	return (bool) $wpdb->insert(
		$table_name,
		array(
			'app_id' => $app_id,
			'event_type' => $event_type,
			'event_status' => $status,
			'user_id' => $user_id,
			'subscription_id' => $subscription_id,
			'ip_address' => $ip_address,
			'user_agent' => $user_agent,
			'message' => $message,
			'payload' => $payload,
			'created_at' => $args['created_at'],
		),
		array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
	);
}

function nexogeno_apps_maybe_install_storage() {
	$version = '1.1.0';
	$installed = get_option( 'nexogeno_apps_storage_version' );

	if ( $installed === $version ) {
		return;
	}

	nexogeno_apps_install_storage();
	update_option( 'nexogeno_apps_storage_version', $version );
}

function nexogeno_apps_get_client_ip() {
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$forwarded_for = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$parts = explode( ',', $forwarded_for );
		$ip = trim( $parts[0] );
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = trim( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return '';
}

function nexogeno_apps_get_user_agent() {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return '';
	}

	$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	return substr( $user_agent, 0, 255 );
}
