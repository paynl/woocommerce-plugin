<?php

/**
 * PPMFWC_Hooks_FastCheckout_TransactionCreate
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Hooks_FastCheckout_Buttons
{
    /**
     * Add fast checkout CSS
     * @return void
     */
    public static function ppmfwc_fast_checkout_css()
    {
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (
                (!empty($gateway->settings['ideal_fast_checkout_on_cart']) && $gateway->settings['ideal_fast_checkout_on_cart'] == 1) ||
                (!empty($gateway->settings['ideal_fast_checkout_on_minicart']) && $gateway->settings['ideal_fast_checkout_on_minicart'] == 1) ||
                (!empty($gateway->settings['ideal_fast_checkout_on_product']) && $gateway->settings['ideal_fast_checkout_on_product'] == 1)
            ) {
                wp_register_style('ppmfwc_fast_checkout_style', PPMFWC_PLUGIN_URL . 'assets/css/payfastcheckout.css');
                wp_enqueue_style('ppmfwc_fast_checkout_style');
            }
        }
    }

    /**
     * Show fast checkout on cart page
     * @return void
     */
    public static function ppmfwc_fast_checkout_cart()
    {
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (!empty($gateway->settings['ideal_fast_checkout_on_cart']) && $gateway->settings['ideal_fast_checkout_on_cart'] == 1) {
                if (is_user_logged_in() && !empty($gateway->settings['ideal_fast_checkout_guest_only']) && $gateway->settings['ideal_fast_checkout_guest_only'] == 'yes') {
                    return;
                }

                if (!empty($gateway->settings['ideal_fast_checkout_modal']) && $gateway->settings['ideal_fast_checkout_modal'] == 'yes') {
                    echo '<div class="pay-fast-checkout cart">
                            <a class="checkout-button button alt wc-forward" onClick="toggleModal(\'/?wc-api=Wc_Pay_Gateway_Fccreate&source=cart\')">Fast Checkout</a>
                        </div>';
                } else {
                    echo '<div class="pay-fast-checkout cart">
                            <a href="/?wc-api=Wc_Pay_Gateway_Fccreate&source=cart" class="checkout-button button alt wc-forward fast-checkout-trigger-modal">Fast Checkout</a>
                        </div>';
                }
            }
        }
    }

    /**
     * Show fast checkout on mini cart
     * @return void
     */
    public static function ppmfwc_fast_checkout_mini_cart()
    {
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (!empty($gateway->settings['ideal_fast_checkout_on_minicart']) && $gateway->settings['ideal_fast_checkout_on_minicart'] == 1) {
                if (is_user_logged_in() && !empty($gateway->settings['ideal_fast_checkout_guest_only']) && $gateway->settings['ideal_fast_checkout_guest_only'] == 'yes') {
                    return;
                }

                if (!empty($gateway->settings['ideal_fast_checkout_modal']) && $gateway->settings['ideal_fast_checkout_modal'] == 'yes') {
                    echo '<span class="pay-fast-checkout-minicart">
                            <a class="checkout-button button alt fast-checkout-trigger-modal" onClick="toggleModal(\'/?wc-api=Wc_Pay_Gateway_Fccreate\')">Fast Checkout</a>
                        </span>';
                } else {
                    echo '<span class="pay-fast-checkout-minicart">
                            <a href="/?wc-api=Wc_Pay_Gateway_Fccreate" class="checkout-button button alt">Fast Checkout</a>
                        </span>';
                }
            }
        }
    }

    /**
     * Show fast checkout on product page
     * @return void
     */
    public static function ppmfwc_fast_checkout_product()
    {
        global $product;
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (!empty($gateway->settings['ideal_fast_checkout_on_product']) && $gateway->settings['ideal_fast_checkout_on_product'] == 1) {
                if (is_user_logged_in() && !empty($gateway->settings['ideal_fast_checkout_guest_only']) && $gateway->settings['ideal_fast_checkout_guest_only'] == 'yes') {
                    return;
                }
                echo '<input type="hidden" name="fast-checkout-product-id" value="' . esc_attr($product->get_id()) . '" />';

                if (!empty($gateway->settings['ideal_fast_checkout_modal']) && $gateway->settings['ideal_fast_checkout_modal'] == 'yes') {
                    echo '<div class="pay-fast-checkout-product">
                            <div class="pay-fast-checkout fast-checkout-product-modal">
                                <a class="checkout-button button alt">Fast Checkout</a>
                            </div>
                        </div>';
                } else {
                    wp_register_script('ppmfwc_fastcheckout_script', PPMFWC_PLUGIN_URL . 'assets/js/payfastcheckout.js', array('jquery'), '1.0', true);
                    wp_enqueue_script('ppmfwc_fastcheckout_script');
                    echo '<div class="pay-fast-checkout-product">
                            <div class="pay-fast-checkout">
                                <a class="checkout-button button alt">Fast Checkout</a>
                            </div>
                        </div>';
                }
            }
        }
    }

    /**
     * Show fast checkout on cart page
     * @return void
     */
    public static function ppmfwc_fast_checkout_blocks_cart()
    {
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (!empty($gateway->settings['ideal_fast_checkout_on_cart']) && $gateway->settings['ideal_fast_checkout_on_cart'] == 1) {
                if (is_user_logged_in() && !empty($gateway->settings['ideal_fast_checkout_guest_only']) && $gateway->settings['ideal_fast_checkout_guest_only'] == 'yes') {
                    return;
                }
                $post = get_post();
                if (!empty($post) && WC_Blocks_Utils::has_block_in_page($post->ID, 'woocommerce/cart') === true) {
                    if (count(WC()->cart->get_cart()) > 0) {
                        if (!empty($gateway->settings['ideal_fast_checkout_modal']) && $gateway->settings['ideal_fast_checkout_modal'] == 'yes') {
                            echo '<div class="pay-fast-checkout block-cart">
                                    <a class="checkout-button button alt wc-forward" onClick="toggleModal(\'/?wc-api=Wc_Pay_Gateway_Fccreate&source=cart\')">Fast Checkout</a>
                                </div>';
                        } else {
                            echo '<div class="pay-fast-checkout block-cart">
                                    <a href="/?wc-api=Wc_Pay_Gateway_Fccreate&source=cart" class="checkout-button button alt wc-forward">Fast Checkout</a>
                                </div>';
                        }
                    }
                }
            }
        }
    }

    /**
     * Show fast checkout on cart page
     * @return void
     */
    public static function ppmfwc_fast_checkout_modal()
    {
        $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);
        if ($gateway->enabled == 'yes') {
            if (!empty($gateway->settings['ideal_fast_checkout_modal']) && $gateway->settings['ideal_fast_checkout_modal'] == 'yes') {
                if (
                    (!empty($gateway->settings['ideal_fast_checkout_on_cart']) && $gateway->settings['ideal_fast_checkout_on_cart'] == 1) ||
                    (!empty($gateway->settings['ideal_fast_checkout_on_minicart']) && $gateway->settings['ideal_fast_checkout_on_minicart'] == 1) ||
                    (!empty($gateway->settings['ideal_fast_checkout_on_product']) && $gateway->settings['ideal_fast_checkout_on_product'] == 1)
                ) {
                    wp_register_script('ppmfwc_fastcheckout_script', PPMFWC_PLUGIN_URL . 'assets/js/payfastcheckout.js', array('jquery'), '1.0', true);
                    wp_enqueue_script('ppmfwc_fastcheckout_script');
                    echo '<div class="modal-backdrop" id="modal-backdrop" onclick="closeModal()"></div>';
                    echo '<div class="modal" id="fast-checkout-modal">
                            <div class="modal-body-wrapper">
                                <div class="eye-catcher"></div>
                                <div class="modal-content">
                                    <div class="modal-header roboto-slab">
                                        <span>Bestel sneller</span> met iDEAL snel bestellen
                                    </div>
                                    <div class="modal-text">
                                        <p class="modal-description">Maak een iDEAL profiel aan en sla je adres op voor je volgende bestelling</p>
                                    </div>
                                    <div class="modal-actions">
                                        <button class="modal-button button-primary">Fast Checkout</button>
                                        <button class="modal-button button-secondary roboto-medium" onclick="closeModal()">Zelf mijn adres invullen en betalen</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                }
            }
        }
    }
}
