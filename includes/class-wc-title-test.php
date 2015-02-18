<?php

if (!defined('WPINC')) {
	die;
}

/**
 *
 *
 * Main plugin functionality
 *
 * @author        Justinas Staskus (justinas.staskus@gmail.com)
 * @package        WC_Title_Test
 * @version     1.0.0
 */
class WC_Title_Test
{

	/**
	 *
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin version
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Setting up wordpress hooks.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	private function __construct()
	{

		require_once 'functions.php';

		if (isset($_GET['filter_by_title_test']) && intval($_GET['filter_by_title_test']) > 0) {
			add_filter("woocommerce_register_post_type_shop_order", array($this, 'woocommerce_register_post_type_shop_order'));
		}

		//Admin hooks
		add_action('init', array($this, 'initialize'));

	}

	/**
	 *
	 * initializing plugin
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function initialize()
	{

		if (!session_id()) {
			session_start();
		}

		if (is_admin()) {

			if (isset($_GET['action']) && $_GET['action'] == "duplicate_test") {
				$this->duplicate_test();
			}


			//"After order being placed" hook
			add_action('woocommerce_checkout_order_processed', array($this, 'woocommerce_checkout_order_processed_action'));

			add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
			add_action('save_post_wc_title_test', array($this, 'action_save_post_wc_title_test'));

			add_action('admin_enqueue_scripts', array($this, 'scripts'));

			add_action('admin_notices', array($this, 'admin_notices'));

			add_action('woocommerce_admin_order_item_headers', array($this, 'action_woocommerce_admin_order_item_headers'), 10, 3);
			add_action('woocommerce_admin_order_item_values', array($this, 'action_woocommerce_admin_order_item_values'), 10, 3);

			add_filter('gettext', array($this, 'featured_image_gettext'));
			add_filter('woocommerce_hidden_order_itemmeta', array($this, 'filter_woocommerce_hidden_order_itemmeta'));


			add_filter('parse_query', array($this, 'filter_by_title_test'));

			add_action('admin_head', array($this, 'action_admin_head'));

			add_action('wp_ajax_wc_title_test_delete', array($this, 'wc_title_test_delete'));

			add_filter('post_type_link', array($this, 'append_query_string'), 10, 2);


		}

		add_filter('woocommerce_order_item_name', array($this, 'filter_woocommerce_order_item_name'), 20, 2);

		$this->register_post_type();
	}

	/**
	 *
	 * To change our custom post type preview link.
	 *
	 * @param $url
	 * @param $post
	 * @return string
	 */
	public function append_query_string($url, $post)
	{

		if ('wc_title_test' == get_post_type($post) && $post->post_parent) {
			return get_post_permalink($post->post_parent) . '?wc_title_test=' . $post->ID;
		}

		return $url;
	}

	/**
	 *
	 * To delete title test via ajax
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function wc_title_test_delete()
	{

		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

		if ($id) {
			wp_delete_post($id);
		}

		wp_die();

	}


	/**
	 *
	 * Add meta necessary boxes on admin panel
	 *
	 * @since     1.0.0
	 * @return void
	 *
	 */
	public function add_meta_boxes()
	{

		// Title test
		global $action;

		if ($action == 'edit') {

			$pid = get_the_ID();

			$copy_link = "/wp-admin/post.php?post=" . $pid . "&action=duplicate_test";

			$title = sprintf(__('Title Test Versions. <span style="font-weight: normal; font-size: 12px;">Add New:</span> <a class="add-new" href="%s">Create Empty</a><span style="font-weight: normal; font-size: 12px;"> or </span><a class="add-new" href="%s">Duplicate original</a>', 'wc_title_test'), 'post-new.php?post_type=wc_title_test&pid=' . $pid, $copy_link);
			add_meta_box('wc-title-test-list', $title, array($this, 'render_tests_list_meta_box'), 'product', 'normal', 'high');

		}


		add_meta_box('wc-title-test-parent', __('Parent Product', 'wc_title_test'), array($this, 'render_parent_product_meta_box'), 'wc_title_test', 'normal');

	}


