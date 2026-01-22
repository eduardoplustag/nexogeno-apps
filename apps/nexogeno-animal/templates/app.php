<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$app = isset( $nexogeno_app ) ? $nexogeno_app : array();
$app_name = $app['name'] ?? 'Nexogeno App';

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $app_name ); ?></title>
	<?php wp_head(); ?>
</head>
<body>
	<main>
		<h1><?php echo esc_html( $app_name ); ?></h1>
		<p>App <?php echo esc_html( $app_name ); ?> comming soon.</p>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
