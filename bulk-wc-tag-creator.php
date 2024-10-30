<?php
/**
 * Plugin Name: Bulk WooCommerce Tag Creator
 * Plugin URI: http://korexindo.com/
 * Description: A plugin to create multiple WooCommerce Tags - inspired by Bulk WooCommerce Category Creator
 * Version: 1.0
 * Author: Panjianom Adipratomo
 * Author URI: https://profiles.wordpress.org/pije76/
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.4
 * License: GPL2
 *
 * @package  BWTC
 */


if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * BWTC_Bulk_WooCommerce_Tag_Creator class
 */

if ( !class_exists( 'BWTC_Bulk_WooCommerce_Tag_Creator' ) ) {

class BWTC_Bulk_WooCommerce_Tag_Creator {
	
	public function __construct() {

		add_action( 'admin_init', 	array( &$this, 'bwtc_check_compatibility' ) );
		
		add_action(	'admin_menu', 	array( $this, 'bwtc_TagCreatorMenu' ) );

		// Language Translation
		add_action( 'init',			array( &$this, 'bwtc_update_po_file' ) );
	}

	/**
	 * Ensure that the plugin is deactivated when WooCommerce is deactivated.
	 *
	 * @since 1.0
	 */
	public static function bwtc_check_compatibility() {
	
		if ( ! self::bwtc_check_woo_installed() ) {
	
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
	
				add_action( 'admin_notices', array( 'BWTC_Bulk_WooCommerce_Tag_Creator', 'bwtc_disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
	
			}
	
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0
	 * @return boolean tru if WooCommerce is active else false
	 */

	public static function bwtc_check_woo_installed() {
			
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display a notice in the admin Plugins page if this plugin is activated while WooCommerce is deactivated.
	 * 
	 * @since 1.0
	 */

	public static function bwtc_disabled_notice() {
	
		$class = 'notice notice-error';
		$message = __( 'Bulk WooCommerce Tag Creator requires WooCommerce installed and activate.', 'bwtc-bulk-woocommerce-tagging-creator' );
	
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	
	}

	/**
	 * Adds Bulk WooCommerce Tag Creator menu under Product Menu
	 * 
	 * @since 1.0
	 */

	public static function bwtc_TagCreatorMenu() {

	    add_submenu_page(	 'edit.php?post_type=product',
	    				__( 'Bulk WooCommerce Tag Creator Page', 	'bwtc-bulk-woocommerce-tagging-creator' ),
	    				__( 'Bulk Tag Creator', 					'bwtc-bulk-woocommerce-tagging-creator' ),
	    				'manage_product_terms',
	    				'bulk_woocommerce_tag_creator',
	    				array( 	'BWTC_Bulk_WooCommerce_Tag_Creator',
	    						'bwtc_TagSettingsPage' 
	    					 )
	    			 );

	    add_action('admin_init', array( 'BWTC_Bulk_WooCommerce_Tag_Creator', 'bwtc_RegisterPluginSettings' ) );
	}

	/**
	 * Language Translation
	 * 
	 * @since 1.0
	 */

	public static function  bwtc_update_po_file() {
		
		$domain = 'bwtc-bulk-woocommerce-tagging-creator';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		
		if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
			return $loaded;
		} else {
			load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
		}
	}

	/**
	 * Registering the settings
	 * 
	 * @since 1.0
	 */
	
	public static function bwtc_RegisterPluginSettings() {
	    
	    //register our settings
		register_setting( 'bwtc-bulk-tag-creator-group', 'options_textarea' );

	    BWTC_Bulk_WooCommerce_Tag_Creator::bwtc_CreateTags();
	}

	/**
	 * Check for the added categoies and based on that create tags
	 * 
	 * @since 1.0
	 */

	public static function bwtc_CreateTags() {

	    $returnedStr = ( isset( $_POST['bwtc_options_textarea'] ) && $_POST['bwtc_options_textarea'] != "" ) ? esc_attr( $_POST['bwtc_options_textarea'] ) : "";
	    $parent_id = ( isset( $_POST['bwtc_parent'] ) && $_POST['bwtc_parent'] != "" ) ? esc_attr( $_POST['bwtc_parent'] ) : 0;

	    if( $returnedStr != "" ) {

		    $trimmed 			= trim( $returnedStr );
		    $tags_array 	= explode( ',', $trimmed );

		    foreach ( $tags_array as $key => $value) {

		        $term = term_exists( $value, 'tag' );

		        if ( $term == 0 || is_null( $term ) ) {	        	
		            BWTC_Bulk_WooCommerce_Tag_Creator::bwtc_create_tag( $value, $parent_id );
		        }
		    }
	    }
	}

	/**
	 * Create WooCommerce tag
	 * @param string $value string of the tags
	 * @param int $parent_id Parent ID of the Tag
	 *
	 * @since 1.0
	 */

	public static function bwtc_create_tag( $value, $parent_id ) {
	
	    $trimmedValue 		= trim( $value );
	    $hyphenatedValue 	= str_replace( " ", "-", $trimmedValue );
	    
	    wp_insert_term(	$trimmedValue,
	        			'product_tag',
	        			array(
/*	            			'description' 	=> $trimmedValue,*/
	            			'slug'			=> $hyphenatedValue,
	            			'parent' 		=> $parent_id
	            		)
	        		  );
	}

	/**
	 * Bulk Tag Creator Page
	 *
	 * @since 1.0
	 */

	public static function bwtc_TagSettingsPage() {

		$tags = get_terms( array( 'taxonomy' => 'product_tag','hide_empty' => false, 'parent' => 0 ) );

		?>
		<div class="wrap">
		
		<h1><?php echo __( 'Bulk Tag Creator', 'bwtc-bulk-woocommerce-tagging-creator' )?> </h1>

		<form method='post'><input type='hidden' name='form-name' value='form 1' />
		    
		    <?php settings_fields( 'bwtc-bulk-tag-creator-group' ); ?>
		    
		    <?php do_settings_sections( 'bwtc-bulk-tag-creator-group' ); ?>
		    
		    <table class="form-table">
		        <tr valign="top">
			        <th scope="row"><?php echo __( 'Enter tags separated by commas', 'bwtc-bulk-woocommerce-tagging-creator' ); ?>  </th>
			        <td>
			        	<textarea cols="50" rows="8" name="bwtc_options_textarea"></textarea>
			        </td>
		        </tr>

		        <tr>
		        	<th scope="row"><?php echo __( 'Parent Tag', 'bwtc-bulk-woocommerce-tagging-creator' ); ?>  </th>
		        	<td>
		        		<select name="bwtc_parent" id="parent" class="postform">
						<option value=""><?php echo __( 'None', 'bwtc-bulk-woocommerce-tagging-creator' ); ?></option>

						<?php
							foreach ( $tags as $key => $value ) {
								printf( "<option value='%s'>%s</option>\n",
                                    esc_attr( $value->term_id ),
                                    __( $value->name, 'bwtc-bulk-woocommerce-tagging-creator' )
                                );
							}
						?>
						</select>
					</td>
		        </tr>
		    
		    </table>

		    <?php submit_button( __( 'Bulk Create Tags', 'bwtc-bulk-woocommerce-tagging-creator' ) ); ?>

		</form>
	<?php
	}
}
$bwtc = new BWTC_Bulk_WooCommerce_Tag_Creator();
}
?>