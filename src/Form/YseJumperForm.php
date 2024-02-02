<?php

namespace Drupal\yse_jumper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class YseJumperForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yse_jumper';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yse_jumper.settings'];
  }

  /**
   * {@inheritdoc}
   *  List all the types we want to include and exclude.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yse_jumper.settings');

    $form['excluded_parabundles'] = [
      '#type' => 'select',
      '#title' => t('Excluded Paragraph Types'),
      '#default_value' => $config->get('excluded_parabundles'),
      '#options' => $this->getTypeList('paragraph'),
      '#description' => t('Select all paragraph types you do NOT want getting jumpers.'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];

    $form['selected_nodebundles'] = [
      '#type' => 'select',
      '#title' => t('Select Nodes to give Paragraphs jumpers'),
      '#default_value' => $config->get('selected_nodebundles'),
      '#options' => $this->getTypeList('node'),
      '#description' => t('Select all node types we will assign paragraph jumpers to.'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('yse_jumper.settings');
    $config->set('excluded_parabundles', array_keys($form_state->getValue('excluded_parabundles')))->save();
    $config->set('selected_nodebundles', array_keys($form_state->getValue('selected_nodebundles')))->save();
    parent::submitForm($form, $form_state);
  }

  public function getTypeList($ent_type, $label = TRUE) {
    $types = [];
    $ent_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo($ent_type);
    foreach ($ent_types as $typeid => $bundle) {
      $types[$typeid] = $label ? $bundle['label'] : $typeid;
    }
    return $types;
  }

}
