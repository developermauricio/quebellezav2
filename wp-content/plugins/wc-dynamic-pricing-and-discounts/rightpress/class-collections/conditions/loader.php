<?php

// Exit if accessed directly
defined('ABSPATH') || exit;

// Load class collection files

require_once 'rightpress-condition-exception.class.php';
require_once 'rightpress-conditions-timeframes.class.php';
require_once 'rightpress-controller-condition-fields.class.php';
require_once 'rightpress-controller-condition-methods.class.php';
require_once 'rightpress-controller-conditions.class.php';

require_once 'condition-fields/rightpress-condition-field.class.php';
require_once 'condition-fields/rightpress-condition-field-decimal.class.php';
require_once 'condition-fields/rightpress-condition-field-decimal-decimal.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-capabilities.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-countries.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-coupons.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-custom-taxonomy.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-payment-methods.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-product-attributes.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-product-categories.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-product-tags.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-product-types.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-product-variations.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-products.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-roles.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-shipping-classes.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-shipping-methods.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-shipping-zones.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-states.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-users.class.php';
require_once 'condition-fields/rightpress-condition-field-multiselect-weekdays.class.php';
require_once 'condition-fields/rightpress-condition-field-number.class.php';
require_once 'condition-fields/rightpress-condition-field-number-number.class.php';
require_once 'condition-fields/rightpress-condition-field-select.class.php';
require_once 'condition-fields/rightpress-condition-field-select-timeframe.class.php';
require_once 'condition-fields/rightpress-condition-field-select-timeframe-event.class.php';
require_once 'condition-fields/rightpress-condition-field-select-timeframe-span.class.php';
require_once 'condition-fields/rightpress-condition-field-text.class.php';
require_once 'condition-fields/rightpress-condition-field-text-date.class.php';
require_once 'condition-fields/rightpress-condition-field-text-datetime.class.php';
require_once 'condition-fields/rightpress-condition-field-text-meta-key.class.php';
require_once 'condition-fields/rightpress-condition-field-text-postcode.class.php';
require_once 'condition-fields/rightpress-condition-field-text-text.class.php';
require_once 'condition-fields/rightpress-condition-field-text-time.class.php';

require_once 'condition-methods/rightpress-condition-method.class.php';
require_once 'condition-methods/rightpress-condition-method-boolean.class.php';
require_once 'condition-methods/rightpress-condition-method-coupons.class.php';
require_once 'condition-methods/rightpress-condition-method-date.class.php';
require_once 'condition-methods/rightpress-condition-method-datetime.class.php';
require_once 'condition-methods/rightpress-condition-method-field.class.php';
require_once 'condition-methods/rightpress-condition-method-list.class.php';
require_once 'condition-methods/rightpress-condition-method-list-advanced.class.php';
require_once 'condition-methods/rightpress-condition-method-meta.class.php';
require_once 'condition-methods/rightpress-condition-method-numeric.class.php';
require_once 'condition-methods/rightpress-condition-method-point-in-time.class.php';
require_once 'condition-methods/rightpress-condition-method-postcode.class.php';
require_once 'condition-methods/rightpress-condition-method-time.class.php';

