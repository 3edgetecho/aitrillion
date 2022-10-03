<?php 

    add_filter( 'rest_authentication_errors', 'aitrillion_auth_check', 99 );

    add_action('rest_api_init', function () {
        
        register_rest_route( 'aitrillion/v1', 'getshopinfo',array(
                    'methods'  => 'GET',
                    'callback' => 'getStoreDetail'
        ));

        register_rest_route( 'aitrillion/v1', 'getcustomers',array(
                    'methods'  => 'GET',
                    'callback' => 'getCustomers'
        ));

        register_rest_route( 'aitrillion/v1', 'getcategories',array(
                    'methods'  => 'GET',
                    'callback' => 'getCategories'
        ));

        register_rest_route( 'aitrillion/v1', 'getproducts',array(
                    'methods'  => 'GET',
                    'callback' => 'getProducts'
        ));

        register_rest_route( 'aitrillion/v1', 'getorders',array(
                    'methods'  => 'GET',
                    'callback' => 'getOrders'
        ));
    });


/* 
Check header authentication
compare request username password with store username password.
return error message if authentication fail
*/

function aitrillion_auth_check(){

    $request_user = $_SERVER["PHP_AUTH_USER"];
    $request_pw = $_SERVER["PHP_AUTH_PW"];

    //echo '<br>request_user: '.$request_user;
    //echo '<br>request_pw: '.$request_pw;

    $api_key = get_option('_aitrillion_api_key');
    $api_pw = get_option('_aitrillion_api_password');

    //echo '<br>api_key: '.$api_key;
    //echo '<br>api_pw: '.$api_pw;

    if(($request_user != $api_key) || ($request_pw != $api_pw)){

        $return['result'] = false;
        $return['message'] = 'Invalid api username or password';


        $log_message = 'Get Shop Info '.PHP_EOL.'return: '.print_r($return, true);

        aitrillion_api_log($log_message);

        echo json_encode($return);
        exit;

    }else{

        return true;
    }
}

function getStoreDetail(WP_REST_Request $request){

    
    //echo '<br>request: <pre>'; print_r($request); echo '</pre>';

    $endpoint = $request->get_route();

    //echo '<br>endpoint: '.$endpoint;

    //$return['app_name'] = AITRILLION_APP_NAME;
    $return['shop_name'] = DOMAIN;

    $return['shop_type'] = 'woocommerce';
    $return['shop_owner'] = '';
    
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

    $log_message = 'Get Shop Info '.$endpoint.PHP_EOL.'return: '.print_r($return, true);

    aitrillion_api_log($log_message);

    return $response;
}

