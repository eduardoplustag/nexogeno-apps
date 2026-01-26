<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_user_can_access( $app, $user_id, &$subscription_id = null ) {
	if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	$can_access = nexogeno_apps_user_has_valid_subscription( $app, $user_id, $subscription_id );

	return (bool) apply_filters( 'nexogeno_apps_user_can_access', $can_access, $app, $user_id );
}

function nexogeno_apps_user_has_valid_subscription( $app, $user_id, &$subscription_id = null ) {
	$subscription_id = null;

	if ( ! $user_id ) {
		return false;
	}

	$product_ids = isset( $app['products'] ) ? (array) $app['products'] : array();
	$product_ids = array_values( array_filter( array_map( 'intval', $product_ids ) ) );

	if ( empty( $product_ids ) ) {
		return false;
	}

	if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
		return false;
	}

	$subscriptions = wcs_get_users_subscriptions( $user_id );
	if ( empty( $subscriptions ) ) {
		return false;
	}

	foreach ( $subscriptions as $subscription ) {
		if ( ! nexogeno_apps_subscription_is_valid( $subscription ) ) {
			continue;
		}

		foreach ( $product_ids as $product_id ) {
			if ( method_exists( $subscription, 'has_product' ) && $subscription->has_product( $product_id ) ) {
				$subscription_id = method_exists( $subscription, 'get_id' ) ? (int) $subscription->get_id() : 0;
				return true;
			}
		}
	}

	return false;
}

function nexogeno_apps_subscription_is_valid( $subscription ) {
	if ( ! $subscription ) {
		return false;
	}

	$allowed_statuses = apply_filters( 'nexogeno_apps_allowed_subscription_statuses', array( 'active' ) );
	$allowed_statuses = array_values( array_filter( array_map( 'sanitize_key', (array) $allowed_statuses ) ) );

	if ( empty( $allowed_statuses ) ) {
		return false;
	}

	if ( method_exists( $subscription, 'has_status' ) ) {
		return $subscription->has_status( $allowed_statuses );
	}

	$status = method_exists( $subscription, 'get_status' ) ? $subscription->get_status() : '';
	return in_array( $status, $allowed_statuses, true );
}

function nexogeno_apps_user_has_active_subscription( $user_id ) {
	if ( ! $user_id ) {
		return false;
	}

	if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
		return false;
	}

	$subscriptions = wcs_get_users_subscriptions( $user_id );
	if ( empty( $subscriptions ) ) {
		return false;
	}

	foreach ( $subscriptions as $subscription ) {
		if ( nexogeno_apps_subscription_is_valid( $subscription ) ) {
			return true;
		}
	}

	return false;
}

function nexogeno_apps_should_show_apps_menu( $user_id ) {
	if ( ! $user_id ) {
		return false;
	}

	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	$has_subscription = nexogeno_apps_user_has_active_subscription( $user_id );
	return (bool) apply_filters( 'nexogeno_apps_should_show_apps_menu', $has_subscription, $user_id );
}
