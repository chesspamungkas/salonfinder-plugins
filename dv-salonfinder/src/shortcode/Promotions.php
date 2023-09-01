<?php
namespace SF\shortcode;
use SF\core\IShortCode;
use SF\core\Ulti;

class Promotions implements IShortCode {
  public function initShortCode() {

  }

  public function initCron() {
    
  }

  private function cachePosts() {
    $cacheJson = [];
    $args = array(
      'post_type'      => 'product',
      'posts_per_page' => 100,
      'post_status'    => 'publish',
      'post_parent'	 => 0,
      'orderby'		 => 'post_date',
      'order'			 => 'DESC',
      'meta_query'     => array(
        'relation' => 'OR',
        array( // Simple products type
          'key'           => '_sale_price',
          'value'         => 0,
          'compare'       => '>',
          'type'          => 'numeric'
        ),
        array( // Variable products type
          'key'           => '_min_variation_sale_price',
          'value'         => 0,
          'compare'       => '>',
          'type'          => 'numeric'
        )
      )
    );
    
    $productQuery = new WP_Query( $args );
    foreach($productQuery->posts as $post) {
      $productCache = [
        'image'=>[],
        'merchant'=> [],
      ];
      $product = wc_get_product($post->ID);
      $productCache['title'] = apply_filter($product->post_title, 'post_title');
      $productCache['url'] = get_permalink($product);
      if ( has_post_thumbnail($post->ID) ) {
        $imageArray = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'dvsf-promotion' );     
        if($imageArray) {
          $productCache['image'] = [];
          $productCache['image'] = $imageArray;
          $productCache['image']['alt'] = get_the_title(get_post_thumbnail_id($post->ID));
        }        
      }
      if ($product->product_type == 'variable') {
        $variations = $product->get_available_variations();
        $childProductIDs = $variations->get_visible_children();
        if(count($childProductIDs)) {
          $childProductID = $childProductIDs[0];
          $merchants = get_the_terms($product, 'merchant');
          if(count($merchants)) {
            $merchant = $marchant[0];
            $productCache['merchant'] = [
              'id'=> $merchant->term_id,
              'name'=> $merchant->name,
              'slug'=>$merchant->slug,
            ];
          }
        }
        $productCache['salesFrom'] = get_post_meta( $childProductIDs, '_sale_price_dates_from', true );
        $productCache['salesTo'] =  get_post_meta( $childProductIDs, '_sale_price_dates_to', true );
        $productCache['promoName'] =  get_post_meta( $childProductIDs, 'promoName', true );
        $productCache['promoTerms'] =  get_post_meta( $childProductIDs, 'promoTerms', true );
      }
    }
  }
  private function toHTML() {

  }
}

