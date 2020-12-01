<?php
class Pay_Helper_Cart{
    public static function fillCartFromOrder(WC_Order $order){
        global $woocommerce;
        $cart = $woocommerce->cart;
        $cart instanceof WC_Cart;
        
        $items = $order->get_items();
        foreach($items as $item){
            $cart->add_to_cart($item['product_id'], $item['qty'], $item['variation_id']);  
        }
    }
}