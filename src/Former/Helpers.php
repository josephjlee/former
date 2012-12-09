<?php
/**
 * Helpers
 *
 * Various helpers used by all Former classes
 */
namespace Former;

use \Underscore\String;

class Helpers
{
  public function __construct($app)
  {
    $this->app = $app;
  }

  ////////////////////////////////////////////////////////////////////
  ////////////////////////// HTML HELPERS ////////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Add a class to an attributes array
   *
   * @param  array  $attributes An array of attributes
   * @param  string $class      The class to add
   * @return array              The modified attributes array
   */
  public function addClass($attributes, $class)
  {
    if (!isset($attributes['class'])) $attributes['class'] = null;

    // Prevent adding a class twice
    if (!String::contains($attributes['class'], $class)) {
      $attributes['class'] = trim($attributes['class']. ' ' .$class);
    }

    return $attributes;
  }

  /**
   * Convert HTML characters to entities.
   *
   * @param  string  $value
   * @return string
   */
  public function entities($value)
  {
    return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
  }

  /**
   * Convert entities to HTML characters.
   *
   * @param  string  $value
   * @return string
   */
  public function decode($value)
  {
    return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Convert an array of attributes to a string
   *
   * @param array $attributes
   * @return string
   */
  public function attributes($attributes)
  {
    $html = array();

    foreach ((array) $attributes as $key => $value) {

      // Convert numerical keys to value (required="required")
      if (is_numeric($key)) $key = $value;

      // Encode value
      if (!is_null($value)) {
        $html[] = $key.'="'.$this->entities($value).'"';
      }
    }

    // If empty array, return nothing
    if (count($html) == 0) return false;
    return ' ' .implode(' ' ,$html);
  }

  ////////////////////////////////////////////////////////////////////
  ////////////////////// LOCALIZATION HELPERS ////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Translates a string by trying several fallbacks
   *
   * @param  string $key      The key to translate
   * @param  string $fallback The ultimate fallback
   * @return string           A translated string
   */
  public function translate($key, $fallback = null)
  {
    // If nothing was given, return nothing, bitch
    if(!$key) return null;

    // If no fallback, use the key
    if(!$fallback) $fallback = $key;

    // Assure we don't already have a Lang object
    if($key instanceof Lang) return $key->get();

    // Search for the key itself
    $translation = $this->app['translator']->get($key);

    // If not found, search in the field attributes
    if (!$translation) {
      $translations = $this->app['config']->get('former::translate_from');
      $translation = $this->app['translator']->get($translations.'.'.$key);
    }

    return ucfirst($translation);
  }

  ////////////////////////////////////////////////////////////////////
  /////////////////////////// DATABASE HELPERS ///////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Transforms a Fluent/Eloquent query to an array
   *
   * @param  object $query The query
   * @param  string $value The attribute to use as value
   * @param  string $key   The attribute to use as key
   * @return array         A data array
   */
  public function queryToArray($query, $value, $key)
  {
    // Automatically fetch Lang objects for people who store translated options lists
    if ($query instanceof \Laravel\Lang) {
      $query = $query->get();
    }

    // Fetch the Query if it hasn't been
    if ($query instanceof \Laravel\Database\Eloquent\Query or
       $query instanceof \Laravel\Database\Query) {
      $query = $query->get();
    }

    if(!is_array($query)) $query = (array) $query;

    // Populates the new options
    foreach ($query as $model) {

      // If it's an array, convert to object
      if(is_array($model)) $model = (object) $model;

      // Calculate the value
      if($value and isset($model->$value)) $modelValue = $model->$value;
      elseif(method_exists($model, '__toString')) $modelValue = $model->__toString();
      else $modelValue = null;

      // Calculate the key
      if($key and isset($model->$key)) $modelKey = $model->$key;
      elseif(method_exists($model, 'get_key')) $modelKey = $model->get_key();
      elseif(isset($model->id)) $modelKey = $model->id;
      else $modelKey = $modelValue;

      // Skip if no text value found
      if(!$modelValue) continue;

      $array[$modelKey] = (string) $modelValue;
    }

    return isset($array) ? $array : $query;
  }

  public function renderLabel($label, $field)
  {
    // Get the label and its informations
    extract($label);

    // Add classes to the attributes
    $attributes = $this->app['former.framework']->getLabelClasses($attributes);

    // Append required text
    if ($field->isRequired()) {
      $label .= $this->app['config']->get('former::required_text');
    }

    // Get the field name to link the label to it
    if ($field->isCheckable()) {
      return '<label'.$this->app['former.helpers']->attributes($attributes).'>'.$label.'</label>';
    }

    return $this->app['former.laravel.form']->label($field->name, $label, $attributes);
  }
}