function on_sale_function($atts, $content) {


$width = 288;
$height = 160;
$crop = true;
  $wp_query = new WP_Query( $args );
  $advertisers = array();
  $first_array = array();
  $product_ids = array();

  ob_start();
  $first_count = 0;
 
  // if ( $wp_query->have_posts() ) :
?>
<div class="blog-wrap">
  <div class="wrap blog-perk-slick">
    <?php 
    while ( $wp_query->have_posts() ) : $wp_query->the_post();
        $parent_product = get_the_ID();
		if ( count( $advertisers ) > 10 ) {
			break;
		}
	?>

      <?php	
        $product_s = wc_get_product( get_the_ID() );
        if ($product_s->product_type == 'variable') {
            $args = array(
                'post_parent' => $parent_product,
                'post_type'   => 'product_variation',
                'numberposts' => -1,
            );
            $variations = $product_s->get_available_variations();

            $i = 0;
            foreach ($variations as $variation) {
                $variant_id = $variation['variation_id'];
                $postId = $variant_id;
                if ( has_post_thumbnail($parent_product) ) {
                    $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($parent_product), 'custom-featured-image' );
                    $image_alt = get_the_title(get_post_thumbnail_id($parent_product));
                    $large_image_url = custom_image_resize($large_image_url[0], $width, $height , $crop, $single = true);

				}
                $attribute_pa_branch = get_post_meta($postId, 'attribute_pa_branch', true);
                $attribute_pa_advertiser = get_post_meta($postId, 'attribute_pa_advertiser', true);
                //$branch_name = "".ucfirst(implode(" ",explode("-",trim(str_replace($attribute_pa_branch."-","",$attribute_pa_branch)))));
                $outlets_name = "".ucfirst(implode(" ",explode("-",trim(str_replace($attribute_pa_advertiser."-","",$attribute_pa_advertiser)))));
                $f_branch = str_replace($attribute_pa_advertiser,"",$attribute_pa_branch);
                $branch = ltrim($f_branch, '-');
                $par_ad = "";
                $par_out = array();
                $par_merchant = get_the_terms($parent_product, 'merchant');
                foreach ( $par_merchant as $item ) {
                  if ( $item->parent == 0 ) {
                    $par_ad = $item->name;
                    $par_id = $item->term_id;
                    $par_slug = $item->slug;
                  } else {
                    array_push($par_out, $item->slug);
                  }
                }

                $cur_par_out = $par_out[0];
                $par_out_nums = count($par_out) - 1;
                $cur_out = get_term_by( 'slug', $cur_par_out, 'merchant' );

				$from = get_post_meta( $postId, '_sale_price_dates_from', true );
				$to = get_post_meta( $postId, '_sale_price_dates_to', true );
				$current = time();
			
                if( get_post_meta($postId, '_sale_price', true) && $current >= $from && $current <= $to && $par_ad ) {
				// if($cur_out->slug == $branch){
					if(in_array($par_ad, $advertisers))
					{
						break;
					}
					else
					{
						$advertisers[] = $par_ad;
					}
				// if( in_array( $parent_product, $product_ids ) ) {
					// break;
				// }
				// else {
					// $product_ids[] = $parent_product;
				// }
      ?>
             <div class="blog-perk-item naveen">
                <a href="<?php echo get_permalink($parent_product); if (!empty($variant_id)) {echo '?varid='.$variant_id;} ?>">
				<img class="blog-image" alt="<?php echo $image_alt;?>" src="<?php echo $large_image_url;?>">
				</a>
                <div class="container rm-padding rm-margin">
                    <div class="row rm-padding rm-margin">
                        <div class="col">
                            <h5 class="author"> <?php echo $par_ad; ?></h5>
                        </div>
                    </div>
                    <div class="row rm-padding rm-margin">
                        <div class="col">
                            <h4 class="slic-perk">
                                <a href="<?php echo get_permalink($parent_product);  if (!empty($variant_id)) {echo '?varid='.$variant_id;}  ?>">
                                <?php  
                                    $title = get_the_title($parent_product);  
                                    echo change_promotion_title($title, $variant_id);
                                ?>
                                </a>
                            </h4>
                        </div>
                    </div>
                    <div class="row rm-padding rm-margin">
                        <?php
                            $up = get_post_meta($postId, '_regular_price', true);
                            $sp = get_post_meta($postId, '_sale_price', true);
                            $diff = $up-$sp;
                            $percent = round( ( $diff * 100 ) / $up );
                        ?>
                        <div class="col-md-5 col-xs-5 rm-padding discount-percent">
                            <div class="discounted">
                                <?php echo '-' . $percent . '%'; ?>
                            </div>
                        </div>
                        <div class="col-md-7 col-xs-7 rm-padding service-price">
                            <!-- S$<?php //echo number_format( get_post_meta($postId, '_regular_price', true), 2 ); ?> -->
                            <?php //echo admin_get_variation_price( $variant_id ); ?>
                            <div class="promo-price">
                                <span class="regular-price">S$<?php echo number_format( $up, 2 ); ?></span><br/><span class="sale-price">S$<?php echo number_format( $sp, 2 ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- <div class="blog-content">
                  <div class="fixTextLength">
                    <h4 class="slic-perk"><a href="<?php //echo get_permalink($parent_product);  if (!empty($variant_id)) {echo '?varid='.$variant_id;}  ?>">
                      <?php  //$title = get_the_title($parent_product);  echo change_promotion_title($title, $variant_id);?>
                      </a>
                        <div class="a_serviceprice">
                           
                              <?php 
                               //echo admin_get_variation_price( $variant_id );
                                ?>
                            
                        </div>
                      </h4>

                  </div>
                </div>  -->
              
              </div>
            <?php
                    $first_count++;
                    $first_array[] = get_the_ID();
            // }
                }
            }
        }
    endwhile; 
		
    if( $first_count < 10 ) {
        $limit = 10 - $firts_count;
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'post_parent'	 => 0,
            'posts_per_page' => 50,
            'orderby'		 => 'post_date',
            'order'			 => 'DESC',
            'post__not_in'   => $first_array
        );
    
        $wp_query = new WP_Query( $args );	 
        
        while ( $wp_query->have_posts() ) : $wp_query->the_post(); 
        
            $parent_product = get_the_ID(); 
        
            if ( count( $advertisers ) > $limit ) {
                break;
            }
                
            $product_s = wc_get_product( get_the_ID() );
            if ($product_s->product_type == 'variable') {
              
                $args = array(
                    'post_parent' => $parent_product,
                    'post_type'   => 'product_variation',
                    'numberposts' => -1,
                );
                $variations = $product_s->get_available_variations();

                $i = 0;
                foreach ($variations as $variation) {
                    $variant_id = $variation['variation_id'];
                    $postId = $variant_id;
                    if ( has_post_thumbnail($parent_product) ) {
                        $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($parent_product), 'custom-featured-image' );
                        $image_alt = get_the_title(get_post_thumbnail_id($parent_product));
                        $large_image_url = custom_image_resize($large_image_url[0], $width, $height , $crop, $single = true);
                    }
                    $attribute_pa_branch = get_post_meta($postId, 'attribute_pa_branch', true);
                    $attribute_pa_advertiser = get_post_meta($postId, 'attribute_pa_advertiser', true);
                    //$branch_name = "".ucfirst(implode(" ",explode("-",trim(str_replace($attribute_pa_branch."-","",$attribute_pa_branch)))));
                    $outlets_name = "".ucfirst(implode(" ",explode("-",trim(str_replace($attribute_pa_advertiser."-","",$attribute_pa_advertiser)))));
                    $f_branch = str_replace($attribute_pa_advertiser,"",$attribute_pa_branch);
                    $branch = ltrim($f_branch, '-');
                    $par_ad = "";
                    $par_out = array();
                    $par_merchant = get_the_terms($parent_product, 'merchant');
                    foreach ( $par_merchant as $item ) {
                        if ( $item->parent == 0 ) {
                            $par_ad = $item->name;
                            $par_id = $item->term_id;
                            $par_slug = $item->slug;
                        } else {
                            array_push($par_out, $item->slug);
                        }
                    }

                    $cur_par_out = $par_out[0];
                    $par_out_nums = count($par_out) - 1;
                    $cur_out = get_term_by( 'slug', $cur_par_out, 'merchant' );


                // if( get_post_meta($postId, '_sale_price', true) ) {
                    // if($cur_out->slug == $branch) {
                    if(in_array($par_ad, $advertisers) || $par_ad == '' ) {
                        break;
                    } else {
                        $advertisers[] = $par_ad;
                    }
                    // if( in_array( $parent_product, $product_ids ) ) {
                        // break;
                    // }
                    // else {
                        // $product_ids[] = $parent_product;
                    // }
      ?>
             <div class="blog-perk-item naveen">
                <a href="<?php echo get_permalink($parent_product); ?>"> <img class="blog-image" alt="<?php echo $image_alt;?>" src="<?php echo $large_image_url;?>"> </a>
                <div class="container rm-margin">
                    <div class="row rm-padding rm-margin">
                        <div class="col">
                            <h5 class="author"> <?php echo $par_ad; ?></h5>
                        </div>
                    </div>
                    <div class="row rm-padding rm-margin">
                        <div class="col">
                            <h4 class="slic-perk">
                                <a href="<?php echo get_permalink($parent_product); ?>">
                                <?php  
                                    $title = get_the_title($parent_product);  
                                    echo change_promotion_title($title, $variant_id);
                                ?>
                                </a>
                            </h4>
                        </div>
                    </div>
                    <div class="row rm-padding rm-margin">
                        <div class="col-md-5 col-xs-5 rm-padding">&nbsp;</div>
                        <div class="col-md-7 col-xs-7 rm-padding service-price">
							<div class="promo-price">
                            <?php 
                                if ( get_post_meta($postId , '_sale_price', true) ) {
                                    echo '<span class="regular-price">S$' . number_format( get_post_meta($postId, '_regular_price', true), 2 ) ."</span></br>";
                                    echo '<span class="sale-price">S$' . number_format( get_post_meta($postId, '_sale_price', true), 2 ) ."</span>";
                                } else {
                                    echo '<span class="normal-price">S$' . number_format( get_post_meta($postId, '_regular_price', true), 2 ) ."</span>";
                                }
                            ?>
							</div>
                        </div>
                    </div>
                </div>
                <!--div class="blog-content">
                  <div class="fixTextLength">
                    <h4 class="slic-perk"><a href="<?php //echo get_permalink($parent_product); ?>">
                      <?php  //$title = get_the_title($parent_product);  echo change_promotion_title($title, $variant_id);?>
                      </a>
                        <div class="a_serviceprice">
                           
                              <?php 
                            //    $price = "";
                            //     if ( get_post_meta($postId , '_sale_price', true) )
                            //             $price .= '<span class="regular_price">S$' . number_format( get_post_meta($postId, '_regular_price', true), 2 ) ."</span>";
                                      
                            //           ?>
                                    
                            //           <?php 
                            //           if ( get_post_meta($postId, '_sale_price', true) ) {
                            //       $price .= '<span class="sale_price">S$' . number_format( get_post_meta($postId, '_sale_price', true), 2 ) ."</span>";
                            //           } else {
                                      
                            //       if ( get_post_meta($postId, '_regular_price', true) ) {
                            //          $price .= '<span class="sale_price">S$' . number_format( get_post_meta($postId, '_regular_price', true), 2 ) ."</span>";
                            //       } else {
                            //         $price .= '<span class="sale_price">S$' . number_format( get_post_meta($postId, '_price', true), 2 ) ."</span>";
                            //       }
                            //           }
                            //     // echo  $price;
							// 	echo admin_get_variation_price($postId);
                                ?>
                            
                        </div>
                      </h4>

                  </div>
                </div--> 
              
              </div>
            <?php
                }
            }

        endwhile;
    }
	    
  ?>
		   

  </div>
