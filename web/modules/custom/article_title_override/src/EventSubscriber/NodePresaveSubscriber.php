<?php

namespace Drupal\article_title_override\EventSubscriber;

use Drupal\article_title_override\Event\NodePresaveEvent;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Article Title Override event subscriber.
 */
class NodePresaveSubscriber implements EventSubscriberInterface {

  /**
   * The generator node Seo Title.
   *
   * @param \Drupal\article_title_override\Event\NodePresaveEvent $event
   *   The Node Presave event.
   */
  public function nodeSeotitleGenetator(NodePresaveEvent $event) {
    $node = $event->getNode();
    $referenced_entities = $node->referencedEntities();

    foreach ($referenced_entities as $referenced_entitie) {
      if ($referenced_entitie->getEntityTypeId() == 'taxonomy_term') {
        $taxonomy_term = $referenced_entitie->label();
        break;

      }

    }

    $timestamp_of_creation = $node->get('created')->value;
    $date_of_the_creation = DrupalDateTime::createFromTimestamp($timestamp_of_creation);

    $node->set('field_seo_title', $taxonomy_term . ' - ' . $date_of_the_creation->format('Y-m-d H:i:s'));
    $node->save();

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      NodePresaveEvent::NODE_PRESAVE => ['nodeSeotitleGenetator'],
    ];

  }

}
