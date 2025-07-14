<?php

// REMOVE WP Embed
function my_deregister_scripts(){ wp_deregister_script( 'wp-embed' ); }
add_action( 'wp_footer', 'my_deregister_scripts' );

function alevia_add_woocommerce_support() {
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'alevia_add_woocommerce_support' );

function getCartCount() {
    global $woocommerce;

    return count($woocommerce->cart->cart_contents);
}

function getAriiTerapeuticeMenu() {
    $ariiTerapeuticeMenu = [];
    $ariiTerapeutice = get_terms( array(
        'taxonomy' => 'arii_terapeutice',
        'hide_empty' => false,
    ) );
    if (!empty($ariiTerapeutice)) {
        $index = 0;
        foreach ($ariiTerapeutice as $arieTerapeutica) {
            $ariiTerapeuticeMenu[$index % 3][] = [
                'name' => $arieTerapeutica->name,
                'url' => get_term_link($arieTerapeutica->term_id),
                'img' => get_field('icon', $arieTerapeutica) ?? get_template_directory_uri() . '/img/home-mobile/svg.svg'
            ];
            $index ++;
        }
    }
    return $ariiTerapeuticeMenu;
}
function getChildPages($pageId) {
    $childrenPages = [];
    $args = array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_parent'    => $pageId,
        'order'          => 'ASC',
        'orderby'        => 'menu_order'
    );
    $parent = new WP_Query( $args );
    if ($parent->have_posts()) {
        while ($parent->have_posts()) {
            $parent->the_post();
            if (get_the_ID() == 1558) {
                continue;
            }
            $image = get_field('imagine_meniu', get_the_ID());
            $childrenPages[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'content' => get_the_content(),
                'image_url' => $image['url'] ?? 'https://www.alevia.com.ro/wp-content/uploads/2019/02/photo-1547997256-e86f9dae7440.jpg',
                'url' => get_the_permalink(),
            ];
        }
    }
    return $childrenPages;
}

function fetchAllIngredients() {
    $ingredientsArr = [];
    $ingredients = get_posts(['post_type' => 'ingrediente', 'post_status' => 'publish', 'numberposts' => -1]);
    if (!empty($ingredients)) {
        foreach ($ingredients as $ingredient) {
            $ingredientsArr[ucfirst(substr($ingredient->post_name, 0, 1))][] = [
                'id' => $ingredient->ID,
                'name' => $ingredient->post_title,
                'first_section' => $ingredient->post_content,
                'second_section' => get_field('block_text_secundar', $ingredient->ID),
                'image_url' => get_the_post_thumbnail_url($ingredient->ID, 'full')
            ];
        }
    }
    ksort($ingredientsArr);
    return $ingredientsArr;
}

//redirect to ingredients page...
add_action( 'template_redirect', 'ingredients_redirrect' );
function ingredients_redirrect() {
    $queried_post_type = get_query_var('post_type');
    if ( is_single() && 'ingrediente' ==  $queried_post_type ) {
        wp_redirect(  get_the_permalink(167), 301 );
        exit;
    }
}
/** end redirrect*/


function show_warning($categoryArr) {

    return !empty(array_intersect($categoryArr, [28, 32]));
}

function ddd($val) {
    echo "<pre>";
    var_dump($val);
    die();
}


function getProductCategories($categoryTag = 'product_cat', $onlyUrls = false) {
    $taxonomy     = $categoryTag;
    $orderby      = 'name';
    $show_count   = 0;      // 1 for yes, 0 for no
    $pad_counts   = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no
    $title        = '';
    $empty        = 0;
    $args = array(
        'taxonomy'     => $taxonomy,
        'orderby'      => $orderby,
        'show_count'   => $show_count,
        'pad_counts'   => $pad_counts,
        'hierarchical' => $hierarchical,
        'title_li'     => $title,
        'hide_empty'   => $empty
    );
    $all_categories = get_categories( $args );
    $category = [];
    foreach ($all_categories as $cat) {
        if($cat->category_parent == 0) {
            $category_id = $cat->term_id;
            $category[strtoupper($cat->name)] = [
                'url' => get_term_link($cat)
            ];
            if ($categoryTag == 'product_cat' && !$onlyUrls) {
                unset($category[strtoupper($cat->name)]['url']);
                $args2 = array(
                    'taxonomy'     => $taxonomy,
                    'child_of'     => 0,
                    'parent'       => $category_id,
                    'orderby'      => $orderby,
                    'show_count'   => $show_count,
                    'pad_counts'   => $pad_counts,
                    'hierarchical' => $hierarchical,
                    'title_li'     => $title,
                    'hide_empty'   => $empty
                );
                $sub_cats = get_categories( $args2 );
                if($sub_cats) {
                    foreach($sub_cats as $sub_category) {
                        $category[strtoupper($cat->name)][] = [
                            'name' => $sub_category->name,
                            'url' => get_term_link($sub_category)
                        ];
                    }
                } else {
                    unset($category[strtoupper($cat->name)]);
                }
            }
        }
    }
    return $category;
}

