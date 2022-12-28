<?php 

    add_action("wp_ajax_ait_abandonedcart", "aitrillion_abandonedcart");
    add_action("wp_ajax_nopriv_ait_abandonedcart", "aitrillion_abandonedcart");

    add_filter( 'rest_authentication_errors', 'aitrillion_auth_check', 99 );

    add_action('rest_api_init', function () {
        
        register_rest_route( 'aitrillion/v1', 'getshopinfo',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getStoreDetail',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcustomers',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCustomers',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcategories',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCategories',
                    'permission_callback' => '__return_true' 
        ));

        register_rest_route( 'aitrillion/v1', 'getproducts',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getProducts',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getorders',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getOrders',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcategorycollection',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCategoryCollection',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'searchcategory',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_searchCategory',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'updatescriptversion',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_updateScriptVersion',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'blockloyaltymember',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_blockLoyaltyMember',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'createcoupon',array(
                    'methods'  => 'POST',
                    'callback' => 'aitrillion_createcoupon',
                    'permission_callback' => '__return_true'
        ));

        
    });


/* 
Check header authentication
compare request username password with store username password.
return error message if authentication fail
*/

function aitrillion_auth_check(){

    
    $request_user = '';
    $request_pw = '';

    if(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])){
        $request_user = $_SERVER["PHP_AUTH_USER"];
        $request_pw = $_SERVER["PHP_AUTH_PW"];
    }

    $api_key = get_option('_aitrillion_api_key');
    $api_pw = get_option('_aitrillion_api_password');

    if($api_key && $api_pw){

        $domain = preg_replace("(^https?://)", "", site_url() );

        $url = AITRILLION_END_POINT.'validate?shop_name='.$domain;

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_key.':'.$api_pw )
            )
        ));

        $r = json_decode($response['body']);

        if(isset($r->status) && $r->status != 'sucess'){

            $return['result'] = false;
            $return['message'] = 'Invalid api username or password';
            
            echo json_encode($return);
            exit;
        }

        if(($request_user != $api_key) || ($request_pw != $api_pw)){

            $return['result'] = false;
            $return['message'] = 'Invalid api username or password';
            
            echo json_encode($return);
            exit;

        }else{

            return true;
        }
    }
}

function aitrillion_getStoreDetail(WP_REST_Request $request){

    $endpoint = $request->get_route();

    //$return['app_name'] = AITRILLION_APP_NAME;
    $return['shop_name'] = DOMAIN;

    $return['shop_type'] = 'woocommerce';
    $return['shop_owner'] = '';

    $super_admins = get_super_admins();

    if($super_admins){
        $admin = $super_admins[0];
        $admin_user = get_user_by('login', $admin);

        if($admin_user){
            $return['shop_owner'] = $admin_user->display_name;
        }
    }
    
    $store_city        = get_option( 'woocommerce_store_city' );
    $store_postcode    = get_option( 'woocommerce_store_postcode' );

    // The country/state
    $store_raw_country = get_option( 'woocommerce_default_country' );

    // Split the country/state
    $split_country = explode( ":", $store_raw_country );

    // Country and state separated:
    $store_country = $split_country[0];
    $store_state   = $split_country[1];

    $return['country'] = $store_country;
    $return['city'] = $store_city;
    $return['zip'] = $store_postcode;
    $return['phone'] = '';
    $return['store_name'] = get_bloginfo('name');
    $return['email'] = get_bloginfo( 'admin_email' );

    $return['shop_currency'] = get_woocommerce_currency();
    $return['money_format'] = html_entity_decode(get_woocommerce_currency_symbol());

    //$return['install_date'] = '';
    $return['created_at'] = date('Y-m-d H:i:s');

    $response = new WP_REST_Response($return);
    $response->set_status(200);


    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Get Shop Info '.$endpoint.PHP_EOL.'return: '.print_r($return, true);

    aitrillion_api_log($log_message);

    return $response;
}

