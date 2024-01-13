<?php
namespace Opencart\Catalog\Controller\Feed\Google;
/**
 * Class Merchant
 *
 * @package Opencart\Catalog\Controller\Feed
 */
class Merchant extends \Opencart\System\Engine\Controller {
	/**
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function index(): void {
		$this->cache->get($this->getCacheHash());

		$feed_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/google/merchant', (int)$this->config->get('config_language_id'), 'feed');
		$last_update_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/google/merchant', (int)$this->config->get('config_language_id'), 'last-update');
		$preparing_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/google/merchant', (int)$this->config->get('config_language_id'), 'preparing');

		// Fetch XML from the cache.
		$feed_xml = $this->cache->get($feed_hash_key);
		$current_time = (int)(microtime(true));
		// If the cache is expired, then generate the XML again.
		if (empty($feed_xml)) {
			// Check if the feed is preparing.
			$preparation_start_time = $this->cache->get($preparing_hash_key);
			if (empty($preparation_start_time) || $current_time - (int)$preparation_start_time > 120) {
				// Mark the cache as the feed is preparing.
				$this->cache->set($preparing_hash_key, $current_time);
				$feed_xml = $this->generateXML();
				// Save the generated content to the cache.
				$this->cache->set($feed_hash_key, $feed_xml);
				$this->cache->set($last_update_hash_key, $current_time, 10 * 365 * 86400);
				// Remove the mark.
				$this->cache->delete($preparing_hash_key);
			} else {
				// Refresh the page after five seconds if the feed is preparing on other instance of the script.
				sleep(5);
				header('Location: ./index.php?route=feed/google/merchant&language=' . (int)$this->config->get('config_language_id') . '&_=' . $current_time);

				return;
			}
		}

		$this->response->addHeader('Content-Type: application/xml');
		$this->response->setOutput($feed_xml);
	}

	/**
	 * @param ...$args
	 *
	 * @return string
	 */
	private function getCacheHash(...$args): string {
		$hash_components = [];
		foreach ($args as $arg) {
			$hash_components[] = (string)$arg;
		}

		return hash('sha256', implode('|', $hash_components));
	}

	/**
	 * @throws \Exception
	 */
	private function generateXML(): string {
		$xml = '<?xml version="1.0"?>';
		$xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
		$xml .= '<channel>';
		$xml .= '  <title><![CDATA[' . $this->config->get('config_meta_title') . ']]></title>';
		$xml .= '  <link><![CDATA[' . HTTP_SERVER . ']]></link>';
		$xml .= '  <description><![CDATA[' . $this->config->get('config_meta_description') . ']]></description>';

		$products = $this->collectProducts();
		foreach ($products as $product) {
			$xml .= '  <item>';
			$xml .= '    <g:id><![CDATA[' . $product['id'] . ']]></g:id>';
			if (!empty($product['gtin'])) {
				$xml .= '    <g:gtin><![CDATA[' . $product['gtin'] . ']]></g:gtin>';
			}
			if (!empty($product['brand'])) {
				$xml .= '    <g:brand><![CDATA[' . $product['brand'] . ']]></g:brand>';
			}
			if (!empty($product['mpn'])) {
				$xml .= '    <g:mpn><![CDATA[' . $product['mpn'] . ']]></g:mpn>';
			}
			if (!empty($product['product_type'])) {
				$xml .= '    <g:product_type>' . htmlentities($product['product_type'], ENT_QUOTES, 'UTF-8') . '</g:product_type>';
			}
			$xml .= '    <g:title><![CDATA[' . $product['title'] . ']]></g:title>';
			$xml .= '    <g:description><![CDATA[' . $product['description'] . ']]></g:description>';
			$xml .= '    <g:link><![CDATA[' . $product['link'] . ']]></g:link>';
			$xml .= '    <g:image_link><![CDATA[' . $product['image_link'] . ']]></g:image_link>';
			$xml .= '    <g:condition>new</g:condition>';
			$xml .= '    <g:availability>' . $product['availability'] . '</g:availability>';
			$xml .= '    <g:price>' . $product['price'] . '</g:price>';
			if (!empty($product['shipping'])) {
				$xml .= '    <g:shipping>';
				$xml .= '      <g:country>' . $product['shipping']['country'] . '</g:country>';
				$xml .= '      <g:price>' . $product['shipping']['price'] . '</g:price>';
				$xml .= '    </g:shipping>';
			}
			$xml .= '  </item>';
		}
		$xml .= '  </channel>';
		$xml .= '</rss>';

		return $xml;
	}

