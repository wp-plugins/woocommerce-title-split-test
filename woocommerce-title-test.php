<?php

/**
 * WooCommerce title test
 *
 * @package   WC_Title_Test
 * @author    Justinas Staskus <justinas.staskus@gmail.com>
 * @license   GPL-2.0+
 * @copyright 2014 Justinas Staskus
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce title test
 * Description:       Allows to create different product title versions to see which one sells more.
 * Version:           1.0.0
 * Author:            Justinas Staskus
 * License:           GPL-2.0+
 */
if (!defined('WPINC')) {
	die;
}

/**
 * Home page, to make images load easier.
 */
define("WC_SPLIT_TEST_HOME", plugins_url( '', __FILE__ ));


//Including classes
if (!class_exists('WC_Title_Test')) {
	require_once 'includes/class-wc-title-test.php';
}

//Including classes
if (!class_exists('WC_Title_Test_Switcher')) {
	require_once 'includes/class-wc-title-test-switcher.php';
}


//Loading main plugin class
add_action('plugins_loaded', array('WC_Title_Test', 'get_instance'));
add_action('plugins_loaded', array('WC_Title_Test_Switcher', 'get_instance'));