</div>



<div id="homeTop" ng-controller="lightboxCtrl" class="popUp ng-scope" style="display:none;">
    <section class="newsletter" id="topNewsletter" alt="exclusive beauty tips from singapore's beauty magazine" style="display: block;">
    <a href="#" onclick="return false;" ng-click="closeThis()" class="closeLightBtn"></a>
        

<?php echo do_shortcode('[newsletter-form postid='.$originalID.' location="home-beauty" title="GET THE <br /> BEST BEAUTY DEALS!" button-text="SUBSCRIBE" facebook-subscribe="yes"]Receive exclusive discounts only DV subscribers will get by being on our mailing list![/newsletter-form]');?>

    </section>
</div>


<script type="text/javascript">




    jQuery(document).ready(function($) {
/*
      var element = $('.blog-perk-slick:not(.products)')
      $(element).slick({
          infinite: true,
          slidesToShow: 5,
          slidesToScroll: 5,
          autoplay: false,
          autoplaySpeed: 5000,
          variableWidth: true,
          accessibility: false,
          responsive: [{
                  breakpoint: 980,
                  settings: {
                      slidesToShow: 1,
                      slidesToScroll: 1,
                      variableWidth: true,
                      infinite: true,
                      centerMode: false,
                      autoplay: false,
                      accessibility: false,
                  }
              },
              {
                  breakpoint: 640,
                  settings: {
                      slidesToShow: 1,
                      slidesToScroll: 1,
                      variableWidth: true,
                      infinite: false,
                      centerMode: false,
                      autoplay: false,
                      arrows: false,
                      accessibility: false,
                  }
              },
          ]
      });

    */



      var element = $('.blog-perk-slick:not(.products)')
      $(element).slick({
          infinite: false,
          slidesToShow: 4,
          slidesToScroll: 4,
          autoplay: false,
          autoplaySpeed: 5000,
          variableWidth: true,
          responsive: [{
                  breakpoint: 980,
                  settings: {
                      slidesToShow: 1,
                      slidesToScroll: 1,
                      variableWidth: true,
                      infinite: false,
                      centerMode: false,
                      autoplay: false,
                  }
              },
              {
                  breakpoint: 640,
                  settings: {
                      slidesToShow: 1,
                      slidesToScroll: 1,
                      variableWidth: true,
                      infinite: false,
                      centerMode: false,
                      autoplay: false,
                      arrows: true,
                  }
              },
              {
                  breakpoint: 414,
                  settings: {
                      slidesToShow: 1,
                      slidesToScroll: 1,
                      variableWidth: true,
                      infinite: false,
                      centerMode: false,
                      autoplay: true,
                      arrows: false,
                  }
              },
          ]
      });
  

      var reinitNewsletter = function() {
        angular.element(document.body).injector().invoke(function($compile) {
          var ele = $('.blog-perk-item.perk-form form').first()
          if(ele.length == 0) return;
          var scope = angular.element(ele).scope();
          $compile(ele)(scope);
          $(ele).addClass('initialise')
        });
      }

      $(element).on('init', reinitNewsletter)
      $(element).on('reInit', reinitNewsletter)
      $(element).on('swipe', function() {
        var ele = $('.blog-perk-item.perk-form form').first()
        if(!$(ele).hasClass('initialise')) {
          reinitNewsletter()
        }
      })


    });

  </script>
  <style media="screen">
    .blog-perk-item .blog-content h4.slic-perk{
      height: auto;
    }
  </style>
<?php 
	// endif;
  wp_reset_query();
  $out = ob_get_contents();
  ob_end_clean();
  return $out;
}