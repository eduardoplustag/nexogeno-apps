<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nexogeno_apps_admin_bootstrap() {
	add_action( 'admin_menu', 'nexogeno_apps_register_admin_page' );
	add_action( 'admin_init', 'nexogeno_apps_register_settings' );
	add_action( 'admin_enqueue_scripts', 'nexogeno_apps_admin_enqueue' );
	add_action( 'wp_ajax_nexogeno_apps_product_search', 'nexogeno_apps_ajax_product_search' );
	add_action( 'update_option_nexogeno_apps_status', 'nexogeno_apps_flush_routes_on_status_change', 10, 2 );
}

function nexogeno_apps_register_admin_page() {
	$hook_suffix = add_submenu_page(
		'',
		__( 'NexoGENO Apps', 'nexogeno-apps' ),
		__( 'NexoGENO Apps', 'nexogeno-apps' ),
		'manage_options',
		'nexogeno-apps',
		'nexogeno_apps_render_settings_page'
	);

	if ( $hook_suffix ) {
		$GLOBALS['nexogeno_apps_admin_page_hook'] = $hook_suffix;
	}
}

function nexogeno_apps_register_settings() {
	register_setting( 'nexogeno_apps_settings', 'nexogeno_apps_products', 'nexogeno_apps_sanitize_products_option' );
	register_setting( 'nexogeno_apps_settings', 'nexogeno_apps_status', 'nexogeno_apps_sanitize_status_option' );
}

function nexogeno_apps_sanitize_products_option( $value ) {
	$sanitized = array();

	if ( is_array( $value ) ) {
		foreach ( $value as $app_id => $product_ids ) {
			$app_id = sanitize_key( $app_id );
			if ( ! $app_id ) {
				continue;
			}

			$ids = array_values( array_filter( array_map( 'intval', (array) $product_ids ) ) );
			$sanitized[ $app_id ] = $ids;
		}
	}

	return $sanitized;
}

function nexogeno_apps_sanitize_status_option( $value ) {
	$sanitized = array();

	if ( is_array( $value ) ) {
		foreach ( $value as $app_id => $status ) {
			$app_id = sanitize_key( $app_id );
			if ( ! $app_id ) {
				continue;
			}

			$sanitized[ $app_id ] = empty( $status ) ? 0 : 1;
		}
	}

	return $sanitized;
}

function nexogeno_apps_flush_routes_on_status_change( $old_value, $new_value ) {
	if ( $old_value === $new_value ) {
		return;
	}

	flush_rewrite_rules();
}

function nexogeno_apps_admin_enqueue( $hook ) {
	$admin_page_hook = isset( $GLOBALS['nexogeno_apps_admin_page_hook'] ) ? (string) $GLOBALS['nexogeno_apps_admin_page_hook'] : '';
	if ( ! $admin_page_hook || $admin_page_hook !== $hook ) {
		return;
	}

	$base_url = plugin_dir_url( NEXOGENO_APPS_ROOT . '/nexogeno-apps.php' );

	wp_enqueue_style(
		'nexogeno-apps-admin',
		$base_url . 'core/assets/admin.css',
		array(),
		'0.1.0'
	);

	wp_enqueue_script(
		'nexogeno-apps-admin',
		$base_url . 'core/assets/admin.js',
		array( 'jquery' ),
		'0.1.0',
		true
	);

	wp_localize_script(
		'nexogeno-apps-admin',
		'NexogenoApps',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'nexogeno_apps_product_search' ),
			'minLength' => 2,
			'messages' => array(
				'loading' => __( 'Loading...', 'nexogeno-apps' ),
				'empty' => __( 'No results.', 'nexogeno-apps' ),
				'error' => __( 'Search failed.', 'nexogeno-apps' ),
			),
			'status' => array(
				'active' => __( 'Active', 'nexogeno-apps' ),
				'deactive' => __( 'Deactive', 'nexogeno-apps' ),
			),
			'removeLabel' => __( 'Remove', 'nexogeno-apps' ),
		)
	);
}

