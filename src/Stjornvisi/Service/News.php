<?php

namespace Stjornvisi\Service;

use \DateTime;
use \PDOException;
use Stjornvisi\Lib\DataSourceAwareInterface;

class News extends AbstractService implements DataSourceAwareInterface
{
    const NAME = 'news';

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * Get one news entry.
     *
     * @param int $id event ID
     * @return \stdClass
     * @throws Exception
     */
    public function get($id)
    {
        try {
            $statement = $this->pdo->prepare("
                SELECT * FROM News WHERE id = :id
            ");
            $statement->execute(array(
                'id' => $id
            ));
            $news = $statement->fetchObject();

            if (!$news) {
                return false;
            }

            $news->created_date = new DateTime($news->created_date);
            $news->modified_date = new DateTime($news->modified_date);

            $groupStatement = $this->pdo->prepare("
                SELECT G.id, G.name, G.name_short, G.url
                FROM `Group` G WHERE id = :id
            ");
            $groupStatement->execute(array(
                'id' => $news->group_id
            ));
            $news->group = $groupStatement->fetchObject();

            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return $news;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($groupStatement)?$groupStatement->queryString:null,
                )
            ));
            throw new Exception("Can't get news item. news:[{$id}]", 0, $e);
        }
    }

    public function getNext()
    {
        try {
            $statement = $this->pdo->prepare("
                SELECT * FROM News N
                ORDER BY N.created_date DESC
            ");
            $statement->execute();
            $news = $statement->fetchObject();

            if (!$news) {
                return false;
            }

            $news->created_date = new DateTime($news->created_date);
            $news->modified_date = new DateTime($news->modified_date);

            $groupStatement = $this->pdo->prepare("
                SELECT G.id, G.name, G.name_short, G.url
                FROM `Group` G WHERE id = :id
            ");
            $groupStatement->execute(array(
                'id' => $news->group_id
            ));
            $news->group = $groupStatement->fetchObject();

            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return $news;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($groupStatement)?$groupStatement->queryString:null,
                )
            ));
            throw new Exception("Can't get next news item.", 0, $e);
        }
    }

    public function fetchAll($page = null, $count = 10)
    {
        try {
            if ($page !== null) {
                $statement = $this->pdo->prepare("
                    SELECT * FROM `News` N
                    ORDER BY N.created_date DESC
                    LIMIT {$page},{$count}
                ");
                $statement->execute();
            } else {
                $statement = $this->pdo->prepare("
                    SELECT * FROM `News` N
                    ORDER BY N.created_date DESC
                ");
                $statement->execute();
            }

            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));

            $groupStatement = $this->pdo->prepare("
                SELECT G.id, G.name, G.name_short, G.url
                FROM `Group` G WHERE id = :id
            ");

            return array_map(function ($i) use ($groupStatement) {
                $i->created_date = new DateTime($i->created_date);
                $i->modified_date = new DateTime($i->modified_date);

                $groupStatement->execute(array(
                    'id' => $i->group_id
                ));
                $i->group = $groupStatement->fetchObject();

                return $i;
            }, $statement->fetchAll());
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't get next news item.", 0, $e);
        }
    }

    public function count()
    {
        try {
            $statement = $this->pdo->prepare("SELECT count(*) as total FROM `News` N");
            $statement->execute();
            return (int)$statement->fetchColumn(0);
        } catch (PDOException $e) {
        }
    }

    /**
     * Get related news of a group.
     *
     * The first parameter is the ID of group.
     *
     * The second parameter is optional. It is
     * used to exclude one news entry (by id).
     *
     * @param int $id group id
     * @param int $exclude
     * @return array
     * @throws Exception
     */
    public function getRelated($id, $exclude = 0)
    {
        try {
            $statement = $this->pdo->prepare("
                SELECT * FROM News N
                WHERE (N.group_id = :id OR N.group_id IS NULL)
                AND N.id != :exclude
                ORDER BY N.created_date DESC LIMIT 0, 10;
            ");
            $statement->execute(array(
                'id' => $id,
                'exclude' => $exclude
            ));
            $news = $statement->fetchAll();
            $groupStatement = $this->pdo->prepare("
                SELECT G.id, G.name_short, G.name, G.url
                FROM `Group` G WHERE id = :id
            ");
            foreach ($news as $item) {
                $groupStatement->execute(array(
                    'id' => $item->group_id
                ));
                $item->created_date = new DateTime($item->created_date);
                $item->modified_date = new DateTime($item->modified_date);
                $item->group = $groupStatement->fetchObject();
            }
            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return $news;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($groupStatement)?$groupStatement->queryString:null
                )
            ));
            throw new Exception("Can't get related news entries. group:[{$id}], exclude:[{$exclude}]", 0, $e);
        }
    }

    /**
     * Get news that are connected to groups
     * that the user is connected to.
     * Limit 10
     *
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getByUser($id)
    {
        try {
            $statement = $this->pdo->prepare("
              SELECT * FROM News N WHERE group_id IN (
                SELECT group_id FROM Group_has_User GhU WHERE user_id = :id
              )
              OR group_id IS NULL
              ORDER BY N.created_date DESC LIMIT 0,10;");
            $statement->execute(array('id'=>$id));
            $news = $statement->fetchAll();

            $groupStatement = $this->pdo->prepare("SELECT * FROM `Group` WHERE id = :id;");
            foreach ($news as $item) {
                $groupStatement->execute(array('id'=>$item->group_id));
                $item->group = $groupStatement->fetchObject();
            }
            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return array_map(function ($item) {
                $item->created_date = ( !empty($item->created_date) )
                    ? new DateTime($item->created_date)
                    : $item->created_date;
                return $item;
            }, $news);

        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($groupStatement)?$groupStatement->queryString:null
                )
            ));
            throw new Exception("Can't get news by user. user:[{$id}]", 0, $e);
        }
    }

    /**
     * Get all news in date-range.
     *
     * The second parameter is optional, if not
     * provided, all entries from $from to the newest
     * one is returned
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     * @throws Exception
     */
    public function getRange(DateTime $from, DateTime $to = null)
    {
        try {
            $news = array();
            if ($to) {
                $statement = $this->pdo->prepare("
                    SELECT * FROM News N
                    WHERE N.created_date
                    BETWEEN :from AND :to
                    ORDER BY N.created_date DESC;
                ");
                $statement->execute(array(
                    'from'=>$from->format('Y-m-d H:i:s'),
                    'to' => $to->format('Y-m-d H:i:s')
                ));
                $news = $statement->fetchAll();
            } else {
                $statement = $this->pdo->prepare("
                    SELECT * FROM News N
                    WHERE N.created_date >= :from
                    ORDER BY N.created_date DESC;
                ");
                $statement->execute(array(
                    'from'=>$from->format('Y-m-d H:i:s')
                ));
                $news = $statement->fetchAll();
            }

            $groupStatement = $this->pdo->prepare("SELECT * FROM `Group` WHERE id = :id;");
            foreach ($news as $item) {
                $groupStatement->execute(array('id'=>$item->group_id));
                $item->group = $groupStatement->fetchObject();
                $item->created_date = new DateTime($item->created_date);
                $item->modified_date = new DateTime($item->modified_date);
            }
            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return $news;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($groupStatement)?$groupStatement->queryString:null,
                )
            ));
            throw new Exception("Can't get news in a range", 0, $e);
        }
    }

    /**
     * Get all news in a date-range connected to group
     *
     * @param int $id group id
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     * @throws Exception
     */
    public function getRangeByGroup($id, DateTime $from, DateTime $to)
    {
        try {
            $statement = $this->pdo->prepare("
                SELECT * FROM News N
                WHERE N.created_date
                BETWEEN :from AND :to
                AND N.group_id = :id
                ORDER BY N.created_date DESC;
            ");
            $statement->execute(array(
                'from'=>$from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
                'id' => $id
            ));
            $news =  $statement->fetchAll();

            $groupStatement = $this->pdo->prepare("SELECT G.name, G.name_short, G.url FROM `Group` G WHERE id = :id");
            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return array_map(function ($item) use ($groupStatement) {
                $item->created_date = new DateTime($item->created_date);
                $item->modified_date = new DateTime($item->modified_date);
                if ($item->group_id) {
                    $groupStatement->execute(array('id'=> $item->group_id));
                    $item->group = $groupStatement->fetchObject();
                }
                return $item;
            }, $news);
            foreach ($news as $item) {
                $item->created_date = new DateTime($item->created_date);
                $item->modified_date = new DateTime($item->modified_date);
            }

            return $news;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('read', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't read news in range by group. group:[{$id}]", 0, $e);
        }
    }

    /**
     * Create news entry.
     *
     * @param array $data
     * @return int ID
     */
    public function create(array $data)
    {
        try {
            $data['created_date'] = date('Y-m-d H:i:s');
            $data['modified_date'] = date('Y-m-d H:i:s');
            $insertString = $this->insertString('News', $data);
            $statement = $this->pdo->prepare($insertString);
            $statement->execute($data);
            $id = (int)$this->pdo->lastInsertId();
            $data['id'] = $id;
            $this->getEventManager()->trigger('create', $this, array(
                0 => __FUNCTION__,
                'data' => $data
            ));
            $this->getEventManager()->trigger('index', $this, array(
                0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                'id' => $id,
                'name' => News::NAME,
            ));
            return $id;
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null
                )
            ));
        }
    }
    /**
     * Update one entry.
     *
     * @param $id news ID
     * @param array $data
     * @return int row count
     * @throws Exception
     * @todo created_date
     */
    public function update($id, array $data)
    {
        try {
            $data['modified_date'] = date('Y-m-d H:i:s');
            $data['created_date'] = date('Y-m-d H:i:s');
            $updateString = $this->updateString('News', $data, "id={$id}");
            $statement = $this->pdo->prepare($updateString);
            $statement->execute($data);
            $data['id'] = $id;
            $data['created_date'] = new DateTime($data['created_date']);
            $data['modified_date'] = new DateTime($data['modified_date']);
            $this->getEventManager()->trigger('update', $this, array(
                0 => __FUNCTION__,
                'data' => $data
            ));
            $this->getEventManager()->trigger('index', $this, array(
                0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                'id' => $id,
                'name' => News::NAME,
            ));
            return $statement->rowCount();
        } catch (PDOException $e) {
            $this->getEventManager()->trigger('update', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't update news entry", 0, $e);
        }
    }

    /**
     * Delete one entry.
     *
     * @param $id news ID
     * @return int
     * @throws Exception
     */
    public function delete($id)
    {
        if (( $news = $this->get($id) ) != false) {
            try {
                $statement = $this->pdo->prepare('
                DELETE FROM `News`
                WHERE id = :id');
                $statement->execute(array(
                    'id' => $id
                ));
                $this->getEventManager()->trigger('delete', $this, array(
                    0 => __FUNCTION__,
                    'data' => (array)$news
                ));
                $this->getEventManager()->trigger('index', $this, array(
                    0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                    'id' => $id,
                    'name' => News::NAME,
                ));
                return $statement->rowCount();
            } catch (PDOException $e) {
                $this->getEventManager()->trigger('error', $this, array(
                    'exception' => $e->getTraceAsString(),
                    'sql' => array(
                        isset($statement)?$statement->queryString:null
                    )
                ));
                throw new Exception("can't delete news entry", 0, $e);
            }
        } else {
            return 0;
        }
    }

    public function setDataSource(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
