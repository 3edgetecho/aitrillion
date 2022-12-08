<?php 


add_action( 'woocommerce_single_product_summary', 'aitrillion_product_review_rating', 5 );
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_list_review');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_related_product');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_new_arrival');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_trending_product');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_recent_view');
add_action( 'woocommerce_cart_coupon', 'aitrillion_coupon_widget');
add_action( 'woocommerce_after_cart', 'aitrillion_new_arrival');
add_action( 'woocommerce_after_cart', 'aitrillion_trending_product');
add_action( 'woocommerce_after_cart', 'aitrillion_recent_view');
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_new_arrival');
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_trending_product');
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_recent_view');
add_action( 'wp_footer', 'aitrillion_referral_hidden_fields' );
add_action( 'wp_footer', 'aitrillion_lyt_blocked_customer' );

add_action('wp_enqueue_scripts', 'aitrillion_script');

add_shortcode('ait_product_featured_reviews', 'aitrillion_product_featured_reviews_shortcode');
add_shortcode('ait_site_reviews', 'aitrillion_site_reviews_shortcode');
add_shortcode('ait_new_arrival', 'aitrillion_new_arrival_shortcode');
add_shortcode('ait_trending_product', 'aitrillion_trending_product_shortcode');
add_shortcode('ait_recent_view', 'aitrillion_recent_view_shortcode');
add_shortcode('ait_affiliate', 'aitrillion_affiliate_shortcode');
add_shortcode('ait_loyalty', 'aitrillion_loyalty_shortcode');
add_shortcode('ait_list_review', 'aitrillion_list_review_shortcode');
add_shortcode('ait_related_product', 'aitrillion_ait_related_product_shortcode');
add_shortcode('ait_coupon_widget', 'aitrillion_coupon_widget_shortcode');


function aitrillion_product_review_rating() {

    echo '<span class="egg-product-reviews-rating" data-id="'.wc_get_product()->get_id().'" id="'.wc_get_product()->get_id().'"></span>';
}

function aitrillion_ait_list_review() {
    echo do_shortcode('[ait_list_review]');
}

function aitrillion_related_product() {
    echo do_shortcode('[ait_related_product]');
}

function aitrillion_ait_new_arrival() {
    echo do_shortcode('[ait_new_arrival]');
}

function aitrillion_ait_trending_product() {
    echo do_shortcode('[ait_trending_product]');
}

function aitrillion_ait_recent_view() {
    echo do_shortcode('[ait_recent_view]');
}

function aitrillion_coupon_widget(){
    echo do_shortcode('[ait_coupon_widget]');
}

function aitrillion_new_arrival() {
        echo do_shortcode('[ait_new_arrival]');
}

function aitrillion_trending_product() {
    echo do_shortcode('[ait_trending_product]');
}

function aitrillion_recent_view () {
    echo do_shortcode('[ait_recent_view]');
}
function aitrillion_after_shop_new_arrival() {
    echo do_shortcode('[ait_new_arrival]');
}
function aitrillion_after_shop_trending_product() {
    echo do_shortcode('[ait_trending_product]');
}

function aitrillion_after_shop_recent_view() {
    echo do_shortcode('[ait_recent_view]');
}

function aitrillion_script() {

    $username = get_current_user();

    $userid = get_current_user_id();

    $current_user = wp_get_current_user();

    $aitrilltion_script = get_option('_aitrillion_script_url');

    //echo '<br>aitrilltion_script: '.$aitrilltion_script;

    $script_version = get_option('_aitrillion_script_version');

    //echo '<br>script_version: '.$script_version;

    $script = "
    <!-- AITRILLION APP SCRIPT -->

    var aioMeta = {
        meta_e: '".$current_user->user_email."',
        meta_i: '".$userid."',
        meta_n: '".$username."',
    } 

    <!-- END AITRILLION APP SCRIPT -->";

    $url = explode('?', $aitrilltion_script);

    wp_enqueue_script( 'aitrillion-script', $url[0].'?v='.$script_version.'&'.$url[1], array(), null);

    wp_add_inline_script('aitrillion-script', $script, 'after'); 
}

