<?php
namespace Opencart\Catalog\Controller\StructuredData;
/**
 * Class Breadcrumbs
 *
 * @package Opencart\Catalog\Controller\StructuredData
 */
class Breadcrumbs extends \Opencart\System\Engine\Controller {
	/**
	 * @param array $breadcrumbs_items
	 *
	 * @return string
	 */
	public function index(array $breadcrumbs_items = []): string {
		$data = [];

		$data['structured_data'] = [
			"@context"        => "https://schema.org",
			"@type"           => "BreadcrumbList",
			"itemListElement" => []
		];

		foreach ($breadcrumbs_items as $index => $breadcrumbs_item) {
			$data['structured_data']['itemListElement'][] = [
				"@type"    => "ListItem",
				"position" => $index + 1,
				"name"     => $breadcrumbs_item['text'],
				"item"     => html_entity_decode($breadcrumbs_item['href'], ENT_QUOTES, 'UTF-8')
			];
		}

		return $this->load->view('structured_data/default', $data);
	}
}