function aitrillion_getCustomers(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    $updated_at = array();
    
    if(isset($params['updated_at']) && !empty($params['updated_at'])){
        $updated_at = array( 
                            array( 'after' => $params['updated_at'], 'inclusive' => true )  
                        );
    }

    if($params['result_type'] == 'count'){

        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',    
             'date_query' => $updated_at,      
          )
        );

        $customers = $customer_query->get_results();

        $return = array();

        $return['result'] = true;
        $return['customers']['count'] = count($customers);


        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Customer API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }

    if($params['result_type'] == 'row'){

        if(isset($params['page'])  && !empty($params['page'])){
            $paged = $params['page'];
        }else{
            $paged = 1;
        }

        if(isset($params['limit'])  && !empty($params['limit'])){
            $limit = $params['limit'];
        }else{
            $limit = 10;
        }

        if($paged == 1){
            $offset = 0;  
        }else {
            $offset = ($paged-1) * $limit;
        }
        
        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',
             'paged' => $paged,
             'number' => $limit,
             'offset' => $offset,
             'date_query' => $updated_at, 
             /*'meta_query' => array(
                    array(
                        'key' => '_aitrillion_sync',
                        'compare' => 'NOT EXISTS' // this should work...
                    ),
                )*/
          )
        );

        $customers = $customer_query->get_results();

        if(count($customers) > 0){

            $return = array();

            foreach ( $customers as $customer_id ) {

               $customer = new WC_Customer( $customer_id );

               $modified_date = $customer->get_date_modified();

               $c = array();

               $c['id'] = $customer_id;
               $c['first_name'] = $customer->get_first_name();
               $c['last_name'] = $customer->get_last_name();
               $c['email'] = $customer->get_email();
               $c['verified_email'] = true;
               $c['phone'] = $customer->get_billing_phone();
               $c['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
               $c['accepts_marketing'] = true;

                if(!empty($customer->get_date_modified())){
                    $c['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
                }else{
                    $c['updated_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
                }
               

               $c['orders_count'] = $customer->get_order_count();
               $c['total_spent'] = $customer->get_total_spent();

               $last_order = $customer->get_last_order();
               

               if(!empty($last_order)){
                    
                    $last_order_id = $last_order->get_id();
                    

                    $c['last_order_name'] = $last_order_id;
                    $c['last_order_id'] = $last_order_id;

               }else{
                    $c['last_order_name'] = null;
                    $c['last_order_id'] = null;
               }

               $c['default_address'] = array(
                                'id' => 1,
                                'customer_id' => $customer_id,
                                'first_name' => $customer->get_billing_first_name(),
                                'last_name' => $customer->get_billing_last_name(),
                                'company' => $customer->get_billing_company(),
                                'address1' => $customer->get_billing_address_1(),
                                'city' => $customer->get_billing_city(),
                                'province' => $customer->get_billing_state(),
                                'zip' => $customer->get_billing_postcode(),
                                'phone' => $customer->get_billing_phone(),
                                'name' => $customer->get_billing_first_name().' '.$customer->get_billing_last_name(),
                                'country_code' => $customer->get_billing_country(),
                                'default' => true,

                            );

               $address[] = array(
                                'id' => 2,
                                'customer_id' => $customer_id,
                                'first_name' => $customer->get_shipping_first_name(),
                                'last_name' => $customer->get_shipping_last_name(),
                                'company' => $customer->get_shipping_company(),
                                'address1' => $customer->get_shipping_address_1(),
                                'city' => $customer->get_shipping_city(),
                                'province' => $customer->get_shipping_state(),
                                'zip' => $customer->get_shipping_postcode(),
                                'phone' => $customer->get_shipping_phone(),
                                'name' => $customer->get_shipping_first_name().' '.$customer->get_shipping_last_name(),
                                'country_code' => $customer->get_shipping_country(),
                                'default' => false,

                            );

                $c['addresses'] = $address;
                $c['type'] = null;

                $address = array();

                $return['customers'][] = $c;

               update_user_meta($customer_id, '_aitrillion_sync', 'true');
               update_user_meta($customer_id, '_aitrillion_sync_date', date('Y-m-d H:i:s'));
            }

            $return['result'] = true;

            $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
            $log_message .= 'Get Customer API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

            aitrillion_api_log($log_message);

            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;

        }else{
            $return = array();

            $return['status'] = false;
            $return['msg'] = 'No Customer found';

            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;
        }

       
    }
   
}

function aitrillion_getCategories(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type'])){

        $return['result'] = false;
        $return['message'] = 'Result type not defined';

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }
}

