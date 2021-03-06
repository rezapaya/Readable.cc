<?php

namespace Swiftlet\Controllers;

class Index extends \Swiftlet\Controllers\Read
{
	protected
		$title = 'Popular Reading'
		;

	/**
	 * Default action
	 */
	public function index()
	{
		$this->getItems();
	}

	/**
	 * Get popular items
	 */
	public function items()
	{
		if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			$this->view->name = 'read';
		}

		$this->getItems();
	}

	/**
	 * Get popular items
	 */
	public function getItems()
	{
		$userId = $this->app->getSingleton('session')->get('id');

		$excludes = !empty($_GET['excludes']) ? explode(' ', $_GET['excludes']) : array();
		$page     = !empty($_GET['page'])     ? max(1, (int) abs($_GET['page'])) : 1;

		$dbh = $this->app->getSingleton('pdo')->getHandle();

		$select = '
			SELECT
				feeds.title AS feed_title,
				feeds.link  AS feed_link,
				items.*
			FROM feeds
			STRAIGHT_JOIN (
				SELECT
					id,
					feed_id,
					score,
					0           AS vote,
					0           AS starred,
					0           AS feed_subscribed
				FROM          items
				WHERE
					items.score   > 0 AND
					items.hidden  = 0 AND
					items.english = 1 AND
					items.short   = 0
					' . ( $userId && $excludes ? 'AND items.id NOT IN ( ' . implode(', ', array_fill(0, count($excludes), '?')) . ' )' : '' ) . '
				ORDER BY DATE(IF(items.posted_at, items.posted_at, items.created_at)) DESC, items.score DESC
			) AS items ON items.feed_id = feeds.id
			';

		if ( $userId ) {
			$select = '
				SELECT
					main.id,
					main.feed_id,
					main.feed_title,
					main.feed_link,
					COALESCE(users_items.score, 0)   AS score,
					COALESCE(users_items.vote,  0)   AS vote,
					COALESCE(users_items.saved, 0)   AS starred,
					IF(users_feeds.id IS NULL, 0, 1) AS feed_subscribed
				FROM (
					' . $select . '
					LIMIT 1000
				) AS main
				LEFT JOIN users_items ON users_items.item_id = main.id      AND users_items.user_id = ? AND ( users_items.read = 0 OR users_items.read IS NULL )
				LEFT JOIN users_feeds ON users_feeds.feed_id = main.feed_id AND users_feeds.user_id = ?
				';
		}

		$select = '
			SELECT
				main.*,
				items.url,
				items.title,
				items.contents,
				items.posted_at,
				NULL AS folder_id
			FROM (
				' . $select . '
				LIMIT ?, ?
			) AS main
			STRAIGHT_JOIN items ON items.id = main.id
			';

		$sth = $dbh->prepare($select);

		$i = 1;

		foreach( $excludes as $key => $itemId ) {
			$sth->bindParam($i ++, $excludes[$key], \PDO::PARAM_INT);
		}

		if ( $userId ) {
			$sth->bindParam($i ++, $userId, \PDO::PARAM_INT);
			$sth->bindParam($i ++, $userId, \PDO::PARAM_INT);
		}

		$limitFrom  = ( $page - 1 ) * self::ITEMS_PER_PAGE;
		$limitCount = self::ITEMS_PER_PAGE;

		$sth->bindParam($i ++, $limitFrom,  \PDO::PARAM_INT);
		$sth->bindParam($i ++, $limitCount, \PDO::PARAM_INT);

		$sth->execute();

		$result = $sth->fetchAll(\PDO::FETCH_OBJ);

		$items = $result;

		$this->prepare($items);

		$this->view->set('items', $items);
	}
}
