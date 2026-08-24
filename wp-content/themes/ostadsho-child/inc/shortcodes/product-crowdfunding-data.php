<?php

// هدف مشارکت مردمی
add_shortcode( 'crowdfunding_goal_amount', 'crowdfunding_goal_amount_shortcode' );
function crowdfunding_goal_amount_shortcode( $args ) {

	global $product;

	if ( empty( $product ) ) {
		return '';
	}

	return floatval( get_post_meta( $product->id, 'goal_amount', true ) );
}

//مبلغ جمع شده
add_shortcode( 'crowdfunding_raised_amount', 'crowdfunding_raised_amount_shortcode' );
function crowdfunding_raised_amount_shortcode( $args ) {

	global $product;

	if ( empty( $product ) ) {
		return '';
	}

	return floatval( get_post_meta( $product->id, 'raised_amount', true ) );
}
//کل مبلغ پروژه
add_shortcode( 'crowdfunding_total_budget', 'crowdfunding_total_budget_shortcode' );
function crowdfunding_total_budget_shortcode( $args ) {

	global $product;

	if ( empty( $product ) ) {
		return '';
	}

	return floatval( get_post_meta( $product->id, 'total_budget', true ) );
}
//حداقل مبلغ مشارکت
add_shortcode( 'crowdfunding_minimum_amount', 'crowdfunding_minimum_amount_shortcode' );
function crowdfunding_minimum_amount_shortcode( $args ) {

	global $product;

	if ( empty( $product ) ) {
		return '';
	}

	return floatval( get_post_meta( $product->id, 'minimum_amount', true ) );
}
