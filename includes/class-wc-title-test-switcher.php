<?php

if (!defined('WPINC')) {
	die;
}

/**
 *
 * This class is responsible for choosing random test and replacing
 * origin product data with test one.
 *
 *
 * @author        Justinas Staskus (justinas.staskus@gmail.com)
 * @package        WC_Title_Test
 * @version        1.0.0
 */
class WC_Title_Test_Switcher
{

	/**
	 * Key to use for session.
	 *
	 * @since    1.0.0
	 */
	const SESSION_KEY = "woocommerce_title_test_map";

	/**
	 *
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 *
	 * To save  test data into array
	 * so they not going to be loaded again on one request.
	 *
	 * @var array
	 */
	private $test_map = array();

	/**
	 * Setting up wordpress hooks.
	 *
	 * @since    1.0.0
	 * @return    void
	 */
	private function __construct()
	{

		if (!isset($_SESSION[self::SESSION_KEY])) {
			$_SESSION[self::SESSION_KEY] = array();
		}

		//Admin hooks
		add_action('init', array($this, 'initialize'));

		add_filter('woocommerce_product_title', array($this, 'filter_woocommerce_product_title'), 10, 2);

	}

	/**
	 *
	 * initializing plugin
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function initialize()
	{
		if (!is_admin()) {

			add_filter('the_title', array($this, 'filter_the_title'));

		}

	}


	/**
	 *
	 * Get random test for a product.
	 *
	 * @since    1.0.0
	 * @param $post_id
	 * @return null|WP_Post
	 */
	private function get_product_random_tests($post_id)
	{

		if (wcst_is_bot()) {
			return get_post($post_id);
		}

		if (!isset($_SESSION[self::SESSION_KEY][$post_id])) {

			$args = array(
				'post_parent' => $post_id,
				'post_type' => 'wc_title_test',
				'post_status' => 'publish',
			);

			$posts = get_posts($args);

			$posts[] = get_post($post_id);

			$rand_id = $posts[array_rand($posts)]->ID;

			$_SESSION[self::SESSION_KEY][$post_id] = $rand_id;

			$this->increase_display_count($rand_id);

		}

		$test = get_post($_SESSION[self::SESSION_KEY][$post_id]);

		if (!isset($_GET['wc_title_test']) || !$_GET['wc_title_test']) {

			if ($test->post_status != "publish") {
				unset($_SESSION[self::SESSION_KEY][$post_id]);
				return $this->get_product_random_tests($post_id);
			}

		}

		return $test;

	}

	/**
	 *
	 * To get test for a product either save in session
	 * or chosen randomly using method above.
	 *
	 * @since    1.0.0
	 * @param $post_id
	 * @return mixed
	 */
	private function get_test($post_id)
	{

		if (!isset($this->test_map[$post_id])) {

			if (isset($_GET["wc_title_test"]) && $_GET["wc_title_test"]) {

				if ("original" == $_GET["wc_title_test"]) {

					if (!isset($_SESSION[self::SESSION_KEY][$post_id]) || $_SESSION[self::SESSION_KEY][$post_id] != $post_id) {
						$_SESSION[self::SESSION_KEY][$post_id] = $post_id;
						$this->increase_display_count($post_id);
					}

				} elseif (intval($_GET['wc_title_test']) > 0) {

					$test_id = intval($_GET['wc_title_test']);

					if (wp_get_post_parent_id($test_id) == $post_id) {
						if (!isset($_SESSION[self::SESSION_KEY][$post_id]) || $_SESSION[self::SESSION_KEY][$post_id] != $test_id) {
							$_SESSION[self::SESSION_KEY][$post_id] = $test_id;
							$this->increase_display_count($test_id);
						}
					}

				}

			}

			$this->test_map[$post_id] = $this->get_product_random_tests($post_id);

		}

		return $this->test_map[$post_id];

	}

	/**
	 *
	 * Increases display count of title test.
	 *
	 * @param $id
	 * @return int
	 */
	private function increase_display_count($id)
	{

		$current_count = intval(get_post_meta($id, "display_count", true));

		$current_count++;

		update_post_meta($id, "display_count", $current_count);

		return $current_count;

	}

	/**
	 *
	 * To change product title
	 *
	 * @since    1.0.0
	 * @param $title
	 * @param $product
	 * @return mixed
	 */
	public function filter_woocommerce_product_title($title, $product)
	{

		$post = $product->post;

		if ($post && "product" == $post->post_type) {

			$test = $this->get_test($post->ID);

			if ($test->post_type != "wc_title_test") {
				return $title;
			}

			return $test->post_title;

		}

		return $title;

	}

	/**
	 *
	 * To change product title
	 *
	 * @since    1.0.0
	 * @param $title
	 * @return mixed
	 */
	public function filter_the_title($title)
	{

		global $post;

		if ($post && "product" == $post->post_type && in_the_loop()) {

			$test = $this->get_test($post->ID);

			if ($test->post_type != "wc_title_test") {
				return $title;
			}

			return $test->post_title;
		}

		return $title;
	}


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance()
	{

		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}
