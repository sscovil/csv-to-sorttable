<?php
/**
 * Plugin Name: CSV to SortTable
 * Plugin URI: https://github.com/sscovil/csv-to-sorttable
 * Description: Import data from a spreadsheet (.csv file format) and display it in a sortable table.
 * Version: 4.2
 * Author: sscovil
 * Author URI: http://shaunscovil.com
 * Text Domain: csv-to-sorttable
 * License: GPL2
 */
 
// Define file path constants.
define( 'CSV_06082013_PATH', plugin_dir_path(__FILE__) );
define( 'CSV_06082013_URL', plugin_dir_url(__FILE__) );
define( 'CSV_06082013_BASE', plugin_basename(__FILE__) );

// For PHP versions < 5.3.
if ( ! function_exists( 'str_getcsv' ) )
    require_once CSV_06082013_PATH . 'lib/str_getcsv4.php';

// Instantiate primary plugin class.
require_once CSV_06082013_PATH . 'lib/class-csv-to-sorttable.php';
CSV_to_SortTable::instance();