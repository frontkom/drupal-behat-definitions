<?php

namespace Frontkom\DrupalBehatDefinitions;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\commerce_product\ProductVariationStorageInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Class CommerceContext.
 *
 * Provide Behat step-definitions for common Commerce functionalities.
 */
class CommerceContext extends EntityContextBase {

  /**
   * Generate coupons.
   *
   * @Given coupons:
   */
  public function createCoupons(TableNode $nodesTable) {
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion_coupon');
    foreach ($nodesTable->getHash() as $nodeHash) {
      $coupon = (object) $nodeHash;
      $promotion = $this->getPromotionByName($coupon->promotion);

      $coupon_saved = $this->couponCreate($coupon);
      $coupon_loaded = $storage->load($coupon_saved->id);
      $promotion->get('coupons')->appendItem($coupon_loaded);
      $promotion->save();
    }
  }

  /**
   * Load promotion by name.
   */
  public function getPromotionByName($name) {
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
    $promotions = $storage->loadByProperties(['name' => $name]);

    if (count($promotions) !== 1) {
      throw new \Exception('Expected 1 promotion with title ' . $name . ' but found ' . count($promotions));
    }

    return reset($promotions);
  }

  /**
   * Create coupon.
   */
  public function couponCreate($coupon) {
    $saved = $this->getDriver()->createEntity('commerce_promotion_coupon', $coupon);
    return $saved;
  }

  /**
   * Visit promotion edit page.
   *
   * @Then I visit promotion :name edit page
   */
  public function iVisitPromotionEditPage($name) {
    $promotion = $this->getPromotionByName($name);
    $this->getSession()->visit($this->locatePath($promotion->toUrl('edit-form')->toString()));
  }

  /**
   * Check if product variation exist.
   *
   * @Then /^a product with SKU "([^"]*)" should exist$/
   */
  public function aProductWithSkuShouldExist($sku) {
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
    $product_variations = $variation_storage->loadBySku($sku);
    if (empty($product_variations)) {
      throw new \Exception('No variation found with SKU ' . $sku);
    }
  }

  /**
   * Set records DB for exchange rates.
   *
   * Valid for commerce_exchanger^2, because in version 1 the exchange rates are in config.
   *
   * @Given I set :value exchange rates
   */
  public function setManualExchangeRates($value) {
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    $currencies = $currency_storage->loadMultiple();
    $currencies = array_keys($currencies);
    $database = \Drupal::database();
    $time = time();

    foreach ($currencies as $currency) {
      $query = $database->insert('commerce_exchanger_latest_rates')
        ->fields([
          'exchanger',
          'source',
          'target',
          'value',
          'timestamp',
          'manual',
        ]);

      $query->values([
        'exchanger' => 'manual',
        'source' => $currency,
        'target' => 'NOK',
        'value' => $value,
        'timestamp' => $time,
        // A weird value just to identify records to remove in AfterScenario.
        'manual' => '666666',
      ]);
      $query->execute();
    }
  }

  /**
   * Remove exchange rates.
   *
   * @AfterScenario
   */
  public function cleanExchangeRates() {
    $database = \Drupal::database();
    $database->delete('commerce_exchanger_latest_rates')
      ->condition('manual', '666666', '=')
      ->execute();
  }

}
