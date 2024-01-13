<?php
namespace Opencart\Catalog\Model\Blog;

/**
 *  Class Store
 *
 * @package Opencart\Catalog\Model\Design
 */
class Store extends \Opencart\System\Engine\Model {
	/**
	 * @param int      $store_id
	 * @param int      $language_id
	 * @param int|null $limit
	 *
	 * @return array
	 */
	public function getTags(int $store_id, int $language_id, ?int $limit = null): array {
		$query = "SELECT tag, article_count FROM `" . DB_PREFIX . "blog_tag` WHERE language_id = " . $language_id . " AND store_id = " . $store_id . " AND article_count > 0";
		$query .= " ORDER BY article_count DESC";

		if ($limit !== null) {
			$query .= ' LIMIT ' . $limit;
		}

		$query = $this->db->query($query);

		return $query->rows;
	}

	/**
	 * @param int      $store_id
	 * @param int|null $limit
	 *
	 * @return array
	 */
	public function getAuthors(int $store_id, ?int $limit = null): array {
		$query = "SELECT bat.blog_author_id, bat.fullname, bat.email, bat.post_count from `" . DB_PREFIX . "blog_store_to_article` bsta";
		$query .= " INNER JOIN `" . DB_PREFIX . "blog_article` ba ON (ba.blog_article_id = bsta.blog_article_id and bsta.store_id = " . $store_id . ")";
		$query .= " INNER JOIN `" . DB_PREFIX . "blog_author` bat ON (ba.blog_author_id = bat.blog_author_id)";
		$query .= " GROUP BY bat.blog_author_id";
		$query .= " ORDER BY bat.fullname DESC";

		if ($limit !== null) {
			$query .= ' LIMIT ' . $limit;
		}

		$query = $this->db->query($query);

		return $query->rows;
	}
}
