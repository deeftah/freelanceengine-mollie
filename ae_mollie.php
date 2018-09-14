<?php
add_filter('ae_admin_menu_pages', 'ae_mollie_add_settings');
function ae_mollie_add_settings($pages) {
    $sections = array();
    $options = AE_Options::get_instance();

    $api_link = " <a class='find-out-more' target='_blank' href='https://www.mollie.com/dashboard/developers/api-keys' >" . __("Find out more", ET_DOMAIN) . " <span class='icon' data-icon='i' ></span></a>";

    /**
     * ae fields settings
     */
    $sections = array(
        'args' => array(
            'title' => __("Mollie API", ET_DOMAIN) ,
            'id' => 'meta_field',
            'icon' => 'F',
            'class' => ''
        ) ,

        'groups' => array(
            array(
                'args' => array(
                    'title' => __("Mollie API", ET_DOMAIN) ,
                    'id' => 'api-key',
                    'class' => '',
                    'desc' => __('Live API-key.', ET_DOMAIN) . $api_link,
                    'name' => 'mollie'
                ) ,
                'fields' => array(
                    array(
                        'id' => 'api_key',
                        'type' => 'text',
                        'label' => __("Live API-key", ET_DOMAIN) ,
                        'name' => 'api_key',
                        'class' => ''
                    )
                )
            )
        )
    );

    $temp = new AE_section($sections['args'], $sections['groups'], $options);

    $stripe_setting = new AE_container(array(
        'class' => 'field-settings',
        'id' => 'settings',
    ) , $temp, $options);

    $pages[] = array(
        'args' => array(
            'parent_slug' => 'et-overview',
            'page_title' => __('Mollie', ET_DOMAIN) ,
            'menu_title' => __('MOLLIE', ET_DOMAIN) ,
            'cap' => 'administrator',
            'slug' => 'ae-mollie',
            'icon' => '$',
            'desc' => __("Integrate the Mollie payment gateway to your site", ET_DOMAIN)
        ) ,
        'container' => $stripe_setting
    );

    return $pages;
}

add_filter( 'ae_support_gateway', 'ae_mollie_add' );
function ae_mollie_add($gateways){
        $gateways['mollie'] = 'Mollie';
        return $gateways;
}

add_action('after_payment_list', 'ae_mollie_render_button');
add_action('after_payment_list_upgrade_account', 'ae_mollie_render_button');
function ae_mollie_render_button() {
?>
        <li class="panel">
        <span class="title-plan mollie-payment" data-type="mollie">
            <?php _e("Mollie", ET_DOMAIN); ?>
            <span><?php _e("Send your payment to our mollie account", ET_DOMAIN); ?></span>
        </span>
        <a data-toggle="collapse" data-type="mollie" data-parent="#fre-payment-accordion" href="#fre-payment-mollie" class="btn collapsed select-payment"><?php _e("Select", ET_DOMAIN); ?></a>
        <?php require __DIR__ . "/form.php"; ?>
    </li>
<?php
}

add_action('ae_payment_script', 'ae_mollie_add_script');
function ae_mollie_add_script() {
    global $user_ID, $ae_post_factory;
    $ae_pack = $ae_post_factory->get('pack');
    $packs = $ae_pack->fetch();

    //wp_enqueue_script('stripe.checkout', 'https://checkout.stripe.com/v2/checkout.js');
    //wp_enqueue_script('stripe', 'https://js.stripe.com/v1/');
    wp_enqueue_script('ae_stripe', plugin_dir_url(__FILE__) . '/stripe.js', array(
        'underscore',
        'backbone',
        'appengine'
    ) , '1.0', true);
}

require_once __DIR__ . "/_lib.php";
add_filter('ae_setup_payment', 'ae_mollie_setup_payment', 10, 3);
function ae_mollie_setup_payment($response, $paymentType, $order) {
    if ($paymentType == 'MOLLIE') {

        $order_pay = $order->generate_data_to_pay();
        $key = ae_get_option('mollie');

            global $wp_rewrite;
            $returnURL = et_get_page_link('process-payment', array(
                'paymentType' => 'mollie',
                'token' => md5($order_pay['ID']),
                'order-id' => $order_pay['ID']
            ));

$j = begin($key["api_key"], [
  "amount" => ["currency" => $order_pay['currencyCodeType'], "value" => $order_pay['total']],
  "description" => "Betaling",
  "redirectUrl" => $returnURL,
  //"webhookUrl" => "https://usenet.today/action/pay/cb",
  "metadata" => json_encode($order_pay)
]);
session_start();
$_SESSION["mollie_id"] = $j["id"];

            $response = array(
                'success' => true,
                'data' => array(
                    'ACK' => true,
                    'url' => $j["_links"]["checkout"]["href"]
                ) ,
                'paymentType' => 'mollie'
            );
    }
    return $response;
}

add_filter( 'ae_process_payment', 'ae_mollie_process_payment', 10 ,2 );
function ae_mollie_process_payment ( $payment_return, $data) {
        $payment_type = $data['payment_type'];
        $order = $data['order'];
        if( $payment_type == 'mollie') {

session_start();
$key = ae_get_option('mollie');
$j = status($key["api_key"], $_SESSION["mollie_id"]);

		if (strtolower($j["status"]) === "paid") {

                        $payment_return =       array (
                                'ACK'                   => true,
                                'payment'               =>      'mollie',
                                'payment_status' =>'Completed'

                        );
                        $order->set_status ('publish');
                        $order->update_order();

                } else {
                        $payment_return =       array (
                                'ACK'                   => false,
                                'payment'               =>      'mollie',
                                'payment_status' =>'Pending',
                                'msg'   => __('Mollie payment method false.', ET_DOMAIN)

                        );
                }
        }
        return $payment_return;
}

