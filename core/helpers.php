<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_bootstrap() {
	nexogeno_apps_maybe_install_storage();
	nexogeno_apps_registry_bootstrap();
	nexogeno_apps_router_bootstrap();
	nexogeno_apps_menu_bootstrap();
}

function nexogeno_apps_normalize_config( $config, $source_file ) {
	$defaults = array(
		'id'            => '',
		'name'          => '',
		'version'       => '0.0.0',
		'enabled'       => true,
		'route'         => '',
		'query_var'     => 'nexogeno_app',
		'query_value'   => '',
		'products'      => array(),
		'templates_dir' => '',
		'assets_dir'    => '',
		'template'      => '',
		'python_url'    => '',
		'source_file'   => $source_file,
	);

	$normalized = array_merge( $defaults, is_array( $config ) ? $config : array() );

	$normalized['id']          = sanitize_key( $normalized['id'] );
	$normalized['name']        = is_string( $normalized['name'] ) ? $normalized['name'] : '';
	$normalized['version']     = is_string( $normalized['version'] ) ? $normalized['version'] : '0.0.0';
	$normalized['enabled']     = (bool) $normalized['enabled'];
	$normalized['route']       = ltrim( (string) $normalized['route'], '/' );
	$normalized['query_var']   = sanitize_key( $normalized['query_var'] );
	$normalized['query_value'] = (string) ( $normalized['query_value'] !== '' ? $normalized['query_value'] : $normalized['id'] );
	$normalized['products']    = array_values( array_filter( array_map( 'intval', (array) $normalized['products'] ) ) );
	$normalized['templates_dir'] = (string) $normalized['templates_dir'];
	$normalized['assets_dir']    = (string) $normalized['assets_dir'];
	$normalized['template']      = (string) $normalized['template'];
	$normalized['python_url']    = esc_url_raw( (string) $normalized['python_url'] );
	$override_products = nexogeno_apps_get_products_for_app( $normalized['id'], null );
	if ( null !== $override_products ) {
		$normalized['products'] = $override_products;
	}
	$override_status = nexogeno_apps_get_status_for_app( $normalized['id'], null );
	if ( null !== $override_status ) {
		$normalized['enabled'] = $override_status;
	}

	if ( $normalized['templates_dir'] && ! $normalized['template'] ) {
		$default_template = rtrim( $normalized['templates_dir'], '/' ) . '/app.php';
		if ( file_exists( $default_template ) ) {
			$normalized['template'] = $default_template;
		}
	}

	return $normalized;
}

function nexogeno_apps_validate_config( $config ) {
	$errors = array();

	if ( empty( $config['id'] ) ) {
		$errors[] = 'missing_id';
	}

	if ( empty( $config['route'] ) ) {
		$errors[] = 'missing_route';
	}

	if ( empty( $config['query_var'] ) ) {
		$errors[] = 'missing_query_var';
	}

	return $errors;
}

function nexogeno_apps_get_apps() {
	return nexogeno_apps_registry_get_all();
}

function nexogeno_apps_get_app( $app_id ) {
	$app_id = sanitize_key( $app_id );
	return nexogeno_apps_registry_get( $app_id );
}

function nexogeno_apps_get_query_vars() {
	$query_vars = array();
	foreach ( nexogeno_apps_get_apps() as $app ) {
		if ( empty( $app['query_var'] ) ) {
			continue;
		}
		$query_vars[] = $app['query_var'];
	}

	return array_values( array_unique( $query_vars ) );
}

function nexogeno_apps_find_by_request() {
	foreach ( nexogeno_apps_get_apps() as $app ) {
		$query_var = $app['query_var'];
		if ( ! $query_var ) {
			continue;
		}

		$current_value = get_query_var( $query_var );
		if ( $current_value && (string) $current_value === (string) $app['query_value'] ) {
			return $app;
		}
	}

	return null;
}

function nexogeno_apps_get_products_for_app( $app_id, $default = null ) {
	$app_id = sanitize_key( $app_id );
	if ( ! $app_id ) {
		return $default;
	}

	$stored = get_option( 'nexogeno_apps_products', array() );
	if ( ! is_array( $stored ) || ! array_key_exists( $app_id, $stored ) ) {
		return $default;
	}

	$products = array_values( array_filter( array_map( 'intval', (array) $stored[ $app_id ] ) ) );
	return $products;
}

