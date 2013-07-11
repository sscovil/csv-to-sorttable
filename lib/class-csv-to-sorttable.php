<?php

/**
 * Class CSV_to_SortTable
 *
 * @package csv-to-sorttable
 */
class CSV_to_SortTable {

    protected static $instance, $valid_icon_types, $valid_image_types;

    /**
     * Singleton Factory
     *
     * @return object
     */
    public static function instance() {
        if ( !isset( self::$instance ) )
            self::$instance = new CSV_to_SortTable();

        return self::$instance;
    }

    /**
     * Construct
     *
     * @uses function add_shortcode
     */
    protected function __construct() {
        $this->set_valid_icon_types();
        $this->set_valid_image_types();
        add_shortcode( 'csv', array( $this, 'shortcode' ) ); // New shortcode.
        add_shortcode( 'csv2table', array( $this, 'shortcode' ) ); // Old shortcode.
    }

    /**
     * Set Valid Icon Types
     *
     * @uses function apply_filters
     */
    function set_valid_icon_types() {
        self::$valid_icon_types = array(
            'doc', 'docx', 'odt', 'rtf', 'txt', 'wpd', // Use DOC icon
            'pdf',                                     // Use PDF icon
            'ppt', 'pptx', 'odp',                      // Use PPT icon
            'xls', 'xlsx', 'ods', 'csv',               // Use XLS icon
            'zip', 'zipx', 'gz', '7z', 'rar', 'tar',   // Use ZIP icon
        );
        apply_filters( 'sorttable_valid_icon_file_types', self::$valid_icon_types );
    }

