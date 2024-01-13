<?php
namespace Opencart\Catalog\Model\Blog;

/**
 *  Class Article
 *
 * @package Opencart\Catalog\Model\Design
 */
class Article extends \Opencart\System\Engine\Model {
	/**
	 * @param int $store_id
	 * @param int $language_id
	 * @param int $blog_article_id
	 *
	 * @return array|null
	 */
	public function getArticle(int $store_id, int $language_id, int $blog_article_id): ?array {
		$query = "SELECT ba.*, bat.fullname as author_name, bat.email as author_email, bat.photo as author_photo, bac.title as title, bac.description as description, bac.content as content, bsta.view_count  FROM `" . DB_PREFIX . "blog_article` ba";
		$query .= " INNER JOIN `" . DB_PREFIX . "blog_store_to_article` bsta ON (ba.blog_article_id =  bsta.blog_article_id AND bsta.store_id = " . (int)$store_id . ") ";
		$query .= " LEFT JOIN `" . DB_PREFIX . "blog_author` bat ON (ba.blog_author_id =  bat.blog_author_id) ";
		$query .= " INNER JOIN `" . DB_PREFIX . "blog_article_content` bac ON (bac.blog_article_id =  ba.blog_article_id and bac.language_id = '" . $this->db->escape($language_id) . "') ";
		$query .= " WHERE ba.status = 1 AND ba.`blog_article_id` = '" . (int)$blog_article_id . "' LIMIT 1";

		$query = $this->db->query($query);
		if ($query->num_rows > 0) {
			return $query->row;
		}

		return null;
	}

	/**
	 * @param int $blog_article_id
	 * @param int $language_id
	 *
	 * @return array|null
	 */
	public function getArticleTags(int $blog_article_id, int $language_id): ?array {
		$query = "SELECT btta.* FROM `" . DB_PREFIX . "blog_article` ba INNER JOIN `" . DB_PREFIX . "blog_tag_to_article` btta ON (btta.blog_article_id = ba.blog_article_id AND btta.language_id = " . $language_id . ")";
		$query .= " WHERE ba.status = 1 AND ba.`blog_article_id` = '" . (int)$blog_article_id . "'";
		$query = $this->db->query($query);

		return $query->rows;
	}

