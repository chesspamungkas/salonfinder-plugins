<?php if($imageObj = get_field('image', $outletID)):?>
  <img src="<?= $imageObj['sizes']['dvsf-stardard']?>" alt="<?= $imageObj['alt'] ?>" />
<?php else: ?>
  <?php $ad_logo = get_field('outlet_featured_image', $outletID); ?>
  <?php $ad_alt = get_field('outlet_image_alt', $outletID); ?>
  <img src="<?= $ad_logo ?>" alt="<?= $ad_alt ?>" />
<?php endif; ?>
