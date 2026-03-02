<?php

declare(strict_types=1);

namespace Drupal\log\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hook implementations for log.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
    return [
      'log' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . '::preprocessLog',
      ],
    ];
  }

  /**
   * Prepares variables for log templates.
   *
   * Default template: log.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the log information and any
   *     fields attached to the log. Properties used:
   *     - #log: A \Drupal\log\Entity\Log object. The log entity.
   *   - attributes: HTML attributes for the containing element.
   */
  public function preprocessLog(array &$variables) {
    $variables['log'] = $variables['elements']['#log'];
    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_log')]
  public function themeSuggestionsLog(array $variables) {
    $suggestions = [];
    $log = $variables['elements']['#log'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    $suggestions[] = 'log__' . $sanitized_view_mode;
    $suggestions[] = 'log__' . $log->bundle();
    $suggestions[] = 'log__' . $log->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'log__' . $log->id();
    $suggestions[] = 'log__' . $log->id() . '__' . $sanitized_view_mode;
    return $suggestions;
  }

}
