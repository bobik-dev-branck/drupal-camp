<?php

namespace Drupal\article_title_override\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * The custom Node PreSave Event.
 */
class NodePresaveEvent extends Event {

  /**
   * Called during hook_node_insert().
   */
  const NODE_PRESAVE = 'nodePresave';


  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;

  }

  /**
   * Gets the node that saving now.
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   */
  public function getNode() {
    return $this->node;
  }

}
