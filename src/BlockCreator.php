<?php

namespace Drupal\social_kpi_lite;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;

class BlockCreator {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\block\Entity\Block
   */
  protected $entity;

  /**
   * Path to directory with the file source.
   *
   * @var string
   */
  protected $path;

  /**
   * Identifier of a block.
   * Should be equal to filename.
   *
   * @var string
   */
  protected $id;

  /**
   * Cache for the parsed data
   *
   * @var array
   */
  protected $data;

  /**
   * BlockCreator constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Set path to directory with the file source and
   * identifier of the block being created.
   *
   * @param string $path
   * @param string $id
   */
  public function setSource($path, $id) {
    $this->path = $path;
    $this->id = $id;
  }

  /**
   * Parse data from a yaml file.
   *
   * @param bool $reset
   *   If TRUE, file will be parsed again.
   *
   * @return array
   */
  protected function getData($reset = FALSE) {
    if (!$this->data || $reset) {
      $source = "{$this->path}/{$this->id}.yml";
      $this->data = Yaml::parse($source);
    }

    return $this->data;
  }

  /**
   * Get created entity.
   *
   * @return \Drupal\block_content\Entity\BlockContent|null
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set plugin id.
   *
   * @param string $plugin_id
   */
  public function setPluginId($plugin_id) {
    $this->getData(TRUE);
    $this->data['plugin'] = $plugin_id;
  }

  /**
   * Create entity with values defined in a yaml file.
   *
   * @return \Drupal\block\Entity\Block
   */
  public function create() {
    $values = $this->getData();

    // If block already exists, skip creating and return an existing entity.
    if ($block = $this->entityTypeManager->getStorage('block')->load($values['id'])) {
      $this->entity = $block;

      return $this->entity;
    }

    // Get the current theme id to place the block.
    if (empty($values['theme'])) {
      $values['theme'] = $this->configFactory->get('system.theme')->get('default');
    }

    // Create instance of the entity beign created.
    $this->entity = $this->entityTypeManager
      ->getStorage('block')
      ->create($values);

    $this->entity->save();

    return $this->entity;
  }

}
