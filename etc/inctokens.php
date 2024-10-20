<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node as NodeLoader;


//tokens may work well with share but this module will focus on preprocess for now.

/**
 * Implements hook_token_info().
 */
function yse_jumper_token_info() {

  $types['jumper'] = array(
    'name' => t('Jumper'),
    'description' => t('Tokens for jumpnav anchors from entity UUID.'),
    'needs-data' => 'user',
  );

  $tokens['jumper']['hex'] = array(
    'name' => t('Hex Group'),
    'description' => t('First group of hex from UUID.'),
  );

  $tokens['jumper']['slug'] = array(
    'name' => t('Slug'),
    'description' => t('Slugified value from jumplink field.'),
  );

  $tokens['jumper']['anchor'] = array(
    'name' => t('Anchor'),
    'description' => t('Anchor for placement using DOM element id'),
  );

  $tokens['jumper']['string'] = array(
    'name' => t('String'),
    'description' => t('Text as placed into jumplink field'),
  );

  $tokens['jumper']['nodepath'] = array(
    'name' => t('Parent Node Path'),
    'description' => t('Config specific upwards recursion to canonical node path'),
  );

  $tokens['jumper']['stepcount'] = array(
    'name' => t('Parent Node Path'),
    'description' => t('Number representing nested level for paragraph in a node'),
  );

  $tokens['jumper']['libitempath'] = array(
    'name' => t('Parent Library Item path'),
    'description' => t('Recursion to canonical paragraphs library item path'),
  );

  $tokens['paragraph']['jumper'] = array(
    'name' => t('Paragraph Jumper'),
    'description' => t('Jump Anchor for Paragraph.'),
    'type' => 'jumper',
  );

 //just here to let us config fields. We may need to split code if $entity doesn't do it.
  $tokens['block_content']['jumper'] = array(
    'name' => t('Block Content Jumper'),
    'description' => t('Jump Anchor for Paragraph.'),
    'type' => 'jumper',
  );

  return array(
    'types' => $types,
    'tokens' => $tokens,
  );
}

/**
 * Implements hook_tokens().
 */
function yse_jumper_tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $replacements = [];

  if ($type == 'jumper' && !empty($data['paragraph'])) {
    $nid = $node_load = null;
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
        $nid = $node->id();
        /** @var \Drupal\node\Entity\Node $nodeload */
        $node_load = NodeLoader::load($nid);
    }


    $paragraph = $data['paragraph'];
    $paracfobj = \Drupal::config('yse_jumper.settings');
    $needjumpr = $paracfobj->get('jump_field_paths_only');
    $para_type = $paragraph->bundle();
    $para_uuid = $paragraph->uuid();
    $hex_array = explode("-", $para_uuid);
    $hex_group = reset($hex_array);
    $hex_jumpr = 'jump_' . $hex_group;
    $str_jumpr = null;
    if ($paragraph->hasField('field_jump_nav_heading')){
      $has_jumpr = true;
      $str_jumpr = $paragraph->get('field_jump_nav_heading')->value;
    }
    if (empty($str_jumpr)){
      $slugr = null;
      $domid = $hex_jumpr;
    } else {
      $slugr = Html::getId($str_jumpr);
      $domid = $slugr;
    }
    $anchor = '#'. $domid;

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'hex':
          if ($hex_group && !empty($hex_group)) {
            $replacements[$original] = $hex_group;
          }
          break;
        case 'slug':
          if ($slugr && !empty($slugr)) {
            $replacements[$original] = $slugr;
          }
          break;
        case 'anchor':
          if ($anchor && !empty($anchor)) {
            $replacements[$original] = $anchor;
          }
          break;
        case 'string':
          if ($has_jumpr && !empty($str_jumpr)) {
            $replacements[$original] = $str_jumpr;
          }
          break;
        case 'nodepath':
          if (!empty($node_load)){
            //replacing _yse_jumper_get_parentnode_path for now
            $home_node = $node_load->toUrl()->toString();
            if (!empty($home_node)){
              $bubbleable_metadata->addCacheableDependency($node_load);
              $replacements[$original] = $home_node;
            }
          }
          break;
        case 'stepcount':
          if (empty($stepcount) && !empty($node_load)){
            $para_step = intval(_yse_jumper_count_stepups($paragraph, $nid));
            if (!empty($para_step)){
              $bubbleable_metadata->addCacheableDependency($node_load);
              $replacements[$original] = $para_step;
            }
          }
        break;
        case 'libitempath':
          //if ($para_type == 'from_library'){
          if (empty($needjumpr) || ($needjumpr && $has_jumpr)){
            $home_node = _yse_jumper_get_library_path($paragraph, $nid);
            if (!empty($home_node)){
              $replacements[$original] = $home_node;
            }
          }
          break;
      }
    }
  }

  if ($type == 'paragraph' && !empty($data['paragraph'])) {
    if ($jumpr_tokens = $token_service->findWithPrefix($tokens, 'jumper')) {
      $replacements += $token_service->generate(
        'jumper',
        $jumpr_tokens,
        ['paragraph' => $data['paragraph']],
        $options,
        $bubbleable_metadata
      );
    }
  }

  return $replacements;

}

