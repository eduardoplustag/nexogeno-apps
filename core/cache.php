<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_cache_get( $key ) {
	$group = 'nexogeno_apps';
	return wp_cache_get( $key, $group );
}

function nexogeno_apps_cache_set( $key, $value, $ttl = 0 ) {
	$group = 'nexogeno_apps';
	return wp_cache_set( $key, $value, $group, (int) $ttl );
}

