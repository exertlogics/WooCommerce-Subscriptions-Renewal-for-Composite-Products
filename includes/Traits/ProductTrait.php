<?php

namespace WSRCP\Traits;

trait ProductTrait {
    protected $title = "WSRCP Subscription Renewal Product";
    protected $price = 10;
    protected $isVisible = false;
    protected $status = "published";
    // protected $sku = "wsrcp-renewal-product";
    protected $slug = "wsrcp-renewal-product";
    protected $password = "wsrcp-renewal-product";

    public function __construct($sku = 'wsrcp-renewal-product') {
        $this->sku = $sku;
    }

    protected function getProduct() 
    {
        try {

            // Get product with slug
            // $existing_product = get_page_by_path($this->slug, OBJECT, 'product');
            // if ($existing_product) {
            //     return wc_get_product($existing_product->ID);
            // }
            $existing_product = $this->get_product_by_slug($this->slug);
            if ($existing_product) {
                return wc_get_product($existing_product['id']);
            }
            // print_better($existing_product, 'Existing Product');
            // wsrcp_die('Product not found');

            $product = new \WC_Product();
            $product->set_name($this->title);
            // $product->set_id(-1);
            // set slug
            $product->set_slug('wsrcp-renewal-product');
            $product->set_price($this->price);
            $product->set_regular_price($this->price);
            $product->set_sale_price($this->price);
            // $product->set_sku($this->sku);
            $product->set_manage_stock(false);
            $product->set_stock_quantity(0);
            $product->set_stock_status('instock');
            $product->set_backorders('no');
            $product->set_sold_individually(true);
            $product->set_tax_status('none');
            $product->set_tax_class('');
            $product->set_status($this->status);
            $product->set_catalog_visibility('hidden');
            $product->save();

            $product_id = $product->get_id();

            wp_update_post(array(
                'ID'           => $product_id,
                'post_password' => $this->password,
            ));

            return $product;
        } catch (\Exception $e) {
            print_better($e->getMessage(), 'Error Creating Product');
            wsrcp_die('Error Creating Product');
            return $e->getMessage();
        }
    }

    protected function get_product_by_slug($slug) {
        global $wpdb;
    
        // Prepare the raw SQL query to fetch the product ID
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product' LIMIT 1",
            $slug
        );
    
        // Get the product ID
        $product_id = $wpdb->get_var($query);
    
        // Check if the product exists
        if ($product_id) {
            // Initialize the WC_Product object
            $product = wc_get_product($product_id);
    
            // If the product object is valid, return its data as an array
            if ($product) {
                return [
                    'id'           => $product->get_id(),
                    'name'         => $product->get_name(),
                    'price'        => $product->get_price(),
                    'stock_status' => $product->get_stock_status(),
                    'type'         => $product->get_type(),
                    'sku'          => $product->get_sku(),
                    'description'  => $product->get_description(),
                    'short_description' => $product->get_short_description(),
                    'categories'   => wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']),
                    'image'        => wp_get_attachment_url($product->get_image_id()),
                ];
            }
        }
    
        // If no product is found, return null
        return null;
    }

}