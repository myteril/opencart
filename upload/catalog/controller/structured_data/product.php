<?php
namespace Opencart\Catalog\Controller\StructuredData;

/**
 * Class Product
 *
 * @package Opencart\Catalog\Controller\StructuredData
 */
class Product extends \Opencart\System\Engine\Controller {
	/**
	 * @param array{
	 *     name: string,
	 *     description: string,
	 *     gtin: ?string,
	 *     sku: ?string,
	 *     mpn: ?string,
	 *     product_code: string,
	 *     manufacturer: ?string,
	 *     image: string[],
	 *     price: string|int|float|double,
	 *     currency_code: string,
	 *     width: int|float|string|null,
	 *     height: int|float|string|null,
	 *     depth: int|float|string|null,
	 *     weight: int|float|string|null,
	 *     length_unit: string,
	 *     weight_unit: string,
	 *     item_availability: string,
	 * 	   rating: ?array{
	 *		   average: int|float,
	 *		   review_count: int
	 * 	   }
	 * } $product_data
	 *
	 * @return string
	 */
	public function index(array $product_data): string {

		$data = [];

		$data['structured_data'] = [
			"@context"    => "https://schema.org/",
			"@type"       => "Product",
			"name"        => $product_data['name'],
			"description" => $product_data['description'],
			"image"       => []
		];

		if (!empty($product_data['price'])) {
			$data['structured_data']['offers'] = [
				"@type"              => "Offer",
				"itemCondition"      => "https://schema.org/NewCondition",
				"availability"       => "https://schema.org/" . $product_data['item_availability'],
				"priceSpecification" => [
					"@type"         => "UnitPriceSpecification",
					"price"         => $product_data['price'],
					"priceCurrency" => $product_data['currency_code']
				],
			];
		}

		if (!empty($product_data['gtin']) && is_string($product_data['gtin'])) {
			$data['structured_data']['gtin'] = $product_data['gtin'];
		}

		if (!empty($product_data['sku']) && is_string($product_data['sku'])) {
			$data['structured_data']['sku'] = $product_data['sku'];
		}

		if (!empty($product_data['mpn']) && is_string($product_data['mpn'])) {
			$data['structured_data']['mpn'] = $product_data['mpn'];
		}

		if (!empty($product_data['product_code']) && is_string($product_data['product_code'])) {
			$data['structured_data']['model'] = $product_data['product_code'];
		}

		foreach ($product_data['image'] as $image) {
			$data['structured_data']['image'][] = html_entity_decode($image, ENT_QUOTES, 'utf-8');
		}

		if (!empty($product_data['manufacturer']) && is_string($product_data['manufacturer'])) {
			$data['structured_data']['manufacturer'] = [
				"@type" => "Organization",
				"name"  => $product_data['manufacturer']
			];
		}

		if (!empty($product_data['width']) && is_numeric($product_data['width']) && (float)($product_data['width']) > 0) {
			$data['structured_data']['width'] = [
				"@type"    => "QuantitativeValue",
				"value"    => (float)($product_data['width']),
				"unitText" => $product_data['length_unit']
			];
		}

		if (!empty($product_data['height']) && is_numeric($product_data['height']) && (float)($product_data['height']) > 0) {
			$data['structured_data']['height'] = [
				"@type"    => "QuantitativeValue",
				"value"    => (float)($product_data['height']),
				"unitText" => $product_data['length_unit']
			];
		}

		if (!empty($product_data['depth']) && is_numeric($product_data['depth']) && (float)($product_data['depth']) > 0) {
			$data['structured_data']['depth'] = [
				"@type"    => "QuantitativeValue",
				"value"    => (float)($product_data['depth']),
				"unitText" => $product_data['length_unit']
			];
		}

		if (!empty($product_data['weight']) && is_numeric($product_data['weight']) && (float)($product_data['weight']) > 0) {
			$data['structured_data']['weight'] = [
				"@type"    => "QuantitativeValue",
				"value"    => (float)($product_data['weight']),
				"unitText" => $product_data['weight_unit']
			];
		}

		if (!empty($product_data['rating']['review_count'])) {
			$data['structured_data']['aggregateRating'] = [
				"@type"       => "AggregateRating",
				"ratingValue" => (float)($product_data['rating']['average']),
				"reviewCount" => (int)($product_data['rating']['review_count']),
			];
		}

		return $this->load->view('structured_data/default', $data);
	}
}
