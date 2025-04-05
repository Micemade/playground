<?php require 'wordpress/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/post.php';

function create_product( $title, $short_desc, $price, $image_url ) {
	$product_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_excerpt' => $short_desc,
			'post_status'  => 'publish',
			'post_type'    => 'product',
		)
	);

	wp_set_object_terms( $product_id, 'simple', 'product_type' );

	// Set product data
	update_post_meta( $product_id, '_price', $price );
	update_post_meta( $product_id, '_regular_price', $price );
	update_post_meta( $product_id, '_manage_stock', 'no' );
	update_post_meta( $product_id, '_stock_status', 'instock' );
	update_post_meta( $product_id, '_virtual', 'no' );
	update_post_meta( $product_id, '_downloadable', 'no' );

	// Download and attach image
	if ( $image_url ) {
		$upload = media_sideload_image( $image_url, $product_id, '', 'id' );
		if ( ! is_wp_error( $upload ) ) {
			set_post_thumbnail( $product_id, $upload );
		}
	}

	return $product_id;
}

$products = array(
	array(
		'title'      => 'Premium Headphones',
		'short_desc' => 'High-quality wireless headphones with noise cancellation.',
		'price'      => '199.99',
		'image_url'  => 'https://raw.githubusercontent.com/Micemade/playground/main/media/mpl-playground/2024/11/audio-monitor-01.jpg',
	),
	array(
		'title'      => 'Smart Watch',
		'short_desc' => 'Feature-rich smartwatch with health monitoring.',
		'price'      => '249.99',
		'image_url'  => 'https://raw.githubusercontent.com/Micemade/playground/main/media/mpl-playground/2024/11/cosmetic-02.jpg',
	),
	array(
		'title'      => 'Wireless Speaker',
		'short_desc' => 'Portable Bluetooth speaker with premium sound quality.',
		'price'      => '129.99',
		'image_url'  => 'https://raw.githubusercontent.com/Micemade/playground/main/media/mpl-playground/2024/11/footwear-02.jpg',
	),
	array(
		'title'      => 'Gaming Mouse',
		'short_desc' => 'Ergonomic gaming mouse with customizable buttons.',
		'price'      => '79.99',
		'image_url'  => 'https://raw.githubusercontent.com/Micemade/playground/main/media/mpl-playground/2024/11/furniture-03.jpg',
	),
);

foreach ( $products as $product ) {
	create_product(
		$product['title'],
		$product['short_desc'],
		$product['price'],
		$product['image_url']
	);
}