	/**
	 *
	 * Renders title tests list meta box on product edit page.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function render_tests_list_meta_box()
	{

		$parent_post = get_post();

		$parent_post->original = true;

		$tests[] = $parent_post;

		$args = array(
			'post_parent' => get_the_ID(),
			'post_type' => 'wc_title_test',
			'post_status' => 'any',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => -1
		);

		$tests = array_merge($tests, get_posts($args));

		$total_display_count = 0;
		$total_order_count = 0;

		foreach ($tests as $key => $value) {

			$tests[$key]->order_count = $this->get_test_order_count($value->ID);
			$tests[$key]->display_count = get_post_meta($value->ID, "display_count", true);

			$total_display_count = $total_display_count + $tests[$key]->display_count;
			$total_order_count = $total_order_count + $tests[$key]->order_count;

		}

		include 'templates/tests_list.php';

	}


	/**
	 *
	 * Gets total number of how many order test has made.
	 *
	 * @param $test_id
	 * @since     1.0.0
	 * @return int
	 */
	private function get_test_order_count($test_id)
	{
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT SUM(oim2.meta_value) as order_count FROM {$wpdb->posts} p
RIGHT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_type = 'line_item' AND oi.order_id = p.ID
RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_key = '_title_test' AND oim.order_item_id = oi.order_item_id
RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON oim2.meta_key = '_qty' AND oim2.order_item_id = oi.order_item_id
WHERE
p.post_type IN ('" . implode("','", wcst_get_order_types()) . "')
AND
oim.meta_value = %s
GROUP BY oim.meta_value
", $test_id);


		return intval($wpdb->get_var($sql));

	}


	/**
	 *
	 * Render parent product meta box on edit title test page.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function render_parent_product_meta_box()
	{

		$postID = isset($_GET['post']) ? intval($_GET['post']) : 0;
		$productID = isset($_GET['pid']) ? intval($_GET['pid']) : 0;


		if ($postID) {
			$productID = wp_get_post_parent_id($postID);
		}

		$parent = get_post($productID);

		if ($parent && $parent->post_type == 'product') {

			$title = sanitize_title($parent->post_title);

			echo sprintf('<a href="%s">%s</a>', get_edit_post_link($parent->ID), $title);
			echo '<input type="hidden" name="wc_title_test_parent_product" value="' . $parent->ID . '" >';

		} else {

			echo __('Parent Product Not Found', 'wc_title_test');

		}

	}


	/**
	 *
	 * Hook which happens on custom post type gets saved.
	 *
	 * @param $post_id
	 * @since     1.0.0
	 * @return void
	 */
	public function action_save_post_wc_title_test($post_id)
	{

		global $wpdb;

		if ("wc_title_test" != get_post_type($post_id)) {
			return;
		}

		$parent = isset($_POST['wc_title_test_parent_product']) ? intval($_POST['wc_title_test_parent_product']) : 0;

		if (!$parent) {
			return;
		}

		$parent_post = get_post($parent);

		$sql = $wpdb->prepare("UPDATE $wpdb->posts p SET p.post_parent = %d WHERE p.ID = %d", array($parent_post->ID, $post_id));

		$wpdb->query($sql);

	}

	/**
	 *
	 * To show notice when title test id deleted.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function admin_notices()
	{
		if (!isset($_GET['test_deleted'])) {
			return;
		}
		?>
		<div class="updated">
			<p><?php esc_html_e('Title test has been deleted.', 'wc_title_test'); ?></p>
		</div>
	<?php
	}


	/**
	 *
	 * To hide add new title test button on edit page.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function action_admin_head()
	{

		global $pagenow, $action;


		if ('wc_title_test' == get_post_type() && "post.php" == $pagenow && "edit" == $action)
			echo '<style type="text/css">
    #favorite-actions {display:none;}
    .add-new-h2{display:none;}
    .tablenav{display:none;}
    </style>';

	}

	/**
	 *
	 * To change the title of orders list when filtering by title test.
	 *
	 * @since     1.0.0
	 * @param array $args
	 * @return array
	 */
	public function woocommerce_register_post_type_shop_order($args)
	{

		$title_test = get_post(intval($_GET['filter_by_title_test']));

		$args['labels']['name'] = sprintf(__('Orders by title test "%s"', 'wc_title_test'), $title_test->post_title);

		return $args;
	}


	/**
	 *
	 * Filters orders by title test.
	 *
	 * @since     1.0.0
	 * @param $query
	 * @return mixed
	 */
	public function filter_by_title_test($query)
	{
		global $typenow, $wpdb;

		if ($typenow == 'shop_order' && isset($_GET['filter_by_title_test']) && intval($_GET['filter_by_title_test']) > 0) {

			$title_test = intval($_GET['filter_by_title_test']);

			$sql = $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
RIGHT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_type = 'line_item' AND oi.order_id = p.ID
RIGHT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_key = '_title_test' AND oim.order_item_id = oi.order_item_id
WHERE
p.post_type IN ('" . implode("','", wcst_get_order_types()) . "')
AND
oim.meta_value = %s
", $title_test);

			$results = $wpdb->get_results($sql);

			$filter = array();

			foreach ($results as $key => $value) {
				$filter[] = $value->ID;
			}

			if (!$filter) {
				$filter = array(0);
			}

			$query->set("post__in", $filter);

		}

		return $query;
	}