function aitrillion_getProducts(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    $args['status'] = 'publish';

    if(isset($params['updated_at']) && !empty($params['updated_at'])){
        $args['date_created'] = '>'.$params['updated_at'];
    }

    if(isset($params['page']) && !empty($params['page'])){
        $args['page'] = $params['page'];
    }else{
        $args['page'] = 1;
    }

    if(isset($params['limit']) && !empty($params['limit'])){
        $args['limit'] = $params['limit'];
    }else{
        $args['limit'] = 10;
    }

    if($params['result_type'] == 'count'){

        unset($args['page']);
        $args['limit'] = '-1';

    }

    $products = wc_get_products( $args );

    if($params['result_type'] == 'count'){

        $return = array();

        $return['result'] = true;
        $return['products']['count'] = count($products);

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Product API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response; 
        exit;

    }

    if($params['result_type'] == 'row'){

        $return = array();

        foreach ( $products as $product ){ 

            $p['id'] = $product->get_id();
            $p['title'] = $product->get_title();
            $p['body_html'] = $product->get_description();
            $p['vendor'] = '';
            $p['created_at'] = $product->get_date_created()->date('Y-m-d H:i:s');
            $p['updated_at'] = $product->get_date_modified()->date('Y-m-d H:i:s');
            $p['published_at'] = $p['created_at'];
            $p['product_type'] = $product->get_type();
            $p['handle'] = get_permalink( $product->get_id() );
            $p['tags'] = '';
            $p['published_scope'] = 'web';

            $image_id        = $product->get_image_id();

            $img = array();

            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );

                $img[] = array('id' => $image_id, 'product_id' => $p['id'], 'src' => $image_url, 'position' => 1, 'created_at' => $p['created_at'], 'updated_at' => $p['updated_at']);
            }

            $attachment_ids  = $product->get_gallery_image_ids();

            $position = 2;
            foreach ( $attachment_ids as $attachment_id ) {
                $image_url = wp_get_attachment_url( $attachment_id );

                $img[] = array('id' => $attachment_id, 'product_id' => $p['id'], 'src' => $image_url, 'position' => $position, 'created_at' => $p['created_at'], 'updated_at' => $p['updated_at']);
                $position++;
            }

            //$product_images['images']      = $img;

            $p['images'] = $img;

            if($product->get_type() == 'variable'){

                $available_variations = $product->get_available_variations();
                $attributes = array();
                $position = 1;

                foreach ($available_variations as $key => $variations) 
                { 
                    $a = array();

                    $a['id'] = $variations['variation_id'];
                    $a['product_id'] = $p['id'];
                    $a['price'] = $variations['display_price'];
                    $a['compare_at_price'] = $variations['display_regular_price'];  // sale price
                    $a['sku'] = $variations['sku'];
                    $a['position'] = $position;
                    $a['inventory_quantity'] = $variations['max_qty'];
                    $a['image_id'] = $variations['image_id'];
                    $a['created_at'] = $p['created_at'];
                    $a['updated_at'] = $p['updated_at'];

                    foreach($variations['attributes'] as $key => $val){

                        if(isset($val) && !empty($val)){

                            $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*

                            $a['title'] = $option_name;

                            $a['option1'] = $val;
                        }

                    }

                    $position++;

                    $attributes[] = $a;

                }

                $p['variants'] = $attributes;

            }else{

                $a['id'] = $p['id'];
                $a['product_id'] = $p['id'];
                $a['title'] = $p['title'];
                $a['price'] = $product->get_sale_price();
                $a['compare_at_price'] = $product->get_regular_price();
                $a['sku'] = $product->get_sku();
                $a['position'] = 1;
                $a['option1'] = '';
                $a['inventory_quantity'] = $product->get_stock_quantity();
                $a['image_id'] = $image_id;
                $a['created_at'] = $p['created_at'];
                $a['updated_at'] = $p['updated_at'];

                $p['variants'][] = $a;
            }

            $return['products'][] = $p;

        }

        $return['result'] = true;

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Product API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

