<form class="cart ng-pristine ng-valid allpro-duct" action="<?= $product->add_to_cart_url(); ?>" method="post" enctype="multipart/form-data">
  <button type="submit" class="single_add_to_cart_button button alt">BUY</button>
  <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
  <input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
  <input type="hidden" name="variation_id" class="variation_id" value="<?php echo $variation_ID ?>" />
</form>