function getPostTitles($postType = 'afectiuni') {
    $args = array(
        'post_type' => $postType,
        'posts_per_page' => -1,
        'orderby'=> 'title',
        'order' => 'ASC'
    );
    $loop = new WP_Query($args);
    $titles = [];
    $urlPostType = $postType;
    if ($postType == 'afectiuni') {
        $urlPostType = 'afectiune';
    }
    while($loop->have_posts()): $loop->the_post();
        $titles[get_the_ID()] = [
            'title' => get_the_title(),
            'id' => $postType . '_'. get_the_ID(),
            'idx' => get_the_ID(),
            'url' => get_permalink( wc_get_page_id( 'shop' ) ) . '/?' . $urlPostType . '[]=' . get_the_ID(),
        ];
    endwhile;
    wp_reset_query();
    return $titles;
}

function get_the_posts_pagination_themed( $args = array() ) {
    $links = [];

    // Don't print empty markup if there's only one page.
    if ( $GLOBALS['wp_query']->max_num_pages > 1 ) {
        $args = wp_parse_args(
            $args,
            array(
                'mid_size'           => 1,
                'prev_text'          => _x( '<img src="'. get_template_directory_uri() . '/img/arrow.svg" alt="">', 'previous set of posts' ),
                'next_text'          => _x( '<img src="'. get_template_directory_uri() . '/img/arrow.svg" alt="">', 'next set of posts' ),
                'screen_reader_text' => __( 'Posts navigation' ),
            )
        );

        // Set up paginated links.
        $links = paginate_links( $args );
    }

    return $links;
}

function getCartItems() {
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $itemDetails = [];
    foreach($items as $item => $values) {
        $_product =  wc_get_product( $values['data']->get_id());
        $itemDetails[] = [
            'name' => $_product->get_title(),
            'price' => number_format((float)wc_get_price_including_tax($_product,['price' => $_product->get_price()]), 2, '.', ''),
            'qty' => $values['quantity'],
            'imageUrl' => wp_get_attachment_image_src( get_post_thumbnail_id($_product->get_id()), 'thumbnail'),
            'deleteLink' => wc_get_cart_remove_url( $item ),
        ];
    }
    return $itemDetails;
}

function getLoginText() {
    $current_user = wp_get_current_user();
    if (!$current_user->exists()) {
        return 'Autentificare';
    }

    if ($current_user->user_firstname) {
        return 'Salut, ' . $current_user->user_firstname;
    }

    return 'Salut, ' . $current_user->user_login;
}

function getFooterMenu($menuName = 'Suport Clienti') {
    $returnMenus = [];
    $menus = wp_get_nav_menus();
    if (!empty($menus)) {
        foreach ($menus as $menu) {
            $returnMenus[$menu->term_id] = [
                'name' => $menu->name,
                'menus' => [],
            ];
            $menuItems = wp_get_nav_menu_items($menu);
            if (!empty($menuItems)) {
                foreach ($menuItems as $menuItem) {
                    $returnMenus[$menu->term_id]['menus'][] = [
                        'name' => $menuItem->title,
                        'url' => $menuItem->url
                    ];
                }
            }
        }
    }
    ksort($returnMenus);
    return $returnMenus;
}

remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

