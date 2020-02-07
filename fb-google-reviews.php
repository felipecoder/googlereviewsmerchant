<?php
/*
 * Plugin Name: FB Google Reviews (WooCommerce)
 * Plugin URI: http://felipebruno.com.br/
 * Description: Integração WooCommerce com Avaliações do Google
 * Author: Felipe Bruno
 * Author URI: http://felipebruno.com.br/
 * Version: 1.0.1
 *
*/

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_integrations', 'fb_cielo_add_class' );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links' );

function plugin_action_links( $links ) {
	$plugin_links   = array();
	$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration&section=fb_google_reviews' ) ) . '">' . __( 'Configurações', 'fb_cielo' ) . '</a>';

	return array_merge( $plugin_links, $links );
}

function fb_cielo_add_class( $gateways ) {
	$integrations[] = 'FB_Google_Reviews'; // your class name is here
	return $integrations;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'fb_google_reviews_init_class' );
function fb_google_reviews_init_class() {
 
	class FB_Google_Reviews extends WC_Integration{
 
 		/**
 		* Class constructor, more about it in Step 3
 		*/
 		public function __construct() {
 
			$this->id = 'fb_google_reviews'; // payment gateway plugin ID
			$this->icon = plugin_dir_url( __FILE__ ).'img/icon.jpg'; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; // in case you need a custom credit card form
			$this->method_title = 'FB Google Reviews';
			$this->method_description = 'Integração WooCommerce com Avaliações do Google'; // will be displayed on the options page
		 
			// Method with all the options fields
			$this->init_form_fields();
		 
			// Load the settings.
			$this->init_settings();
			$this->enabled = $this->get_option( 'enabled' );
			$this->merchant_id = $this->get_option( 'merchant_id' );
			$this->enabled_symbol = $this->get_option( 'enabled_symbol' );
			$this->position = $this->get_option( 'position' );
			$this->days = $this->get_option( 'days' );

			add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

			add_action( 'woocommerce_thankyou', array( $this, 'fb_google_reviews_script_optin' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'fb_google_reviews_script_badge' ) );

			add_shortcode('fb_google_reviews_badge',  array( $this, 'fb_google_reviews_shortcode_badge' ));
 
 		}
 
		/**
 		* Plugin options, we deal with it in Step 3 too
 		*/
		public function init_form_fields()
		{
 
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Ativar/Desativar',
					'label'       => 'Ativar o plugin',
					'type'        => 'checkbox',
					'description' => 'Ativa ou desativa o plugin',
					'default'     => 'yes'
				),
				'merchant_id' => array(
					'title'       => 'Merchant ID',
					'type'        => 'text',
					'description' => 'Digite o código do seu Google Merchant',
					'default'     => '',
					'desc_tip'    => true,
				),
				'enabled_symbol' => array(
					'title'       => 'Simbolo do Google',
					'label'       => 'Mostrar o Simbolo',
					'type'        => 'checkbox',
					'description' => 'Marque essa opção se você deseja mostrar o simbolo do google reviews, ATENÇÃO! DEVE SER CONFIGURADO OS VALORES ABAIXO.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'position' => array(
					'title'       => 'Posição do Simbolo',
					'type'        => 'select',
					'description' => 'Defina a posição do simbolo, caso use o indefinido o shortcode é <b>[fb_google_reviews_badge]</b>',
					'options'	  => array(
						'BottomLeft' => 'Inferior Esquerdo',
						'BottomRight' => 'Inferior Direito',
						'undefined' => 'Irei decidir',
					)
				),
				'days' => array(
					'title'       => 'Dias Adicionais de Entrega',
					'type'        => 'number',
					'description' => 'Adicione os dias adicionais de entrega',
					'default'     => '1',
					'desc_tip'    => true,
				)
			);
 
		}
		 
		public function fb_google_reviews_script_badge()
		{
			if ( 'no' === $this->enabled ) {
				return;
			}

			if ( 'no' === $this->enabled_symbol ) {
				return;
			}

			if ( empty($this->merchant_id) ) {
				return;
			}

			if ($this->position == 'undefined') {
				return;
			}

			switch ($this->position) {
				case 'BottomLeft':
					$position = '"position": "BOTTOM_LEFT"';
					break;
				case 'BottomRight':
					$position = '"position": "BOTTOM_RIGHT"';
					break;
				
				default:
					$position = '"position": "INLINE"';
					break;
			}
			
			$fb_google_reviews_script = '
			<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
			
			<script>
			window.renderBadge = function() {
				var ratingBadgeContainer = document.createElement("div");
				document.body.appendChild(ratingBadgeContainer);
				window.gapi.load(\'ratingbadge\', function() {
				window.gapi.ratingbadge.render(ratingBadgeContainer, {
					// REQUIRED
					"merchant_id": '.$this->merchant_id.', // place your merchant ID here, get it from your Merchant Center at https://merchants.google.com/mc/merchantdashboard
					// OPTIONAL
					'.$position.'
					});
				});
			}
			</script>';
			echo $fb_google_reviews_script;
		}
		 
		public function fb_google_reviews_shortcode_badge()
		{
			if ( 'no' === $this->enabled ) {
				return;
			}

			if ( 'no' === $this->enabled_symbol ) {
				return;
			}

			if ( empty($this->merchant_id) ) {
				return;
			}

			$shortcode = '
			<script src="https://apis.google.com/js/platform.js" async defer></script>
			<g:ratingbadge merchant_id='.$this->merchant_id.'></g:ratingbadge>
			';
			echo $shortcode;
			
			$fb_google_reviews_script = '
			<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
			
			<script>
			window.renderBadge = function() {
				var ratingBadgeContainer = document.createElement("div");
				document.body.appendChild(ratingBadgeContainer);
				window.gapi.load(\'ratingbadge\', function() {
				window.gapi.ratingbadge.render(ratingBadgeContainer, {
					// REQUIRED
					"merchant_id": '.$this->merchant_id.', // place your merchant ID here, get it from your Merchant Center at https://merchants.google.com/mc/merchantdashboard
					
					// OPTIONAL
					"position": "INLINE"
					});
				});
			}
			</script>';
		}
		 
		public function fb_google_reviews_script_optin( $order_id )
		{
			if ( 'no' === $this->enabled ) {
				return;
			}

			if ( 'no' === $this->enabled_symbol ) {
				return;
			}

			if ( empty($this->merchant_id) ) {
				return;
			}

			if ( $this->days <= 0 ) {
				$this->days = 1;
			}

			$order = new WC_Order( $order_id );

			?>
			<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

			<script>			
					window.renderOptIn = function() {
						window.gapi.load('surveyoptin', function() {
						window.gapi.surveyoptin.render(
							{
							// REQUIRED FIELDS
							"merchant_id": "<?php echo esc_attr( $this->merchant_id ); ?>"",
							"order_id": "<?php echo esc_attr( $order->get_order_number() ); ?>",
							"email": "<?php echo esc_attr( $order->get_billing_email() ); ?>",
							"delivery_country": "<?php echo esc_attr( $order->get_billing_country() ); ?>",
							"estimated_delivery_date": "<?php echo esc_attr( date( 'Y-m-d', strtotime( '+'.$this->days.' day', strtotime( $order->get_date_created() ) ) ) ); ?>",
							"opt_in_style": "CENTER_DIALOG"
							});
						});
					}</script>

			<?php
		}
 	}
}
?>