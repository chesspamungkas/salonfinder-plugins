<?php
namespace SF\models;

use SF\core\TermModels;

class CollectionPA extends TermModels {
  const TERM_NAME = 'pa_collection';
  const COLLECTION_ID = '___product_collection_id';
  const COLLECTION_SLUG = '___product_collection_slug';

  public static $TERM_NAME = 'pa_collection';

  public function addColectionID($id) {
    $this->metaData[self::COLLECTION_ID] = $id;
  }

  public function addCollectionSlug($slug) {
    $this->metaData[self::COLLECTION_SLUG] = $slug;
  }

  public function getCollection() {
    $collectionID = $this->metaData[self::COLLECTION_ID];
    $model = new Collection();
    if($collectionID) {
      return $model->find($collectionID);
    } else {
      return $model->findByOldWay($this->slug);
    }
  }

  function termName()
  {
    return 'pa_collection';
  }

}