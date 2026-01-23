<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$app = isset( $nexogeno_app ) ? $nexogeno_app : array();
$app_name = $app['name'] ?? 'Nexogeno App';
$user_id = isset( $nexogeno_app_user_id ) ? (int) $nexogeno_app_user_id : get_current_user_id();
$python_url = $app['python_url'] ?? '';
$python_url = apply_filters( 'nexogeno_apps_python_url', $python_url, $app, $user_id );
$iframe_src = '';

if ( $python_url && $user_id ) {
	$token = nexogeno_apps_python_create_token( $app, $user_id );
	$iframe_src = add_query_arg( array( 'nx_token' => $token ), $python_url );
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $app_name ); ?></title>
	<style>
		html, body {
			margin: 0;
			padding: 0;
			height: 100%;
		}
		.nx-studio-wrap {
			min-height: 100vh;
			background: #0b0e13;
		}
		.nx-studio-frame {
			border: 0;
			display: block;
			width: 100%;
			height: 100vh;
		}
		.nx-studio-fallback {
			color: #e5e7eb;
			font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			padding: 48px 24px;
			max-width: 720px;
			margin: 0 auto;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body>
	<main class="nx-studio-wrap">
		<?php if ( $iframe_src ) : ?>
			<iframe
				class="nx-studio-frame"
				src="<?php echo esc_url( $iframe_src ); ?>"
				title="<?php echo esc_attr( $app_name ); ?>"
				referrerpolicy="no-referrer"
				loading="eager"
				allowfullscreen
			></iframe>
		<?php else : ?>
			<div class="nx-studio-fallback">
				<h1><?php echo esc_html( $app_name ); ?></h1>
				<p>URL do app Python nao configurada ou usuario invalido.</p>
			</div>
		<?php endif; ?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