function aitrillion_product_featured_reviews_shortcode() {

    $message ='<div id="egg-product-featured-reviews"></div>'; 
    return $message;
}

function aitrillion_site_reviews_shortcode() {

    $message ='<div class="egg-site-all-reviews"></div>'; 
    return $message;
}

function aitrillion_new_arrival_shortcode() {

    $message ='<div id="aionewarrival" class="aionewarrival"></div>'; 
    return $message;
}

function aitrillion_trending_product_shortcode() {

    $message ='<div id="aiotrendingproducts" class="aiotrendingproducts"></div>'; 
    return $message;
}

function aitrillion_recent_view_shortcode() {

    $message ='<div id="aiorecentview" class="aiorecentview" ></div>'; 
    return $message;
}

function aitrillion_affiliate_shortcode() {

    $message ='<div id="aio-affiliate-dashboard" style="text-align:center"><img id="aft-loader" src="https://static.aitrillion.com/review/src/assets/images/loader.gif"></div>'; 
    return $message;
}

function aitrillion_loyalty_shortcode() {

    $message ='<span class="aaa-ly-cus-available-p"> </span>
    <span class="aaa-ly-cus-spent-p"> </span>
    <span class="aaa-ly-cus-lifetime-p"> </span>'; 
    return $message;
}

function aitrillion_list_review_shortcode() {

    $message = include AIT_PATH . 'list-review.html';
    return $message;
}

function aitrillion_ait_related_product_shortcode() {

    $message ='<div id="aiorelatedproducts" class="aiorelatedproducts"></div>'; 
    return $message;
}

function aitrillion_coupon_widget_shortcode() {

    $message = '<div class="aaa-loyalty-cartredeem-widget"></div>'; 
    return $message;
}

function aitrillion_referral_hidden_fields() {
    if (is_user_logged_in()) {

        $userid = get_current_user_id();

        $current_user = wp_get_current_user();

        ?>
        <input type="hidden" name="is_customer_logged_in" value="1" class="is_customer_logged_in"> 
        <input type="hidden" name="referral_customer_logged_id" value="<?=$userid?>" class="referral_customer_logged_id"> 
        <input type="hidden" name="referral_customer_email" value="<?=$current_user->user_email?>" class="referral_customer_email">
        <?php 
    }
    else { 
        
        ?>
        <input type="hidden" name="is_customer_logged_in" value="0" class="is_customer_logged_in">
        <input type="hidden" name="referral_customer_logged_id" value="0" class="referral_customer_logged_id">

    <?php }

}

function aitrillion_lyt_blocked_customer(){

    if (is_user_logged_in()) {

        $userid = get_current_user_id();

        $lyt_blocked_customers = get_option('_aitrillion_block_loyalty_members');

        //echo '<br>lyt_blocked_customers: '.$lyt_blocked_customers;

        if(!empty($lyt_blocked_customers)){
            $customer_ids = explode(',', $lyt_blocked_customers);

            if(in_array($userid, $customer_ids)){
                echo '<input type="hidden" name="aio_lyt_blocked_customer" value="'.$userid.'" class="aio_lyt_blocked_customer">';
            }
        }

    }
}

function aitrillion_affiliate_endpoint() {
    add_rewrite_endpoint( 'affiliate', EP_PAGES );
}
add_action( 'init', 'aitrillion_affiliate_endpoint' );


add_filter ( 'woocommerce_account_menu_items', 'aitrillion_affiliate_link', 40 );
function aitrillion_affiliate_link( $menu_links ){

    $affiliate_module = get_option('_aitrillion_affiliate_module');

    if($affiliate_module == 1){
        $menu_links = array_slice( $menu_links, 0, 5, true ) 
                        + array( 'affiliate' => 'Affiliate Program' )
                        + array_slice( $menu_links, 5, NULL, true );
    }
    
    return $menu_links;
}



function aitrillion_affiliate_content() {
    //echo "affiliate";
    echo do_shortcode('[ait_affiliate]');
}
add_action( 'woocommerce_account_affiliate_endpoint', 'aitrillion_affiliate_content' );


?>