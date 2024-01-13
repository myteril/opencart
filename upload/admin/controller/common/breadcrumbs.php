<?php
namespace Opencart\Admin\Controller\Common;
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

		return $this->load->view('common/breadcrumbs', $data);
	}
}
