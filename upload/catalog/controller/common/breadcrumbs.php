<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Breadcrumbs
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Breadcrumbs extends \Opencart\System\Engine\Controller {
	/**
	 * @param array $breadcrumbs_items
	 *
	 * @return string
	 */
	public function index(array $breadcrumbs_items = []): string {
		$data = ['breadcrumbs' => $breadcrumbs_items];
		$data['structured_data'] = $this->load->controller('structured_data/breadcrumbs', $breadcrumbs_items);
		if (!empty($data['breadcrumbs'])) {
			$data['breadcrumbs'][0]['text'] = $this->language->get('text_home_icon');
		}

		return $this->load->view('common/breadcrumbs', $data);
	}
}
