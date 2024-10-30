<?php 
/**
 * Plugin Name: CF7 Signature
 * Plugin URI: https://ciphercoin.com/
 * Description: Signature input field for contact form 7
 * Author: wpdebuglog 
 * Text Domain: cf7-signature
 * Domain Path: /languages/
 * Version: 1.0.0
 */

require plugin_dir_path(__FILE__).'/actions.php';

add_action( 'init', 'cf7sg_sign_load_textdomain' );

function cf7sg_sign_load_textdomain() {
    load_plugin_textdomain( 'cf7-signature', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

add_action( 'wpcf7_init', 'cf7sg_add_signature_tag', 10, 0 );

function cf7sg_add_signature_tag() {

    CF7SG_SIGN_ACTIONS::get_instance();

    add_action('wpcf7_enqueue_scripts', 'cf7sg_load_scripts');

	wpcf7_add_form_tag( array( 'sign', 'sign*' ),
		'cf7sg_signature_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function cf7sg_load_scripts(){
    wp_enqueue_script('jQuery');
    wp_enqueue_script('cf7-signpad', plugin_dir_url(__FILE__).'/signature-pad.min.js');
    wp_enqueue_script('cf7-sign-js', plugin_dir_url(__FILE__).'/script.js', array('jquery'), time() );
}

function cf7sg_signature_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = wpcf7_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}


	$atts['name'] = $tag->name;
	$atts['type'] = 'hidden';

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap wpcf7-sign-wrap %1$s"><input %2$s />%3$s
            <canvas style="display:block" data-hidden="%4$s" class="signature-pad" width=400 height=200></canvas>
            <button class="btn btn-primary cf7sg-sign">%5$s</button>
        </span>',
		sanitize_html_class( $tag->name ), 
        $atts, 
        $validation_error, 
        $tag->name,
        __( "Clear", "cf7-signature" )
	);

	return $html;
}


/* Tag generator */

add_action( 'wpcf7_admin_init', 'cf7sg_sign_tag_generator', 19, 0 );

function cf7sg_sign_tag_generator() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'sign', __( 'sign', 'cf7-signature' ),
		'cf7sg_sign_tag_generator_box' );
}

function cf7sg_sign_tag_generator_box( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'sign';

	$description = __( "Generate a form-tag for a date input field. For more details, see %s.", 'cf7-signature' );

	$desc_link = wpcf7_link( __( 'https://contactform7.com/date-field/', 'cf7-signature' ), __( 'Date field', 'cf7-signature' ) );

?>
<div class="control-box">
<fieldset>
<!-- <legend><?php //echo sprintf( esc_html( $description ), $desc_link ); ?></legend> -->

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'cf7-signature' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'cf7-signature' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'cf7-signature' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'cf7-signature' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>


	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'cf7-signature' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'cf7-signature' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'cf7-signature' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'cf7-signature' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
