<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_router_bootstrap() {
	add_action( 'init', 'nexogeno_apps_register_routes', 10 );
	add_filter( 'query_vars', 'nexogeno_apps_register_query_vars' );
	add_action( 'template_redirect', 'nexogeno_apps_handle_request' );
}

function nexogeno_apps_register_query_vars( $vars ) {
	foreach ( nexogeno_apps_get_query_vars() as $query_var ) {
		if ( ! in_array( $query_var, $vars, true ) ) {
			$vars[] = $query_var;
		}
	}

	return $vars;
}

function nexogeno_apps_register_routes() {
	foreach ( nexogeno_apps_get_apps() as $app ) {
		if ( empty( $app['enabled'] ) || empty( $app['route'] ) ) {
			continue;
		}

		$regex = '^' . preg_quote( $app['route'], '#' ) . '/?$';
		$query = 'index.php?' . $app['query_var'] . '=' . rawurlencode( $app['query_value'] );

		add_rewrite_rule( $regex, $query, 'top' );
	}
}

function nexogeno_apps_handle_request() {
	$app = nexogeno_apps_find_by_request();
	if ( ! $app ) {
		return;
	}

	$user_id = get_current_user_id();
	$subscription_id = null;

	if ( empty( $app['enabled'] ) ) {
		nexogeno_apps_log_event(
			$app['id'],
			'access',
			array(
				'status' => 'denied',
				'user_id' => $user_id,
				'message' => 'App disabled',
			)
		);
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( 'App desativado.', 'nexogeno-apps' ) );
	}

	if ( ! nexogeno_apps_user_can_access( $app, $user_id, $subscription_id ) ) {
		nexogeno_apps_log_event(
			$app['id'],
			'access',
			array(
				'status' => 'denied',
				'user_id' => $user_id,
				'subscription_id' => $subscription_id,
				'message' => 'Access denied',
			)
		);
		nexogeno_apps_render_access_denied( $app, $user_id );
		exit;
	}

	nexogeno_apps_log_event(
		$app['id'],
		'access',
		array(
			'status' => 'allowed',
			'user_id' => $user_id,
			'subscription_id' => $subscription_id,
			'message' => 'Access granted',
		)
	);

	nexogeno_apps_render_app( $app, $user_id );
	exit;
}

function nexogeno_apps_render_access_denied( $app, $user_id ) {
	do_action( 'nexogeno_apps_access_denied', $app, $user_id );

	status_header( 403 );
	nocache_headers();

	$message = apply_filters( 'nexogeno_apps_access_denied_message', __( 'Acesso negado.', 'nexogeno-apps' ), $app, $user_id );
	wp_die( esc_html( $message ) );
}

function nexogeno_apps_render_app( $app, $user_id ) {
	do_action( 'nexogeno_apps_before_render', $app, $user_id );

	$template = $app['template'];
	if ( $template && file_exists( $template ) ) {
		$nexogeno_app         = $app;
		$nexogeno_app_user_id = $user_id;
		include $template;
	} else {
		$payload = array(
			'app'     => $app,
			'user_id' => $user_id,
		);
		echo wp_json_encode( $payload );
	}

	do_action( 'nexogeno_apps_after_render', $app, $user_id );
}
