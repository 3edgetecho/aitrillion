<?php 

add_action( 'user_register', 'aitrillion_sync_user_register', 10, 1 );
add_action( 'profile_update', 'aitrillion_sync_user_update', 10, 2 );
add_action( 'delete_user', 'aitrillion_sync_user_delete' );

add_action( 'woocommerce_new_product', 'aitrillion_sync_product_create', 10, 1 );
add_action( 'woocommerce_update_product', 'aitrillion_sync_product_updated', 10, 1 );
add_action( 'wp_trash_post', 'aitrillion_sync_product_delete', 99 );
add_action( 'delete_post', 'aitrillion_sync_product_delete', 99 );

add_action('create_product_cat', 'aitrillion_sync_product_category_create', 10, 1);
add_action('edit_product_cat', 'aitrillion_sync_product_category_update', 10, 1);
add_action('delete_product_cat', 'aitrillion_sync_product_category_delete', 10, 1);

add_action( 'woocommerce_new_order', 'aitrillion_sync_order_create',  1, 1  );
add_action( 'woocommerce_process_shop_order_meta', 'aitrillion_sync_order_update');
add_action( 'wp_trash_post', 'aitrillion_sync_order_delete', 99 );

add_action( 'updated_option', 'aitrillion_sync_store_detail', 10, 3 );

function aitrillion_sync_user_register( $user_id ) {

    update_user_meta($user_id, '_aitrillion_user_sync', 'false');

    $new_users = get_option( '_aitrillion_created_users' );

    $new_users[] = $user_id;

    update_option('_aitrillion_created_users', $new_users);

    return false;
}


function aitrillion_sync_user_update( $user_id, $old_user_data ) {

    update_user_meta($user_id, '_aitrillion_user_sync', 'false');

    $updated_users = get_option( '_aitrillion_updated_users' );

    $updated_users[] = $user_id;

    update_option('_aitrillion_updated_users', $updated_users);

    return false;
    
}


function aitrillion_sync_user_delete( $user_id ) {

    $deleted_users = get_option( '_aitrillion_deleted_users' );

    $deleted_users[] = $user_id;

    update_option('_aitrillion_deleted_users', $deleted_users);

    aitrillion_api_log('user deleted: '.$user_id.PHP_EOL);

    return false;
}


function aitrillion_sync_product_create( $product_id ) {

    add_post_meta($product_id, '_aitrillion_product_sync', 'false');

    $created_products = get_option( '_aitrillion_created_products' );

    $created_products[] = $product_id;

    update_option('_aitrillion_created_products', $created_products);

    return true;
}

function aitrillion_sync_product_updated( $product_id ) {

    update_post_meta($product_id, '_aitrillion_product_sync', 'false');

    $updated_products = get_option( '_aitrillion_updated_products' );

    $updated_products[] = $product_id;

    update_option('_aitrillion_updated_products', $updated_products);

    return false;
}

function aitrillion_sync_product_delete( $post_id ){

    if( get_post_type( $post_id ) != 'product' ) return;

    $deleted_products = get_option( '_aitrillion_deleted_products' );

    $deleted_products[] = $post_id;

    update_option('_aitrillion_deleted_products', $deleted_products);

    aitrillion_api_log('product deleted: '.$post_id.PHP_EOL);

    return false;
    
}

function aitrillion_sync_product_category_create( $category_id ){

    add_term_meta($category_id, '_aitrillion_category_sync', 'false');

    $created_categories = get_option( '_aitrillion_created_categories' );

    $created_categories[] = $category_id;

    update_option('_aitrillion_created_categories', $created_categories);

    return true;
}

function aitrillion_sync_product_category_update( $category_id ){

    add_term_meta($category_id, '_aitrillion_category_sync', 'false');

    $updated_categories = get_option( '_aitrillion_updated_categories' );

    $updated_categories[] = $category_id;

    update_option('_aitrillion_updated_categories', $updated_categories);

    return true;
}

function aitrillion_sync_product_category_delete( $category_id ){

    $deleted_categories = get_option( '_aitrillion_deleted_categories' );

    $deleted_categories[] = $category_id;

    update_option('_aitrillion_deleted_categories', $deleted_categories);

    aitrillion_api_log('category deleted: '.$category_id.PHP_EOL);

    return true;
}

function aitrillion_sync_order_create($order_id){

    update_post_meta($order_id, '_aitrillion_order_sync', 'false');

    $created_orders = get_option( '_aitrillion_created_orders' );

    $created_orders[] = $order_id;

    update_option('_aitrillion_created_orders', $created_orders);

    return false;
}

function aitrillion_sync_order_update ( $order_id )
{

    update_post_meta($order_id, '_aitrillion_order_sync', 'false');

    $updated_products = get_option( '_aitrillion_updated_orders' );

    $updated_products[] = $order_id;

    update_option('_aitrillion_updated_orders', $updated_products);

    return false;
    
}

function aitrillion_sync_order_delete( $post_id ){

    $deleted_orders = get_option( '_aitrillion_deleted_orders' );

    $deleted_orders[] = $post_id;

    update_option('_aitrillion_deleted_orders', $deleted_orders);

    aitrillion_api_log('order deleted: '.$post_id.PHP_EOL);

    return false;
}

function aitrillion_sync_store_detail( $option_name, $old_value, $value ) {

    $store_details = array('woocommerce_store_address', 
                            'woocommerce_store_address_2', 
                            'woocommerce_store_city', 
                            'woocommerce_default_country',
                            'woocommerce_store_postcode',
                            'woocommerce_currency'
                        );

    if( in_array($option_name, $store_details) ){

        update_option('_aitrillion_shop_updated', true);
    }

}

?>