<?php
namespace Opencart\Catalog\Controller\StructuredData;

/**
 * Class Logo
 *
 * @package Opencart\Catalog\Controller\StructuredData
 */
class Logo extends \Opencart\System\Engine\Controller {
	/**
	 * @param string $store_name
	 * @param string $url
	 * @param string $logo
	 *
	 * @return string
	 */
	public function index(string $store_name, string $url, string $logo): string {
		$data = [];

		$data['structured_data'] = [
			"@context" => "https://schema.org",
			"@type"    => "Organization",
			"name"     => $store_name,
			"url"      => $url,
			"logo"     => $logo,
		];

		return $this->load->view('structured_data/default', $data);
	}
}