function nexogeno_apps_get_status_for_app( $app_id, $default = null ) {
	$app_id = sanitize_key( $app_id );
	if ( ! $app_id ) {
		return $default;
	}

	$stored = get_option( 'nexogeno_apps_status', array() );
	if ( ! is_array( $stored ) || ! array_key_exists( $app_id, $stored ) ) {
		return $default;
	}

	return (bool) $stored[ $app_id ];
}

function nexogeno_apps_menu_bootstrap() {
	add_filter( 'wp_nav_menu_objects', 'nexogeno_apps_filter_menu_objects', 10, 2 );
	add_filter( 'wp_get_nav_menu_items', 'nexogeno_apps_filter_nav_menu_items', 10, 3 );
}

function nexogeno_apps_filter_menu_objects( $items, $args ) {
	if ( empty( $items ) ) {
		return $items;
	}

	$user_id = get_current_user_id();
	if ( ! nexogeno_apps_should_show_apps_menu( $user_id ) ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( empty( $item->classes ) || ! in_array( 'apps', $item->classes, true ) ) {
			continue;
		}

		$item->classes = array_values( array_diff( $item->classes, array( 'hide' ) ) );
	}

	return $items;
}

function nexogeno_apps_filter_nav_menu_items( $items, $menu, $args ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $items;
	}

	$menu_id = 0;
	if ( is_object( $menu ) && isset( $menu->term_id ) ) {
		$menu_id = (int) $menu->term_id;
	} elseif ( is_numeric( $menu ) ) {
		$menu_id = (int) $menu;
	}

	$target_menu_id = (int) apply_filters( 'nexogeno_apps_menu_id', 119 );
	if ( $menu_id !== $target_menu_id ) {
		return $items;
	}

	$apps = nexogeno_apps_get_apps();
	if ( empty( $apps ) ) {
		return $items;
	}

	$dynamic_items = array();
	$position = 1;

	foreach ( $apps as $app ) {
		if ( empty( $app['enabled'] ) ) {
			continue;
		}

		$product_ids = isset( $app['products'] ) ? (array) $app['products'] : array();
		$product_ids = array_values( array_filter( array_map( 'intval', $product_ids ) ) );
		if ( empty( $product_ids ) ) {
			continue;
		}

		$url = nexogeno_apps_get_app_url( $app );
		if ( ! $url ) {
			continue;
		}

		$title = ! empty( $app['name'] ) ? $app['name'] : $app['id'];
		$dynamic_items[] = nexogeno_apps_build_menu_item( $title, $url, $position );
		$position++;
	}

	return ! empty( $dynamic_items ) ? $dynamic_items : $items;
}

function nexogeno_apps_get_app_url( $app ) {
	if ( ! is_array( $app ) ) {
		return '';
	}

	if ( ! empty( $app['route'] ) ) {
		$path = '/' . ltrim( (string) $app['route'], '/' );
		return user_trailingslashit( home_url( $path ) );
	}

	if ( ! empty( $app['query_var'] ) && isset( $app['query_value'] ) ) {
		$base = home_url( '/' );
		return add_query_arg( array( $app['query_var'] => (string) $app['query_value'] ), $base );
	}

	return '';
}

function nexogeno_apps_build_menu_item( $title, $url, $position = 1 ) {
	$virtual_id = 100000 + (int) $position;
	$item = (object) array(
		'ID'               => $virtual_id,
		'db_id'            => 0,
		'menu_item_parent' => 0,
		'object_id'        => 0,
		'object'           => 'custom',
		'type'             => 'custom',
		'title'            => $title,
		'url'              => $url,
		'target'           => '_blank',
		'attr_title'       => '',
		'description'      => '',
		'classes'          => array( 'menu-item', 'menu-item-type-custom', 'menu-item-object-custom', 'nexogeno-app-item' ),
		'xfn'              => 'noopener noreferrer',
		'menu_order'       => (int) $position,
		'status'           => 'publish',
	);

	return wp_setup_nav_menu_item( $item );
}
