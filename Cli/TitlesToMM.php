<?php
namespace RKW;

if (php_sapi_name() != "cli") {
    echo 'This script has to be executed via CLI.' . "\n";
    exit(1);
}

if (! file_exists('vendor')) {
    echo 'This script has to be executed from the document root.' . "\n";
    exit(1);
}

if (count($argv) < 4) {
    echo 'Missing parameters. Usage:' . "\n" .
        'php5.6 web/typo3conf/ext/rkw_registration/Cli/TitlesToMM.php [HOST] [DATABASE] [USER] [PASS]' . "\n";
    exit(1);
}

$credentials = [
    'host' => $argv[1],
    'database' => $argv[2],
    'user' => $argv[3],
    'pass' => $argv[4]
];

$titlesToMM = new \RKW\TitlesToMM($credentials);
$titlesToMM->migrate();
echo "Done.\n";


# ================================================================================

require_once ('vendor/autoload.php');

class TitlesToMM
{

    /**
     * @var \PDO
     */
    protected $pdo;


    /**
     * constructor
     *
     * @param array $credentials
     */
    public function __construct($credentials)
    {
        $this->pdo = new \PDO('mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database'] . ';charset=utf8', $credentials['user'], $credentials['pass']);
    }


    /**
     * Builds MySQL for updates and deletes
     *
     * @return string
     * @throws \Exception
     */
    public function migrate()
    {

        // cleanup
        //=================================================
        $this->pdo->query('
            TRUNCATE tx_rkwregistration_title_record_mm;
        ');

        //=================================================

        $newTitleRelations = [];

        //=================================================
        // fetch users
        $sqlUsers = 'SELECT uid, tx_rkwregistration_title 
            FROM fe_users
            WHERE 
                tx_rkwregistration_title > 0
        ';

        $sthUsers = $this->pdo->prepare($sqlUsers);
        $sthUsers->execute();
        $users = $sthUsers->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user) {

            $newTitleRelation = [
                'uid_local'   => $user['tx_rkwregistration_title'],
                'uid_foreign' => $user['uid'],
                'tablenames'  => 'fe_users',
            ];

            // push
            $newTitleRelations[] = $newTitleRelation;

        }

        // ==============================
        // save title relations
        foreach ($newTitleRelations as $newTitleRelation) {
            $this->insert('tx_rkwregistration_title_record_mm', $newTitleRelation);
        }

        exit();
    }


    /**
     * insert
     *
     * @param string $table
     * @param array  $insertProperties
     * @return int
     * @throws \Exception
     */
    public function insert($table, $insertProperties)
    {
        $columns = implode(',', array_keys($insertProperties));
        $placeholder = implode(',', array_fill(0, count($insertProperties), '?'));
        $values = array_values($insertProperties);

        // fix for boolean conversion (false is converted to empty string)
        $values = array_map(
            function ($value) {
                return is_bool($value) ? (int)$value : $value;
            },
            $values
        );
        $sql = 'INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . $placeholder . ')';

        $sth = $this->pdo->prepare($sql);
        if ($result = $sth->execute($values)) {
            return $this->pdo->lastInsertId();
        } else {
            $error = $sth->errorInfo();
            throw new \Exception($error[2] . ' on execution of "' . $sth->queryString . '" with params ' . print_r($insertProperties, true));
        }

    }

    /**
     * insert
     *
     * @param string $table
     * @param int    $uid
     * @return boolean
     * @throws \Exception
     */
    public function delete($table, $uid)
    {

        $sql = 'UPDATE ' . $table . ' SET deleted = 1 WHERE uid = ?';
        $sth = $this->pdo->prepare($sql);
        if ($result = $sth->execute(array($uid))) {
            return true;
        } else {
            $error = $sth->errorInfo();
            throw new \Exception($error[2] . ' on execution of "' . $sth->queryString . '" with params ' . print_r($uid, true));
        }

    }

}
