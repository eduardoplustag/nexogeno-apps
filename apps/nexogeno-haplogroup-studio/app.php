<?php

return array(
	'id'            => 'nexogeno-haplogroup-studio',
	'name'          => 'NexoGENO Haplogroup Studio',
	'version'       => '0.1.0',
	'enabled'       => true,
	'route'         => 'app-nexogeno-haplogroup-studio',
	'query_var'     => 'nexogeno_app',
	'query_value'   => 'haplogroup-studio',
	'products'      => array(5318),
	'templates_dir' => __DIR__ . '/templates',
	'template'      => __DIR__ . '/templates/app.php',
	'python_url'    => 'https://nexogeno-haplogroup-studio.nexogeno.com',
);
