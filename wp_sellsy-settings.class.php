<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

require_once WPI_PATH_INC . '/sellsyconnect_curl.php';
require_once WPI_PATH_INC . '/sellsytools.php';

class wp_sellsySettings {

	var $form;
	private $settings;
	private $sections;
	private $checkboxes;

	public function __construct() {

		$this->settings   = array();
		$this->checkboxes = array();
		$this->wpiGet_settings();

		$this->sections['sellsy_connexion'] = __( 'Connexion to Sellsy', 'gravity-forms-sellsy-addon' );
		$this->sections['sellsy_options']   = __( 'Plugin options', 'gravity-forms-sellsy-addon' );

		add_action( 'admin_init', array( &$this, 'wpiRegister_settings' ) );


		if ( ! get_option( 'wpsellsy_options' ) ) {
			$this->wpiInitialize_settings();
		}

	}

	public function wpiGet_settings() {

		/* Section Connexion Sellsy */

		$this->settings['WPIconsumer_token'] = array(
			'title'   => __( 'Consumer Token', 'gravity-forms-sellsy-addon' ),
			'desc'    => '',
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_connexion'
		);

		$this->settings['WPIconsumer_secret'] = array(
			'title'   => __( 'Consumer Secret', 'gravity-forms-sellsy-addon' ),
			'desc'    => '',
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_connexion'
		);

		$this->settings['WPIutilisateur_token'] = array(
			'title'   => __( 'User Token', 'gravity-forms-sellsy-addon' ),
			'desc'    => '',
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_connexion'
		);

		$this->settings['WPIutilisateur_secret'] = array(
			'title'   => __( 'User Secret', 'gravity-forms-sellsy-addon' ),
			'desc'    => '',
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_connexion'
		);

		/* Section Options du plugin */

		$this->settings['thfo_form'] = array(
			'title'   => __( 'Form ID (previously imported in Gravity form)', 'gravity-forms-sellsy-addon' ),
			'desc'    => '',
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_options'
		);


	}

	public function wpiInitialize_settings() {

		$default_settings = array();

		foreach ( $this->settings AS $id => $setting ) {
			if ( $setting['type'] != 'heading' ) {
				$default_settings[ $id ] = $setting['std'];
			}
		}

		update_option( 'wpsellsy_options', $default_settings );

	}

	function thfo_get_forms() {


		foreach ( $forms as $this->form ) {
			//var_dump($form);
			return $this->form['id'];

			return $this->form['title'];
		}

	}

	public function wpiDisplay_section() {

	}

	public function wpiDisplay_setting( $args = array() ) {

		extract( $args );

		$options = get_option( 'wpsellsy_options' );

		if ( ! isset( $options[ $id ] ) AND $type != 'checkbox' ) {
			$options[ $id ] = $std;
		} elseif ( ! isset( $options[ $id ] ) ) {
			$options[ $id ] = 0;
		}

		$field_class = '';
		if ( $class != '' ) {
			$field_class = ' ' . $class;
		}

		switch ( $type ) {

			case 'select':
				echo '<select class="select' . $field_class . '" name="wpsellsy_options[' . $id . ']">';

				foreach ( $choices as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[ $id ], $value, false ) . '>' . $label . '</option>';
				}

				echo '</select>';

				if ( $desc != '' ) {
					echo '<br><span class="description">' . $desc . '</span>';
				}

				break;

			case 'radio':
				$i = 0;
				foreach ( $choices as $value => $label ) {
					echo '<input class="radio' . $field_class . '" type="radio" name="wpsellsy_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[ $id ], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					if ( $i < count( $options ) - 1 ) {
						echo '<br />';
					}
					$i ++;
				}

				if ( $desc != '' ) {
					echo '<span class="description">' . $desc . '</span>';
				}

				break;

			case 'text':
			default:
				echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="wpsellsy_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[ $id ] ) . '" />';

				if ( $desc != '' ) {
					echo '<br><span class="description">' . $desc . '</span>';
				}

				break;

		}

	}

	public function wpiRegister_settings() {

		register_setting( 'wpsellsy_options', 'wpsellsy_options', array( &$this, 'wpiSanitize_settings' ) );

		foreach ( $this->sections AS $slug => $title ) {
			add_settings_section( $slug, $title, array( &$this, 'wpiDisplay_section' ), 'wpi-admPage' );
		}

		$this->wpiGet_settings();

		foreach ( $this->settings AS $id => $setting ) {
			$setting['id'] = $id;
			$this->wpiCreate_setting( $setting );
		}

	}

	public function wpiCreate_setting( $args = array() ) {

		$defaults = array(
			'id'      => 'champ_defaut',
			'title'   => 'default_field',
			'desc'    => __( 'Default description', 'gravity-forms-sellsy-addon' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'sellsy_connexion',
			'choices' => array(),
			'class'   => ''
		);

		extract( wp_parse_args( $args, $defaults ) );

		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class
		);

		if ( $type == 'checkbox' ) {
			$this->checkboxes[] = $id;
		}

		add_settings_field( $id, $title, array( $this, 'wpiDisplay_setting' ), 'wpi-admPage', $section, $field_args );

	}

	public function wpiSanitize_settings( $input ) {

		if ( current_user_can( 'manage_options' ) AND check_admin_referer( 'wpi_nonce_field', 'wpi_nonce_verify_adm' ) ) {

			$output = array();

			foreach ( $input AS $key => $value ) {
				if ( isset( $input[ $key ] ) ) {
					$output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );
				}
			}

			function wpiValidate_settings( $output, $input ) {

				foreach ( $input AS $key => $val ) {

					switch ( $key ) {

						case 'WPIconsumer_token':
							if ( strlen( $val ) != 40 ) {
								add_settings_error( 'wpsellsy_options', 'WPIconsumer_token', __( 'Consumer Token is missing or wrong, please check.', 'gravity-forms-sellsy-addon' ), 'error' );
							} else {
								$output[ $key ] = sanitize_text_field( $input[ $key ] );
							}
							break;

						case 'WPIconsumer_secret':
							if ( strlen( $val ) != 40 ) {
								add_settings_error( 'wpsellsy_options', 'WPIconsumer_secret', __( 'Consumer Secret is missing or wrong, please check.', 'gravity-forms-sellsy-addon' ), 'error' );
							} else {
								$output[ $key ] = sanitize_text_field( $input[ $key ] );
							}
							break;

						case 'WPIutilisateur_token':
							if ( strlen( $val ) != 40 ) {
								add_settings_error( 'wpsellsy_options', 'WPIutilisateur_token', __( 'User token is missing or wrong, please check.', 'gravity-forms-sellsy-addon' ), 'error' );
							} else {
								$output[ $key ] = sanitize_text_field( $input[ $key ] );
							}
							break;

						case 'WPIutilisateur_secret':
							if ( strlen( $val ) != 40 ) {
								add_settings_error( 'wpsellsy_options', 'WPIutilisateur_secret', __( 'User secret is missing or wrong, please check.', 'gravity-forms-sellsy-addon' ), 'error' );
							} else {
								$output[ $key ] = sanitize_text_field( $input[ $key ] );
							}
							break;
					}
				}

				return $output;
			}

			add_filter( 'wpiSanitize_settings', 'wpiValidate_settings', 10, 2 );

			return apply_filters( 'wpiSanitize_settings', $output, $input );
		}
	}
}