	/**
	 *
	 * To change product name by test chosen.
	 *
	 * @since     1.0.0
	 * @param $name
	 * @param $item
	 * @return string
	 */
	public function filter_woocommerce_order_item_name($name, $item)
	{

		if (isset($item['title_test']) && $item['title_test']) {

			$spit_test = get_post($item['title_test']);

			if ($spit_test) {

				$_product = wc_get_product(isset($item['product_id']) ? $item['product_id'] : 0);

				if ($_product && !$_product->is_visible()) {
					return $spit_test->post_title;
				} else {
					return sprintf('<a href="%s">%s</a>', get_permalink($item['product_id']), $spit_test->post_title);
				}

			}
		}

		return $name;
	}

	/**
	 *
	 * To show title test column on order items meta box.
	 *
	 *
	 * @since     1.0.0
	 * @param $_product
	 * @param $item
	 * @param $item_id
	 * @return void
	 */
	public function action_woocommerce_admin_order_item_values($_product, $item, $item_id)
	{

		$title_test_id = wc_get_order_item_meta($item_id, '_title_test', true);

		if ($title_test_id) {

			$title_test = get_post($title_test_id);

			if ($title_test->post_type == "wc_title_test") {
				echo
					'<td class="thumb"> ' .
					'<a target="_blank" href="' . esc_url(admin_url('post.php?post=' . $title_test->ID . '&action=edit')) . '">' .
					$this->get_title_test_image($title_test, $_product)
					. '</a>' .
					'</td>';

				echo
					'<td class="name"> ' .
					'<a target="_blank" href="' . esc_url(admin_url('post.php?post=' . $title_test->ID . '&action=edit')) . '">' . esc_html($title_test->post_title) . '</a>' .
					'</td>';

				return;
			}

		}

		echo '<td class="thumb">none</td>';
		echo '<td class="name"></td>';

	}

	/**
	 *
	 * To show title test column head on order items meta box.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function action_woocommerce_admin_order_item_headers()
	{
		echo '<th colspan="2" class="title-test">' . translate('Title test used', 'wc_title_test') . '</th>';
	}

	/**
	 *
	 * To hide our custom post meta.
	 *
	 * @since     1.0.0
	 * @param $input
	 * @return array
	 */
	public function filter_woocommerce_hidden_order_itemmeta($input)
	{
		$input[] = "_title_test";
		return $input;
	}

	/**
	 *
	 * To get image of title test.
	 * If title test has got no image
	 * takes original one.
	 *
	 * @since     1.0.0
	 * @param $title_test
	 * @param $product
	 * @param string $size
	 * @param array $attr
	 * @return mixed|string|void
	 */
	private function get_title_test_image($title_test, $product, $size = 'shop_thumbnail', $attr = array())
	{

		if (has_post_thumbnail($title_test->ID)) {
			$image = get_the_post_thumbnail($title_test->ID, $size, $attr);
		} else if (has_post_thumbnail($product->id)) {
			$image = get_the_post_thumbnail($product->id, $size, $attr);
		} elseif (($parent_id = wp_get_post_parent_id($product->id)) && has_post_thumbnail($parent_id)) {
			$image = get_the_post_thumbnail($parent_id, $size, $attr);
		} else {
			$image = wc_placeholder_img($size);
		}

		return $image;
	}

	/**
	 *
	 * To save which test is being used after order occurred.
	 *
	 * @since     1.0.0
	 * @param $order_id
	 * @return void
	 */
	public function woocommerce_checkout_order_processed_action($order_id)
	{

		$order = get_post($order_id);

		if (!$order || $order->post_type != "shop_order" || !isset($_SESSION[WC_Title_Test_Switcher::SESSION_KEY])) {
			return;
		}

		$order = new WC_Order($order_id);

		$items = $order->get_items("line_item");

		foreach ($items as $order_item_id => $value) {

			if (isset($value['product_id']) && $value['product_id']) {

				$product_id = intval($value['product_id']);

				if (isset($_SESSION[WC_Title_Test_Switcher::SESSION_KEY][$product_id])) {

					$product = get_post($product_id);

					wc_update_order_item($order_item_id, array('order_item_name' => $product->post_title));

					$test_id = $_SESSION[WC_Title_Test_Switcher::SESSION_KEY][$product_id];

					wc_add_order_item_meta($order_item_id, '_title_test', $test_id);

				}

			}

		}

	}

