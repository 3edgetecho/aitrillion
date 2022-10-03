<?php 

/**
 * Plugin Name:       Ai Trillion
 * Plugin URI:        https://www.aitrillion.com/
 * Description:       Ai Trillion Integration
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Ai Trillion
 * Author URI:        https://www.aitrillion.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://www.aitrillion.com/
 * Text Domain:       ai-trillion
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    return;
}


// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Test to see if WooCommerce is active (including network activated).
$woocommerce_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (in_array( $woocommerce_plugin_path, wp_get_active_and_valid_plugins() )) 
{

    include_once $woocommerce_plugin_path;   // Include woocommerce library

    // Defines the path to the main plugin file.
    define( 'AIT_FILE', __FILE__ );

    // Defines the path to be used for includes.
    define( 'AIT_PATH', plugin_dir_path( AIT_FILE ) );

    // Defines the URL to the plugin.
    define( 'AIT_URL', plugin_dir_url( AIT_FILE ) );

    // Define end point of Ai Trillion 
    define('AITRILLION_END_POINT', 'https://connector-api-dev.aitrillion.com/dev/');
    define('AITRILLION_APP_NAME', 'Aitrillion Wordpress');

    $domain = preg_replace("(^https?://)", "", site_url() );

    define('DOMAIN', $domain);

    include AIT_PATH . 'common_functions.php';
    include AIT_PATH . 'platform_api.php';

        // add the admin options page
        
        add_action('admin_menu', 'aitrillion_admin_menu');

        function aitrillion_admin_menu() {

            //create new top-level menu
            add_menu_page(
                'AiTrillion', 
                'AiTrillion', 
                'manage_options', 
                'aitrillion.php',
                'aitrillion_options_page'
            );

            add_submenu_page(
                'aitrillion.php',
                'AiTrillion Settings',
                'Settings',
                'manage_options',
                'aitrillion.php',
                'aitrillion_options_page'
            );

            /*add_submenu_page(
                'aitrillion.php',
                'Ai Trillion Sync Data',
                'Sync Data',
                'manage_options',
                'aitrillion.php',
                'aitrillion_sync_data'
            );*/

        }

        add_action('admin_init', 'aitrillion_admin_init');

        function aitrillion_admin_init(){

            register_setting( 'aitrillion_options', '_aitrillion_api_key' );
            register_setting( 'aitrillion_options', '_aitrillion_api_password' );

        }

     // display the admin options page
        function aitrillion_options_page() {
    ?>
            <div class="wrap">
                <h1>AiTrillion Settings</h1>

                <form method="post" action="options.php">

                    <?php settings_fields( 'aitrillion_options' ); ?>

                    <?php do_settings_sections( 'aitrillion_options' ); ?>
                    <table class="form-table">
                        <tr valign="top">
                        <th scope="row">AiTrillion API Key</th>
                        <td><input type="text" name="_aitrillion_api_key" value="<?php echo esc_attr( get_option('_aitrillion_api_key') ); ?>" /></td>
                        </tr>
                         
                        <tr valign="top">
                        <th scope="row">AiTrillion API Password</th>
                        <td>
                            <input type="password" name="_aitrillion_api_password" value="<?php echo esc_attr( get_option('_aitrillion_api_password') ); ?>" />
                        </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>

                </form>
            </div>
     
    <?php
        }

        add_action('updated_option', 'validate_api_key', 10, 3);

        function validate_api_key($option_name, $old_value, $value){

            if($option_name == '_aitrillion_api_key' || $option_name == '_aitrillion_api_password'){

                $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
                $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

                $domain = preg_replace("(^https?://)", "", site_url() );

                $url = AITRILLION_END_POINT.'validate?shop_name='.$domain;

                $response = wp_remote_get( $url, array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                    )
                ));

                $r = json_decode($response['body']);

                aitrillion_api_log('Validate key api '.$url.PHP_EOL.print_r($r, true));

                if(isset($response->status) && $response->status == 'sucess'){
                    update_option('_aitrillion_valid_key', 'true');
                }else{
                    update_option('_aitrillion_valid_key', 'true');
                }
            }
        }
    
}else{
    echo 'This plugin works with wocommerce only. Please install and activate woocommerce first.';
}

