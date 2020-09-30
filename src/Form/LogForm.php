<?php

namespace Drupal\log\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Log entities.
 *
 * @ingroup log
 */
class LogForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form += parent::form($form, $form_state);
    /** @var \Drupal\log\Entity\LogInterface $log */
    $log = $this->entity;
    if ($this->moduleHandler->moduleExists('views')) {
      $num_of_logs = $this->entityTypeManager->getStorage('log')
        ->getQuery()
        ->condition('type', $log->bundle())
        ->count()
        ->execute();
      if ($num_of_logs > 0) {
        $form['name']['widget'][0]['value']['#description'] = FieldFilteredMarkup::create($form['name']['widget'][0]['value']['#description'] . ' ' . $this->t('As you type, frequently used log names will be suggested.'));
        $form['name']['widget'][0]['value']['#autocomplete_route_name'] = 'log.autocomplete.name';
        $form['name']['widget'][0]['value']['#autocomplete_route_parameters'] = ['log_bundle' => $log->bundle()];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Saved the %label log.', ['%label' => $this->entity->label()]));
    $account = $this->currentUser();
    if ($account->hasPermission('access log overview')) {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    else {
      $form_state->setRedirectUrl($this->entity->toUrl());
    }
  }

}