function nexogeno_apps_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$apps = nexogeno_apps_get_apps();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'NexoGENO Apps', 'nexogeno-apps' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'nexogeno_apps_settings' ); ?>

			<?php if ( empty( $apps ) ) : ?>
				<p><?php esc_html_e( 'No apps registered yet.', 'nexogeno-apps' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Select subscription products for each app.', 'nexogeno-apps' ); ?></p>
				<table class="widefat fixed striped nexogeno-apps-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'App name', 'nexogeno-apps' ); ?></th>
							<th><?php esc_html_e( 'Config / Products', 'nexogeno-apps' ); ?></th>
							<th><?php esc_html_e( 'View', 'nexogeno-apps' ); ?></th>
							<th><?php esc_html_e( 'Active / Deactive', 'nexogeno-apps' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $apps as $app ) : ?>
							<?php
								$app_id = $app['id'];
								$app_name = $app['name'] ? $app['name'] : $app_id;
								$selected_ids = nexogeno_apps_get_products_for_app( $app_id, $app['products'] );
								$selected_ids = array_values( array_filter( array_map( 'intval', (array) $selected_ids ) ) );
								$is_enabled = nexogeno_apps_get_status_for_app( $app_id, $app['enabled'] );
								$route = ltrim( (string) $app['route'], '/' );
								$view_url = $route ? home_url( trailingslashit( $route ) ) : '';
								?>
							<tr>
								<td class="nexogeno-apps-col-name">
									<strong><?php echo esc_html( $app_name ); ?></strong>
									<div class="description"><?php echo esc_html( $app_id ); ?></div>
								</td>
								<td class="nexogeno-apps-col-config">
									<div class="nexogeno-apps-selector" data-app-id="<?php echo esc_attr( $app_id ); ?>">
										<div class="nexogeno-apps-tags">
											<input type="hidden" name="nexogeno_apps_products[<?php echo esc_attr( $app_id ); ?>][]" value="0">
											<?php foreach ( $selected_ids as $product_id ) : ?>
												<?php $label = nexogeno_apps_get_product_label( $product_id ); ?>
												<span class="nexogeno-apps-tag" data-product-id="<?php echo esc_attr( $product_id ); ?>">
													<span class="nexogeno-apps-tag-label"><?php echo esc_html( $label ); ?></span>
													<button type="button" class="nexogeno-apps-remove" aria-label="<?php esc_attr_e( 'Remove', 'nexogeno-apps' ); ?>">&times;</button>
													<input type="hidden" name="nexogeno_apps_products[<?php echo esc_attr( $app_id ); ?>][]" value="<?php echo esc_attr( $product_id ); ?>">
												</span>
											<?php endforeach; ?>
										</div>
										<input type="text" class="nexogeno-apps-search" placeholder="<?php esc_attr_e( 'Search products...', 'nexogeno-apps' ); ?>">
										<ul class="nexogeno-apps-results"></ul>
									</div>
								</td>
								<td class="nexogeno-apps-col-view">
									<?php if ( $view_url && $is_enabled ) : ?>
										<a class="button nexogeno-apps-view" href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'View', 'nexogeno-apps' ); ?>
											<span class="dashicons dashicons-external" aria-hidden="true"></span>
										</a>
									<?php else : ?>
										<span class="description"><?php esc_html_e( 'Unavailable', 'nexogeno-apps' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="nexogeno-apps-col-status">
									<label class="nexogeno-apps-status">
										<input type="hidden" name="nexogeno_apps_status[<?php echo esc_attr( $app_id ); ?>]" value="0">
										<input type="checkbox" name="nexogeno_apps_status[<?php echo esc_attr( $app_id ); ?>]" value="1" <?php checked( $is_enabled ); ?>>
										<span><?php echo $is_enabled ? esc_html__( 'Active', 'nexogeno-apps' ) : esc_html__( 'Deactive', 'nexogeno-apps' ); ?></span>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function nexogeno_apps_ajax_product_search() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'nexogeno_apps_product_search', 'nonce' );

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	$term = trim( $term );

	if ( '' === $term ) {
		wp_send_json_success( array() );
	}

	$args = array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => 20,
		's' => $term,
		'fields' => 'ids',
		'no_found_rows' => true,
	);

	$term_id = '';
	if ( preg_match( '/^#?[0-9]+$/', $term ) ) {
		$term_id = ltrim( $term, '#' );
	}

	if ( '' !== $term_id ) {
		$args['post__in'] = array( (int) $term_id );
		$args['orderby'] = 'post__in';
		$args['s'] = '';
	}

	$query = new WP_Query( $args );
	$results = array();

	if ( ! empty( $query->posts ) ) {
		foreach ( $query->posts as $product_id ) {
			$results[] = array(
				'id' => $product_id,
				'text' => nexogeno_apps_get_product_label( $product_id ),
			);
		}
	}

	wp_send_json_success( $results );
}

function nexogeno_apps_get_product_label( $product_id ) {
	$product_id = (int) $product_id;
	if ( ! $product_id ) {
		return '';
	}

	$name = '';
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$name = $product->get_name();
		}
	}

	if ( ! $name ) {
		$name = get_the_title( $product_id );
	}

	if ( ! $name ) {
		$name = 'Product';
	}

	return sprintf( '#%d - %s', $product_id, $name );
}