// Breadcrumbs
function custom_breadcrumbs() {

    // Settings
    $separator          = '/';
    $breadcrums_id      = 'breadcrumbs';
    $breadcrums_class   = 'breadcrumbs';
    $home_title         = 'Acasa';

    // If you have any custom post types with custom taxonomies, put the taxonomy name below (e.g. product_cat)
    $custom_taxonomy    = 'product_cat';

    // Get the query & post information
    global $post,$wp_query;

    // Do not display on the homepage
    if ( !is_front_page() ) {

        // Home page
        echo '<a class="bread-link bread-home" href="' . get_home_url() . '" title="' . $home_title . '">' . $home_title . '</a>';
        echo $separator;
        if ( is_archive() && !is_tax() && !is_category() && !is_tag() ) {
            echo '<span class="bread-current bread-archive">' . post_type_archive_title('', false) . '</span>';
        } else if ( is_archive() && is_tax() && !is_category() && !is_tag() ) {
            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if(is_tax()) {
                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);
                $term = get_term_by('slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
                echo '<a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . ucfirst(str_replace('_', ' ', $term->taxonomy)) . '</a>';
                echo $separator;

            }

            $custom_tax_name = get_queried_object()->name;
            echo '<span class="bread-current bread-archive">' . $custom_tax_name . '</span>';

        } else if ( is_single() ) {
            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if($post_type != 'post') {

                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);
                if ($post_type == 'cariere') {
                    $post_type_archive = get_permalink(209);
                }

                echo '<a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a>';
                echo $separator;

            } elseif ($post_type == 'post') {
                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);
                echo '<a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a>';
                echo $separator;
            }

            // Get post category info
            $category = get_the_category();
            if(!empty($category) && count($category) > 1) {

                // Get last category post is in
                $last_category = end(array_values($category));

                // Get parent any categories and create array
                $get_cat_parents = rtrim(get_category_parents($last_category->term_id, true, ','),',');
                $cat_parents = explode(',',$get_cat_parents);

                // Loop through parent categories and store in variable $cat_display
                $cat_display = '';
                foreach($cat_parents as $parents) {
                    $cat_display .= $parents;
                    $cat_display .= $separator;
                }

            }

            // If it's a custom post type within a custom taxonomy
            $taxonomy_exists = taxonomy_exists($custom_taxonomy);
            if(empty($last_category) && !empty($custom_taxonomy) && $taxonomy_exists) {

                $taxonomy_terms = get_the_terms( $post->ID, $custom_taxonomy );
                if (!empty($taxonomy_terms)) {
                    $cat_id         = $taxonomy_terms[0]->term_id;
                    $cat_nicename   = $taxonomy_terms[0]->slug;
                    $cat_link       = get_term_link($taxonomy_terms[0]->term_id, $custom_taxonomy);
                    $cat_name       = $taxonomy_terms[0]->name;
                }
            }

            // Check if the post is in a category
            if(!empty($last_category)) {
                echo $cat_display;
                echo '<span class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</span>';

                // Else if post is in a custom taxonomy
            } else if(!empty($cat_id)) {

                echo '<a class="bread-cat bread-cat-' . $cat_id . ' bread-cat-' . $cat_nicename . '" href="' . $cat_link . '" title="' . $cat_name . '">' . $cat_name . '</a>';
                echo $separator;
                echo '<span class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</span>';

            } else {

                echo '<span class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</span>';

            }

        } else if ( is_category() ) {

            // Category page
            echo '<span class="bread-current bread-cat">' . single_cat_title('', false) . '</span>';

        } else if ( is_page() ) {

            // Standard page
            if( $post->post_parent ){

                // If child page, get parents
                $anc = get_post_ancestors( $post->ID );

                // Get parents in the right order
                $anc = array_reverse($anc);

                // Parent page loop
                if ( !isset( $parents ) ) $parents = null;
                foreach ( $anc as $ancestor ) {
                    $parents .= '<a class="bread-parent bread-parent-' . $ancestor . '" href="' . get_permalink($ancestor) . '" title="' . get_the_title($ancestor) . '">' . get_the_title($ancestor) . '</a>';
                    $parents .= $separator;
                }

                // Display parent pages
                echo $parents;

                // Current page
                echo '<span title="' . get_the_title() . '"> ' . get_the_title() . '</span>';

            } else {

                // Just display current page if not parents
                echo '<span class="bread-current bread-' . $post->ID . '"> ' . get_the_title() . '</span>';

            }

        } else if ( is_tag() ) {

            // Tag page

            // Get tag information
            $term_id        = get_query_var('tag_id');
            $taxonomy       = 'post_tag';
            $args           = 'include=' . $term_id;
            $terms          = get_terms( $taxonomy, $args );
            $get_term_id    = $terms[0]->term_id;
            $get_term_slug  = $terms[0]->slug;
            $get_term_name  = $terms[0]->name;

            // Display the tag name
            echo '<span class="bread-current bread-tag-' . $get_term_id . ' bread-tag-' . $get_term_slug . '">' . $get_term_name . '</span>';

        } elseif ( is_day() ) {

            // Day archive

            // Year link
            echo '<a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link( get_the_time('Y') ) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a>';
            echo $separator;

            // Month link
            echo '<a class="bread-month bread-month-' . get_the_time('m') . '" href="' . get_month_link( get_the_time('Y'), get_the_time('m') ) . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</a>';
            echo $separator;

            // Day display
            echo '<span class="bread-current bread-' . get_the_time('j') . '"> ' . get_the_time('jS') . ' ' . get_the_time('M') . ' Archives</span>';

        } else if ( is_month() ) {

            // Month Archive

            // Year link
            echo '<a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link( get_the_time('Y') ) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a>';
            echo '<li class="separator separator-' . get_the_time('Y') . '"> ' . $separator . ' </li>';

            // Month display
            echo '<span class="bread-month bread-month-' . get_the_time('m') . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</span>';

        } else if ( is_year() ) {

            // Display year archive
            echo '<span class="bread-current bread-current-' . get_the_time('Y') . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</span>';

        } else if ( is_author() ) {

            // Auhor archive

            // Get the author information
            global $author;
            $userdata = get_userdata( $author );

            // Display author name
            echo '<span class="bread-current bread-current-' . $userdata->user_nicename . '" title="' . $userdata->display_name . '">' . 'Author: ' . $userdata->display_name . '</span>';

        } else if ( get_query_var('paged') ) {

            // Paginated archives
            echo '<span class="bread-current bread-current-' . get_query_var('paged') . '" title="Page ' . get_query_var('paged') . '">'.__('Page') . ' ' . get_query_var('paged') . '</span>';

        } else if ( is_search() ) {

            // Search results page
            echo '<span class="bread-current bread-current-' . get_search_query() . '" title="Search results for: ' . get_search_query() . '">Rezultatele cautarii: ' . get_search_query() . '</span>';

        } elseif ( is_404() ) {

            // 404 page
            echo '<span>' . 'Error 404' . '</span>';
        } else {
            echo '<span class="bread-current bread-' . $post->ID . '"> ' . single_post_title('', false) . '</span>';
        }
    }
}

