<?php 

add_action( 'user_register', 'sync_user_register', 10, 1 );
add_action( 'profile_update', 'sync_user_update', 10, 2 );
add_action( 'delete_user', 'sync_user_delete' );

add_action( 'woocommerce_new_product', 'sync_product_create', 10, 1 );
add_action( 'woocommerce_update_product', 'sync_product_updated', 10, 1 );
add_action( 'wp_trash_post', 'sync_product_delete', 99 );
add_action( 'delete_post', 'sync_product_delete', 99 );

add_action( 'woocommerce_new_order', 'sync_order_create',  1, 1  );
add_action( 'woocommerce_process_shop_order_meta', 'sync_order_update');
add_action( 'wp_trash_post', 'sync_order_delete', 99 );

add_action( 'updated_option', 'sync_store_detail', 10, 3 );

function sync_user_register( $user_id ) {

    add_user_meta($user_id, '_aitrillion_user_sync', 'false');

    $new_users = get_option( '_aitrillion_created_users' );

    $new_users[] = $user_id;

    update_option('_aitrillion_created_users', $new_users);

    return false;
}


function sync_user_update( $user_id, $old_user_data ) {

    add_user_meta($user_id, '_aitrillion_user_sync', 'false');

    $updated_users = get_option( '_aitrillion_updated_users' );

    $updated_users[] = $user_id;

    update_option('_aitrillion_updated_users', $updated_users);

    return false;
    
}


function sync_user_delete( $user_id ) {

    $deleted_users = get_option( '_aitrillion_deleted_users' );

    $deleted_users[] = $user_id;

    update_option('_aitrillion_deleted_users', $deleted_users);

    aitrillion_api_log('user deleted: '.$user_id.PHP_EOL);

    return false;
}


function sync_product_create( $product_id ) {

    add_post_meta($product_id, '_aitrillion_product_sync', 'false');

    $created_products = get_option( '_aitrillion_created_products' );

    $created_products[] = $product_id;

    update_option('_aitrillion_created_products', $created_products);

    return false;
}

function sync_product_updated( $product_id ) {

    add_post_meta($product_id, '_aitrillion_product_sync', 'false');

    $updated_products = get_option( '_aitrillion_updated_products' );

    $updated_products[] = $product_id;

    update_option('_aitrillion_updated_products', $updated_products);

    return false;
}

function sync_product_delete( $post_id ){

    if( get_post_type( $post_id ) != 'product' ) return;

    $deleted_products = get_option( '_aitrillion_deleted_products' );

    $deleted_products[] = $post_id;

    update_option('_aitrillion_deleted_products', $deleted_products);

    aitrillion_api_log('product deleted: '.$post_id.PHP_EOL);

    return false;
    
}

function sync_order_create($order_id){

    add_post_meta($order_id, '_aitrillion_order_sync', 'false');

    $created_orders = get_option( '_aitrillion_created_orders' );

    $created_orders[] = $order_id;

    update_option('_aitrillion_created_orders', $created_orders);

    return false;
}

function sync_order_update ( $order_id )
{

    add_post_meta($order_id, '_aitrillion_order_sync', 'false');

    $updated_products = get_option( '_aitrillion_updated_orders' );

    $updated_products[] = $order_id;

    update_option('_aitrillion_updated_orders', $updated_products);

    return false;
    
}

function sync_order_delete( $post_id ){

    $deleted_orders = get_option( '_aitrillion_deleted_orders' );

    $deleted_orders[] = $post_id;

    update_option('_aitrillion_deleted_orders', $deleted_orders);

    aitrillion_api_log('order deleted: '.$post_id.PHP_EOL);

    return false;
}

function sync_store_detail( $option_name, $old_value, $value ) {

    $store_details = array('woocommerce_store_address', 
                            'woocommerce_store_address_2', 
                            'woocommerce_store_city', 
                            'woocommerce_default_country',
                            'woocommerce_store_postcode',
                            'woocommerce_currency'
                        );

    if( in_array($option_name, $store_details) ){
        
        $return['shop_name'] = DOMAIN;

        $return['shop_type'] = 'woocommerce';
        $return['shop_owner'] = '';
        $return['status'] = 1;
        
        $store_city        = get_option( 'woocommerce_store_city' );
        $store_postcode    = get_option( 'woocommerce_store_postcode' );

        // The country/state
        $store_raw_country = get_option( 'woocommerce_default_country' );

        // Split the country/state
        $split_country = explode( ":", $store_raw_country );

        // Country and state separated:
        $store_country = $split_country[0];
        $store_state   = $split_country[1];

        $return['address1'] = get_option( 'woocommerce_store_address' );
        $return['address2'] = get_option( 'woocommerce_store_address_2' );
        $return['country'] = $store_country;
        $return['city'] = $store_city;
        $return['zip'] = $store_postcode;
        $return['phone'] = '';
        $return['store_name'] = get_bloginfo('name');
        $return['email'] = get_bloginfo( 'admin_email' );

        $return['shop_currency'] = get_woocommerce_currency();
        $return['money_format'] = html_entity_decode(get_woocommerce_currency_symbol());

        //echo '<pre>'; print_r($return); echo '</pre>';

        $json_payload = json_encode($return);

        $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
        $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

        $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

        $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
        $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

        $url = AITRILLION_END_POINT.'shops/update';

        $response = wp_remote_post( $url, array(
            'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                    ),
            'body' => $json_payload
        ));

        //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

        $r = json_decode($response['body']);

        aitrillion_api_log('Store updated: '.PHP_EOL.print_r($return, true).PHP_EOL.print_r($r, true));
    }

}

?>