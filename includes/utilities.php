<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Gets webinars registration results object from order's meta,
 * Now supports multiple webinars in single order. so it returns array of reg_results objects
 * @param null|WC_Order|WP_Post $post null for global $post
 * @return array
 */
function __webinarjam_get_webinar_registration_results_from_order($post=null){

    if( is_numeric($post) || is_null($post) ){
        $post=get_post($post);
    }

    if( ($post instanceof WP_Post && ('shop_order' === $post->post_type) ) || $post instanceof WC_Abstract_Order) {

        $result = json_decode( get_post_meta($post->ID, 'webinarjam_registration_result', true) );

        // lets check if it's single object or array of objects and turn it to array of objects or null if it's null
        if(is_null($result)){
            return [];
        }

        if(is_object($result)){
            // lets check if it's legacy single reg_result array.
            $result = [$result];
            update_post_meta($post->ID,'webinarjam_registration_result',json_encode($result));
        }

        return  $result;
    }

    return [];
}

/**
 * Check if $post as WP_Post or post_id or global $post is order and have webinarjam products in it
 * @param null|int|WP_Post|WC_Order $post
 * @return bool
 */
function __webinarjam_order_has_webinars($post=null){

    $order=null;

    if( is_numeric($post) || is_null($post) ){
        $post=get_post($post);
    }

    if($post instanceof WP_Post && ('shop_order' === $post->post_type) ) {
        $order = wc_get_order($post->ID);
    }elseif($post instanceof WC_Abstract_Order) {
        $order=$post;
    }

    if($order instanceof WC_Abstract_Order){
        $products = $order->get_items();
        foreach ($products as &$product_item) {
            $product = wc_get_product($product_item['product_id']);
            if ($product->product_type === 'webinarjam') {
                return true;
            }
        }
    }

    return false;
}

/**
 * @param null $post
 * @return array
 */
function __webinarjam_order_get_webinars($post=null){
    $order=null;

    if( is_numeric($post) || is_null($post) ){
        $post=get_post($post);
    }

    if($post instanceof WP_Post && ( 'shop_order' === $post->post_type ) ){
        $order = wc_get_order($post->ID);
    }elseif($post instanceof WC_Abstract_Order) {
        $order=$post;
    }

    $products=[];

    if($order instanceof WC_Abstract_Order){
        $product_items = $order->get_items();
        foreach ($product_items as &$product_item) {
            $product = wc_get_product($product_item['product_id']);
            if ($product->product_type === 'webinarjam') {
                $products[]=$product;
            }
        }
    }
    return $products;
}

/** Returns Last WC_Order->ID from current users, order history where he ordered webinarjam webinar
 * @return int|null
 */
function __webinarjam_get_current_user_last_order_id_with_webinarjam_webinar(){

    $customer = wp_get_current_user();

    if($customer instanceof WP_User && $customer->ID>0){

        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $customer->ID,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
        ) );

        foreach($customer_orders as $customer_order){
            if( __webinarjam_order_has_webinars($customer_order) ){

                if($customer_order instanceof WP_Post){

                    return $customer_order->ID;

                }elseif($customer_order instanceof WC_Abstract_Order){

                    return $customer_order->get_id();

                }elseif( is_array($customer_order ) && isset($customer_order['ID']) ){

                    return $customer_order['ID'];
                }

            }
        }
    }

    return null;

}

/**
 * Returns array of WP_Post or empty array of orders which has webinarjam webinars as product items
 * @return array
 */
function __webinarjam_get_current_user_orders_with_webinarjam_webinars(){

    $orders =[];
    $customer = wp_get_current_user();

    if($customer instanceof WP_User && $customer->ID>0){

        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $customer->ID,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
        ) );

        foreach($customer_orders as $customer_order){
            if( __webinarjam_order_has_webinars($customer_order) ){
                $orders[]=$customer_order;
            }
        }

    }

    return $orders;

}

function __webinarjam_make_placeholder_replacements_for_webinar($content='', $webinar_reg_result ){

    $substitutes=(array)$webinar_reg_result;

    foreach($substitutes as $item=>$value){
        $content=str_replace('{'.$item.'}',$value,$content);
    }

    return $content;

}