/**
 * Change number of products that are displayed per page (shop page)
 */
if (isset($_GET['nb_posts'])) {
    $nbPosts = intval($_GET['nb_posts'] / 3 );
    update_option( 'woocommerce_catalog_rows', $nbPosts ?? 5 );
}
/**
 * Change number of products that are displayed per page (shop page)
 */

function validateFilterParams() {
    if (isset($_GET['afectiune']) || isset($_GET['ingredient']) || isset($_GET['price_range'])) {
        $arr = [
            'afectiuni' => buildFilterBoxParams($_GET['afectiune'] ?? null),
            'ingrediente' => buildFilterBoxParams($_GET['ingredient'] ?? null),
        ];
        return $arr;

    }
    return null;
}

function buildFilterBoxParams($params = null, $isPrice = false) {
    $finalFilter = '';
    if ($params == null) {
        return null;
    }
    foreach ($params as $key => $param) {
        if (is_numeric($param)) {
            $finalFilter .= '"' . $param . '", ';
        }
    }
    $finalFilter = substr($finalFilter, 0, -2);
    return $finalFilter;
}

add_action( 'woocommerce_product_query', 'so_27971630_product_query' );



function so_27971630_product_query( $q ){
    if (isset($_SESSION['campaign_enabled']) && !empty($_SESSION['campaign_products'])) {
        $productsIncluded = $_SESSION['campaign_products'];
    }
    $filters = validateFilterParams();
    if (!empty($filters)) {
        $meta_query = $q->get( 'meta_query' );
        foreach ($filters as $filterType => $values) {
            if (!empty($values)) {
                $meta_query[] = [
                    'key' => $filterType,
                    'value' => $values,
                    'compare' => 'LIKE'
                ];
            }
        }
        $q->set( 'meta_query', $meta_query );
    }
    if (isset($productsIncluded)) {
        $q->set('post__in', $productsIncluded);
    }
}

