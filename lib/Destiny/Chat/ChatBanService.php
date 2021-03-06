<?php
namespace Destiny\Chat;

use Destiny\Common\Application;
use Destiny\Common\Service;
use Doctrine\DBAL\DBALException;

/**
 * @method static ChatBanService instance()
 */
class ChatBanService extends Service {

    /**
     * @param int $userId
     * @param string $ipaddress
     * @return array
     * @throws DBALException
     */
    public function getUserActiveBan($userId, $ipaddress = "") {
        $conn = Application::getDbConn();
        if(empty($ipaddress)) {
            $stmt = $conn->prepare('
              SELECT
                b.id,
                b.userid,
                u.username,
                b.targetuserid,
                u2.username AS targetusername,
                b.ipaddress,
                b.reason,
                b.starttimestamp,
                b.endtimestamp
              FROM
                bans AS b
                INNER JOIN dfl_users AS u ON u.userId = b.userid
                INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
              WHERE 
                b.starttimestamp < NOW() AND 
                b.targetuserid = :userId AND
                (b.endtimestamp > NOW() OR b.endtimestamp IS NULL)
              GROUP BY b.targetuserid
              ORDER BY b.id DESC
              LIMIT 0,1
            ');
            $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        } else {
            $stmt = $conn->prepare('
              SELECT
                b.id,
                b.userid,
                u.username,
                b.targetuserid,
                u2.username AS targetusername,
                b.ipaddress,
                b.reason,
                b.starttimestamp,
                b.endtimestamp
              FROM
                bans AS b
                INNER JOIN dfl_users AS u ON u.userId = b.userid
                INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
              WHERE 
                b.starttimestamp < NOW() AND 
                (b.targetuserid = :userId OR b.ipaddress = :ipaddress) AND
                (b.endtimestamp > NOW() OR b.endtimestamp IS NULL)
              GROUP BY b.targetuserid
              ORDER BY b.id DESC
              LIMIT 0,1
            ');
            $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue('ipaddress', $ipaddress, \PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param $userid
     * @return int $count The number of rows modified
     * @throws DBALException
     */
    public function removeUserBan($userid) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          UPDATE bans
          SET endtimestamp = NOW()
          WHERE
            targetuserid = :targetuserid AND
            (
              endtimestamp IS NULL OR
              endtimestamp >= NOW()
            )
        ");
        $stmt->bindValue('targetuserid', $userid, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * @param array $ban
     * @return string
     * @throws DBALException
     */
    public function insertBan(array $ban) {
        $conn = Application::getDbConn();
        $conn->insert('bans', $ban);
        return $conn->lastInsertId();
    }

    /**
     * update an existing ban
     * @param array $ban
     * @throws DBALException
     */
    public function updateBan(array $ban) {
        $conn = Application::getDbConn();
        $conn->update('bans', $ban, ['id' => $ban ['id']]);
    }

    /**
     * @param int $banId
     * @return array
     * @throws DBALException
     */
    public function getBanById($banId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT
            b.id,
            b.userid,
            u.username,
            b.targetuserid,
            u2.username AS targetusername,
            b.ipaddress,
            b.reason,
            b.starttimestamp,
            b.endtimestamp
          FROM
            bans AS b
            INNER JOIN dfl_users AS u ON u.userId = b.userid
            INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
          WHERE b.id = :id
          ORDER BY b.id DESC
          LIMIT 0,1
        ');
        $stmt->bindValue('id', $banId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @return array
     * @throws DBALException
     */
    public function getActiveBans() {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT
                b.id AS banid,
                b.starttimestamp,
                b.endtimestamp,
                b.reason,
                b.ipaddress,
                b.targetuserid AS targetuserid,
                tu.username AS targetusername,
                b.userid AS banninguserid,
                u.username AS banningusername
            FROM
                bans AS b,
                dfl_users AS tu,
                dfl_users AS u
            WHERE
                (
                    b.endtimestamp IS NULL OR
                    b.endtimestamp >= NOW()
                ) AND
                b.userid       = u.userId AND
                b.targetuserid = tu.userId
            GROUP BY b.starttimestamp
            ORDER BY b.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Removes all of the bans and notifies the chat to refresh the bans
     * so it actually notices the bans being removed
     *
     * @throws DBALException
     */
    public function purgeBans() {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('TRUNCATE TABLE bans');
        $stmt->execute();
    }

}