	/**
	 * @throws \Exception
	 *
	 * @return array
	 */
	private function collectProducts(): array {

		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$products_in_database = $this->model_catalog_product->getProductsForFeed();

		$products = [];

		foreach ($products_in_database as $product) {
			$price = $product['price'];
			if (!empty($product['discount'])) {
				$price = $product['discount'];
			} elseif (!empty($product['special'])) {
				$price = $product['special'];
			}
			$price = $this->tax->calculate($price, $product['tax_class_id']);

			if (is_file(DIR_IMAGE . html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $this->model_tool_image->resize(html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8'), $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
			}

			$gtin = null;
			if (!empty($product['ean'])) {
				$gtin = $product['ean'];
			} elseif (!empty($product['upc'])) {
				$gtin = $product['upc'];
			} elseif (!empty($product['jan'])) {
				$gtin = $product['jan'];
			} elseif (!empty($product['isbn'])) {
				$gtin = $product['isbn'];
			}

			$in_stock = false;
			if (empty($product['subtract'])) {
				$in_stock = true;
			} elseif ((int)($product['quantity']) > 0) {
				$in_stock = true;
			}

			$this->load->model('catalog/category');

			$categories = $this->model_catalog_product->getCategories((int)($product['product_id']));

			$selected_category = '';
			$selected_category_level = -1;

			foreach ($categories as $category_id) {
				$category_info = $this->model_catalog_category->getCategoryWithPath((int)($category_id['category_id']));

				if ($category_info) {
					$category_name = ($category_info['path']) ? $category_info['path'] . ' > ' . $category_info['name'] : $category_info['name'];
					$category_level = mb_substr_count($category_name, ' > ');
					if ($category_level > $selected_category_level) {
						$selected_category = $category_name;
						$selected_category_level = $category_level;
					}
				}
			}

			$this->load->model('checkout/shipping_method');
			$shipping_methods = $this->model_checkout_shipping_method->getMethods([
				'country_id' => $this->config->get('config_country_id'),
				'zone_id'    => $this->config->get('config_zone_id')
			]);

			$minimum_shipping_cost = null;
			foreach ($shipping_methods as $shipping_method_code => $shipping_method) {
				foreach ($shipping_method['quote'] as $quote_code => $quote) {
					$shipping_cost = $this->tax->calculate($quote['cost'], $quote['tax_class_id']);
					if ($minimum_shipping_cost === null || $shipping_cost < $minimum_shipping_cost) {
						$minimum_shipping_cost = $shipping_cost;
					}
				}
			}
			$this->load->model('localisation/country');
			$country = $this->model_localisation_country->getCountry((int)($this->config->get('config_country_id')));

			$products[] = [
				'title'        => $product['meta_title'],
				'description'  => $product['meta_description'],
				'image_link'   => $image,
				'link'         => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id']),
				'price'        => number_format((float)$price, 2, '.', '') . ' ' . $this->config->get('config_currency'),
				'id'           => $product['model'],
				'brand'        => $product['manufacturer_name'],
				'gtin'         => $gtin,
				'mpn'          => $product['mpn'],
				'availability' => $in_stock ? 'in_stock' : 'out_of_stock',
				'product_type' => $selected_category,
				'shipping'     => $minimum_shipping_cost === null ? null : ([
					'country' => $country['iso_code_2'],
					'price'   => number_format((float)$minimum_shipping_cost, 2, '.', '') . ' ' . $this->config->get('config_currency'),
				])
			];
		}

		return $products;
	}
}
