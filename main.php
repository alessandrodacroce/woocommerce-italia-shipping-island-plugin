<?php

// Main load plug-in

/*
Plugin Name: Woocommerce Shipping Island
Plugin URI: http://www.alessandrodacroce.it/progetto/plugin-woocommerce-spedizione-verso-le-isole/
Description: Add other method to add other fee for island shipping - italian market
Author: Alessandro Dacroce <adacroce@gmail.com>
Version: 0.1-beta
Author URI: http://alessandrodacroce.it/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

function your_shipping_method_init() {
    if ( ! class_exists( 'WC_Your_Shipping_Method' ) ) {
        class WC_Your_Shipping_Method extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct() {
                $this->id                   = 'Shipping Island';
                $this->title                = __( 'Shipping Island' );
                $this->method_description   = __( 'Adds fee if the shipment to the islands' ); // 
                $this->enabled              = "yes"; // This can be added as an setting but for this example its forced enabled
                $this->init();
            }
    
            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
   
                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id  , array( $this, 'process_admin_options' ) ); 
                add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
                
                $this->init_form_fields();
            }
                
            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package ) {
                // This is where you'll add your rates
            }
            
            
            function init_form_fields() {
                
                $wc_shipping_island = get_option('wc_shipping_island');
                              
                $this->form_fields = array(
                    'stato' => array(
                        'title' => __( 'Stato', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Scrivere off per disattivare il plugin, altrimenti on per attivarlo', 'woocommerce' ),
                        'default' => ( isset($wc_shipping_island) ) ? $wc_shipping_island["stato"] :  __( 'on', 'woocommerce' )
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                        'default' => ( isset($wc_shipping_island) ) ? $wc_shipping_island["title"] :  __( 'Gestione Isole', 'woocommerce' )
                    ),
                    'costo' => array(
                        'title' => __( 'Costo gestione', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Il costo che verrà applicato se la spedizione è verso le isole', 'woocommerce' ),
                        'default' => ( isset($wc_shipping_island) ) ? $wc_shipping_island["costo"] :  __("7.5", 'woocommerce')
                    ),
                    'isole' => array(
                        'title'     => __( 'Seleziona iniziale CAP isole' ),
                        'type'      => 'textarea',
                        'description' => __( 'Seleziona l\'iniziale del CAP per le isole per le quali vuoi attivare questo pagamento', 'woocommerce' ),
                        'default'   => ( isset($wc_shipping_island) ) ? implode(", ", $wc_shipping_island["cap"]) :  '07, 08, 09, 92, 93, 95,  94, 98, 90, 96, 97, 91, 86, 87, 88'
                    )
                );
            } // End init_form_fields()
            
            function process_admin_options(){
                
                $cap_isole = explode(", ", $_POST['woocommerce_Shipping_Island_isole']);
                
                $args = array(
                    'stato'         => $_POST['woocommerce_Shipping_Island_stato'],
                    'title'         => $_POST['woocommerce_Shipping_Island_title'],
                    'costo'         => $_POST['woocommerce_Shipping_Island_costo'],
                    'cap'           => $cap_isole
                );
                
                update_option('wc_shipping_island', $args );
                
            }
            
        }
    }
}
add_action( 'woocommerce_shipping_init', 'your_shipping_method_init' );

function string_starts_with($string, $search) { 
   return (strncmp($string, $search, strlen($search)) == 0);  
}

function my_cart_message() {
    
    $wc_shipping_island = get_option('wc_shipping_island');
    // print_r ($wc_shipping_island);
    if ( isset($wc_shipping_island) && ( $wc_shipping_island["stato"] == 'on' ) ){
        $str .= '<small>';
        $str .= '<b>Nota:</b> Se la destinazione è un isola sarà applicato un costo di gestione di euro ' . $wc_shipping_island["costo"];
        $str .= '</small><div class="clearfix"></div><br/>';
        print $str;
    }
}
add_action( 'woocommerce_proceed_to_checkout', 'my_cart_message');



function add_your_shipping_method( $methods ) {
    $methods[] = 'WC_Your_Shipping_Method'; 
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_your_shipping_method' );


function woocommerce_custom_surcharge() {
   global $woocommerce;
   $wc_shipping_island = get_option('wc_shipping_island');
   if ( isset($wc_shipping_island) && ( $wc_shipping_island["stato"] == 'on' ) ){
       if ( is_admin() && ! defined( 'DOING_AJAX' ) )
          return;
       
       $cap = $woocommerce->customer->get_shipping_postcode();
       $surcharge     = $wc_shipping_island["costo"];
    
       $cap_isole = $wc_shipping_island["cap"];
       //print_r( $cap_isole ) ; die();
    
       foreach ( $cap_isole as $cap_isola ) {
          if ( string_starts_with( $cap, $cap_isola ) ){
             $woocommerce->cart->add_fee( $wc_shipping_island["title"], $surcharge, true, '' );
          }
       }
    }
}
add_action( 'woocommerce_cart_calculate_fees','woocommerce_custom_surcharge' );
