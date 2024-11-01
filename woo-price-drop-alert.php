<?php
/**
* Plugin Name: Woo Price Drop Alert
* Description: User will notify via email if user's selected product price get down by current price.
* Version: 1.1
* Author: Niket Joshi - Ecodeblog
* Author URI: https://ecodeblog.com
* License: GPLv2 or later
**/

/* Check whether woocomerce is activated or not in site. */
if (!function_exists('wpda_activate')) {
  function wpda_activate() {
    global $table_prefix, $wpdb;

    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
      include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }
    if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
      // Deactivate the plugin.
      deactivate_plugins( plugin_basename( __FILE__ ) );
      // Throw an error in the WordPress admin console.
      $error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'simplewlv' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/simplewlv/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', 'simplewlv' ) . '</p>';
      die( $error_message ); // WPCS: XSS ok.
    }

    $tblname = 'wpda';
    $wp_track_table = $table_prefix . "$tblname";

    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) {
        $sql = "CREATE TABLE `".$wp_track_table."`(";
        $sql .= "  `id`  int(11) NOT NULL auto_increment, ";
        $sql .= "  `user_id`  int NOT NULL, ";
        $sql .= "  `user_email`  varchar(200) NOT NULL, ";
        $sql .= "  `product_id`  int NOT NULL, ";
        $sql .= "  `product_price`  int NOT NULL, ";
        $sql .= "  `status`  varchar(20) NOT NULL, ";
        $sql .= " PRIMARY KEY `id` (`id`) ";
        $sql .= ");";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
  // wp_schedule_event(time(), 'every_minute', 'checkEmail'); //five_seconds / hourly
  }
}
register_activation_hook( __FILE__, 'wpda_activate' );
/* Check whether woocommerce is activated ot not in site */

/* Load stylesheet in prodct page */
if (!function_exists('wpda_pda')) {
  function wpda_pda() {
      if(is_singular( 'product' )){
      	$plugin_url = plugin_dir_url( __FILE__ );
      	wp_enqueue_style( 'Price-drop-stylesheet', $plugin_url . 'css/price-drop-custom.css');
      	wp_enqueue_script( 'Price-drop-scripts', $plugin_url . 'js/price-drop-custom.js','',false,1);
      }
  }
}
add_action( 'wp_enqueue_scripts', 'wpda_pda' );
/* Load stylesheet in prodct page */


/* Added Form for the follow price drop notification */
add_action( 'woocommerce_after_add_to_cart_button', 'wpda_add_content_after_addtocart_button_func' );
if (!function_exists('wpda_add_content_after_addtocart_button_func')) {
  function wpda_add_content_after_addtocart_button_func() {
  	global $product;  // Get Global varriable for the identidy product
    if ( $product->is_type( 'simple' ) ) {
      $product_id = $product->get_id();
      /*echo "<pre>";
      print_r($product); die();*/
    	$old_price = isset($product->sale_price) ? $product->regular_price : $product->sale_price;
    	// Echo content.
    	$content = '';
    	$header= "Price drop Alert";
      $admin_ajax = admin_url('admin-ajax.php');
    	if(is_user_logged_in()){
        $button_name= "Notify me";
        $user = wp_get_current_user();
        $email= $user->data->user_email;
        $id= get_current_user_id();
        $form = "<form class='price_drop_alert'><input type='button' name='pricedrop_alert_submit' id='pricedrop_alert_submit' class='btn form-control pricedrop_alert_submit guest btn btn-sm' value='".$button_name."' data-email='".$email."' data-id='".base64_encode($id)."' data-product='".base64_encode($product_id)."' data-price= '".base64_encode($old_price)."' /></form>";
      }else{
        $button_name= "Notify";
        $form = "<form class='price_drop_alert'><input type='email' name='pricedrop_alert_email' placeholder='Email address' id='pricedrop_alert_email' class='form-control pricedrop_alert_email' /><input type='button' name='pricedrop_alert_submit' id='pricedrop_alert_submit' class='btn form-control pricedrop_alert_submit btn btn-sm' value='".$button_name."' data-product='".base64_encode($product_id)."' data-price= '".base64_encode($old_price)."' /></form>";
      }
    	$content.= "<input type='hidden' name='url' id='url' value='$admin_ajax' /><div class='pricedrop_alert_main price_drop_alert'>";
    	$content.= "<div class='pricedrop_alert_header'><h3>".__($header)."</h3></div>";
    	$content.= "<div class='pricedrop_alert_form'>".$form."</div>";
    	$content.= "</div>";
    }
  	 echo $content;
  }
}