    /**
     * Set Valid Image Types
     *
     * @uses function apply_filters
     */
    function set_valid_image_types() {
        self::$valid_image_types = array( 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp' );
        apply_filters( 'sorttable_valid_image_file_types', self::$valid_image_types );
    }

    /**
     * CSV Shortcode
     *
     * @uses function shortcode_atts
     * @uses function wp_remote_fopen
     *
     * @param  array  $atts User-defined shortcode attributes.
     * @return string       HTML for rendered table, or blank string if no data.
     */
    function shortcode( $atts ) {
        // Default values for shortcode attributes.
        $defaults = array(
            'src'        => null,   // Source file url.
            'source'     => null,   // Alternate attribute for source file url.
            'id'         => '',     // Optional value for <table> id attribute.
            'unsortable' => '',     // Comma-separated list of column numbers that should be unsortable.
            'number'     => '',     // Comma-separated list of column numbers that should be sorted as numeric.
            'date'       => '',     // Comma-separated list of column numbers that should be sorted as date.
            'group'      => 0,      // Column to check for identical values and apply matching class to rows.
            'disable'    => '',     // Available options: css, icons, images, all
        );
        $atts = shortcode_atts( $defaults, $atts );

        // Create an array of enabled features based on 'disable' attribute.
        $enabled = $this->enabled_features( $atts['disable'] );

        // Enqueue plugin JavaScript & CSS only on pages where shortcode is used.
        $this->load_js_css( $enabled );

        // Determine .csv file source based on 'src' and 'source' attributes; default to test.csv URL.
        $src = $this->csv_source( $atts );

        // Get contents of .CSV file as string.
        $file = wp_remote_fopen( $src );

        // Parse CSV string into a multidimensional array.
        $rows = $this->parse_csv( $file );

        // Render table and return HTML string.
        return $this->render_table( $rows, $atts );
    }

    /**
     * Enabled Features
     *
     * @param  string $disable A string of feature names to disable.
     * @return array           Plugin features with boolean values: true if enabled, false if disabled.
     */
    function enabled_features( $disable ) {
        $enabled = array(
            'css'    => true,
            'icons'  => true,
            'images' => true,
        );

        // Mark features as false if they are listed in 'disable' shortcode attribute.
        foreach( $enabled as $feature => $value ) {
            if ( false !== strpos( $disable, 'all' ) || false !== strpos( $disable, $feature ) )
                $enabled[$feature] = false;
        }
        return $enabled;
    }

    /**
     * Load JavaScript & CSS
     *
     * @uses function wp_register_script
     * @uses function wp_register_style
     * @uses function wp_enqueue_script
     * @uses function wp_enqueue_style
     *
     * @param array $enabled Feature names as keys, with boolean values (true == enabled, false == disabled).
     */
    function load_js_css( $enabled ) {
        // JavaScript: sorttable.js by Stuart Langridge: http://www.kryogenix.org/code/browser/sorttable/
        wp_register_script(
            $handle    = 'sorttable',
            $src       = CSV_06082013_URL . 'js/sorttable.min.js',
            $deps      = array( 'jquery' ),
            $ver       = 2.0,
            $in_footer = false
        );
        wp_enqueue_script( $handle );

        // JavaScript: add-img-tags.js
        if ( isset( $enabled['images'] ) && $enabled['images'] ) {
            wp_register_script(
                $handle    = 'add-img-tags',
                $src       = CSV_06082013_URL . 'js/add-img-tags.min.js',
                $deps      = array( 'jquery' )
            );
            wp_enqueue_script( $handle );
        }

        // CSS: sorttable.css
        if ( isset( $enabled['css'] ) && $enabled['css'] ) {
            wp_register_style(
                $handle = 'sorttable',
                $src    = CSV_06082013_URL . 'css/sorttable.min.css'
            );
            wp_enqueue_style( $handle );
        }

        // CSS: file-type-icons.css
        if ( isset( $enabled['icons'] ) && $enabled['icons'] ) {
            wp_register_style(
                $handle = 'file-type-icons',
                $src    = CSV_06082013_URL . 'css/file-type-icons.min.css'
            );
            wp_enqueue_style( $handle );
        }
    }

    /**
     * CSV Source
     *
     * @param  array  $atts Shortcode attributes.
     * @return string       URL of .csv file to load.
     */
    function csv_source( $atts ) {
        if ( isset( $atts['src'] ) || isset( $atts['source'] ) ) {
            $src = isset( $atts['source'] ) ? esc_url( $atts['source'] ) : esc_url( $atts['src'] );
            if ( 0 === strpos( $src, '/' ) )
                return home_url( $src );
            else
                return $src;
        }
        return CSV_06082013_URL . 'test.csv'; // Default file URL if no .csv file source is defined.
    }

    /**
     * Parse CSV
     *
     * @uses function str_getcsv ( or str_getcsv4 by V.Krishn, if PHP < 5.3 )
     * @uses function make_clickable
     *
     * @param  string $file CSV file contents as string.
     * @return array        CSV file contents as multidimensional array.
     */
    function parse_csv( $file ) {
        // Parse file string into an array of rows.
        $file = str_replace( "\r\n", "\n", trim( $file ) );
        $rows = explode( "\n", $file );
        $data = array();

        // Parse each row into an array of columns.
        foreach( $rows as $row => $cols ) {
            $cols = str_getcsv( $cols, ',' );

            // Populate table data array with cell contents.
            foreach( $cols as $col => $cell ) {
                $data[$row][$col] = make_clickable( $cell );
            }
        }
        return apply_filters( 'csv_to_sorttable_data_array', $data );
    }

    /**
     * Render Table
     *
     * @param  array  $rows Multidimensional array of table row & column data.
     * @param  array  $atts Shortcode attributes.
     * @return string       HTML for rendered table, or blank string if no data.
     */
    function render_table( $rows, $atts ) {
        if ( 1 > count( $rows ) )
            return '';

        // Create an array of column classes based on column number and optional shortcode attributes.
        $col_classes = $this->column_classes( $rows, $atts );

        // Begin rendering HTML and storing it as a string using output buffer.
        ob_start();
        printf( '<table id="%s" class="sortable">', $this->string_to_html_class( $atts['id'] ) );

        // Loop through each row.
        foreach( $rows as $row => $cols ) {
            $tag = 'td';

            // First row cell tag should be <th> & row tag should be preceded by <thead> tag.
            if ( 0 == $row ) {
                $tag = 'th';
                echo '<thead>';
            }

            // Before second row, close out the <thead> tag and open the <tbody> tag.
            elseif ( 1 == $row )
                echo '</thead><tbody>';

            // Create a string of row classes based on row number and optional shortcode attributes.
            $row_classes = $this->row_classes( $row, $cols, $atts );

            // Print opening table row tag & apply row classes.
            printf( '<tr class="%s">', $row_classes );

            // Loop through each column.
            foreach( $cols as $col => $cell ) {
                $class = $col_classes[$col];

                // Check cell contents for href value & get file extension from URL.
                $href_val = $this->href_val( $cell );
                if ( $href_val )
                    $file_ext = $this->file_ext( $href_val );
                else
                    $file_ext = null;

                // Compare URL file extension to valid icon & image types; add class to cell if found.
                if ( isset( $file_ext ) ) {
                    $class .= $this->icon_class( $file_ext );
                    $class .= $this->image_class( $file_ext );
                }

                // Print cell content wrapped in <th> or <td> tags.
                printf(
                    '<%1$s class="%2$s">%3$s</%1$s>',
                    $tag,
                    $class,
                    $cell
                );
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Href Value
     *
     * @param  string      $string Text or HTML to search for an href value.
     * @return bool|string         Value of href attribute within string, or false if none found.
     */
    function href_val( $string ) {
        $pattern = '/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU';
        preg_match( $pattern, $string, $url );
        return isset( $url[2] ) ? $url[2] : false;
    }

    /**
     * File Extension
     *
     * @param  string      $url URL to search for a file extension.
     * @return null|string      File extension of URL, or null if no file extension.
     */
    function file_ext( $url ) {
        return isset( $url ) ? pathinfo( $url, PATHINFO_EXTENSION ) : null;
    }

    /**
     * Row Classes
     *
     * @param  int    $row  Current row number.
     * @param  array  $cols Column cell values.
     * @param  array  $atts Shortcode attributes.
     * @return string       Table row classes, separated by spaces.
     */
    function row_classes( $row, $cols, $atts ) {
        // Create row class based on row number.
        $row_classes = 'row' . strval( $row + 1 );

        // If 'group' shortcode attribute is set, add a group class to row classes.
        $group = intval( $atts['group'] );
        if ( $group > 0 && $group <= count( $cols ) ) {

            // Create valid HTML class name based on group cell value.
            $group_class = strtolower( strip_tags( $cols[strval( $group - 1 )] ) );
            $group_class = $this->string_to_html_class( $group_class );

            // Truncate group class name to a maximum of 25 characters.
            $row_classes .= ( empty( $group_class ) ) ? '' : ' ' . substr( $group_class, 0, 25 );
        }
        return $row_classes;
    }

    /**
     * Column Classes
     *
     * @param  array $rows Multidimensional array of table row & column data.
     * @param  array $atts Shortcode attributes.
     * @return array       Table column classes, separated by spaces.
     */
    function column_classes( $rows, $atts ) {
        // Parse shortcode attributes with comma-separated lists of column numbers into arrays.
        $unsortable = explode( ',', $atts['unsortable'] );
        $number     = explode( ',', $atts['number'] );
        $date       = explode( ',', $atts['date'] );

        // Create array of row classes based on column number and optional shortcode attributes.
        $col_classes = array();
        for( $i = 0; $i < count( $rows[0] ); $i++ ) {
            $count = strval( $i + 1 );
            $col_classes[$i] = 'col' . $count;
            if ( in_array( $count, $unsortable ) ) {
                $col_classes[$i] .= ' sorttable_nosort';
            } elseif ( in_array( $count, $number ) ) {
                $col_classes[$i] .= ' sorttable_numeric';
            } elseif ( in_array( $count, $date ) ) {
                $col_classes[$i] .= ' sorttable_mmdd';
            } else {
                $col_classes[$i] .= ' sorttable_alpha';
            }
        }
        return $col_classes;
    }

    /**
     * Get Icon Class
     *
     * @param  string      $file_ext File extension to compare with valid file-types, for converting URLs to icons.
     * @return bool|string           File type class, or false if no match found.
     */
    function icon_class( $file_ext ) {
        $valid_ext = self::$valid_icon_types;

        // If file type is supported, return file-type class name.
        if ( in_array( $file_ext, $valid_ext ) )
            return ' file-type-' . $file_ext;

        return false;
    }

    /**
     * Get Image Class
     *
     * @param  string      $file_ext File extension to compare with valid image-types, for converting URLs to images.
     * @return bool|string           Image type class, or false if no match found.
     */
    function image_class( $file_ext ) {
        $valid_ext = self::$valid_image_types;
        if ( in_array( $file_ext, $valid_ext ) )
            return ' image img-type-' . $file_ext;

        return false;
    }

    /**
     * String To HTML Class
     *
     * @param  string $string Text or HTML to convert to valid HTML class name.
     * @return string         Valid HTML class name with only letters, numbers, dashes & underscores.
     */
    function string_to_html_class( $string ) {
        // Strip html & php tags and replace whitespace with dashes.
        $string = preg_replace(
            $pattern = '/[\s_]/',
            $replace = '-',
            $subject = trim( $string )
        );

        // Strip all special characters except dashes and underscores.
        $string = preg_replace(
            $pattern = '/[^a-z0-9\-\_]/',
            $replace = '',
            $subject = strtolower( $string )
        );
        return $string;
    }

}