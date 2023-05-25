<?php
/**
 * Newspack Hub Node representation
 *
 * @package Newspack
 */

namespace Newspack_Network\Hub;

use Newspack_Network\Crypto;
use WP_Post;

/**
 * Class to represent one Node of the netowrk
 */
class Node {

	/**
	 * The WP_Post object for this Node
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Constructs a new Node
	 *
	 * @param WP_Post|int $node A WP_Post object or a post ID.
	 */
	public function __construct( $node ) {
		if ( is_numeric( $node ) ) {
			$node = get_post( $node );
		}

		if ( ! $node instanceof WP_Post || Nodes::POST_TYPE_SLUG !== $node->post_type ) {
			return false;
		}

		$this->post = $node;
	}

	/**
	 * Returns the Node's ID
	 *
	 * @return ?int
	 */
	public function get_id() {
		if ( $this->post instanceof WP_Post && ! empty( $this->post->ID ) ) {
			return $this->post->ID;
		}
	}
	
	/**
	 * Returns the Node's URL
	 *
	 * @return ?string
	 */
	public function get_url() {
		return get_post_meta( $this->get_id(), 'node-url', true );
	}

	/**
	 * Returns the Node's Private key
	 *
	 * @return ?string
	 */
	public function get_private_key() {
		return get_post_meta( $this->get_id(), 'private-key', true );
	}

	/**
	 * Returns the Node's Public Key
	 *
	 * @return ?string
	 */
	public function get_public_key() {
		return get_post_meta( $this->get_id(), 'public-key', true );
	}

	/**
	 * Returns the Node's Authorization Header to be used in REST request to it
	 *
	 * @return ?string
	 */
	public function get_authorization_header() {
		return 'Basic ' . base64_encode( $this->get_app_user() . ':' . $this->get_app_pass() );
	}

	/**
	 * Returns the Node's App User
	 *
	 * @return ?string
	 */
	public function get_app_user() {
		return get_post_meta( $this->get_id(), 'app-user', true );
	}

	/**
	 * Returns the Node's App Pass
	 *
	 * @return ?string
	 */
	public function get_app_pass() {
		return get_post_meta( $this->get_id(), 'app-pass', true );
	}

	/**
	 * Verifies that a signed message was signed with this Node's private key
	 *
	 * @param string $message The message to be verified.
	 * @return string|false The verified message or false if the message could not be verified.
	 */
	public function verify_signed_message( $message ) {
		return Crypto::verify_signed_message( $message, $this->get_public_key() );
	}
}