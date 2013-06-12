=== CSV to SortTable ===

Contributors: sscovil
Tags: data, table, csv, import, sort, sortable, sorttable
Requires at least: 3.2.1
Tested up to: 3.5.1
Stable tag: 4.0.2

Import data from a spreadsheet (.csv file format) and display it in a sortable HTML table.


== Description ==

CSV to SortTable is great for anyone who keeps track of important information using a spreadsheet. It could be used for product catalogs, inventory lists, or even leaderboards in a competition.


= How To Use =

Insert a sortable table into any page or post using this shortcode:

`[csv src=http://example.com/myfile.csv]`

The result will be a beautiful, semantically-correct HTML table populated with the contents of your file.


= Optional Features =

This plugin automatically does a few things for you, all of which can be disabled:

1. Adds CSS to the table, to make it look nice.
2. Converts image file URLs into clickable image links.
3. Converts some other file URLs into clickable file-type icons (.doc, .pdf, .ppt, .xls, .zip).

To disable any of these features, use the `disable` shortcode attribute:

`[csv src=http://example.com/myfile.csv disable=css]`
`[csv src=http://example.com/myfile.csv disable=icons,images]`
`[csv src=http://example.com/myfile.csv disable=all]`

The first shortcode would just disable the plugin CSS; the second would disable both icons and images; and the third would disable all features. You can disable any combination using a comma-separated list.


= Group Classes =

Let's say you have a table with three columns: Item, Description and Type. You want all table rows of the same Type to have the same CSS class, so you can highlight them in different colors or modify them all at once with JavaScript.

This can be done by assigning a column number to the `group` shortcode attribute, like this:

`[csv src=http://example.com/myfile.csv group=3]`

The result would be a special class assigned to each table row based on the value of the third column.


= Sorting Options =

Most table data can be sorted alphabetically, but you may have numbers, dates or columns that should not be sortable. To change the sorting rules for one or more columns, use the following shortcode attributes.

`[csv src=http://example.com/myfile.csv number=2]`
`[csv src=http://example.com/myfile.csv date=3]`
`[csv src=http://example.com/myfile.csv unsortable=4,5,6]`

The values can be a single column number, or multiple column numbers in a comma-separated list.


== Installation ==

1. Install and activate the plugin via `WP-Admin > Plugins`.
2. Add shortcode to a post or page: `[csv src=http://example.com/data.csv]`.
3. Use optional shortcode attributes to modify table behavior (see description).

TinyMCE button coming soon!


== Screenshots ==

1. Default sortable table shows off some key features.
2. Table sorted by `Description` column (A-Z).
3. Table sorted by `Description` column (Z-A).
4. Table sorted by `Group` column (A-Z).


== Credits ==

This plugin utilizes some excellent open source scripts, functions and images whose creators deserve to be recognized.

1. Stuart Langridge wrote [sorttable.js], the JavaScript that inspired this plugin and makes it possible to sort tables by clicking on the column headers.
2. V.Krishn wrote a handy [PHP function] that enables users of PHP < 5.3 to utilize the `str_getcsv()` function that powers this plugin.
3. Blake Knight created the beautiful [file type icons] used in this plugin and made them free for all.

[sorttable.js]: http://www.kryogenix.org/code/browser/sorttable/
[PHP function]: http://github.com/insteps/phputils
[file type icons]: http://blog.blake-knight.com/2010/06/15/free-vector-pack-document-icons/


== Changelog ==

= 4.0.2 =
* Fixed support for old shortcode `[csv2table]`.

= 4.0.1 =
* Fixed bug causing image-type and file-type classes from incorrectly carrying over into other table cells.

= 4.0 =
* Major code revision!
* Replaced custom CSV file import function with WordPress core function: `wp_remote_fopen()`.
* Replaced custom CSV parser function with `str_getcsv()` (including support for PHP < 5.3).
* Replaced custom HTML/link handler function with WordPress core function: `make_clickable()`.
* JavaScript and CSS is optional and only loads on posts & pages where shortcode is used.
* Added single shortcode attribute to easily disable features: css, icons, images, or all.
* Removed file type icons for image and media files.
* Added feature that converts image file URLs into images with links using jQuery.
* Modified row and column classes and 'group' class feature.
* Killed `even` and `odd` classes introduced in v2.0; use CSS selectors `:nth-child(even)` and `:nth-child(odd)`.
* Added shotcode `[csv src=""]` and retained legacy support for `[csv2table source=""]`.
* Included test.csv file as default if source file is defined.

= 3.1 =
* Fixed bug that was adding td .col class without column number (i.e. class was 'col' instead of 'col1', 'col2', etc.)
* Added `icons` shortcode parameter to replace url links for certain file types (e.g. PDF, MP3, MOV) with file-type icons
* Renamed functions using the mnsp_ prefix
* Cleaned up code

= 3.0 =
* Replaced fopen() function with curl for retrieving .csv data
* Added mnsp_parse_csv() function to replace fgetcsv(), which requires fopen() -- str_getcsv() would have worked with curl, but requires PHP v5.3
* Changed the default CSS to a nicer light blue theme
* Cleaned up code

= 2.1.1 =
* Cleaned up code by creating a separate function for finding links in cell data.

= 2.1 =
* Fixed problem with URLs getting truncated when converted to links.
* Now correcly converts email and www addresses to `mailto:` and `http://` links, respectively.

= 2.0 =
* Automatically detects URLs contained in cells and converts them into HTML links.
* Added `group` option, which assigns a unique common class to all adjacent rows containing the same data in the specified column.
* Added `even` and `odd` classes to row groups.

= 1.0 =
* First public release.