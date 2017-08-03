<?php
/*
Plugin Name: Gravity Form Sellsy Addon
Plugin URI: http://www.thivinfo.com
Description: Le plugin Gravity Form Sellsy Addon vous permet de connecter un formulaire de contact Gravity Form avec votre compte Sellsy.
Version: 1.1.2
Author: Sébastien SERRE
Author URI: http://www.sebastien-serre.fr
License: GPL2
Text Domain: gravity-forms-sellsy-addon
Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

define( 'WPI_VERSION', '1.1.2' );
define( 'WPI_PATH', dirname( __FILE__ ) );
define( 'WPI_PATH_INC', dirname( __FILE__ ) . '/inc' );
define( 'WPI_PATH_LANG', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
define( 'WPI_FOLDER', basename( WPI_PATH ) );
define( 'WPI_URL', plugins_url() . '/' . WPI_FOLDER );
define( 'WPI_URL_INCLUDES', WPI_URL . '/inc' );
define( 'WPI_API_URL', 'https://apifeed.sellsy.com/0/' );
define( 'WPI_SOURCE_URL', 'https://www.sellsy.com/?_f=prospection_prefs&action=sources' );
define( 'WPI_WEB_URL', 'https://www.sellsy.com/' );
define( 'WPI_WEBAPI_URL', 'https://www.sellsy.com/?_f=prefsApi' );


if ( ! class_exists( 'wp_sellsyClass' ) ) {

	class wp_sellsyClass {
		var $gform_hook;

		function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'thfo_add_adminCSS' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'thfo_add_adminJS' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'thfo_pointers_styles' ) );
			add_action( 'admin_menu', array( $this, 'thfo_adm_pages_callback' ) );
			register_deactivation_hook( __FILE__, 'thfo_on_deactivate_callback' );
			add_action( 'admin_init', array( $this, 'thfo_restrict_admin' ), 1 );
			add_action( 'admin_init', array( $this, 'thfo_check_cURL' ), 2 );
			add_action( 'admin_init', array( $this, 'thfo_register_settings' ), 5 );
			add_action( 'init', array( $this, 'thfo_loadLang' ) );
			add_action( 'gform_after_submission', array( $this, "thfo_dynamic_filtering" ), 10, 1 );
		}

		function thfo_dynamic_filtering() {
			$formid = $this->thfo_sellsy_options( 'thfo_form' );
			add_action( 'gform_after_submission_' . $formid, array( $this, 'thfo_post_to_sellsy' ), 10, 1 );
		}

		function thfo_post_to_sellsy( $entry ) {
			require_once WPI_PATH_INC . '/fonctions.php';
			require_once WPI_PATH_INC . '/sellsyconnect_curl.php';
			require_once WPI_PATH_INC . '/sellsytools.php';

			$request_prosp = array(
				'method' => 'Prospects.create'
			);
			if ( isset( $entry['1.6'] ) && $entry['1.6'] != '' ) {
				$name = sanitize_text_field( stripslashes( $entry['1.6'] ) );
			}

			if ( isset( $entry['1.3'] ) && $entry['1.3'] != '' ) {
				$surname = sanitize_text_field( stripslashes( $entry['1.3'] ) );
			}

			if ( ! empty( $name ) || ! empty( $surname ) ) {

				$request_prosp['params']['third']['name'] = $name . ' ' . $surname;
			}

			if ( isset( $entry['2'] ) && $entry['2'] != '' ) {
				$mail                                      = sanitize_text_field( stripslashes( $entry['2'] ) );
				$request_prosp['params']['third']['email'] = $mail;
			}
			if ( isset( $entry['3'] ) && $entry['3'] != '' ) {
				$phone                                   = sanitize_text_field( stripslashes( $entry['3'] ) );
				$request_prosp['params']['third']['tel'] = $phone;
			}
			if ( isset( $entry['4'] ) && $entry['4'] != '' ) {
				$company                                    = sanitize_text_field( stripslashes( $entry['4'] ) );
				$request_prosp['params']['contact']['name'] = $company;
			}
			if ( isset( $entry['5'] ) && $entry['5'] != '' ) {
				$message                                        = sanitize_text_field( stripslashes( $entry['5'] ) );
				$request_prosp['params']['third']['stickyNote'] = $message;
			}

			if ( count( $request_prosp ) > 1 ) {
				//Add prospect
				sellsyConnect_curl::load()->checkApi();
				$createProsp = sellsyConnect_curl::load()->requestApi( $request_prosp, false );
				$idProspect  = $createProsp->response;
			}
		}

		public function getSources( $WPInom_opp_source ) {
			$request = array(
				'method' => 'Opportunities.getSources',
				'params' => array()
			);
			$sources = sellsyConnect_curl::load()->requestApi( $request );
			foreach ( $sources->response AS $key => $source ) {
				if ( is_object( $source ) AND $source->label == $WPInom_opp_source ) {
					$sourceid = $source->id;
				}
			}

			return $sourceid;
		}

		public function getFunnels() {
			$request = array(
				'method' => 'Opportunities.getFunnels',
				'params' => array()
			);
			$funnels = sellsyConnect_curl::load()->requestApi( $request );
			foreach ( $funnels->response AS $key => $funnel ) {
				if ( is_object( $funnel ) AND $funnel->name == 'défaut' ) {
					$pipelineId = $funnel->id;
				} else {
					$pipelineId = $funnel;
				}
			}

			return $pipelineId;
		}

		public function getStepId( $funnelId ) {
			$request = array(
				'method' => 'Opportunities.getStepsForFunnel',
				'params' => array(
					'funnelid' => $funnelId
				)
			);
			$steps   = sellsyConnect_curl::load()->requestApi( $request );
			$i       = 0;
			foreach ( $steps->response AS $key => $step ) {
				if ( $i != 1 ) {
					$stepId = $step->id;
					$i ++;
				}
			}

			return $stepId;
		}

		public function getCurrentIdent() {
			$request = array(
				'method' => 'Opportunities.getCurrentIdent',
				'params' => array()
			);
			$ident   = sellsyConnect_curl::load()->requestApi( $request );

			return $ident->response;
		}

		function thfo_add_adminJS() {

			wp_enqueue_script( 'wpsellsyjscsource', plugins_url( '/js/wp_sellsy.js', __FILE__ ), array( 'jquery' ), '1.0', 1 );
			wp_localize_script( 'wpsellsyjscsource', 'ajax_var', array(
					'url'   => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wpi_ajax_nonce' )
				)
			);
		}

		function thfo_add_adminCSS( $hook ) {

			if ( is_admin() ) {
				if ( 'toplevel_page_wpi-admPage' != $hook ) {
					return;
				}
				wp_register_style( 'wpsellsystylesadmin', plugins_url( '/css/wp_sellsy_admin.css', __FILE__ ), array(), '1.0', 'screen' );
				wp_enqueue_style( 'wpsellsystylesadmin' );
			}
		}

		function thfo_adm_pages_callback() {

			add_menu_page( 'GF Sellsy Addons', 'GF Sellsy Addons', 'manage_options', 'wpi-admPage', array(
				$this,
				'wpi_admPage'
			), plugins_url( '/img/icon.png', __FILE__ ) );
		}

		function wpi_admPage() {

			if ( is_admin() AND current_user_can( 'manage_options' ) ) {
				include_once WPI_PATH_INC . '/wp_sellsy-adm-page.php';
			}
		}

		function thfo_loadLang() {

			load_plugin_textdomain( 'gravity-forms-sellsy-addon', true, WPI_PATH_LANG );
		}

		function thfo_on_deactivate_callback() {

			delete_option( 'wpsellsy_options' );
		}

		function thfo_register_settings() {

			require_once WPI_PATH . '/wp_sellsy-settings.class.php';

			new wp_sellsySettings();
		}

		function thfo_check_cURL() {

			if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
				echo '<div class="error"><p>';
				__( 'PHP cURL seems to be unavailable in your server. You can\'t use this plugin, please contact your administrator. .', 'gravity-forms-sellsy-addon' );
				echo '</p></div>';
			}
		}

		function thfo_restrict_admin() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have correct permissions.', 'gravity-forms-sellsy-addon' ) );
			}
		}

		function thfo_pointers_styles( $hook_suffix ) {

			$wp_sellsyScriptStyles = false;
			$dismissed_pointers    = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

			if ( ! in_array( 'wpi_pointer', $dismissed_pointers ) ) {
				$wp_sellsyScriptStyles = true;
				add_action( 'admin_print_footer_scripts', array( $this, 'thfo_pointers_scripts' ) );
			}

			if ( $wp_sellsyScriptStyles ) {
				wp_enqueue_style( 'wp-pointer' );
				wp_enqueue_script( 'wp-pointer' );
			}
		}

		function thfo_pointers_scripts() {

			$pointer_content = '<h3>Gravity Forms SellSy Addons</h3>';
			$pointer_content .= '<p>' . __( 'You just install GF Sellsy Addons. Click here to set up', 'gravity-forms-sellsy-addon' ) . '</p>';
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function ($) {
					$('#toplevel_page_wpi-admPage').pointer({
						content: '<?php echo $pointer_content; ?>',
						position: {
							edge: 'left',
							align: 'right'
						},
						pointerWidth: 350,
						close: function () {
							$.post(ajaxurl, {
								pointer: 'wpi_pointer',
								action: 'dismiss-wp-pointer'
							});
						}
					}).pointer('open');
				});
				//]]>
			</script>

			<?php
		}

		public function thfo_sellsy_options( $option ) {
			$options = get_option( 'wpsellsy_options' );
			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			} else {
				return false;
			}
		}

		public function thfo_checkSellsy_connect() {

			if ( is_admin() AND current_user_can( 'manage_options' ) ) {
				sellsyTools::storageSet( 'infos', sellsyConnect_curl::load()->getInfos() );
				if ( isset( $_SESSION['oauth_error'] ) AND $_SESSION['oauth_error'] != '' ) {
					return false;
				} else {
					return true;
				}
			} else {
				wp_die( __( 'You do not have correct permissions.', 'gravity-forms-sellsy-addon' ) );
			}
		}

		public function thfo_checkOppSource( $sourceParam ) {

			if ( is_admin() AND current_user_can( 'manage_options' ) ) {
				$request = array(
					'method' => 'Opportunities.getSources',
					'params' => array()
				);
				$sources = sellsyConnect_curl::load()->requestApi( $request );
				$sourceX = null;
				foreach ( $sources->response AS $source ) {
					if ( is_object( $source ) AND strcasecmp( $source->label, $sourceParam ) == 0 ) {
						$sourceX = $source->id;
						break;
					}
				}
				if ( $sourceX == null ) {
					return false;
				} else {
					return true;
				}
			} else {
				wp_die( __( 'You do not have correct permissions.', 'gravity-forms-sellsy-addon' ) );
			}
		}
	}

}
if ( class_exists( 'wp_sellsyClass' ) ) {
	$wp_sellsy = new wp_sellsyClass();
}