function new_after_loop_shop_per_page() {
    update_option( 'woocommerce_catalog_rows', 5 );
}
function getAllAfectiuni($type = 'string') {
    $afectiuni = getPostTitles($postType = 'afectiuni');
    $afectiuniResponse = '';
    $afectiuniJSON = [];
    foreach ($afectiuni as $afectiune) {
        $afectiuniResponse .= '"' . $afectiune['title'] . '",';
        $afectiuniJSON[$afectiune['title']] = $afectiune['url'];
    }
    if ($type == 'string') {
        return substr($afectiuniResponse,0, -1);
    } else {
        return $afectiuniJSON;
    }

}

function getMaxPrice() {
    $prices    = getFilteredPrices();
    if (empty($prices)) {
        return 0;
    }
    return $prices->max_price;
}

function getFilteredPrices() {
    global $wpdb;
    if (!isset(WC()->query->get_main_query()->query_vars)) {
        return [];
    }
    $args       = WC()->query->get_main_query()->query_vars;
    $tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
    $meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
    if ( ! is_post_type_archive( 'product' ) && ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
        $tax_query[] = array(
            'taxonomy' => $args['taxonomy'],
            'terms'    => array( $args['term'] ),
            'field'    => 'slug',
        );
    }
    foreach ( $meta_query + $tax_query as $key => $query ) {
        if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
            unset( $meta_query[ $key ] );
        }
    }
    $meta_query = new WP_Meta_Query( $meta_query );
    $tax_query  = new WP_Tax_Query( $tax_query );
    $search     = WC_Query::get_main_search_query_sql();
    $meta_query_sql   = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
    $tax_query_sql    = $tax_query->get_sql( $wpdb->posts, 'ID' );
    $search_query_sql = $search ? ' AND ' . $search : '';
    $sql = "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN (
				SELECT ID FROM {$wpdb->posts}
				" . $tax_query_sql['join'] . $meta_query_sql['join'] . "
				WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
				AND {$wpdb->posts}.post_status = 'publish'
				" . $tax_query_sql['where'] . $meta_query_sql['where'] . $search_query_sql . '
			)';
    $sql = apply_filters( 'woocommerce_price_filter_sql', $sql, $meta_query_sql, $tax_query_sql );
    return $wpdb->get_row( $sql ); // WPCS: unprepared SQL ok.
}

function getLastNoutati() {
    $noutati = get_posts(['post_type' => 'noutati', 'post_status' => 'publish', 'numberposts' => 2]);
    $noutatiArr = [];
    if (!empty($noutati)) {
        foreach ($noutati as $noutate) {
            $noutatiArr[] = [
                'id' => $noutate->ID,
                'name' => $noutate->post_title,
                'date' => get_the_date( 'd M Y', $noutate->ID )
            ];
        }
    }
    return $noutatiArr;
}

