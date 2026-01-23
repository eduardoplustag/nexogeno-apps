<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_python_base64url_encode( $data ) {
	$encoded = base64_encode( $data );
	return rtrim( strtr( $encoded, '+/', '-_' ), '=' );
}

function nexogeno_apps_python_get_secret() {
	if ( defined( 'NEXOGENO_APPS_PYTHON_SECRET' ) && NEXOGENO_APPS_PYTHON_SECRET ) {
		return (string) NEXOGENO_APPS_PYTHON_SECRET;
	}

	return wp_salt( 'nexogeno_apps_python' );
}

function nexogeno_apps_python_get_token_ttl( $app ) {
	$ttl = apply_filters( 'nexogeno_apps_python_token_ttl', 3600, $app );
	$ttl = (int) $ttl;

	return $ttl > 60 ? $ttl : 60;
}

function nexogeno_apps_python_create_token( $app, $user_id ) {
	$now = time();
	$payload = array(
		'iss' => home_url(),
		'app' => isset( $app['id'] ) ? (string) $app['id'] : '',
		'uid' => (int) $user_id,
		'iat' => $now,
		'exp' => $now + nexogeno_apps_python_get_token_ttl( $app ),
	);

	$payload_json = wp_json_encode( $payload );
	$payload_b64  = nexogeno_apps_python_base64url_encode( $payload_json );
	$signature    = hash_hmac( 'sha256', $payload_b64, nexogeno_apps_python_get_secret(), true );
	$signature_b64 = nexogeno_apps_python_base64url_encode( $signature );

	return $payload_b64 . '.' . $signature_b64;
}
