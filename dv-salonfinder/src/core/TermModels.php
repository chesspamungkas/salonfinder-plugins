<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 6:47 PM
 */

namespace SF\core;


class TermModels {
  public $_termArg = [];
  public $metaData = [];

  public static $TERM_NAME = null;
  public static $ACF_PREFIX = '';
	public static $FOREIGN_KEY = null;

  private $__data=[];

  public function __get($name) {
    return $this->__data[$name];
  }

  public function __set($name, $value) {
    $this->__data[$name] = $value;
  }

  public function get_data() {
    return $this->__data;
  }

  public function __construct($arg = []) {
    $term = [];
    if(is_numeric($arg)) {
      $term = get_term($arg, self::$TERM_NAME, ARRAY_A);
    } else if(is_array($arg)) {
      $term = $arg;
    }
    $this->_termArg = $term;
    $this->setData($term);
  }

  public function getForeignKeyID() {
    return $this->getMeta(self::$FOREIGN_KEY);
  }

  public function addMeta($data = []) {
    $this->metaData = array_merge($this->metaData, $data);
  }

  public function getMeta($metaKey) {
    return get_term_meta($this->term_id, $metaKey, true);
  }

  public function addMetaData($key, $value) {
    $this->metaData[$key] = $value;
  }

  public static function findByID($id) {
    $value = get_term_by('id', $id, static::$TERM_NAME, ARRAY_A);
    if($value instanceof \WP_Error) {
      return false;
    }
    if($value) {
      $class=get_called_class();
      $model = new $class;
      $model->setData($value);
      return $model;
    }
    return false;
  }

  public static function findByName($name) {
    $value = get_term_by('name', $name, static::$TERM_NAME, ARRAY_A);
    if($value instanceof \WP_Error) {
      return false;
    }
    if($value) {
      $class=get_called_class();
      $model = new $class;
      $model->setData($value);
      return $model;
    }
    return false;
  }

  public static function findBySlugNew($slug) {
    $value = get_term_by('slug', $slug, static::$TERM_NAME, ARRAY_A);
    if($value instanceof \WP_Error) {      
      return false;
    }    
    if($value) {
      $class=get_called_class();
      $model = new $class;
      $model->setData($value);
      return $model;
    }
    return false;
  }


  public static function findBySlug($slug) {
    $value = get_term_by('slug', $slug, static::$TERM_NAME, ARRAY_A);
    if($value instanceof \WP_Error) {
      return $value;
    }
    $class=get_called_class();
    $model = new $class;
    $model->setData($value);
    return $model;
  }

  public function findByOldWay($slug) {
    $term = get_term_by('slug', $slug, static::$TERM_NAME, ARRAY_A);
    if($term instanceof \WP_Error) {
      return $term;
    }
    $this->setData($term);
    return $this;
  }

  public function find($value) {
    $term = get_term($value, static::$TERM_NAME, ARRAY_A);
    if($term instanceof \WP_Error) {
      return $term;
    }
    $this->setData($term);
    return $this;
  }

  public static function findByForeignKey($id) {
	  $args = array(
		  'hide_empty' => false, // also retrieve terms which are not used yet
		  'meta_query' => array(
			  array(
				  'key'       => static::$FOREIGN_KEY,
				  'value'     => $id,
				  'compare'   => '='
			  )
		  ),
		  'taxonomy'  => static::$TERM_NAME,
    );
    
    $terms = get_terms( $args );
	  if($terms instanceof \WP_Error) {
		  return $terms;
	  }
	  if(count($terms)>0) {      
      $term = get_term($terms[0], static::$TERM_NAME, ARRAY_A);
		  $class=get_called_class();
		  $model = new $class;
      $model->setData($term);
		  return $model;
    }
    
	  return null;
  }

  public function setData($data) {
    $this->__data = $data;
  }

  public function saveMetaData($termID = null) {
    if($termID==null)
      $termID=$this->term_id;
    foreach($this->metaData as $key=>$value) {
      $result = add_term_meta($termID, $key, $value, true);
      if(!$result) {
        $previousMetaValue = get_term_meta($termID, $key, true);
        update_term_meta($termID, $key, $value, $previousMetaValue);
      }
    }
  }

  public function save() {
    if(static::$TERM_NAME == null)
      return false;

    $term = null;
    if($this->term_id && $this->term_id>0) {
      $term = wp_update_term($this->term_id, static::$TERM_NAME, $this->__data);
    } else {

      $term = wp_insert_term($this->_termArg['name'], static::$TERM_NAME, $this->__data);
      if($term instanceof \WP_Error) {        
        $term = get_term_by('name', $this->name, ARRAY_A);
      }
    }
    if($term) {
      $this->setData($term);
      $this->saveMetaData();
    }

    return $term;
  }

  public static function activate($id) {    
    update_field('status', 'active', $id);
  }

  public static function inactivate($id) {    
    update_field('status', 'inactive', $id);
  }

  public function getCustomField($fieldName, $defaultValue = null) {
    $class=get_called_class();
    $termID = $class::$TERM_NAME.'_'.$this->term_id;
    return get_field($fieldName, $termID)?:$defaultValue;
  }

  public function addMetaDatas($body, $customFields = null, $overrideCustomFields = []) {
    if(!$body)
      return;
    if (!$customFields || count($customFields) <=0) {
      $customFields = $this->customFields;
    }

    foreach ($customFields as $key => $fieldNames) {

      if(isset($body[$key])) {
        $value = $body[$key];
        if($value === true) {
          $value = 1;
        }
        if($value === false) {
          $value = 0;
        }
        foreach($overrideCustomFields as $overrideCustomField=>$fn) {
          if ($key == $overrideCustomField) {
            $value = $fn($body[$key], $body);
          }
        }
        if($value !== null) {
          if(!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
          }
          foreach($fieldNames as $fieldName) {
            $this->addMetaData($fieldName, $value);            
          }
        }
      }
    }
  }

  public function saveCustomFields($termID, $body, $customFields = null, $overrideCustomFields = []) {
    if(!$body)
      return;
    if (!$customFields || count($customFields) <=0) {
      $customFields = $this->customFields;
    }

    foreach ($customFields as $key => $fieldNames) {

      if(isset($body[$key])) {
        $value = $body[$key];
        foreach($overrideCustomFields as $overrideCustomField=>$fn) {
          if ($key == $overrideCustomField) {
            $value = $fn($body[$key], $body);
          }
        }
        if($value && $value!="") {
          if(!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
          }
          foreach($fieldNames as $fieldName) {
            update_field($fieldName, $value, $termID);
          }
        }
      }
    }
  }
}