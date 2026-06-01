<?php

ecp_get_log()->emergency( 'Run update settings to version 3.3.1' );


global $wpdb;

$migration_map = array(
	'card'          => 'ecommpay-card',
	'etoken-google' => 'ecommpay-google-pay',
	'etoken'        => 'ecommpay-apple-pay',
);

foreach ( $migration_map as $key => $value ) {
	$sql = "UPDATE {$wpdb->prefix}postmeta t1, {$wpdb->prefix}postmeta t2
        SET t1.meta_value = %s
        WHERE t2.meta_key = '_ecommpay_payment_method' and t2.meta_value = %s
        and t2.post_id = t1.post_id
        and t1.meta_key = '_payment_method' and t1.meta_value = 'ecommpay'";
	ecp_get_log()->emergency( $sql );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table prefix cannot be prepared.
	$wpdb->query( $wpdb->prepare( $sql, $value, $key ) );
}

foreach ( $migration_map as $key => $value ) {
	$sql = "UPDATE{$wpdb->prefix}postmeta t1, {$wpdb->prefix}postmeta t2, {$wpdb->prefix}posts p
        SET t1.meta_value = %s
        WHERE t2.meta_key = '_ecommpay_payment_method' and
        t2.meta_value = %s and
        t2.post_id = p.post_parent and
        p.ID = t1.post_id and
        t1.meta_key = '_payment_method' and
        t1.meta_value = 'ecommpay'";
	ecp_get_log()->emergency( $sql );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table prefix cannot be prepared.
	$wpdb->query( $wpdb->prepare( $sql, $value, $key ) );
}