function getCategoryColor($productTerms) {
    return 'orange';
    //return get_field('culoare_categorie', reset($productTerms));
}
add_action( 'woocommerce_before_shop_loop', 'woocommerce_pagination', 10 );
add_action( 'woocommerce_after_shop_loop', 'new_after_loop_shop_per_page', 10 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
remove_action( 'woocommerce_account_content', 'woocommerce_output_all_notices', 5 );


function wphub_register_settings() {
    add_option( 'wphub_use_api', '1');
    add_option( 'wphub_api_callback', 'alpha');
    register_setting( 'default', 'wphub_use_api' );
    register_setting( 'default', 'wphub_api_callback' );
}
add_action( 'admin_init', 'wphub_register_settings' );

function wphub_register_options_page() {
    add_options_page('Page title', 'Optiuni Articole', 'manage_options', 'wphub-options', 'wphub_options_page');
}
add_action('admin_menu', 'wphub_register_options_page');

// Disable autoptimize on pages with the word "test" in the URL
add_filter('autoptimize_filter_noptimize','my_ao_noptimize',10,0);
function my_ao_noptimize() {
if (strpos($_SERVER['REQUEST_URI'],'despre-companie')!==false) {
return true;
} else {
return false;
}
}

function wphub_options_page() {
    ?>
    <div class="wrap">
        <h2>Optiuni Articole</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'default' ); ?>
            <h3>Setari Pagina Articole</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="wphub_use_api">Subtitlu pagina</label></th>
                    <td><textarea id="wphub_use_api" name="wphub_use_api" value="<?php echo get_option('wphub_use_api'); ?>" style="width:400px;height:200px;" ><?php echo get_option('wphub_use_api'); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function remove_post_type_page_from_search() {
    global $wp_post_types;
    $wp_post_types['post']->exclude_from_search = true;
}
add_action('init', 'remove_post_type_page_from_search');

function isMobile() {
    require_once('libs/MobileDetect/Mobile_Detect.php');
    $detect = new Mobile_Detect();
    return $detect->isMobile();
}
?>
<?php
/**
 * Extend WordPress search to include custom fields
 *
 * https://adambalee.com
 */

/**
 * Join posts and postmeta tables
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
 */
function cf_search_join( $join ) {
    global $wpdb;

    if ( is_search() ) {
        $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    }

    return $join;
}
add_filter('posts_join', 'cf_search_join' );

/**
 * Modify the search query with posts_where
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
 */
function cf_search_where( $where ) {
    global $pagenow, $wpdb;

    if ( is_search() ) {
        $where = preg_replace(
            "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
    }

    return $where;
}
add_filter( 'posts_where', 'cf_search_where' );

/**
 * Prevent duplicates
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
 */
function cf_search_distinct( $where ) {
    global $wpdb;

    if ( is_search() ) {
        return "DISTINCT";
    }

    return $where;
}
add_filter( 'posts_distinct', 'cf_search_distinct' );

function pd_search_posts_per_page($query) {
    if ( $query->is_search ) {
        $keyword = $query->get('s');
        $keyword = strtolower($keyword);
        if (strpos($keyword, 'thistle') !== false || strpos($keyword, 'silymarin') !== false) {
            $query->set('s', 'Silimarina');
        }
    	if (!is_admin()) {
		$query->set( 'post_type', array('product','page', 'post', 'ingrediente') );
   	}
        $query->set( 'posts_per_page', '100' );
    }
    return $query;
}
add_filter( 'pre_get_posts','pd_search_posts_per_page' );

add_filter( 'posts_orderby', function($q) {
        if ( ! is_search() || is_admin() ) return $q;
        return 'CASE alevia_posts.post_type WHEN "product" then 10 WHEN "post" THEN 9 WHEN "ingrediente" THEN 8 ELSE 5 END DESC, alevia_posts.post_title LIKE \'%' . get_search_query() . '%\' DESC';
});

// WEBBING removals for Google PageSpeed

remove_action( 'wp_head', 'rsd_link' );

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

add_filter('xmlrpc_enabled', '__return_false');

remove_action( 'wp_head', 'wlwmanifest_link' ) ;

function disable_pingback( &$links ) {
 foreach ( $links as $l => $link )
 if ( 0 === strpos( $link, get_option( 'home' ) ) )
 unset($links[$l]);
}
add_action( 'pre_ping', 'disable_pingback' );

// Unload Contact Form 7 from all pages

add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

// END WEBBING

add_theme_support( 'woocommerce', array(
    'thumbnail_image_width' => 200,
    'gallery_thumbnail_image_width' => 100,
    'single_image_width' => 500,
) );

function e12_remove_product_image_link( $html, $post_id ) {
    return preg_replace( "!<(a|/a).*?>!", '', $html );
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'e12_remove_product_image_link', 10, 2 );


function hasPromoCode($productId) {
    if (has_term(120, 'product_tag', $productId)) {
        return false;
    }

    $p = wc_get_product($productId);
    if ($p->is_on_sale()) {
        return true;
    }

    if (get_field('tip_promotie', $productId) !== 'dezactivat' && !empty(get_field('valoare_promotie', $productId))) {
        return true;
    }

    $productCategories = wp_get_post_terms($productId,['product_cat', 'arii_terapeutice']);
    foreach ($productCategories as $category) {
        if (get_field('tip_promotie', $category) !== 'dezactivat' && !empty(get_field('valoare_promotie', $category))) {
            return true;
        }
    }


    return false;
}

function getPromoCode($productId) {
	
    $p = wc_get_product($productId);
    if ($p->is_on_sale()) {
        $x = $p->get_regular_price('edit');
        $y = $p->get_sale_price('edit');
        return '-' . abs(round((($y - $x) / $x) * 100, 0)) . '%';
    }

    if (get_field('tip_promotie', $productId) !== 'dezactivat' && !empty(get_field('valoare_promotie', $productId))) {
        return formatPromoMessage(get_field('tip_promotie', $productId), get_field('valoare_promotie', $productId));
    }

    $productCategories = wp_get_post_terms($productId,['product_cat', 'arii_terapeutice']);
    foreach ($productCategories as $category) {
        if (get_field('tip_promotie', $category) !== 'dezactivat' && !empty(get_field('valoare_promotie', $category))) {
            return formatPromoMessage(get_field('tip_promotie', $category), get_field('valoare_promotie', $category));
        }

    }

    return false;
}

function formatPromoMessage($type, $value) {
    switch ($type) {
        case 'procent' :
            return '-' . $value . '%';
        case 'grup' :
            return $value . ' <span>Gratis<span>';
        case 'text' :
            return $value;
        case 'dezactivat' :
        default :
            return '';
    }
}

function getPromoText($productId) {
    if (get_field('tip_promotie', $productId) !== 'dezactivat' && !empty(get_field('descriere_extra', $productId))) {
        return formatPromoMessageDescription(get_field('tip_promotie', $productId), get_field('descriere_extra', $productId));
    }

    $productCategories = wp_get_post_terms($productId,['product_cat', 'arii_terapeutice']);
    foreach ($productCategories as $category) {
        if (get_field('tip_promotie', $category) !== 'dezactivat' && !empty(get_field('descriere_extra', $category))) {
            return formatPromoMessageDescription(get_field('tip_promotie', $category), get_field('descriere_extra', $category));
        }

    }

    return false;
}

function formatPromoMessageDescription($type, $value) {
    return $value;
}
// Exclude some products from global role-based rules
function wcfad_exclude_bulk_product_ids($excluded) {
    return get_field('produse_excluse', 5);
}
add_filter( 'wcfad_exclude_bulk_product_ids', 'wcfad_exclude_bulk_product_ids' );


function updateDiscount($post_id, $percent) {
    update_field( 'tip_promotie', 'dezactivat', $post_id );
    update_field( 'valoare_promotie', '0', $post_id );
}


function exportProducts() {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1
    );

    $loop = new WP_Query( $args );
$arr = [];
    while ( $loop->have_posts() ) : $loop->the_post();
        global $product;
        $arr[] = [
                'name' => get_the_title(),
                'sku' => $product->get_sku(),
                'grupa_produs' => getCatName(wp_get_post_terms(get_the_ID(),'product_cat')),
                'denumire_produs' => get_the_title(),
                'afectiune' => formatField(get_field('afectiuni')),
                'produse_similare' => formatField(get_field('produse_similare')),
                'produse_similare_sku' => getProductSkus(get_field('produse_similare')),
                'ingrediente' => formatField(get_field('ingrediente')),
                'descriere_produs' => strip_tags(get_field('descriere')),
                'descriere_produs_short' => get_the_content(),
                'arii_terapeutice' => getAriiTerapeuticeName(wp_get_post_terms(get_the_ID(),'arii_terapeutice'))
        ];
    endwhile;
print_r(json_encode($arr));
    wp_reset_query();
}

function formatField($field) {
    $returnStr = [];
    if (!empty($field)) {
        foreach ($field as $subfield) {
            $returnStr[] = $subfield->post_title;
        }
    }

    return implode(',', $returnStr);
}
function getProductSkus($field) {
    $returnStr = [];
    if (!empty($field)) {
        foreach ($field as $subfield) {
            $product = wc_get_product( $subfield->ID );
            $returnStr[] = $product->get_sku();
        }
    }

    return implode(',', $returnStr);
}

function getCatName($field) {
    $returnStr = '';
    if (!empty($field)) {
        foreach ($field as $subfield) {
            $returnStr = $subfield->name;
        }
    }
    return $returnStr;
}

function getAriiTerapeuticeName($field) {
    $returnStr = [];
        if (!empty($field)) {
            foreach ($field as $subfield) {
                $returnStr[] = $subfield->name;
            }
        }
        return implode(',', $returnStr);
}

function resetProductTags()
{
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
    );

    $loop = new WP_Query( $args );

    while ( $loop->have_posts() ) : $loop->the_post();
        update_field('tip_promotie', 'dezactivat');
    endwhile;

    wp_reset_query();
}