// Ajax call to get the data which are submitted by user from the single-product page

add_action("wp_ajax_wpda_GetuserDetail", "wpda_GetuserDetail");
add_action("wp_ajax_nopriv_wpda_GetuserDetail", "wpda_GetuserDetail");
if (!function_exists('wpda_GetuserDetail')) {
  function wpda_GetuserDetail(){
    global $wpdb;
    $table_name = $wpdb->prefix . "wpda";
    if($_POST['action'] == 'wpda_GetuserDetail'){
      if(is_user_logged_in()){
        $email= sanitize_email($_POST['data']);
      }else{
        $email= sanitize_email($_POST['data']);
      }
      $product= sanitize_text_field(base64_decode($_POST['product']));
      $price= sanitize_text_field(base64_decode($_POST['price']));
      if($email == null){
        $data['MSG']= 'Please enter email address';
        $data['FLAG']= false;
        echo  json_encode($data);
        return true;
      }elseif($product == null){
        $data['MSG']= 'Please product id';
        $data['FLAG']= false;
        echo  json_encode($data);
        return true;
      }elseif($price == null){
        $data['MSG']= 'Please add old price';
        $data['FLAG']= false;
        echo  json_encode($data);
        return true;
      }
    $user_count= $wpdb->get_results("SELECT * FROM $table_name WHERE product_id = '".$product."' AND product_price = '". $price ."' AND user_email = '".$email."' ");
    if(count($user_count) != null){
        $data['MSG']= 'You have already applied for the same product before!';
        $data['FLAG']= true;
    }else{
        $wpdb->insert($table_name, array('user_email' => $email, 'product_id' => $product, 'product_price'=> $price, 'status'=> 'active'));

        $data['MSG']= 'We will notify once product price get down by current price!';
        $data['FLAG']= true;
    }
    }else{
      $data['MSG']= 'Something went wrong!';
      $data['FLAG']= false;
    }
    echo json_encode($data);
    die();
  }
}

/* Change email content type */
remove_filter( 'wp_mail_content_type', 'wpda_set_html_content_type' );
add_filter( 'wp_mail_content_type', 'wpda_set_html_content_type' );

if (!function_exists('wpda_set_html_content_type')) {
  function wpda_set_html_content_type() {
    return 'text/html';
  }
}

/* Call function on update product */
add_action( 'post_updated', 'wpda_UpdateProduct', 1, 3 );
if (!function_exists('wpda_UpdateProduct')) {
  function wpda_UpdateProduct( $product_id ) {
      global $wpdb, $product;
      if ( $product->is_type( 'simple' ) ) {
        $table_name = $wpdb->prefix . "wpda";
        $product = wc_get_product( $product_id );
        $product_name= $product->get_title();
        $product_price= $product->get_regular_price();
        $product_sale_price= $product->get_sale_price();
        if( $product->is_on_sale() ) {
            $price= $product->get_sale_price();
        }else{
            $price= $product->get_regular_price();
        }
        $product_id= $product_id;
        $user_count= $wpdb->get_results("SELECT id,product_id,user_email,product_price FROM $table_name WHERE product_id = '".$product_id."' AND product_price >= '". $price ."' AND status = 'active'");

        if(count($user_count) > 0){
          foreach ($user_count as $value) {
              $message=  "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head><body>";
              $message.= "<table><tr>Hey ". $value->user_email .", </tr><tr><td>Product <b>". $product_name ."</b> you followed on our store when product price is <b>". get_woocommerce_currency_symbol().$value->product_price ."</b> and now it's available in our store with current price <b>".get_woocommerce_currency_symbol().$product_price.".</b><td></tr>";
              $message.= "<tr><td>Please visit out store once you get this email.</td></tr><br/>";
              $message.= "<tr><td>Thank you,<br/> ". get_bloginfo() ." </td></tr>";
              $message.= "</table></body></html>";
              wp_mail($value->user_email,'Price drop at '.get_bloginfo(),$message);
              $wpdb->update($table_name, array('status' => 'active'), array('id' => $value->id), array('%s'),array('%d'));
          }
        }
      }
  }
}