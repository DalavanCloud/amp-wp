<?php
/**
 * Class AMP_Form_Sanitizer.
 *
 * @package AMP
 * @since 0.7
 */

/**
 * Class AMP_Form_Sanitizer
 *
 * Strips and corrects attributes in forms.
 *
 * @since 0.7
 */
class AMP_Form_Sanitizer extends AMP_Base_Sanitizer {

	/**
	 * Tag.
	 *
	 * @var string HTML <form> tag to identify and process.
	 *
	 * @since 0.7
	 */
	public static $tag = 'form';

	/**
	 * Sanitize the <form> elements from the HTML contained in this instance's DOMDocument.
	 *
	 * @link https://www.ampproject.org/docs/reference/components/amp-form
	 * @since 0.7
	 */
	public function sanitize() {

		/**
		 * Node list.
		 *
		 * @var DOMNodeList $node
		 */
		$nodes     = $this->dom->getElementsByTagName( self::$tag );
		$num_nodes = $nodes->length;

		if ( 0 === $num_nodes ) {
			return;
		}

		for ( $i = $num_nodes - 1; $i >= 0; $i-- ) {
			$node = $nodes->item( $i );
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			// In HTML, the default method is 'get'.
			$method = 'get';
			if ( $node->getAttribute( 'method' ) ) {
				$method = strtolower( $node->getAttribute( 'method' ) );
			} else {
				$node->setAttribute( 'method', $method );
			}

			/*
			 * In HTML, the default action is just the current URL that the page is served from.
			 * The action "specifies a server endpoint to handle the form input. The value must be an
			 * https URL and must not be a link to a CDN".
			 */
			if ( ! $node->getAttribute( 'action' ) ) {
				$action_url = esc_url_raw( '//' . $_SERVER['HTTP_HOST'] . wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: ignore. input var okay, sanitization ok.
			} else {
				$action_url = $node->getAttribute( 'action' );
			}
			$xhr_action = $node->getAttribute( 'action-xhr' );

			// Make HTTP URLs protocol-less, since HTTPS is required for forms.
			if ( 'http://' === strtolower( substr( $action_url, 0, 7 ) ) ) {
				$action_url = substr( $action_url, 5 );
			}

			/*
			 * "For GET submissions, provide at least one of action or action-xhr".
			 * "This attribute is required for method=GET. For method=POST, the
			 * action attribute is invalid, use action-xhr instead".
			 */
			if ( 'get' === $method ) {
				if ( $action_url !== $node->getAttribute( 'action' ) ) {
					$node->setAttribute( 'action', $action_url );
				}
			} elseif ( 'post' === $method ) {
				$this->remove_attribute( $node, 'action' );
				if ( ! $xhr_action ) {
					$node->setAttribute( 'action-xhr', $action_url );
				} elseif ( 'http://' === substr( $xhr_action, 0, 7 ) ) {
					$node->setAttribute( 'action-xhr', substr( $xhr_action, 5 ) );
				}
			}

			/*
			 * The target "indicates where to display the form response after submitting the form.
			 * The value must be _blank or _top". The _self and _parent values are treated
			 * as synonymous with _top, and anything else is treated like _blank.
			 */
			$target = $node->getAttribute( 'target' );
			if ( '_top' !== $target ) {
				if ( ! $target || in_array( $target, array( '_self', '_parent' ), true ) ) {
					$node->setAttribute( 'target', '_top' );
				} elseif ( '_blank' !== $target ) {
					$node->setAttribute( 'target', '_blank' );
				}
			}
		}
	}
}