function determineProductAddToCartText($product)
{
    $productAddToCartText = 'Adaugă în coș';
    switch ($product->get_stock_status()):
        case 'instock': $productAddToCartText = 'Adaugă în coș'; break;
        case 'onbackorder': $productAddToCartText = 'Disponibil la precomandă'; break;
        default: $productAddToCartText = 'Stoc epuizat';
    endswitch;
    return $productAddToCartText;
}

// .webp patch

add_filter('mod_rewrite_rules', function($rules) {
	return str_replace(
		"RewriteCond %{REQUEST_FILENAME} !-d\n",
		"RewriteCond %{REQUEST_FILENAME} !-d\nRewriteCond %{REQUEST_URI} !(.*webp)$\n",
		$rules
	);
});

// hide ACF notification

add_filter( 'acf/the_field/escape_html_optin', '__return_true' );
add_filter( 'acf/admin/prevent_escaped_html_notice', '__return_true' );

/**
 * Allow editing the robots.txt & htaccess data.
 *
 * @param bool Can edit the robots & htacess data.
 */

 add_filter( 'rank_math/can_edit_file', '__return_true' );

 /*Notificări recenzii */

 function new_comment_moderation_recipients( $emails, $comment_id ) { 
    return array( 'development@webbing.ro, luiz.iordachioaia@alevia.com.ro, cristina.enache@alevia.com.ro, camelia.nastasi@alevia.com.ro' );
}
add_filter( 'comment_moderation_recipients', 'new_comment_moderation_recipients', 24, 2 );
add_filter( 'comment_notification_recipients', 'new_comment_moderation_recipients', 24, 2 );

