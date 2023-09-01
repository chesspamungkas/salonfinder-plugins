<div class="blog-wrap">
  <div class="wrap blog-perk-slick">
    <div class="blog-perk-item naveen">
      <a href="<?= $productCache['url'] ?>">
				<img class="blog-image" alt="<?= $productCache['image']['alt'] ;?>" src="<?= $productCache['image']['src'] ;?>">
      </a>
      <div class="container rm-padding rm-margin">
          <div class="row rm-padding rm-margin">
              <div class="col">
                  <h5 class="author"> <?= $productCache['merchant']['id'] ; ?></h5>
              </div>
          </div>
          <div class="row rm-padding rm-margin">
              <div class="col">
                  <h4 class="slic-perk">
                      <a href="<?= $productCache['url'] ?>">
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
    </div>
</div>