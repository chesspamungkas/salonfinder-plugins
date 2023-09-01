<?= $this->render('views/shortcode/listBranches/title') ?>
<section id="search-result-body">
  <?php foreach($outlets as $outlet): ?>
    <div class="repeatdiv outlets">
      <div class="repeatleft outletsImage">
        <a href="<?= $merchantLink ?>" class="" title="">
          <?= $this->render('views/shortcode/listBranches/outletImage',['outletID'=>$outlet['acfID']]) ?>
        </a>
      </div>
      <div class="repeatright outletInformation <?= $outlet["promotion"]?'has-promotion':''; ?>">
        <div class="container">
          <div class="row">
            <div class="repeatright_title col-md-12 col-sm-12">
              <div class="row">
                <div class="col-md-9 col-xs-12">
                  <h3>
                    <a href="<?= $merchantLink ?>">
                      <?= get_field('outlet_brandname', $outlet['acfID']); ?>
                    </a>
                  </h3>
                </div>
                <div class="col-md-3 col-xs-12">
                  <span class="product_promotion">promotion</span>
                </div>
                <div class="col-md-12 col-xs-12">
                  <p class="outlet_address"><?= get_field('outlet_address', $outlet['acfID']); ?>, Singapore <?= $outlet['postalCode'] ?></p>
                </div>
              </div>
            </div> <!-- end repeatright_title -->
          </div> <!-- end row -->
          <?php foreach($outlet['products'] as $i=>$product): ?>
            <div class="row repeatrightprice rightprice_<?php echo $i; ?> <?php if ( $i >= 4 ) { echo 'rightprice_none'; } ?>">
              <div class="col-md-4 col-xs-12">
                <a href="<?= get_permalink($product->get_id()) ?>&varid=<?php echo $product->variation_id; ?>" id="product_<?= $product->variation_id ?>"><?= change_promotion_title($product->get_title(), $product->variation_id); ?></a>
              </div> <!-- end col-md-4 col-xs-12 -->
              <div class="col-md-2 col-xs-3">
                <span class="duration <?php if ( get_post_meta( $product->ID, 'duration', true ) ) { echo 'dur_active'; } ?>"><?php if ( get_post_meta( $product->ID, 'duration', true ) ) { echo get_post_meta( $product->ID, 'duration', true ); } ?></span>
              </div> <!-- end col-md-2 col-xs-3 -->
              <div class="col-md-4 col-xs-6">
                <?php if($product->is_on_sale()): ?>
                  <strong><span class="regular_price">S$<?= $product->get_regular_price(); ?></span></strong>
                  <strong><span class="sale_price">S$<?= $product->get_sale_price(); ?></span></strong>
                <?php else: ?>
                  <strong><span style="color:#000">S$<?= $product->get_regular_price(); ?></span></strong>
                <?php endif; ?>
              </div> <!-- end col-md-4 col-xs-6 -->
              <div class="col-md-2 col-xs-3">
                <div class="buy-details">
                  <form class="cart ng-pristine ng-valid allpro-duct" action="<?= $product->add_to_cart_url(); ?>" method="post" enctype="multipart/form-data">
                    <button type="submit" class="single_add_to_cart_button button alt">BUY</button>
                    <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
                    <input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
                    <input type="hidden" name="variation_id" class="variation_id" value="<?php echo $variation_ID ?>" />
                  </form>
                </div> <!-- end buy-details -->
              </div> <!-- end col-md-2 col-xs-3 -->
            </div> <!-- end row repeatrightprice -->
          <?php endforeach; ?>

        </div> <!-- end container -->
      </div> <!-- end repeatright -->
      <div style="clear:both"></div>
    </div>  <!-- end repeatdiv -->
  <?php endforeach; ?>
</section>