/*Transport gratuit pentru produse dintr-o anumită categorie - Sameday */

add_action('curiero_overwrite_sameday_shipping', 'curiero_overwrite_sameday_shipping_for_product_categories', 10, 1);
add_action('curiero_overwrite_sameday_easybox_shipping', 'curiero_overwrite_sameday_shipping_for_product_categories', 10, 1);
function curiero_overwrite_sameday_shipping_for_product_categories($args)
{
    foreach (WC()->cart->get_cart() as $item) {
        if (has_term([119], 'product_cat', $item['product_id'])) {
            $args['cost'] = 0;
            $args['label'] = rtrim($args['label'], ': Gratuit') . ': Gratuit';
            return $args;
        }
    }
    return $args;
}

/*Transport gratuit pentru produse dintr-o anumită categorie - Cargus */

add_action('curiero_overwrite_cargus_shipping', 'curiero_overwrite_cargus_shipping_for_product_categories', 10, 1);
function curiero_overwrite_cargus_shipping_for_product_categories($args)
{
    foreach (WC()->cart->get_cart() as $item) {
        if (has_term([119], 'product_cat', $item['product_id'])) {
            $args['cost'] = 0;
            $args['label'] = rtrim($args['label'], ': Gratuit') . ': Gratuit';
            return $args;
        }
    }
    return $args;
}

/*Eliminare livrare Sameday, păstrare doar easybox */

add_filter('woocommerce_package_rates', function (array $rates): array {
    if (isset($rates['sameday'])) unset($rates['sameday']);
    return $rates;
  }, 99, 1);


  /**
 * @snippet       Bulk Remove Product Categories @ WooCommerce Products Admin
 * @how-to        businessbloomer.com/woocommerce-customization
 * @author        Rodolfo Melogli, Business Bloomer
 * @compatible    WooCommerce 8
 * @community     https://businessbloomer.com/club/
 */
 
add_action( 'woocommerce_product_bulk_edit_start', 'bbloomer_bulk_edit_remove_product_category' );
 
/* procedura eliminare produse din categorie specifică */

function bbloomer_bulk_edit_remove_product_category() {
   ?>    
   <div class="inline-edit-group">
      <label class="alignleft">
         <span class="title">Delete Cat</span>
         <span class="input-text-wrap">
            <?php wc_product_dropdown_categories( [ 'class' => 'remove_product_cat', 'name' => 'remove_product_cat', 'show_option_none' => 'Select product category to be removed', 'value_field' => 'term_id' ] ); ?>
         </span>
      </label>
   </div>         
   <?php
}
 
add_action( 'woocommerce_product_bulk_edit_save', 'bbloomer_bulk_edit_remove_product_category_save', 9999 );
  
function bbloomer_bulk_edit_remove_product_category_save( $product ) {
   $post_id = $product->get_id();    
   if ( isset( $_REQUEST['remove_product_cat'] ) ) {
      $cat_to_remove = $_REQUEST['remove_product_cat'];
      $categories = $product->get_category_ids();
      if ( ! in_array( $cat_to_remove, $categories ) ) return;
      if ( ( $key = array_search( $cat_to_remove, $categories ) ) !== false ) {
         unset( $categories[$key] );
      }
      $product->set_category_ids( $categories );
      $product->save();
   }
}