function aitrillion_getOrders(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    if($params['result_type'] == 'count'){
        
        $args['status'] = array_keys( wc_get_order_statuses() );
        $args['limit'] = -1;

        if(isset($params['updated_at']) && !empty($params['updated_at'])){
            $args['date_created'] = '>'.$params['updated_at'];
        }

        $orders = wc_get_orders( $args );

        $return = array();

        $return['result'] = true;
        $return['orders']['count'] = count($orders);

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Order API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }


    if($params['result_type'] == 'row'){

        $args['status'] = array_keys( wc_get_order_statuses() );

        if(isset($params['updated_at']) && !empty($params['updated_at'])){
            $args['date_modified'] = '>'.$params['updated_at'];
        }

        if(isset($params['page']) && !empty($params['page'])){
            $args['page'] = $params['page'];
        }else{
            $args['page'] = 1;
        }

        if(isset($params['limit']) && !empty($params['limit'])){
            $args['limit'] = $params['limit'];
        }else{
            $args['limit'] = 10;
        }

        $orders = wc_get_orders( $args );

        $o = array();

        foreach($orders as $order){

            if ( is_a( $order, 'WC_Order_Refund' ) ) {

                continue;
            }

            if($order->get_status() == 'completed'){

                $c_date = $order->get_date_completed();
                $date_completed = $c_date->date_i18n('Y-m-d H:i:s');    

                $o['fulfillment_status'] = 'Fulfilled'; 
                $o['financial_status'] = 'Paid';
                
            }elseif($order->get_status() == 'cancelled'){

                $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');

                $o['fulfillment_status'] = 'Unfulfilled'; 
                $o['financial_status'] = 'Voided';

            }elseif($order->get_status() == 'refunded'){
                
                $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');

                $o['fulfillment_status'] = 'Unfulfilled'; 
                $o['financial_status'] = 'Refunded';

            }else{

                $date_completed = null;

                $o['fulfillment_status'] = 'Unfulfilled'; 
                $o['financial_status'] = 'Unpaid';
            }

            //$o['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped' ; 
            //$o['financial_status'] = $order->get_status();


            //cancelled, completed

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

            $o['customer']['guest'] = $order->get_user_id() == 0 ? 'yes' : 'no';

            $o['customer']['phone'] = $order->get_billing_phone();

            if($order->get_customer_id()){

                $customer = new WC_Customer( $order->get_customer_id() );
                $o['customer']['first_name'] = $customer->get_first_name();
                $o['customer']['last_name'] = $customer->get_last_name();
                $o['customer']['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
                $o['customer']['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
                $o['customer']['verified_email'] = true;

                $last_order = $customer->get_last_order();
               

               if(!empty($last_order)){
                    
                    $last_order_id = $last_order->get_id();

                    $o['customer']['last_order_name'] = $last_order_id;
                    $o['customer']['last_order_id'] = $last_order_id;

               }else{

                    $o['customer']['last_order_name'] = null;
                    $o['customer']['last_order_id'] = null;
               }

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

                $o['customer']['last_order_name'] = null;  
                $o['customer']['last_order_id'] = null;    

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

            // financial_status
            // fulfillment_status

            $return['orders'][] = $o;
            
        }

        $return['result'] = true;

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Order API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

function aitrillion_getCategoryCollection(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['id']) || empty($params['id'])){

        $params['result_type'] = 'row';
    }

    $cat_id = $params['id'];

    if(isset($params['limit']) && !empty($params['limit'])){
        $limit = $params['limit'];
    }else{
        $limit = 10;
    }

    $args = array(
        'post_type'             => 'product',
        'post_status'           => 'publish',
        'posts_per_page'        => $limit,
        'tax_query'             => array(
            array(
                'taxonomy'      => 'product_cat',
                'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                'terms'         => $cat_id,
                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            )
        )
    );

    $products = new WP_Query($args);

    $return = array();

    foreach ($products->posts as $key => $post) {

        $product = wc_get_product( $post->ID );

        $p['id'] = $product->get_id();
        $p['title'] = $product->get_title();
        $p['body_html'] = $product->get_description();
        $p['vendor'] = '';
        $p['created_at'] = $product->get_date_created()->date('Y-m-d H:i:s');
        $p['updated_at'] = $product->get_date_modified()->date('Y-m-d H:i:s');
        $p['published_at'] = $p['created_at'];
        $p['product_type'] = $product->get_type();
        $p['handle'] = get_permalink( $product->get_id() );
        $p['tags'] = '';
        $p['published_scope'] = 'web';

        $image_id        = $product->get_image_id();

        $img = array();

        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );

            $img[] = array('id' => $image_id, 'product_id' => $p['id'], 'src' => $image_url, 'position' => 1, 'created_at' => $p['created_at'], 'updated_at' => $p['updated_at']);
        }

        $attachment_ids  = $product->get_gallery_image_ids();

        $position = 2;
        foreach ( $attachment_ids as $attachment_id ) {
            $image_url = wp_get_attachment_url( $attachment_id );

            $img[] = array('id' => $attachment_id, 'product_id' => $p['id'], 'src' => $image_url, 'position' => $position, 'created_at' => $p['created_at'], 'updated_at' => $p['updated_at']);
            $position++;
        }

        $p['images'] = $img;

        if($product->get_type() == 'variable'){

            $available_variations = $product->get_available_variations();

            $attributes = array();
            $position = 1;

            foreach ($available_variations as $key => $variations) 
            { 
                $a = array();
                
                $a['id'] = $variations['variation_id'];
                $a['product_id'] = $p['id'];
                $a['price'] =  $variations['display_regular_price'];
                $a['compare_at_price'] = $variations['display_price'];  // sale price
                $a['sku'] = $variations['sku'];
                $a['position'] = $position;
                $a['inventory_quantity'] = $variations['max_qty'];
                $a['image_id'] = $variations['image_id'];
                $a['created_at'] = $p['created_at'];
                $a['updated_at'] = $p['updated_at'];

                foreach($variations['attributes'] as $key => $val){

                    if(isset($val) && !empty($val)){

                        $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*

                        $a['title'] = $option_name;

                        $a['option1'] = $val;
                    }

                }

                $position++;

                $attributes[] = $a;

            }

            $p['variants'] = $attributes;

        }else{

            $a['id'] = $p['id'];
            $a['product_id'] = $p['id'];
            $a['title'] = $p['title'];
            $a['price'] = $product->get_regular_price();
            $a['compare_at_price'] = $product->get_sale_price();
            $a['sku'] = $product->get_sku();
            $a['position'] = 1;
            $a['option1'] = '';
            $a['inventory_quantity'] = $product->get_stock_quantity();
            $a['image_id'] = $image_id;
            $a['created_at'] = $p['created_at'];
            $a['updated_at'] = $p['updated_at'];

            $p['variants'][] = $a;
        }

        $return['products'][] = $p;

    }

    $return['result'] = true;

    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Get Product By Category API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

    aitrillion_api_log($log_message);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;

}

function aitrillion_searchCategory(WP_REST_Request $request){

    $params = $request->get_query_params();

    $cat_name = $params['title'];

    $args['taxonomy'] = 'product_cat';
    $args['hide_empty'] = 0;
    $args['name__like'] = $cat_name;

    $categories = get_categories($args);

    $return = array();

    global $title;

    foreach($categories as $category){

        $cat['id'] = $category->term_id;
        $cat['handle'] = get_term_link( $category->term_id, 'product_cat' );
        $cat['body_html'] = $category->description;
        $cat['updated_at'] = '';
        $cat['published_at'] = '';
        $cat['product_count'] = $category->category_count;

        $name = array();

        if($category->parent > 0){
            $name = hierarchical_category_tree($category->parent);
        }

        $name[] = $category->name;

        $cat['title'] = implode(' > ', $name);

        $title = array();

        $return[]['collections'] = $cat;
    }

    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Category search API: '.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

    //aitrillion_api_log($log_message);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}

function hierarchical_category_tree( $cat ) {

    global $title;

        $args['taxonomy'] = 'product_cat';
        $args['hide_empty'] = 0;
        $args['term_taxonomy_id'] = $cat;

        $categories = get_categories($args);

        foreach($categories as $category){

            $title[] = $category->name;

            if($category->parent > 0){
                return hierarchical_category_tree($category->parent);
            }else{
                return array_reverse($title);
            }
        }
}

function aitrillion_updateScriptVersion(){

    $script_version = get_option('_aitrillion_script_version');

    if(empty($script_version)){
        $script_version = 1;
    }else{
        $script_version++;    
    }

    update_option('_aitrillion_script_version', $script_version);

    $script_version = get_option('_aitrillion_script_version');

    $return['result'] = false;
    $return['script_version'] = $script_version;


    $log_message = 'Script version updated '.$script_version.PHP_EOL;

    aitrillion_api_log($log_message);

    echo json_encode($return);
    exit;

}

function aitrillion_blockLoyaltyMember(WP_REST_Request $request){

    $params = $request->get_query_params();

    $member_ids = $params['member_ids'];

    update_option('_aitrillion_block_loyalty_members', $member_ids);

    $return['result'] = false;
    $return['message'] = 'ID Updated';

    $log_message = 'Loyalty block member updated '.$member_ids.PHP_EOL;

    aitrillion_api_log($log_message);

    echo json_encode($return);
    exit;

}

function aitrillion_abandonedcart(){
    
    $cart_id = sanitize_text_field($_GET['quoteid']);

    global $wpdb;

    $order_sessions = $wpdb->get_results( "SELECT * FROM ". $wpdb->prefix."woocommerce_sessions");

    $items = array();

    $abandonedcart = false;

    foreach ( $order_sessions as $order ) {

        $session_value = unserialize($order->session_value);

        if(isset($session_value['cart_id']) && $session_value['cart_id'] == $cart_id){

            $abandonedcart = true;

            $cart = unserialize($session_value['cart']);

            if(is_array($cart) && !empty($cart)){

                $i = 1;
                $total_price = 0;

                foreach($cart as $key => $cart_item){

                    //echo '<pre>'; print_r($cart_item); echo '</pre>';

                    $line_item['id'] = $i;
                    $line_item['product_id'] = $cart_item['product_id'];

                    if(empty($cart_item['variation_id'])){
                        $line_item['variant_id'] = $cart_item['product_id'];

                        $product = wc_get_product( $cart_item['product_id'] );
                    }else{
                        $line_item['variant_id'] = $cart_item['variation_id'];

                        $product = wc_get_product( $cart_item['variation_id'] );
                    }

                    $line_item['quantity'] = $cart_item['quantity'];

                    $line_item['name'] = $product->get_name();
                    $line_item['title'] = $product->get_name();
                    $line_item['sku'] = $product->get_sku();
                    $line_item['price'] = $product->get_price();
                    $line_item['url'] = get_permalink( $product->get_id() );

                    $total_price = $line_item['price'] + $total_price;

                    $image_id        = $product->get_image_id();

                    $img = array();

                    if ( $image_id ) {
                        $image_url = wp_get_attachment_image_url( $image_id, 'full' );

                        $line_item['image'] = $image_url;
                    }else{
                        $line_item['image'] = '';
                    }

                    $items[] = $line_item;

                    $line_item = array();
                    $i++;
                }

                if($i == 1){
                    echo json_encode(array('msg' => 'no item found in abandoned cart'));
                    exit;
                }else{
                    $data['token'] = $cart_id;
                    $data['item_count'] = $i-1;
                    $data['items'] = $items;
                    $data['total_price'] = $total_price;

                    echo json_encode($data);
                    exit;

                }

            }
        }
        
    }

    if(!isset($i) && empty($i)){

        $return['result'] = false;
        $return['message'] = 'No data found';

        echo json_encode($return);
        exit;
    }

}

function aitrillion_createCoupon(WP_REST_Request $request){

    $body = $request->get_body();

    $params = json_decode($body);

    $coupon = new WC_Coupon();

    if(isset($params->coupon_code) && !empty($params->coupon_code)){
        $code = $params->coupon_code;
        $coupon->set_code( $params->coupon_code );
    }else{
        $code = raitrillion_andom_strings(8);
        $coupon->set_code( $code );
    }
    
    if(isset($params->coupon_description) && !empty($params->coupon_description)){
        $coupon->set_description( $params->coupon_description );    
    }

    // discount type can be 'fixed_cart', 'percent' or 'fixed_product', defaults to 'fixed_cart'
    $coupon->set_discount_type( $params->discount_type );

    // discount amount
    $coupon->set_amount( $params->discount_amount );

    if(isset($params->allow_free_shipping) && !empty($params->allow_free_shipping)){
        // allow free shipping
        $coupon->set_free_shipping( true );
    }

    if(isset($params->coupon_expiry) && !empty($params->coupon_expiry)){
        // coupon expiry date
        $coupon->set_date_expires( $params->coupon_expiry );    
    }

    if(isset($params->cart_minimum_amount) && !empty($params->cart_minimum_amount)){
        // minimum spend
        $coupon->set_minimum_amount( $params->cart_minimum_amount );  
    }

    if(isset($params->cart_maximum_amount) && !empty($params->cart_maximum_amount)){
        // maximum spend
        $coupon->set_maximum_amount( $params->cart_maximum_amount );
    }

    if(isset($params->is_individual_use) && !empty($params->is_individual_use)){
        // individual use only
        $coupon->set_individual_use( $params->is_individual_use );
    }

    if(isset($params->product_ids) && !empty($params->product_ids)){
        // products
        $products = explode(',', $params->product_ids);
        $coupon->set_product_ids( $products );
    }    

    if(isset($params->exclude_product_ids) && !empty($params->exclude_product_ids)){
        $exclude_products = explode(',', $params->exclude_product_ids);

        // exclude products
        $coupon->set_excluded_product_ids( $exclude_products );
    } 

    if(isset($params->uses_limit) && !empty($params->uses_limit)){
        // usage limit per coupon
        $coupon->set_usage_limit( $params->uses_limit );
    }

    if(isset($params->per_user_limit) && !empty($params->per_user_limit)){
        // usage limit per user
        $coupon->set_usage_limit_per_user( $params->per_user_limit );
    }    

    if(isset($params->user_id) && !empty($params->user_id)){

        $user_info = get_userdata($params->user_id);

        if($user_info){

            // allowed emails
            $coupon->set_email_restrictions( 
                array( 
                    $user_info->user_email
                )
            );     
        }
    }
    

    $coupon->save();

    $return['result'] = true;
    $return['coupon_codes'] = array($code);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}

// This function will return a random
// string of specified length
function aitrillion_random_strings($length_of_string)
{
    // String of all alphanumeric character
    $str_result = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';

    // Shuffle the $str_result and returns substring
    // of specified length
    return substr(str_shuffle($str_result), 0, $length_of_string);
}

?>