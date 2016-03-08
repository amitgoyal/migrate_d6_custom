<?php

/**
 * @file
 * Contains \Drupal\migrate_d6_custom\Plugin\migrate\process\NodeParagraphTextarea.
 */

namespace Drupal\migrate_d6_custom\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;


/**
 * It saves D6 Page Body field to D8 Page Paragraph (Textarea) field.
 *
 * @MigrateProcessPlugin(
 *   id = "node_paragraph_textarea"
 * )
 */
class NodeParagraphTextarea extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $is_new = 1;
    $paragraphs = array();

    $body = $value;

    $nid = $row->getSourceIdValues()['nid'];
    $node = Node::load($nid);

    $field_type_paragraphs = array();
    if (!empty($node->field_page_paragraph)) {
      $field_type_paragraphs = $node->field_page_paragraph->getValue();
    }

    // Loop through all the paragraphs types associated with Node.
    foreach ($field_type_paragraphs as $paragraph_source) {
      $target_id = $paragraph_source['target_id'];
      $target_revision_id = $paragraph_source['target_revision_id'];

      $paragraph_data = Paragraph::load($target_id);

      if ($paragraph_data->bundle() == 'paragraph_text_area') {
        $paragraph_data->set('field_pt_text_area', $body);
        $paragraph_data->save();

        $is_new = 0;
      }
      // All the existing paragraphs types will be captured.
      // This is done to avoid removal of existing paragraphs types.
      $paragraphs[] = array('target_id' => $target_id, 'target_revision_id' => $target_revision_id);
    }
    // If no paragraphs_type_text is assigned to node.
    if ($is_new) {
      $ppt_values = array(
        'id' => NULL,
        'type' => 'paragraphs_type_text',
        'field_pt_text_area' => $body,
      );
      $ppt_paragraph = Paragraph::create($ppt_values);
      $ppt_paragraph->save();

      $target_id_dest = $ppt_paragraph->Id();
      $target_revision_id_dest = $ppt_paragraph->getRevisionId();
      $paragraphs[] = array('target_id' => $target_id_dest, 'target_revision_id' => $target_revision_id_dest);
    }

    return $paragraphs;
  }

}
