<?php

namespace Drupal\log\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\log\Entity\Log;

/**
 * Sets the current log as a context on log routes.
 */
class LogRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('log')->setRequired(FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject())) {
      $route_contexts = $route_object->getOption('parameters');
      // Check for a log revision parameter first.
      if (isset($route_contexts['log_revision']) && $revision = $this->routeMatch->getParameter('log_revision')) {
        $value = $revision;
      }
      elseif (isset($route_contexts['log']) && $log = $this->routeMatch->getParameter('log')) {
        $value = $log;
      }
      elseif ($this->routeMatch->getRouteName() == 'log.add') {
        $log_type = $this->routeMatch->getParameter('log_type');
        $value = Log::create(['type' => $log_type->id()]);
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['log'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('log', $this->t('Log from URL'));
    return ['log' => $context];
  }

}