// I could run all the token checks in one routine and return an array
function _yse_jumper_count_stepups($entity, $nid = NULL, $steps = 0){
  if (empty(_yse_jumper_proceed_by_type($entity))){
    return null;
  }
  //\Drupal::logger('yse_jumper')->notice('%u - %e type %t is %c steps up', ['%u' => $entity->uuid(), '%c' => $steps, '%t' =>  $entity->getEntityTypeId(), '%e' => $entity->label()]);
  if ($entity instanceof \Drupal\node\Entity\Node and ($entity->id() == $nid)) return $steps;
  if ($entity instanceof \Drupal\node\Entity\Node and empty($entity->_referringItem)) return $steps;

  // If our para is in a library or library-like node which is a ref stored in a paragraph, leapfrog and climb
  // config here would be to save on processing as opposed to checking every node that is not in the target set.
  // We are making a big assumption that the referringItem could only be in this chain of para to surface node.
  // We could make another config setting to help mitigate unwanted climbing.
  if ($entity instanceof \Drupal\node\Entity\Node and !empty($entity->_referringItem) and !empty($entity->_referringItem->getEntity())) return _yse_jumper_count_stepups($entity->_referringItem->getEntity(), $nid, $steps);
  // LibraryItems in the chain are a passthrough, I might change to a test for this in the para rule below...
  if ($entity instanceof Drupal\paragraphs_library\Entity\LibraryItem and !empty($entity->_referringItem) and !empty($entity->_referringItem->getEntity())) return _yse_jumper_count_stepups($entity->_referringItem->getEntity(), $nid, --$steps);
  // If still a paragraph, climb
  if ($entity instanceof \Drupal\paragraphs\Entity\Paragraph) return _yse_jumper_count_stepups($entity->getParentEntity(), $nid, ++$steps);
  // Otherwise, this is an entity we didn't want.
  return null;
}

function _yse_jumper_get_parentnode_path($entity, $nid = NULL){

  if (empty(_yse_jumper_proceed_by_type($entity))){
    return null;
  }

  if ($entity instanceof \Drupal\node\Entity\Node and ($entity->id() == $nid)) return $entity->toUrl()->toString();
  if ($entity instanceof \Drupal\node\Entity\Node and empty($entity->_referringItem)) return $entity->toUrl()->toString();

  // If our para is in a library or library-like node which is a ref stored in a paragraph, leapfrog and climb
  // config here would be to save on processing as opposed to checking every node that is not in the target set.
  // We are making a big assumption that the referringItem could only be in this chain of para to surface node.
  // We could make another config setting to help mitigate unwanted climbing.
  if ($entity instanceof \Drupal\node\Entity\Node and !empty($entity->_referringItem) and !empty($entity->_referringItem->getEntity())) return _yse_jumper_get_parentnode_path($entity->_referringItem->getEntity(), $nid);
  // I thought that maybe LibraryItems would have been smarter...
  if ($entity instanceof Drupal\paragraphs_library\Entity\LibraryItem and !empty($entity->_referringItem) and !empty($entity->_referringItem->getEntity())) return _yse_jumper_get_parentnode_path($entity->_referringItem->getEntity(), $nid);
  // If still a paragraph, climb
  if ($entity instanceof \Drupal\paragraphs\Entity\Paragraph) return _yse_jumper_get_parentnode_path($entity->getParentEntity(), $nid);
  // Otherwise, this is an entity we didn't want.
  return null;
}

function _yse_jumper_get_library_path($entity, $nid = NULL){

  if (empty(_yse_jumper_proceed_by_type($entity))){
    return null;
  }
  // if we somehow are looping back to parent, stop.
  if ($entity instanceof \Drupal\node\Entity\Node and ($entity->id() == $nid)) return null;

  if ($entity instanceof Drupal\paragraphs_library\Entity\LibraryItem) return $entity->toUrl()->toString();
  // If our para is in a stored panel and has a parent, climb
  if ($entity instanceof \Drupal\node\Entity\Node and !empty($entity->_referringItem) and !empty($entity->_referringItem->getEntity())) return _yse_jumper_get_library_path($entity->_referringItem->getEntity(), $nid);
  // If still a paragraph, climb
  if ($entity instanceof \Drupal\paragraphs\Entity\Paragraph) return _yse_jumper_get_library_path($entity->getParentEntity(), $nid);
  // Otherwise, this is an entity we didn't want.
  return null;
}

function _yse_jumper_proceed_by_type($entity){
  $selected_nodebundles = \Drupal::config('yse_jumper.settings')->get('selected_nodebundles');
  $excluded_parabundles = \Drupal::config('yse_jumper.settings')->get('excluded_parabundles');

  if ($entity instanceof \Drupal\node\Entity\Node){
    if (!empty($entity->bundle()) && in_array($entity->bundle(), $selected_nodebundles)){
      //if matched with includes we can use it above
      return true;
    } else {
      return false;
    }
  }

  if ($entity instanceof \Drupal\paragraphs\Entity\Paragraph){
    if (empty($entity->bundle()) || in_array($entity->bundle(), $excluded_parabundles)){
      return false;
    }
    return true;
  }

  if ($entity instanceof Drupal\paragraphs_library\Entity\LibraryItem){
    if (empty($entity->field_reusable_paragraph->entity->bundle())
    || in_array($entity->field_reusable_paragraph->entity->bundle(), $excluded_parabundles) ){
      return false;
    } else {
      return true;
    }
  }

  //If we didn't match any entities in the logic, we shouldn't use above.
  return false;
}
