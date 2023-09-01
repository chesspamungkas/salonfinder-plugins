<?php 
namespace SF\core;

class Ulti {
  static public function render($path, $args = []) {

    $viewPath = str_replace( '\\', DIRECTORY_SEPARATOR, $path );
    $filePath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewPath . '.php';    

    // ensure the file exists
    if ( !file_exists( $filePath ) ) {
      return '';
    }

    // Make values in the associative array easier to access by extracting them
    if ( is_array( $args ) ){
      extract( $args );
    }

    // buffer the output (including the file is "output")
    ob_start();
      include $filePath;
    return ob_get_clean();
  }

  /**
   * Insert an attachment from an URL address.
   *
   * @param  String $url
   * @param  Int    $parent_post_id
   * @return Int    Attachment ID
   */
  static public function insert_image_from_url($url, $description = '', $parent_post_id = null) {
    $ulti = new Ulti();
    $oldImage = $ulti->get_post_by_meta([
      'meta_key'=>Constants::ATTACHEMENT_FK,
      'meta_value'=>$url
    ]);
    if($oldImage) {
      return $oldImage->ID;
    }


    if( !class_exists( 'WP_Http' ) )
      include_once( ABSPATH . WPINC . '/class-http.php' );
    $http = new \WP_Http();
    $response = $http->request( $url );
    // if( !($response instanceof \WP_Error) && $response['response']['code'] != 200 ) {
    if( !is_wp_error($response) && $response['response']['code'] != 200 ) {
      return false;
    }
    
    $fileName = strtolower(str_replace('"', "", str_replace('filename=', '', $response['headers']['content-disposition'])) );
    if(empty($fileName)) {
      $fileName = basename($url);
    }
    $upload = wp_upload_bits( $fileName, null, $response['body'] );

    if( !empty( $upload['error'] ) ) {
      return false;
    }
    $file_path = $upload['file'];
    $file_name = basename( $file_path );
    $file_type = wp_check_filetype( $file_name, null );
    $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
    $wp_upload_dir = wp_upload_dir();
    $post_info = array(
      'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
      'post_mime_type' => $file_type['type'],
      'post_title'     => $attachment_title,
      'post_content'   => $description,
      'post_status'    => 'inherit',
    );
    // Create the attachment
    $attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
    // Include image.php
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id,  $attach_data );
    add_post_meta($attach_id, Constants::ATTACHEMENT_FK, $url, true);

    return $attach_id;
  }

  function get_post_by_meta( $args = array()) {
   
    // Parse incoming $args into an array and merge it with $defaults - caste to object ##
    $args = ( object )wp_parse_args( $args );
   
    // grab page - polylang will take take or language selection ##
    $args = array(
        'meta_query'        => array(
            array(
                'key'       => $args->meta_key,
                'value'     => $args->meta_value
            )
        ),
        'post_type'         => 'attachment',
        'posts_per_page'    => '1'
    );
   
    // run query ##
    $posts = get_posts( $args );
   
    // check results ##
    if ( ! $posts || is_wp_error( $posts ) ) return false;
   
    // test it ##
    #pr( $posts[0] );
   
    // kick back results ##
    return $posts[0];
  }
}