	/**
	 * @param int $store_id
	 * @param int $blog_article_id
	 *
	 * @return void
	 */
	public function increaseViewCountOfArticle(int $store_id, int $blog_article_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "blog_store_to_article` SET view_count = view_count + 1 WHERE blog_article_id = " . $blog_article_id . " AND store_id = " . $store_id . " LIMIT 1;");
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function getArticles(array $data = []): array {
		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));

		$sql = "SELECT ba.*, bat.fullname as author_name, bac.title as title, bac.description as description  FROM `" . DB_PREFIX . "blog_article` ba";

		if (!empty($data['filter_tag'])) {
			$sql .= " INNER JOIN `" . DB_PREFIX . "blog_tag_to_article` btta ON (btta.blog_article_id =  ba.blog_article_id and btta.language_id = '" . $this->db->escape($language['language_id']) . "' and btta.tag = '" . $this->db->escape((string)$data['filter_tag']) . "') ";
		}

		$sql .= " INNER JOIN `" . DB_PREFIX . "blog_store_to_article` bsta ON (ba.blog_article_id =  bsta.blog_article_id AND bsta.store_id = " . (int)($data['store_id']) . ") ";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "blog_author` bat ON (ba.blog_author_id =  bat.blog_author_id) ";
		$sql .= " INNER JOIN `" . DB_PREFIX . "blog_article_content` bac ON (bac.blog_article_id =  ba.blog_article_id and bac.language_id = '" . $this->db->escape($language['language_id']) . "') ";

		// Sorting fields.
		$sort_data = [
			'ba.blog_article_id',
			'bac.title'
		];

		$sql .= " WHERE ba.status = 1 ";

		if (!empty($data['filter_title']) || !empty($data['filter_author'])) {
			$conditions = [];

			if (!empty($data['filter_title'])) {
				$data['filter_title'] = mb_substr($data['filter_title'], 0, 255);

				// Filter only alphanumerical characters.
				$filter_title = '';
				foreach (mb_str_split($data['filter_title']) as $char) {
					if (\IntlChar::isalnum($char)) {
						$filter_title .= $char;
					} else {
						$filter_title .= ' ';
					}
				}
				// Remove unnecessary space characters.
				$filter_title = preg_replace('/\s+/ui', ' ', $filter_title);
				$filter_title = trim($filter_title);

				// Replace remaining space characters with wildcard.
				$filter_title = preg_replace('/\s+/ui', '%', $filter_title);

				$conditions[] = "bac.title LIKE '" . $this->db->escape('%' . $filter_title . '%') . "' or bac.description LIKE '" . $this->db->escape('%' . $filter_title . '%') . "' ";
			}

			if (!empty($data['filter_author'])) {
				$conditions[] = "ba.blog_author_id <=> " . (int)($data['filter_author']);
			}

			$sql .= ' AND (' . implode(') AND (', $conditions) . ')';
		}

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY ba.`blog_article_id`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function getArticlesForSitemap(array $data = []): array {
		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));

		$sql = "SELECT ba.blog_article_id as blog_article_id, ba.date_modified as date_modified  FROM `" . DB_PREFIX . "blog_article` ba";

		$sql .= " INNER JOIN `" . DB_PREFIX . "blog_store_to_article` bsta ON (ba.blog_article_id =  bsta.blog_article_id AND bsta.store_id = " . (int)($data['store_id']) . ") ";
		$sql .= " INNER JOIN `" . DB_PREFIX . "blog_article_content` bac ON (bac.blog_article_id =  ba.blog_article_id and bac.language_id = '" . $this->db->escape($language['language_id']) . "') ";
		$sql .= " WHERE ba.status = 1 ";
		$sql .= " ORDER BY ba.`blog_article_id` DESC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * @param array $data
	 *
	 * @return int
	 */
	public function getTotalArticles(array $data = []): int {
		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));

		$query = "SELECT COUNT(ba.blog_article_id) AS `total` FROM `" . DB_PREFIX . "blog_article` ba";

		if (!empty($data['filter_tag'])) {
			$query .= " INNER JOIN `" . DB_PREFIX . "blog_tag_to_article` btta ON (btta.blog_article_id =  ba.blog_article_id and btta.language_id = '" . $this->db->escape($language['language_id']) . "' and btta.tag = '" . $this->db->escape((string)$data['filter_tag']) . "') ";
		}

		$query .= " INNER JOIN `" . DB_PREFIX . "blog_store_to_article` bsta ON (ba.blog_article_id =  bsta.blog_article_id AND bsta.store_id = " . (int)($data['store_id']) . ") ";
		$query .= " LEFT JOIN `" . DB_PREFIX . "blog_author` bat ON (ba.blog_author_id =  bat.blog_author_id) ";
		$query .= " INNER JOIN `" . DB_PREFIX . "blog_article_content` bac ON (bac.blog_article_id =  ba.blog_article_id and bac.language_id = '" . $this->db->escape($language['language_id']) . "') ";

		$query .= " WHERE ba.status = 1 ";

		if (!empty($data['filter_title']) || !empty($data['filter_author'])) {
			$conditions = [];

			if (!empty($data['filter_title'])) {
				$data['filter_title'] = mb_substr($data['filter_title'], 0, 255);

				// Filter only alphanumerical characters.
				$filter_title = '';
				foreach (mb_str_split($data['filter_title']) as $char) {
					if (\IntlChar::isalnum($char)) {
						$filter_title .= $char;
					} else {
						$filter_title .= ' ';
					}
				}
				// Remove unnecessary space characters.
				$filter_title = preg_replace('/\s+/ui', ' ', $filter_title);
				$filter_title = trim($filter_title);

				// Replace remaining space characters with wildcard.
				$filter_title = preg_replace('/\s+/ui', '%', $filter_title);

				$conditions[] = "bac.title LIKE '" . $this->db->escape('%' . $filter_title . '%') . "' or bac.description LIKE '" . $this->db->escape('%' . $filter_title . '%') . "' ";
			}

			if (!empty($data['filter_author'])) {
				$conditions[] = "ba.blog_author_id <=> " . (int)($data['filter_author']);
			}

			$query .= ' AND (' . implode(') AND (', $conditions) . ')';
		}

		return (int)$this->db->query($query)->row['total'];
	}

	/**
	 * @param int $blog_article_id
	 *
	 * @return array
	 */
	public function getStores(int $blog_article_id): array {
		$query = $this->db->query("SELECT bsta.*, s.name as store_name FROM `" . DB_PREFIX . "blog_store_to_article` bsta LEFT JOIN `" . DB_PREFIX . "store` s ON (bsta.store_id = s.store_id) WHERE bsta.blog_article_id = " . $blog_article_id);
		$result = [];
		foreach ($query->rows as $row) {
			$result[] = [
				'store_id'   => $row['store_id'],
				'store_name' => $row['store_name'] === null ? $this->language->get('text_default') : $row['store_name'],
				'view_count' => $row['view_count']
			];
		}

		return $result;
	}

	/**
	 * @param int $blog_article_id
	 *
	 * @return array
	 */
	public function getContents(int $blog_article_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_article_content` WHERE blog_article_id = " . $blog_article_id);
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = [
				'blog_article_id' => $row['blog_article_id'],
				'language_id'     => $row['language_id'],
				'title'           => $row['title'],
				'description'     => $row['description'],
				'content'         => $row['content'],
			];
		}

		return $result;
	}
}