	/**
	 *
	 * To create new test bu duplicating original one.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	private function duplicate_test()
	{

		$post = get_post(intval($_GET['post']));

		if (!$post || $post->post_type != "product") {
			return;
		}

		$post_copy = array(
			'post_content' => '',
			'post_name' => $post->post_name . "_test",
			'post_title' => $post->post_title . " Duplicate",
			'post_status' => 'draft',
			'post_type' => "wc_title_test",
			'post_author' => $post->post_author,
			'ping_status' => "closed",
			'post_parent' => $post->ID,
			'menu_order' => 0,
			'to_ping' => "",
			'pinged' => "",
			'post_password' => "",
			'post_excerpt' => '',
			'comment_status' => "closed"
		);

		$new_id = wp_insert_post($post_copy);

		$url_to_redirect = "/wp-admin/post.php?post=" . $new_id . "&action=edit";

		wp_redirect($url_to_redirect);

		die();

	}

	/**
	 *
	 * To check if editing our custom post type.
	 *
	 * @since     1.0.0
	 * @return bool
	 */
	private function is_editing_wc_title_test()
	{
		if (!empty($_GET['post_type']) && 'wc_title_test' == $_GET['post_type']) {
			return true;
		}
		if (!empty($_GET['post']) && 'wc_title_test' == get_post_type($_GET['post'])) {
			return true;
		}
		if (!empty($_REQUEST['post_id']) && 'wc_title_test' == get_post_type($_REQUEST['post_id'])) {
			return true;
		}
		return false;
	}


	/**
	 *
	 * To register our custom post type.
	 *
	 * @since     1.0.0
	 * @return void
	 */
	private function register_post_type()
	{

		//Adding a custom post type to store our entry links
		register_post_type('wc_title_test', apply_filters('wc_title_test_custom_post_type', array(
				'labels' => array(
					'name' => __('WooCommerce Title Test', 'wc_title_test'),
					'singular_name' => __('WooCommerce Title Test', 'wc_title_test'),
					'menu_name' => __('WooCommerce Title Tests', 'Admin menu name', 'wc_title_test'),
					'add_new' => __('Add WooCommerce Title Test', 'wc_title_test'),
					'add_new_item' => __('Add New WooCommerce Title Test', 'wc_title_test'),
					'edit' => __('Edit', 'wc_title_test'),
					'edit_item' => __('Edit Title Test Version', 'wc_title_test'),
					'new_item' => __('New Title Test Version', 'wc_title_test'),
					'view' => __('View Title Test', 'wc_title_test'),
					'view_item' => __('View Title Test', 'wc_title_test'),
					'search_items' => __('Search Title Tests', 'wc_title_test'),
					'not_found' => __('No Entry Links found', 'wc_title_test'),
					'not_found_in_trash' => __('No Entry Links found in trash', 'wc_title_test'),
					'parent' => __('Parent Title Test', 'wc_title_test')
				),
				'public' => false,
				'show_ui' => true,
				'capability_type' => 'post',
				'map_meta_cap' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'hierarchical' => false,
				'query_var' => false,
				'supports' => array('title'),
				'show_in_nav_menus' => false,
				'show_in_admin_bar' => false,
				'rewrite' => array('slug' => 'wc_title_test')
			))
		);

	}


	/**
	 * Add custom scripts
	 *
	 * @since     1.0.0
	 * @return void
	 */
	public function scripts()
	{

		if ($this->is_editing_wc_title_test()) {

			wp_dequeue_script('autosave');

			wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);

		}

		wp_enqueue_script('wc-title-test', WC_SPLIT_TEST_HOME . '/assets/js/wc-title-test.js', array('jquery'), self::VERSION, true);
		wp_enqueue_style('wc-title-test', WC_SPLIT_TEST_HOME . '/assets/css/wc-title-test.css', array(), self::VERSION);

	}


	/**
	 *
	 * To change feature image label.
	 *
	 * @param string $string
	 * @return string|void
	 */
	public function featured_image_gettext($string = '')
	{
		if ('Featured Image' == $string && $this->is_editing_wc_title_test()) {
			$string = __('Product Image', 'woocommerce');
		} elseif ('Remove featured image' == $string && $this->is_editing_wc_title_test()) {
			$string = __('Remove product image', 'woocommerce');
		} elseif ('Set featured image' == $string && $this->is_editing_wc_title_test()) {
			$string = __('Set product image', 'woocommerce');
		}
		return $string;
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