require_once 'conditions/rightpress-condition.class.php';
require_once 'conditions/rightpress-condition-cart.class.php';
require_once 'conditions/rightpress-condition-cart-count.class.php';
require_once 'conditions/rightpress-condition-cart-coupons.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-product-attributes.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-product-categories.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-product-shipping-classes.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-product-tags.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-product-variations.class.php';
require_once 'conditions/rightpress-condition-cart-item-quantities-products.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-product-attributes.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-product-categories.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-product-shipping-classes.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-product-tags.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-product-variations.class.php';
require_once 'conditions/rightpress-condition-cart-item-subtotals-products.class.php';
require_once 'conditions/rightpress-condition-cart-items.class.php';
require_once 'conditions/rightpress-condition-cart-items-product-attributes.class.php';
require_once 'conditions/rightpress-condition-cart-items-product-categories.class.php';
require_once 'conditions/rightpress-condition-cart-items-product-shipping-classes.class.php';
require_once 'conditions/rightpress-condition-cart-items-product-tags.class.php';
require_once 'conditions/rightpress-condition-cart-items-product-variations.class.php';
require_once 'conditions/rightpress-condition-cart-items-products.class.php';
require_once 'conditions/rightpress-condition-cart-quantity.class.php';
require_once 'conditions/rightpress-condition-cart-subtotal.class.php';
require_once 'conditions/rightpress-condition-cart-weight.class.php';
require_once 'conditions/rightpress-condition-checkout.class.php';
require_once 'conditions/rightpress-condition-checkout-payment-method.class.php';
require_once 'conditions/rightpress-condition-checkout-shipping-method.class.php';
require_once 'conditions/rightpress-condition-custom-taxonomy.class.php';
require_once 'conditions/rightpress-condition-custom-taxonomy-product.class.php';
require_once 'conditions/rightpress-condition-customer.class.php';
require_once 'conditions/rightpress-condition-customer-capability.class.php';
require_once 'conditions/rightpress-condition-customer-customer.class.php';
require_once 'conditions/rightpress-condition-customer-logged-in.class.php';
require_once 'conditions/rightpress-condition-customer-meta.class.php';
require_once 'conditions/rightpress-condition-customer-role.class.php';
require_once 'conditions/rightpress-condition-customer-value.class.php';
require_once 'conditions/rightpress-condition-customer-value-amount-spent.class.php';
require_once 'conditions/rightpress-condition-customer-value-average-order-amount.class.php';
require_once 'conditions/rightpress-condition-customer-value-last-order-amount.class.php';
require_once 'conditions/rightpress-condition-customer-value-last-order-time.class.php';
require_once 'conditions/rightpress-condition-customer-value-order-count.class.php';
require_once 'conditions/rightpress-condition-customer-value-review-count.class.php';
require_once 'conditions/rightpress-condition-order.class.php';
require_once 'conditions/rightpress-condition-order-coupons.class.php';
require_once 'conditions/rightpress-condition-order-customer.class.php';
require_once 'conditions/rightpress-condition-order-customer-capability.class.php';
require_once 'conditions/rightpress-condition-order-customer-role.class.php';
require_once 'conditions/rightpress-condition-order-items.class.php';
require_once 'conditions/rightpress-condition-order-items-product-attributes.class.php';
require_once 'conditions/rightpress-condition-order-items-product-categories.class.php';
require_once 'conditions/rightpress-condition-order-items-product-tags.class.php';
require_once 'conditions/rightpress-condition-order-items-product-variations.class.php';
require_once 'conditions/rightpress-condition-order-items-products.class.php';
require_once 'conditions/rightpress-condition-order-payment-method.class.php';
require_once 'conditions/rightpress-condition-order-shipping.class.php';
require_once 'conditions/rightpress-condition-order-shipping-method.class.php';
require_once 'conditions/rightpress-condition-order-total.class.php';
require_once 'conditions/rightpress-condition-other.class.php';
require_once 'conditions/rightpress-condition-product.class.php';
require_once 'conditions/rightpress-condition-product-attributes.class.php';
require_once 'conditions/rightpress-condition-product-category.class.php';
require_once 'conditions/rightpress-condition-product-other.class.php';
require_once 'conditions/rightpress-condition-product-other-wc-coupons-applied.class.php';
require_once 'conditions/rightpress-condition-product-product.class.php';
require_once 'conditions/rightpress-condition-product-property.class.php';
require_once 'conditions/rightpress-condition-product-property-meta.class.php';
require_once 'conditions/rightpress-condition-product-property-on-sale.class.php';
require_once 'conditions/rightpress-condition-product-property-regular-price.class.php';
require_once 'conditions/rightpress-condition-product-property-sale-price.class.php';
require_once 'conditions/rightpress-condition-product-property-shipping-class.class.php';
require_once 'conditions/rightpress-condition-product-property-stock-quantity.class.php';
require_once 'conditions/rightpress-condition-product-property-type.class.php';
require_once 'conditions/rightpress-condition-product-tags.class.php';
require_once 'conditions/rightpress-condition-product-variation.class.php';
require_once 'conditions/rightpress-condition-purchase-history.class.php';
require_once 'conditions/rightpress-condition-purchase-history-product-attributes.class.php';
require_once 'conditions/rightpress-condition-purchase-history-product-categories.class.php';
require_once 'conditions/rightpress-condition-purchase-history-product-tags.class.php';
require_once 'conditions/rightpress-condition-purchase-history-product-variations.class.php';
require_once 'conditions/rightpress-condition-purchase-history-products.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity-product-attributes.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity-product-categories.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity-product-tags.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity-product-variations.class.php';
require_once 'conditions/rightpress-condition-purchase-history-quantity-products.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value-product-attributes.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value-product-categories.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value-product-tags.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value-product-variations.class.php';
require_once 'conditions/rightpress-condition-purchase-history-value-products.class.php';
require_once 'conditions/rightpress-condition-shipping.class.php';
require_once 'conditions/rightpress-condition-shipping-country.class.php';
require_once 'conditions/rightpress-condition-shipping-postcode.class.php';
require_once 'conditions/rightpress-condition-shipping-state.class.php';
require_once 'conditions/rightpress-condition-shipping-zone.class.php';
require_once 'conditions/rightpress-condition-time.class.php';
require_once 'conditions/rightpress-condition-time-date.class.php';
require_once 'conditions/rightpress-condition-time-datetime.class.php';
require_once 'conditions/rightpress-condition-time-time.class.php';
require_once 'conditions/rightpress-condition-time-weekdays.class.php';
