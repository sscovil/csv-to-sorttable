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
     */
    protected function __construct() {
        $this->set_valid_icon_types();
        $this->set_valid_image_types();
        add_shortcode( 'csv', array( $this, 'shortcode' ) ); // New shortcode.
        add_shortcode( 'csv2table', array( $this, 'shortcode' ) ); // Old shortcode.
    }

    /**
     * Load JavaScript & CSS
     *
     * @param array $enabled
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
        if ( $enabled['images'] ) {
            wp_register_script(
                $handle    = 'add-img-tags',
                $src       = CSV_06082013_URL . 'js/add-img-tags.min.js',
                $deps      = array( 'jquery' )
            );
            wp_enqueue_script( $handle );
        }

        // CSS: sorttable.css
        if ( $enabled['css'] ) {
            wp_register_style(
                $handle = 'sorttable',
                $src    = CSV_06082013_URL . 'css/sorttable.min.css'
            );
            wp_enqueue_style( $handle );
        }

        // CSS: file-type-icons.css
        if ( $enabled['icons'] ) {
            wp_register_style(
                $handle = 'file-type-icons',
                $src    = CSV_06082013_URL . 'css/file-type-icons.min.css'
            );
            wp_enqueue_style( $handle );
        }
    }

    /**
     * CSV Shortcode
     *
     * @param  array       $atts User-defined shortcode attributes.
     * @return bool|string
     */
    function shortcode( $atts ) {
        // Default values for shortcode attributes.
        $defaults = array(
            'src'        => null,   // Source file url.
            'source'     => null,   // Alternate attribute for source file url.
            'unsortable' => '',     // Comma-separated list of column numbers that should be unsortable.
            'number'     => '',     // Comma-separated list of column numbers that should be sorted as numeric.
            'date'       => '',     // Comma-separated list of column numbers that should be sorted as date.
            'group'      => 0,      // Column to check for identical values and apply matching class to rows.
            'disable'    => '',     // Available options: css, icons, images, all
        );
        $atts = shortcode_atts( $defaults, $atts );

        // Create an array of enabled features.
        $enabled = array(
            'css'    => true,
            'icons'  => true,
            'images' => true,
        );

        // Mark features as false if they are listed in 'disable' shortcode attribute.
        foreach( $enabled as $feature => $value ) {
            if ( false !== strpos( $atts['disable'], 'all' ) || false !== strpos( $atts['disable'], $feature ) )
                $enabled[$feature] = false;
        }

        // Enqueue JavaScript & CSS only when shortcode is used.
        $this->load_js_css( $enabled );

        // Determine .csv file source.
        if ( isset( $atts['src'] ) || isset( $atts['source'] ) ) {
            $src = isset( $atts['source'] ) ? esc_url( $atts['source'] ) : esc_url( $atts['src'] );
        } else {
            $src = CSV_06082013_URL . 'test.csv'; // Default if no .csv file source is defined.
        }

        // Get contents of .CSV file and parse into a multidimensional array.
        $file = wp_remote_fopen( $src );
        $rows = $this->parse_csv( $file );

        // Get array of column classes based on column number and optional shortcode attributes.
        $col_classes = $this->get_column_classes( $rows, $atts );

        // Begin rendering HTML and storing it in an output buffer.
        ob_start();
        echo '<table class="sortable">';

        // Loop through each row.
        foreach( $rows as $row => $cols ) {

            // Default cell tag is <td>.
            $tag = 'td';

            // If this is the first row, cell tag should be <th> & row tag should be preceded by <thead> tag.
            if ( 0 == $row ) {
                $tag = 'th';
                echo '<thead>';

            // If this is the second row, close out the <thead> tag and open the <tbody> tag.
            } elseif ( 1 == $row ) {
                echo '</thead><tbody>';
            }

            // Get string of row classes based on row number and optional shortcode attributes.
            $row_classes = $this->get_row_classes( $row, $cols, $atts );

            // Opening table row tag with row classes.
            printf( '<tr class="%s">', $row_classes );

            // Loop through each column.
            foreach( $cols as $col => $cell ) {
                $class = $col_classes[$col];
                $file_ext = null;

                $href_val = $this->get_href_val( $cell );
                if ( $href_val )
                    $file_ext = $this->get_file_ext( $href_val );

                if ( isset( $file_ext ) ) {
                    $class .= $this->get_icon_class( $file_ext );
                    $class .= $this->get_image_class( $file_ext );
                }

                // Print <th> or <td> tags and cell content.
                printf(
                    '<%1$s class="%2$s">%3$s</%1$s>',
                    $tag,
                    $class,
                    $cell
                );
            }

            // Closing table row tag.
            echo '</tr>';
        }

        echo '</tbody></table>';
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Parse CSV
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

        return $data;
    }

    /**
     * Get Href Value
     *
     * @param  string      $string
     * @return bool|string
     */
    function get_href_val( $string ) {
        $pattern = '/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU';
        preg_match( $pattern, $string, $url );
        return isset( $url[2] ) ? $url[2] : false;
    }

    /**
     * Get File Extension
     *
     * @param  string      $url
     * @return null|string
     */
    function get_file_ext( $url ) {
        return isset( $url ) ? pathinfo( $url, PATHINFO_EXTENSION ) : null;
    }

    /**
     * Get Row Classes
     *
     * @param  int    $row
     * @param  array  $cols
     * @param  array  $atts
     * @return string
     */
    function get_row_classes( $row, $cols, $atts ) {
        // Create row class based on row number.
        $row_classes = 'row' . strval( $row + 1 );

        // If 'group' shortcode attribute is set, add a group class to row classes.
        $group = intval( $atts['group'] );
        if ( $group > 0 && $group <= count( $cols ) ) {

            // Strip html & php tags from group column value and replace whitespace with dashes.
            $group_class = preg_replace(
                $pattern = '/[\s_]/',
                $replace = '-',
                $subject = strtolower( strip_tags( $cols[strval( $group - 1 )] ) )
            );

            // Strip away all special characters except dashes and underscores.
            $group_class = preg_replace(
                $pattern = '/[^a-z0-9\-\_]/',
                $replace = '',
                $subject = $group_class
            );

            // Truncate group class name to a maximum of 25 characters.
            $row_classes .= ( empty( $group_class ) ) ? '' : ' ' . substr( $group_class, 0, 25 );
        }

        return $row_classes;
    }

    /**
     * Get Column Classes
     *
     * @param  array $rows
     * @param  array $atts
     * @return array
     */
    function get_column_classes( $rows, $atts ) {
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
     * Set Valid Icon Types
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
     * Get Icon Class
     *
     * @param  string $file_ext
     * @return string
     */
    function get_icon_class( $file_ext ) {
        $valid_ext = self::$valid_icon_types;

        // If file type is supported, return file-type class name.
        if ( in_array( $file_ext, $valid_ext ) )
            return ' file-type-' . $file_ext;

        return false;
    }

    /**
     * Set Valid Image Types
     */
    function set_valid_image_types() {
        self::$valid_image_types = array( 'jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp' );
        apply_filters( 'sorttable_valid_image_file_types', self::$valid_image_types );
    }

    /**
     * Get Image Class
     *
     * @param  string $file_ext
     * @return string
     */
    function get_image_class( $file_ext ) {
        $valid_ext = self::$valid_image_types;
        if ( in_array( $file_ext, $valid_ext ) )
            return ' image img-type-' . $file_ext;

        return false;
    }

}