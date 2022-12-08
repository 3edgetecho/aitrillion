<?php 


// If a cron job interval does not already exist, create one.
 
add_filter( 'cron_schedules', 'check_every_minute' );

function check_every_minute( $schedules ) {
    $schedules['every_minute'] = array(
        'interval' => 60, // in seconds
        'display'  => __( 'Every Minute' ),
    );

    return $schedules;
}

// Unless an event is already scheduled, create one.
 
add_action( 'init', 'aitrillion_data_sync_cron' );
 
function aitrillion_data_sync_cron() {

    if ( ! wp_next_scheduled( 'aitrillion_data_sync_schedule' ) ) {
        wp_schedule_event( time(), 'every_minute', 'aitrillion_data_sync_schedule' );
    }
}
 
add_action( 'aitrillion_data_sync_schedule', 'aitrillion_data_sync_action' );


function aitrillion_data_sync_action() { 

    echo '<br>cron called';

    sync_new_users();
    sync_updated_users();  
    sync_deleted_users();  

    sync_new_products();
    sync_updated_products();  
    sync_deleted_products(); 

    sync_new_orders();
    sync_updated_orders();
    sync_deleted_orders();

}


function sync_new_users(){
    
    $users = get_option( '_aitrillion_created_users' );

    aitrillion_api_log('new user sync log '.print_r($users, true).PHP_EOL);

    if(!empty($users)){

        $users = array_unique($users);

        foreach($users as $user_id){

            aitrillion_api_log('user id '.$user_id.PHP_EOL);

            $customer = new WC_Customer( $user_id );

            $c = array();

            $c['id'] = $user_id;
            $c['first_name'] = $customer->get_first_name();
            $c['last_name'] = $customer->get_last_name();
            $c['email'] = $customer->get_email();
            $c['verified_email'] = true;
            $c['accepts_marketing'] = false;
            $c['phone'] = $customer->get_billing_phone();
            $c['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');

            if($customer->get_date_modified()){
                $c['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
            }else{
                $c['updated_at'] = null;
            }

            $c['orders_count'] = $customer->get_order_count();
            $c['total_spent'] = $customer->get_total_spent();

            $c['last_order_name'] = null;    // TODO update with order number
            $c['last_order_id'] = null;    // TODO update with order ID

            $c['currency'] = null;
            $c['note'] = null;

            $c['addresses'] = array(
                            'address1' => $customer->get_billing_address_1(),
                            'city' => $customer->get_billing_city(),
                            'province' => $customer->get_billing_state(),
                            'zip' => $customer->get_billing_postcode(),
                            'phone' => $customer->get_billing_phone(),
                            'first_name' => $customer->get_billing_first_name(),
                            'last_name' => $customer->get_billing_last_name(),
                            'country_code' => $customer->get_billing_country(),
                        );

            aitrillion_api_log('customer '.print_r($c, true).PHP_EOL);
            
            $json_payload = json_encode($c);

            //aitrillion_api_log('customer json_payload '.$json_payload.PHP_EOL);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            //aitrillion_api_log('customer bearer '.$bearer.PHP_EOL);

            //echo '<br>bearer: '.$bearer;

            //echo '<br>'.$json_payload;

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'customers/create';

            //aitrillion_api_log('customer add end point '.$url.PHP_EOL);

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //aitrillion_api_log('API Response : '.print_r($response, true).PHP_EOL);

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            //exit;

            $r = json_decode($response['body']);

            update_user_meta($user_id, '_aitrillion_user_sync', 'true');

            aitrillion_api_log('API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));
        }

        delete_option('_aitrillion_created_users');
    }

    
}

function sync_updated_users(){

    $users = get_option( '_aitrillion_updated_users' );

    aitrillion_api_log('udpated users sync log '.print_r($users, true).PHP_EOL);

    if(!empty($users)){

        $users = array_unique($users);

        foreach($users as $user_id){

            aitrillion_api_log('user id '.$user_id.PHP_EOL);

            $customer = new WC_Customer( $user_id );

            $c = array();

            $c['id'] = $user_id;
            $c['first_name'] = $customer->get_first_name();
            $c['last_name'] = $customer->get_last_name();
            $c['email'] = $customer->get_email();
            $c['verified_email'] = true;
            $c['accepts_marketing'] = false;
            $c['phone'] = $customer->get_billing_phone();
            $c['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');

            if($customer->get_date_modified()){
                $c['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
            }else{
                $c['updated_at'] = null;
            }

            $c['orders_count'] = $customer->get_order_count();
            $c['total_spent'] = $customer->get_total_spent();

            $c['last_order_name'] = null;    // TODO update with order number
            $c['last_order_id'] = null;    // TODO update with order ID

            $c['currency'] = null;
            $c['note'] = null;

            $c['addresses'] = array(
                            'address1' => $customer->get_billing_address_1(),
                            'city' => $customer->get_billing_city(),
                            'province' => $customer->get_billing_state(),
                            'zip' => $customer->get_billing_postcode(),
                            'phone' => $customer->get_billing_phone(),
                            'first_name' => $customer->get_billing_first_name(),
                            'last_name' => $customer->get_billing_last_name(),
                            'country_code' => $customer->get_billing_country(),
                        );

            aitrillion_api_log('customer '.print_r($c, true).PHP_EOL);
            
            $json_payload = json_encode($c);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            //echo '<br>bearer: '.$bearer;

            //echo '<br>'.$json_payload;

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'customers/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            update_user_meta($user_id, '_aitrillion_user_sync', 'true');

            aitrillion_api_log('API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));
        }
    }

    delete_option('_aitrillion_updated_users');
}


function sync_deleted_users(){

    $deleted_users = get_option( '_aitrillion_deleted_users' );

    aitrillion_api_log('deleted users sync log: '.print_r($deleted_users, true).PHP_EOL);

    if(!empty($deleted_users)){

        $deleted_users = array_unique($deleted_users);

        foreach($deleted_users as $k => $user_id){

            //aitrillion_api_log('user delete id: '.$user_id.PHP_EOL);

            $json_payload = json_encode(array('id' => $user_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'customers/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            $r = json_decode($response['body']);

            aitrillion_api_log('API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));

        }

        delete_option('_aitrillion_deleted_users');
    }

}

function sync_new_products(){

    $products = get_option( '_aitrillion_created_products' );

    aitrillion_api_log('new product sync log '.print_r($products, true).PHP_EOL);

    if(!empty($products)){

        $products = array_unique($products);

        foreach($products as $product_id){

            $product = wc_get_product( $product_id );

            //echo '<br>product: <pre>'; print_r($product); echo '</pre>';

            $p['id'] = $product->get_id();
            $p['title'] = $product->get_title();
            $p['vendor'] = '';
            $p['product_type'] = $product->get_type();
            $p['created_at'] = $product->get_date_created()->date('Y-m-d H:i:s');
            $p['handle'] = get_permalink( $product->get_id() );
            $p['updated_at'] = $product->get_date_modified()->date('Y-m-d H:i:s');
            $p['published_at'] = $p['created_at'];

            $terms = wp_get_post_terms( $product->get_id(), 'product_tag' );
         
            $tags = null;
            // Loop through each product tag for the current product
            if ( count( $terms ) > 0 ) {
         
                foreach( $terms as $term ) {
                    $tags_array[] = $term->name;
                }
         
                // Combine all of the product tags into one string for output
                $tags = implode( ',', $tags_array );
            }

            $p['tags'] = $tags;

            //$p['body_html'] = $product->get_description();
            //$p['published_scope'] = 'web';

            if($product->get_type() == 'variable'){

                $available_variations = $product->get_available_variations();

                //echo '<br>available_variations: <pre>'; print_r($available_variations); echo '</pre>';

                $attributes = array();

                $position = 1;

                foreach ($available_variations as $key => $variations) 
                { 
                    $a = array();

                    $a['id'] = $variations['variation_id'];
                    $a['product_id'] = $p['id'];
                    $a['title'] = $p['title'];
                    $a['price'] = $variations['display_price'];
                    $a['sku'] = $variations['sku'];
                    $a['position'] = $position;
                    $a['inventory_policy'] = null;
                    $a['compare_at_price'] = $variations['display_regular_price'];
                    $a['fulfillment_service'] = null;
                    $a['inventory_management'] = null;

                    $a['created_at'] = $p['created_at'];
                    $a['updated_at'] = $p['updated_at'];
                    $a['taxable'] = wc_prices_include_tax() ? true : false;
                    $a['barcode'] = null;
                    $a['grams'] = null;
                    $a['image_id'] = $variations['image_id'];

                    $a['weight'] = $variations['weight'];
                    $a['weight_unit'] = get_option('woocommerce_weight_unit');

                    $a['inventory_item_id'] = null;
                    $a['inventory_quantity'] = $variations['max_qty'];

                    $a['old_inventory_quantity'] = null;
                    $a['presentment_prices'] = null;
                    $a['requires_shipping'] = $product->get_virtual() ? false : true;

                    //echo '<pre>'; print_r($variations['attributes']); echo '</pre>';
                    $option_count = 1;
                    foreach($variations['attributes'] as $key => $val){

                        if(isset($val) && !empty($val)){

                            //echo '<br>key: '.$key;

                            $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*

                            $a['title'] = $option_name;

                            //echo '<br>key: '.$option_name.', Val: '.$val;

                            $a['option'.$option_count] = $val;

                            $option_count++;
                        }

                    }

                    $position++;

                    $attributes[] = $a;

                }

                $p['variants'] = $attributes;

            }else{

                $p['variants'][]['id'] = $p['id'];
                $p['variants'][]['product_id'] = $p['id'];
                $p['variants'][]['title'] = $p['title'];
                $p['variants'][]['price'] = $product->get_sale_price();
                $p['variants'][]['sku'] = $product->get_sku();
                $p['variants'][]['position'] = 1;
                $p['variants'][]['inventory_policy'] = null;
                $p['variants'][]['compare_at_price'] = $product->get_regular_price();
                $p['variants'][]['fulfillment_service'] = null;
                $p['variants'][]['inventory_management'] = null;
                $p['variants'][]['option1'] = null;
                $p['variants'][]['option2'] = null;
                $p['variants'][]['option3'] = null;
                $p['variants'][]['created_at'] = $p['created_at'];
                $p['variants'][]['updated_at'] = $p['updated_at'];
                $p['variants'][]['taxable'] = wc_prices_include_tax() ? true : false;
                $p['variants'][]['barcode'] = null;
                $p['variants'][]['grams'] = null;
                $p['variants'][]['image_id'] = $product->get_image_id();
                $p['variants'][]['weight'] = $product->get_weight();
                $p['variants'][]['weight_unit'] = get_option('woocommerce_weight_unit');

                $p['variants'][]['inventory_item_id'] = null;
                $p['variants'][]['inventory_quantity'] = $product->get_stock_quantity();

                $p['variants'][]['old_inventory_quantity'] = null;
                $p['variants'][]['presentment_prices'] = null;
                $p['variants'][]['requires_shipping'] = $product->get_virtual() ? false : true;
                
            }

            $image_id        = $product->get_image_id();

            $img = array();

            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );

                $img[] = array('id' => $image_id, 
                                'product_id' => $p['id'], 
                                'src' => $image_url, 
                                'position' => 1, 
                                'created_at' => $p['created_at'], 
                                'updated_at' => $p['updated_at'],
                                'alt' => null,
                                'width' => null,
                                'height' => null,
                                'variant_ids' => array()

                            );
            }

            $attachment_ids  = $product->get_gallery_image_ids();

            $position = 2;
            foreach ( $attachment_ids as $attachment_id ) {
                $image_url = wp_get_attachment_url( $attachment_id );

                $img[] = array('id' => $attachment_id, 
                                'product_id' => $p['id'], 
                                'src' => $image_url, 
                                'position' => $position, 
                                'created_at' => $p['created_at'], 
                                'updated_at' => $p['updated_at'],
                                'alt' => null,
                                'width' => null,
                                'height' => null,
                                'variant_ids' => array()
                            );

                $position++;
            }

            //$product_images['images']      = $img;

            $p['images'] = $img;

            //echo '<br>status: '.$product->get_type();

            $p['options'] = array();    

            //echo '<pre>'; print_r($p); echo '</pre>';

            aitrillion_api_log('Product: '.$p.PHP_EOL);

            $json_payload = json_encode($p);

            //echo '<pre>'; print_r($json_payload); echo '</pre>';

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            //exit;

            $r = json_decode($response['body']);

            update_post_meta($product_id, '_aitrillion_product_sync', 'true');

            aitrillion_api_log('API Response for product id: '.$product_id.PHP_EOL.print_r($r, true));

            //exit;
        }

        delete_option('_aitrillion_created_products');
    }

}

function sync_updated_products(){

    $products = get_option( '_aitrillion_updated_products' );

    aitrillion_api_log('updated product sync log '.print_r($products, true).PHP_EOL);

    if(!empty($products)){

        $products = array_unique($products);

        foreach($products as $product_id){

            $product = wc_get_product( $product_id );

            //echo '<br>product: <pre>'; print_r($product); echo '</pre>';

            $p['id'] = $product->get_id();
            $p['title'] = $product->get_title();
            $p['vendor'] = '';
            $p['product_type'] = $product->get_type();
            $p['created_at'] = $product->get_date_created()->date('Y-m-d H:i:s');
            $p['handle'] = get_permalink( $product->get_id() );
            $p['updated_at'] = $product->get_date_modified()->date('Y-m-d H:i:s');
            $p['published_at'] = $p['created_at'];

            $terms = wp_get_post_terms( $product->get_id(), 'product_tag' );
         
            $tags = null;
            // Loop through each product tag for the current product
            if ( count( $terms ) > 0 ) {
         
                foreach( $terms as $term ) {
                    $tags_array[] = $term->name;
                }
         
                // Combine all of the product tags into one string for output
                $tags = implode( ',', $tags_array );
            }

            $p['tags'] = $tags;

            //$p['body_html'] = $product->get_description();
            //$p['published_scope'] = 'web';

            if($product->get_type() == 'variable'){

                $available_variations = $product->get_available_variations();

                //echo '<br>available_variations: <pre>'; print_r($available_variations); echo '</pre>';

                $attributes = array();

                $position = 1;

                foreach ($available_variations as $key => $variations) 
                { 
                    $a = array();

                    $a['id'] = $variations['variation_id'];
                    $a['product_id'] = $p['id'];
                    $a['title'] = $p['title'];
                    $a['price'] = $variations['display_price'];
                    $a['sku'] = $variations['sku'];
                    $a['position'] = $position;
                    $a['inventory_policy'] = null;
                    $a['compare_at_price'] = $variations['display_regular_price'];
                    $a['fulfillment_service'] = null;
                    $a['inventory_management'] = null;

                    $a['created_at'] = $p['created_at'];
                    $a['updated_at'] = $p['updated_at'];
                    $a['taxable'] = wc_prices_include_tax() ? true : false;
                    $a['barcode'] = null;
                    $a['grams'] = null;
                    $a['image_id'] = $variations['image_id'];

                    $a['weight'] = $variations['weight'];
                    $a['weight_unit'] = get_option('woocommerce_weight_unit');

                    $a['inventory_item_id'] = null;
                    $a['inventory_quantity'] = $variations['max_qty'];

                    $a['old_inventory_quantity'] = null;
                    $a['presentment_prices'] = null;
                    $a['requires_shipping'] = $product->get_virtual() ? false : true;

                    //echo '<pre>'; print_r($variations['attributes']); echo '</pre>';
                    $option_count = 1;
                    foreach($variations['attributes'] as $key => $val){

                        if(isset($val) && !empty($val)){

                            //echo '<br>key: '.$key;

                            $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*

                            $a['title'] = $option_name;

                            //echo '<br>key: '.$option_name.', Val: '.$val;

                            $a['option'.$option_count] = $val;

                            $option_count++;
                        }

                    }

                    $position++;

                    $attributes[] = $a;

                }

                $p['variants'] = $attributes;

            }else{

                $p['variants']['id'] = $p['id'];
                $p['variants']['product_id'] = $p['id'];
                $p['variants']['title'] = $p['title'];
                $p['variants']['price'] = $product->get_sale_price(); 
                $p['variants']['sku'] = $product->get_sku();
                $p['variants']['position'] = 1;
                $p['variants']['inventory_policy'] = null;
                $p['variants']['compare_at_price'] = $product->get_regular_price();
                $p['variants']['fulfillment_service'] = null;
                $p['variants']['inventory_management'] = null;
                $p['variants']['option1'] = null;
                $p['variants']['option2'] = null;
                $p['variants']['option3'] = null;
                $p['variants']['created_at'] = $p['created_at'];
                $p['variants']['updated_at'] = $p['updated_at'];
                $p['variants']['taxable'] = wc_prices_include_tax() ? true : false;
                $p['variants']['barcode'] = null;
                $p['variants']['grams'] = null;
                $p['variants']['image_id'] = $product->get_image_id();
                $p['variants']['weight'] = $product->get_weight();
                $p['variants']['weight_unit'] = get_option('woocommerce_weight_unit');

                $p['variants']['inventory_item_id'] = null;
                $p['variants']['inventory_quantity'] = $product->get_stock_quantity();

                $p['variants']['old_inventory_quantity'] = null;
                $p['variants']['presentment_prices'] = null;
                $p['variants']['requires_shipping'] = $product->get_virtual() ? false : true;
                
            }

            $image_id        = $product->get_image_id();

            $img = array();

            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );

                $img[] = array('id' => $image_id, 
                                'product_id' => $p['id'], 
                                'src' => $image_url, 
                                'position' => 1, 
                                'created_at' => $p['created_at'], 
                                'updated_at' => $p['updated_at'],
                                'alt' => null,
                                'width' => null,
                                'height' => null,
                                'variant_ids' => array()

                            );
            }

            $attachment_ids  = $product->get_gallery_image_ids();

            $position = 2;
            foreach ( $attachment_ids as $attachment_id ) {
                $image_url = wp_get_attachment_url( $attachment_id );

                $img[] = array('id' => $attachment_id, 
                                'product_id' => $p['id'], 
                                'src' => $image_url, 
                                'position' => $position, 
                                'created_at' => $p['created_at'], 
                                'updated_at' => $p['updated_at'],
                                'alt' => null,
                                'width' => null,
                                'height' => null,
                                'variant_ids' => array()
                            );

                $position++;
            }

            //$product_images['images']      = $img;

            $p['images'] = $img;

            //echo '<br>status: '.$product->get_type();

            $p['options'] = array();    

            //echo '<pre>'; print_r($p); echo '</pre>';

            aitrillion_api_log('Product: '.$p.PHP_EOL);

            $json_payload = json_encode($p);

            //echo '<pre>'; print_r($json_payload); echo '</pre>';

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            //exit;

            $r = json_decode($response['body']);

            update_post_meta($product_id, '_aitrillion_product_sync', 'true');

            aitrillion_api_log('API Response for product id: '.$product_id.PHP_EOL.print_r($r, true));

            //exit;
        }

        delete_option('_aitrillion_updated_products');
    }
}

function sync_deleted_products(){

    $deleted_products = get_option( '_aitrillion_deleted_products' );

    aitrillion_api_log('deleted product sync log: '.print_r($deleted_products, true).PHP_EOL);

    if(!empty($deleted_products)){

        $deleted_products = array_unique($deleted_products);

        aitrillion_api_log('deleted product not empty: '.PHP_EOL);

        foreach($deleted_products as $k => $post_id){

            if( get_post_type( $post_id ) != 'product' ) return;

            //echo 'product delted';

            $json_payload = json_encode(array('id' => $post_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            $r = json_decode($response['body']);

            aitrillion_api_log('Product Delete: product id: '.$post_id.PHP_EOL.print_r($r, true));
        }

        delete_option('_aitrillion_deleted_products');

        aitrillion_api_log('after product delete, delete option : '.PHP_EOL);
    }
}

function sync_new_orders(){

    $orders = get_option( '_aitrillion_created_orders' );

    aitrillion_api_log('new order sync log '.print_r($orders, true).PHP_EOL);

    if(!empty($orders)){

        $orders = array_unique($orders);

        foreach($orders as $order_id){

            $order = wc_get_order( $order_id );

            if($order->get_status() == 'completed'){

                $c_date = $order->get_date_completed();
                $date_completed = $c_date->date_i18n('Y-m-d H:i:s');    
                
            }else{
                $date_completed = null;
            }

            if($order->get_status() == 'cancelled'){
                $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');
            }


            $o['id'] = $order->get_id();
            $o['email'] = $order->get_billing_email();
            $o['created_at'] = $order->get_date_created()->date('Y-m-d H:i:s');
            $o['updated_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
            $o['note'] = $order->get_customer_note();
            $o['closed_at'] = $date_completed;
            $o['confirmed'] = true;
            $o['number'] = $order->get_id();
            $o['gateway'] = $order->get_payment_method();
            $o['currency'] = $order->get_currency();
            $o['browser_ip'] = $order->get_customer_ip_address();
            $o['cart_token'] = $order->get_meta('_aio_card_id');
            $o['token'] = '';
            $o['tags'] = '';
            $o['landing_site'] = '';
            $o['landing_site_ref'] = '';
            $o['location_id'] = '';
            $o['payment_gateway_names'] = array($order->get_payment_method());
            $o['location_id'] = '';
            $o['buyer_accepts_marketing'] = false;
            $o['app_id'] = '';
            $o['source_name'] = 'web';
            $o['total_price'] = $order->get_total();
            $o['total_price_usd'] = '';
            $o['subtotal_price'] = $order->get_subtotal();
            $o['total_tax'] = $order->get_tax_totals();
            $o['total_discounts'] = $order->get_discount_total();
            $o['total_line_items_price'] = $order->get_subtotal();
            $o['referring_site'] = '';
            $o['order_status_url'] = $order->get_view_order_url();

            if($order->get_coupon_codes()){

                $coupon_codes = $order->get_coupon_codes();

                foreach($coupon_codes as $code){

                    $c = new WC_Coupon($code); 
                    $o['discount_codes'][] = array('code' => $code, 'amount' => $c->amount, 'type' => $c->discount_type);

                }

            }else{
                $o['discount_codes'] = array();
            }

            $o['user_id'] = $order->get_user_id();
            $o['processing_method'] = 'direct';
            $o['processed_at'] = $date_completed;
            $o['phone'] = $order->get_billing_phone();
            $o['order_number'] = $order->get_id();
            $o['name'] = $order->get_id();

            if($order->get_status() == 'cancelled'){
                $o['cancelled_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
            }else{
                $o['cancelled_at'] = '';    
            }
            
            $o['cancel_reason'] = '';
            $o['contact_email'] = $order->get_billing_email();

            $o['note_attributes'] = array();  
            $note_attribute = array();

            $sales_cookie = $order->get_meta('_aio_shopify_ref');

            if($sales_cookie){
                $note_attribute[] = array('name' => 'aio_shopify_ref', 'value' => $sales_cookie);

                $o['note_attributes'] = $note_attribute;
            }

            $affiliate_cookie = $order->get_meta('_aio_affiliate_code');

            if($affiliate_cookie){
                $note_attribute[] = array('name' => 'aio_affiliate_code', 'value' => $affiliate_cookie);

                $o['note_attributes'] = $note_attribute;
            }

            $line_items = array();

            foreach ( $order->get_items() as $item_id => $item ) {

                //echo '<br>item id: '.$item_id;

                $i['id'] = $item_id;

                $product = $item->get_product(); 

                $i['type_id'] = $product->get_type();
                $i['variant_id'] = $item->get_variation_id();
                $i['product_id'] = $item->get_product_id();
                $i['title'] = $item->get_name();
                $i['quantity'] = $item->get_quantity();
                $i['sku'] = $product->get_sku();
                $i['price'] = $product->get_price();
                $i['total_discount'] = '';
                $i['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped';
                $i['gift_card'] = false;

                //echo '<pre>'; print_r($item); echo '</pre>';

                $line_items[] = $i;
            }

            $o['line_items'] = $line_items;

            $o['billing_address']['first_name'] = $order->get_billing_first_name();
            $o['billing_address']['last_name'] = $order->get_billing_last_name();
            $o['billing_address']['address1'] = $order->get_billing_address_1();
            $o['billing_address']['address2'] = $order->get_billing_address_2();
            $o['billing_address']['phone'] = $order->get_billing_phone();
            $o['billing_address']['city'] = $order->get_billing_city();
            $o['billing_address']['zip'] = $order->get_billing_postcode();
            $o['billing_address']['province'] = $order->get_billing_state();
            $o['billing_address']['country'] = $order->get_billing_country();
            $o['billing_address']['company'] = $order->get_billing_company();
            $o['billing_address']['latitude'] = '';
            $o['billing_address']['longitude'] = '';
            $o['billing_address']['name'] = $order->get_formatted_billing_full_name();
            $o['billing_address']['country_code'] = $order->get_billing_country();
            $o['billing_address']['province_code'] = $order->get_billing_state();

            $o['shipping_address']['first_name'] = $order->get_shipping_first_name();
            $o['shipping_address']['last_name'] = $order->get_shipping_last_name();
            $o['shipping_address']['address1'] = $order->get_shipping_address_1();
            $o['shipping_address']['address2'] = $order->get_shipping_address_2();
            $o['shipping_address']['phone'] = $order->get_billing_phone();
            $o['shipping_address']['city'] = $order->get_shipping_city();
            $o['shipping_address']['zip'] = $order->get_shipping_postcode();
            $o['shipping_address']['province'] = $order->get_shipping_state();
            $o['shipping_address']['country'] = $order->get_shipping_country();
            $o['shipping_address']['company'] = $order->get_shipping_company();
            $o['shipping_address']['latitude'] = '';
            $o['shipping_address']['longitude'] = '';
            $o['shipping_address']['name'] = $order->get_formatted_shipping_full_name();
            $o['shipping_address']['country_code'] = $order->get_shipping_country();
            $o['shipping_address']['province_code'] = $order->get_shipping_state();

            $o['shipping_lines']['id'] = 1;
            $o['shipping_lines']['title'] = $order->get_shipping_method();
            $o['shipping_lines']['price'] = $order->get_shipping_total();
            $o['shipping_lines']['code'] = null;
            $o['shipping_lines']['source'] = 'wordpress';
            $o['shipping_lines']['phone'] = null;
            $o['shipping_lines']['requested_fulfillment_service_id'] = null;
            $o['shipping_lines']['delivery_category'] = null;
            $o['shipping_lines']['carrier_identifier'] = null;
            $o['shipping_lines']['discounted_price'] = null;
            $o['shipping_lines']['tax_lines'] = array();

            $o['customer']['id'] = $order->get_customer_id();
            $o['customer']['email'] = $order->get_billing_email();

            
            $customer_id = $order->get_customer_id();
            $user_id = $order->get_user_id();

            //echo '<br>customer id: '.$customer_id;
            //echo '<br>user id: '.$user_id;
            

            $o['customer']['guest'] = $order->get_user_id() == 0 ? 'yes' : 'no';

            $o['customer']['phone'] = $order->get_billing_phone();

            if($order->get_customer_id()){

                $customer = new WC_Customer( $order->get_customer_id() );
                $o['customer']['first_name'] = $customer->get_first_name();
                $o['customer']['last_name'] = $customer->get_last_name();
                $o['customer']['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');

                if($customer->get_date_modified()){
                    $o['customer']['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
                }else{
                    $o['customer']['updated_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
                }
                
                $o['customer']['verified_email'] = true;

                $o['customer']['last_order_name'] = null;   // TODO update with order number
                $o['customer']['last_order_id'] = null;    // TODO update with order ID


                $o['customer']['orders_count'] = $customer->get_order_count();
                $o['customer']['total_spent'] = $customer->get_total_spent();

                $o['customer']['default_address'] = array(
                                                    'id' => 1,
                                                    'customer_id' => $order->get_customer_id(),
                                                    'first_name' => $customer->get_billing_first_name(),
                                                    'last_name' => $customer->get_billing_last_name(),
                                                    'company' => $customer->get_billing_company(),
                                                    'address1' => $customer->get_billing_address_1(),
                                                    'address2' => $customer->get_billing_address_2(),
                                                    'city' => $customer->get_billing_city(),
                                                    'province' => $customer->get_billing_state(),
                                                    'zip' => $customer->get_billing_postcode(),
                                                    'phone' => $customer->get_billing_phone(),
                                                    'name' => $customer->get_billing_first_name().' '.$customer->get_billing_last_name(),
                                                    'country_code' => $customer->get_billing_country(),
                                                    'default' => true,

                                                );

            }else{

                $o['customer']['first_name'] = $order->get_billing_first_name();
                $o['customer']['last_name'] = $order->get_billing_last_name();
                $o['customer']['created_at'] = '';
                $o['customer']['updated_at'] = '';
                $o['customer']['verified_email'] = false;

                $o['customer']['last_order_name'] = null;   // TODO update with order number
                $o['customer']['last_order_id'] = null;    // TODO update with order ID

                $o['customer']['orders_count'] = 0;
                $o['customer']['total_spent'] = 0;

                $o['customer']['default_address'] = array(
                                                    'id' => 1,
                                                    'customer_id' => $order->get_customer_id(),
                                                    'first_name' => $order->get_billing_first_name(),
                                                    'last_name' => $order->get_billing_last_name(),
                                                    'company' => $order->get_billing_company(),
                                                    'address1' => $order->get_billing_address_1(),
                                                    'city' => $order->get_billing_city(),
                                                    'province' => $order->get_billing_state(),
                                                    'zip' => $order->get_billing_postcode(),
                                                    'phone' => $order->get_billing_phone(),
                                                    'name' => $order->get_formatted_billing_full_name(),
                                                    'country_code' => $order->get_billing_country(),
                                                    'default' => true
                                                );
            }

            $o['fulfillments'] = array();

            $order_refunds = $order->get_refunds();

            //echo '<br>order_refunds: <pre>'; print_r($order_refunds); echo '</pre>';
            //continue;

            if($order_refunds){

                $order_refund = $order_refunds[0];

                $o['refunds']['id'] = $order_refund->get_id();
                $o['refunds']['order_id'] = $order->get_id();
                $o['refunds']['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $o['refunds']['note'] = $order_refund->get_refund_reason();
                $o['refunds']['user_id'] = $order->get_user_id();
                $o['refunds']['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $o['refunds']['refund_line_items'] = $line_items;

                $transactions['id'] = $order_refund->get_id();
                $transactions['amount'] = $order_refund->get_amount();
                $transactions['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $transactions['currency'] = $order->get_currency();
                $transactions['currency'] = $order->get_currency();
                $transactions['order_id'] = $order->get_id();
                $transactions['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');

                $o['refunds']['transactions'] = $transactions;
                $o['refunds']['order_adjustments'] = array();

            }else{
                $o['refunds'] = array();    
            }

            
            $o['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped'; 
            $o['financial_status'] = $order->get_status();


            $json_payload = json_encode($o);

            //echo '<pre>'; print_r($json_payload); echo '</pre>';

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            //exit;

            $r = json_decode($response['body']);

            update_post_meta($order_id, '_aitrillion_order_sync', 'true');

            aitrillion_api_log('API Response for order id: '.$order_id.PHP_EOL.print_r($r, true));
        }

        delete_option('_aitrillion_created_orders');
    }
}

function sync_updated_orders(){

    $orders = get_option( '_aitrillion_updated_orders' );

    aitrillion_api_log('updated order sync log '.print_r($orders, true).PHP_EOL);

    if(!empty($orders)){

        $orders = array_unique($orders);

        foreach($orders as $order_id){

            // Here comes your code...
            $order = wc_get_order( $order_id );

            if($order->get_status() == 'completed'){

                $c_date = $order->get_date_completed();
                $date_completed = $c_date->date_i18n('Y-m-d H:i:s');    
                
            }else{
                $date_completed = null;
            }

            if($order->get_status() == 'cancelled'){
                $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');
            }


            $o['id'] = $order->get_id();
            $o['email'] = $order->get_billing_email();
            $o['created_at'] = $order->get_date_created()->date('Y-m-d H:i:s');
            $o['updated_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
            $o['note'] = $order->get_customer_note();
            $o['closed_at'] = $date_completed;
            $o['confirmed'] = true;
            $o['number'] = $order->get_id();
            $o['gateway'] = $order->get_payment_method();
            $o['currency'] = $order->get_currency();
            $o['browser_ip'] = $order->get_customer_ip_address();
            $o['cart_token'] = $order->get_meta('_aio_card_id');
            $o['token'] = '';
            $o['tags'] = '';
            $o['landing_site'] = '';
            $o['landing_site_ref'] = '';
            $o['location_id'] = '';
            $o['payment_gateway_names'] = array($order->get_payment_method());
            $o['location_id'] = '';
            $o['buyer_accepts_marketing'] = false;
            $o['app_id'] = '';
            $o['source_name'] = 'web';
            $o['total_price'] = $order->get_total();
            $o['total_price_usd'] = '';
            $o['subtotal_price'] = $order->get_subtotal();
            $o['total_tax'] = $order->get_tax_totals();
            $o['total_discounts'] = $order->get_discount_total();
            $o['total_line_items_price'] = $order->get_subtotal();
            $o['referring_site'] = '';
            $o['order_status_url'] = $order->get_view_order_url();

            if($order->get_coupon_codes()){

                $coupon_codes = $order->get_coupon_codes();

                foreach($coupon_codes as $code){

                    $c = new WC_Coupon($code); 
                    $o['discount_codes'][] = array('code' => $code, 'amount' => $c->amount, 'type' => $c->discount_type);

                }

            }else{
                $o['discount_codes'] = array();
            }

            $o['user_id'] = $order->get_user_id();
            $o['processing_method'] = 'direct';
            $o['processed_at'] = $date_completed;
            $o['phone'] = $order->get_billing_phone();
            $o['order_number'] = $order->get_id();
            $o['name'] = $order->get_id();

            if($order->get_status() == 'cancelled'){
                $o['cancelled_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
            }else{
                $o['cancelled_at'] = '';    
            }
            
            $o['cancel_reason'] = '';
            $o['contact_email'] = $order->get_billing_email();

            $o['note_attributes'] = array();  
            $note_attribute = array();

            $sales_cookie = $order->get_meta('_aio_shopify_ref');

            if($sales_cookie){
                $note_attribute[] = array('name' => 'aio_shopify_ref', 'value' => $sales_cookie);

                $o['note_attributes'] = $note_attribute;
            }

            $affiliate_cookie = $order->get_meta('_aio_affiliate_code');

            if($affiliate_cookie){
                $note_attribute[] = array('name' => 'aio_affiliate_code', 'value' => $affiliate_cookie);

                $o['note_attributes'] = $note_attribute;
            }

            $line_items = array();

            foreach ( $order->get_items() as $item_id => $item ) {

                //echo '<br>item id: '.$item_id;

                $i['id'] = $item_id;

                $product = $item->get_product(); 

                $i['type_id'] = $product->get_type();
                $i['variant_id'] = $item->get_variation_id();
                $i['product_id'] = $item->get_product_id();
                $i['title'] = $item->get_name();
                $i['quantity'] = $item->get_quantity();
                $i['sku'] = $product->get_sku();
                $i['price'] = $product->get_price();
                $i['total_discount'] = '';
                $i['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped';
                $i['gift_card'] = false;

                //echo '<pre>'; print_r($item); echo '</pre>';

                $line_items[] = $i;
            }

            $o['line_items'] = $line_items;

            $o['billing_address']['first_name'] = $order->get_billing_first_name();
            $o['billing_address']['last_name'] = $order->get_billing_last_name();
            $o['billing_address']['address1'] = $order->get_billing_address_1();
            $o['billing_address']['address2'] = $order->get_billing_address_2();
            $o['billing_address']['phone'] = $order->get_billing_phone();
            $o['billing_address']['city'] = $order->get_billing_city();
            $o['billing_address']['zip'] = $order->get_billing_postcode();
            $o['billing_address']['province'] = $order->get_billing_state();
            $o['billing_address']['country'] = $order->get_billing_country();
            $o['billing_address']['company'] = $order->get_billing_company();
            $o['billing_address']['latitude'] = '';
            $o['billing_address']['longitude'] = '';
            $o['billing_address']['name'] = $order->get_formatted_billing_full_name();
            $o['billing_address']['country_code'] = $order->get_billing_country();
            $o['billing_address']['province_code'] = $order->get_billing_state();

            $o['shipping_address']['first_name'] = $order->get_shipping_first_name();
            $o['shipping_address']['last_name'] = $order->get_shipping_last_name();
            $o['shipping_address']['address1'] = $order->get_shipping_address_1();
            $o['shipping_address']['address2'] = $order->get_shipping_address_2();
            $o['shipping_address']['phone'] = $order->get_billing_phone();
            $o['shipping_address']['city'] = $order->get_shipping_city();
            $o['shipping_address']['zip'] = $order->get_shipping_postcode();
            $o['shipping_address']['province'] = $order->get_shipping_state();
            $o['shipping_address']['country'] = $order->get_shipping_country();
            $o['shipping_address']['company'] = $order->get_shipping_company();
            $o['shipping_address']['latitude'] = '';
            $o['shipping_address']['longitude'] = '';
            $o['shipping_address']['name'] = $order->get_formatted_shipping_full_name();
            $o['shipping_address']['country_code'] = $order->get_shipping_country();
            $o['shipping_address']['province_code'] = $order->get_shipping_state();

            $o['shipping_lines']['id'] = 1;
            $o['shipping_lines']['title'] = $order->get_shipping_method();
            $o['shipping_lines']['price'] = $order->get_shipping_total();
            $o['shipping_lines']['code'] = null;
            $o['shipping_lines']['source'] = 'wordpress';
            $o['shipping_lines']['phone'] = null;
            $o['shipping_lines']['requested_fulfillment_service_id'] = null;
            $o['shipping_lines']['delivery_category'] = null;
            $o['shipping_lines']['carrier_identifier'] = null;
            $o['shipping_lines']['discounted_price'] = null;
            $o['shipping_lines']['tax_lines'] = array();

            $o['customer']['id'] = $order->get_customer_id();
            $o['customer']['email'] = $order->get_billing_email();

            
            $customer_id = $order->get_customer_id();
            $user_id = $order->get_user_id();

            //echo '<br>customer id: '.$customer_id;
            //echo '<br>user id: '.$user_id;
            

            $o['customer']['guest'] = $order->get_user_id() == 0 ? 'yes' : 'no';

            $o['customer']['phone'] = $order->get_billing_phone();

            if($order->get_customer_id()){

                $customer = new WC_Customer( $order->get_customer_id() );
                $o['customer']['first_name'] = $customer->get_first_name();
                $o['customer']['last_name'] = $customer->get_last_name();
                $o['customer']['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');

                if($customer->get_date_modified()){
                    $o['customer']['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
                }else{
                    $o['customer']['updated_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
                }

                
                $o['customer']['verified_email'] = true;

                $o['customer']['last_order_name'] = null;   // TODO update with order number
                $o['customer']['last_order_id'] = null;    // TODO update with order ID


                $o['customer']['orders_count'] = $customer->get_order_count();
                $o['customer']['total_spent'] = $customer->get_total_spent();

                $o['customer']['default_address'] = array(
                                                    'id' => 1,
                                                    'customer_id' => $order->get_customer_id(),
                                                    'first_name' => $customer->get_billing_first_name(),
                                                    'last_name' => $customer->get_billing_last_name(),
                                                    'company' => $customer->get_billing_company(),
                                                    'address1' => $customer->get_billing_address_1(),
                                                    'address2' => $customer->get_billing_address_2(),
                                                    'city' => $customer->get_billing_city(),
                                                    'province' => $customer->get_billing_state(),
                                                    'zip' => $customer->get_billing_postcode(),
                                                    'phone' => $customer->get_billing_phone(),
                                                    'name' => $customer->get_billing_first_name().' '.$customer->get_billing_last_name(),
                                                    'country_code' => $customer->get_billing_country(),
                                                    'default' => true,

                                                );

            }else{

                $o['customer']['first_name'] = $order->get_billing_first_name();
                $o['customer']['last_name'] = $order->get_billing_last_name();
                $o['customer']['created_at'] = '';
                $o['customer']['updated_at'] = '';
                $o['customer']['verified_email'] = false;

                $o['customer']['last_order_name'] = null;   // TODO update with order number
                $o['customer']['last_order_id'] = null;    // TODO update with order ID

                $o['customer']['orders_count'] = 0;
                $o['customer']['total_spent'] = 0;

                $o['customer']['default_address'] = array(
                                                    'id' => 1,
                                                    'customer_id' => $order->get_customer_id(),
                                                    'first_name' => $order->get_billing_first_name(),
                                                    'last_name' => $order->get_billing_last_name(),
                                                    'company' => $order->get_billing_company(),
                                                    'address1' => $order->get_billing_address_1(),
                                                    'city' => $order->get_billing_city(),
                                                    'province' => $order->get_billing_state(),
                                                    'zip' => $order->get_billing_postcode(),
                                                    'phone' => $order->get_billing_phone(),
                                                    'name' => $order->get_formatted_billing_full_name(),
                                                    'country_code' => $order->get_billing_country(),
                                                    'default' => true
                                                );
            }

            $o['fulfillments'] = array();

            $order_refunds = $order->get_refunds();

            //echo '<br>order_refunds: <pre>'; print_r($order_refunds); echo '</pre>';
            //continue;

            if($order_refunds){

                $order_refund = $order_refunds[0];

                $o['refunds']['id'] = $order_refund->get_id();
                $o['refunds']['order_id'] = $order->get_id();
                $o['refunds']['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $o['refunds']['note'] = $order_refund->get_refund_reason();
                $o['refunds']['user_id'] = $order->get_user_id();
                $o['refunds']['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $o['refunds']['refund_line_items'] = $line_items;

                $transactions['id'] = $order_refund->get_id();
                $transactions['amount'] = $order_refund->get_amount();
                $transactions['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
                $transactions['currency'] = $order->get_currency();
                $transactions['currency'] = $order->get_currency();
                $transactions['order_id'] = $order->get_id();
                $transactions['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');

                $o['refunds']['transactions'] = $transactions;
                $o['refunds']['order_adjustments'] = array();

            }else{
                $o['refunds'] = array();    
            }

            
            $o['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped'; 
            $o['financial_status'] = $order->get_status();


            $json_payload = json_encode($o);

            //echo '<pre>'; print_r($json_payload); echo '</pre>';

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            //exit;

            $r = json_decode($response['body']);

            update_post_meta($order_id, '_aitrillion_order_sync', 'true');

            aitrillion_api_log('order updated: order id '.$order_id.PHP_EOL.print_r($r, true));
        }

        delete_option('_aitrillion_updated_orders');
    }
}

function sync_deleted_orders(){

    $deleted_orders = get_option( '_aitrillion_deleted_orders' );

    aitrillion_api_log('deleted order sync log: '.print_r($deleted_orders, true).PHP_EOL);

    if(!empty($deleted_orders)){

        $deleted_orders = array_unique($deleted_orders);

        foreach($deleted_orders as $k => $post_id){

            if( get_post_type( $post_id ) != 'shop_order' ) return;

            //echo 'product delted';

            $json_payload = json_encode(array('id' => $post_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            $r = json_decode($response['body']);

            aitrillion_api_log('Order Delete: Order id: '.$post_id.PHP_EOL.print_r($r, true));

        }

        delete_option('_aitrillion_deleted_orders');
    }
}
?>