function getCustomers(WP_REST_Request $request){

    //echo '<br>request: <pre>'; print_r($request); echo '</pre>';

    $params = $request->get_query_params();

    //echo '<br>params: <pre>'; print_r($params); echo '</pre>';

    if(!isset($params['result_type'])){

        $return['result'] = false;
        $return['message'] = 'Result type not defined';

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

    
    // $params['updated_at'] // TODO need to discuss logic and implement in customer, products, orders, category

    if($params['result_type'] == 'count'){

        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',         
          )
        );

        $customers = $customer_query->get_results();

        $return = array();

        $return['result'] = true;
        $return['customers']['count'] = count($customers);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }

    if($params['result_type'] == 'row'){

        $paged = $params['page'] ? $params['page'] : 1;

        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',
             'paged' => $paged,
             'number' => 10,
             /*'meta_query' => array(
                    array(
                        'key' => '_aitrillion_sync',
                        'compare' => 'NOT EXISTS' // this should work...
                    ),
                )*/
          )
        );

        $customers = $customer_query->get_results();

        //echo '<br>customers: <pre>'; print_r($customers); echo '</pre>';

        if(count($customers) > 0){

            $return = array();

            foreach ( $customers as $customer_id ) {

               $customer = new WC_Customer( $customer_id );

               $c = array();

               $c['id'] = $customer_id;
               $c['first_name'] = $customer->get_first_name();
               $c['last_name'] = $customer->get_last_name();
               $c['email'] = $customer->get_email();
               $c['verified_email'] = true;
               $c['phone'] = $customer->get_billing_phone();
               $c['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
               $c['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
               $c['orders_count'] = $customer->get_order_count();
               $c['total_spent'] = $customer->get_total_spent();

               $c['last_order_name'] = null;    // TODO update with order number
               $c['last_order_id'] = null;    // TODO update with order ID

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

            //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;

            //$json_respone = json_encode($response);

            //echo '<br>json_respone: <pre>'; print_r($json_respone); echo '</pre>';

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

function getCategories(WP_REST_Request $request){

    //echo '<br>request: <pre>'; print_r($request); echo '</pre>';

    $params = $request->get_query_params();

    //echo '<br>params: <pre>'; print_r($params); echo '</pre>';

    if(!isset($params['result_type'])){

        $return['result'] = false;
        $return['message'] = 'Result type not defined';

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }
}

function getProducts(WP_REST_Request $request){

    //echo '<br>request: <pre>'; print_r($request); echo '</pre>';

    $params = $request->get_query_params();

    //echo '<br>params: <pre>'; print_r($params); echo '</pre>';

    if(!isset($params['result_type'])){

        $return['result'] = false;
        $return['message'] = 'Result type not defined';

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

    $products = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );

    if($params['result_type'] == 'count'){

        //echo '<br>count: '.count($products);   

        $return = array();

        $return['result'] = true;
        $return['products']['count'] = count($products);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response; 
        exit;

    }

    if($params['result_type'] == 'row'){

        //echo '<br>products: <pre>'; print_r($products); echo '</pre>';

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

            //echo '<br>status: '.$product->get_type();

            if($product->get_type() == 'variable'){

                $available_variations = $product->get_available_variations();

                //echo '<br>available_variations: <pre>'; print_r($available_variations); echo '</pre>';

                $attributes = array();
                
                foreach ($available_variations as $key => $variations) 
                { 
                    $a = array();
                    $position = 1;

                    //echo '<pre>'; print_r($variations); echo '</pre>';

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
                    

                    //echo '<pre>'; print_r($variations['attributes']); echo '</pre>';

                    foreach($variations['attributes'] as $key => $val){

                        if(isset($val) && !empty($val)){

                            //echo '<br>key: '.$key;

                            $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*

                            $a['title'] = $option_name;

                            //echo '<br>key: '.$option_name.', Val: '.$val;

                            $a['option1'] = $val;
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
                $p['variants']['price'] = $product->get_regular_price();
                $p['variants']['compare_at_price'] = $product->get_sale_price();
                $p['variants']['sku'] = $product->get_sku();
                $p['variants']['position'] = 1;
                $p['variants']['option1'] = '';
                $p['variants']['inventory_quantity'] = $product->get_stock_quantity();
                $p['variants']['image_id'] = $image_id;
                $p['variants']['created_at'] = $p['created_at'];
                $p['variants']['updated_at'] = $p['updated_at'];
            }

            $return['products'][] = $p;

        }

        //echo '<br>products: <pre>'; print_r($product_result); echo '</pre>';

        $return['result'] = true;

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

function getOrders(WP_REST_Request $request){

    //echo '<br>request: <pre>'; print_r($request); echo '</pre>';

    $params = $request->get_query_params();

    if($params['result_type'] == 'count'){

        $args = array(
            'status'   => array_keys( wc_get_order_statuses() ),
            'limit' => -1
        );

        $orders = wc_get_orders( $args );

        $return = array();

        $return['result'] = true;
        $return['customers']['count'] = count($orders);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }


    if($params['result_type'] == 'row'){

        $paged = $params['page'] ? $params['page'] - 1 : 0;

        //echo '<pre>'; print_r(wc_get_order_statuses()); echo '</pre>';

        $args = array(
            'status'   => array_keys( wc_get_order_statuses() ),
            'page' => $paged,
            'limit' => 10
        );

        $orders = wc_get_orders( $args );

        //echo '<pre>'; print_r($orders); echo '</pre>';

        $o = array();

        foreach($orders as $order){

            //echo '<br>order id: '.$order->get_id();

            if($order->get_status() == 'completed'){

                $c_date = $order->get_date_completed();
                $date_completed = $c_date->date_i18n('Y-m-d H:i:s');    
                
            }else{
                $date_completed = null;
            }

            if($order->get_status() == 'cancelled'){
                $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');
            }


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
            $o['cart_token'] = '';
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
                $i['fulfillment_status'] = '';
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
            $o['shipping_lines']['carrier_identifier'] = $order->get_shipping_total();
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
                $o['customer']['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
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
            $o['refunds'] = array();
            $o['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped' ; 
            $o['financial_status'] = $order->get_status();

            $return['orders'][] = $o;
            
        }

        $return['result'] = true;

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

?>