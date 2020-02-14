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

if (count($argv) < 6) {
    echo 'Missing parameters. Usage:' . "\n" .
        'php5.6 web/typo3conf/ext/rkw_shop/Cli/OrderToShop.php [HOST] [DATABASE] [USER] [PASS] [PID-DEFAULT] [PID-FOR-SHIPPING-ADDRESSES]' . "\n";
    exit(1);
}

$credentials = [
    'host' => $argv[1],
    'database' => $argv[2],
    'user' => $argv[3],
    'pass' => $argv[4]
];

$pidDefault = $argv[5];
$pidShippingAddress = isset($argv[6]) ? $argv[6] : $argv[5];


$titlesToMM = new \RKW\MigrateTitlesToMM($credentials);
$titlesToMM->migrate();
echo "Done.\n";


# ================================================================================

require_once ('vendor/autoload.php');

class MigrateTitlesToMM
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
    public function __construct($credentials) {
        $this->pdo = new \PDO('mysql:host='. $credentials['host'] . ';dbname=' . $credentials['database'] . ';charset=utf8', $credentials['user'], $credentials['pass']);
    }


    /**
     * Builds MySQL for updates and deletes
     *
     * @param int $pid
     * @param int $pidShippingAddress
     * @return string
     * @throws \Exception
     */
    public function moveIt ($pid = 2829, $pidShippingAddress = 618)
    {

        // cleanup
        //=================================================
        $this->pdo->query('
            TRUNCATE tx_rkwshop_domain_model_product;
            TRUNCATE tx_rkwshop_domain_model_stock;
            TRUNCATE tx_rkwshop_domain_model_order;
            TRUNCATE tx_rkwshop_domain_model_orderitem;
            TRUNCATE tx_rkwregistration_domain_model_shippingaddress;
            DELETE FROM tt_content WHERE list_type = \'rkwshop_itemlist\' AND CType = \'list\';
            DELETE FROM sys_file_reference WHERE tablenames = \'tx_rkwshop_domain_model_product\';
        ');


        //=================================================

        $newProducts = [];
        $newPlugins = [];
        $deletePlugins = [];
        $newStocks = [];
        $newFileReferences = [];

        //=================================================
        // fetch pages
        $sqlPages = 'SELECT uid, title, subtitle, tx_rkwbasics_series, tx_rkwsearch_pubdate, tx_rkwauthors_authorship, tx_rkwbasics_file, deleted, hidden, tstamp, crdate 
            FROM pages
            WHERE 
                tx_bmpdf2content_is_import = 1
                AND tx_bmpdf2content_is_import_sub = 0
        ';

        $sthPages = $this->pdo->prepare($sqlPages);
        $sthPages->execute();
        $pages = $sthPages->fetchAll(\PDO::FETCH_ASSOC);

        $seriesAdmins = [];
        foreach ($pages as $page) {


            // fetch authors from mm-table
            $authorList = [];
            if ($page['tx_rkwauthors_authorship']) {
                $sqlAuthors = 'SELECT uid_foreign 
                    FROM tx_rkwauthors_pages_authors_mm
                    WHERE 
                        uid_local = ?
                    ORDER BY sorting ASC
                ';

                $sthAuthors = $this->pdo->prepare($sqlAuthors);
                $sthAuthors->execute([$page['uid']]);
                $authors = $sthAuthors->fetchAll(\PDO::FETCH_ASSOC);

                foreach($authors as $author) {
                    $authorList[] = $author['uid_foreign'];
                }
            }

            // copy file reference from sys_file_reference
            if ($page['tx_rkwbasics_file']) {
                $sqlFiles = 'SELECT *
                    FROM sys_file_reference
                    WHERE 
                        uid_foreign = ?
                        AND fieldname = \'txRkwbasicsFile\'
                        AND tablenames = \'pages\'
                        AND deleted = 0
                        AND hidden = 0
                    LIMIT 1;
                ';

                $sthFiles = $this->pdo->prepare($sqlFiles);
                $sthFiles->execute([$page['uid']]);
                $files = $sthFiles->fetchAll(\PDO::FETCH_ASSOC);

                foreach($files as $file) {
                    $file['fieldname'] = 'download';
                    $file['tablenames'] = 'tx_rkwshop_domain_model_product';
                    unset ($file['uid']);
                    $newFileReferences[] = $file;
                }
            }

            $newProduct = [
                'uid' => $page['uid'],
                'pid' => $pid,
                'tstamp' => $page['tstamp'],
                'crdate' => $page['crdate'],
                'deleted' => $page['deleted'],
                'hidden' => $page['hidden'],
                'title' => $page['title'],
                'subtitle' => $page['subtitle'],
                'page' => $page['uid'],
                'stock' => 0,
                'product_bundle' => $page['tx_rkwbasics_series'],
                'record_type' => '\RKW\RkwShop\Domain\Model\ProductDownload',
                'publishing_date' => $page['tx_rkwsearch_pubdate'],
                'download' => $page['tx_rkwbasics_file'],
                'author' => implode(',', $authorList)
            ];

            if (
                ($page['tx_rkwbasics_series'])
                && (! isset($seriesAdmins[$page['tx_rkwbasics_series']]))
            ) {
                $seriesAdmins[$page['tx_rkwbasics_series']] = [];
            }

            $newStock = [
                'uid' => $newProduct['uid'],
                'pid' => $pid,
                'product' => $newProduct['uid'],
                'amount' => 0,
                'comment' => 'Initial stock',
                'tstamp' => time(),
                'crdate' => time(),
            ];

            // get flexforms in current page
            $sqlPlugins = 'SELECT uid, header, colPos, pi_flexform, deleted, hidden, tstamp, crdate 
                FROM tt_content
                WHERE 
                    pid = ?
                    AND CType = "list"
                    AND list_type = "rkworder_rkworder"
                    ORDER BY deleted ASC, hidden ASC
                    LIMIT 1
            ';

            $sthPlugins = $this->pdo->prepare($sqlPlugins);
            $sthPlugins->execute([intval($page['uid'])]);
            $plugins = $sthPlugins->fetchAll(\PDO::FETCH_ASSOC);

            // get settings from flexform
            foreach ($plugins as $plugin) {
                $settings = $this->serializeFlexformDataFromDb($plugin['pi_flexform']);

                // set admins
                if ($settings['settings.mail.backendUser']) {

                    if (
                        ($page['tx_rkwbasics_series'])
                        && (isset($seriesAdmins[$page['tx_rkwbasics_series']]))
                    ) {
                        $seriesAdmins[$page['tx_rkwbasics_series']] = array_merge($seriesAdmins[$page['tx_rkwbasics_series']], explode(',', $settings['settings.mail.backendUser']));
                    } else {
                        $newProduct['backend_user'] = $settings['settings.mail.backendUser'];
                    }
                }


                if (
                    ($plugin['deleted'] == 0)
                    && ($plugin['hidden'] == 0)
                ) {

                    // set stock to available value
                    $newStock['amount'] = 500;
                    $newProduct['record_type'] = 0;
                    $newProduct['stock'] = $newProduct['uid'];

                    $newPlugin = [
                        'pid' => $page['uid'],
                        'header' => $plugin['header'],
                        'tstamp' => $plugin['tstamp'],
                        'crdate' => $plugin['crdate'],
                        'pi_flexform' => $this->getNewFlexform([$newProduct['uid']]),
                        'CType' => 'list',
                        'list_type' => 'rkwshop_itemlist',
                        'colPos' => $plugin['colPos'],
                    ];

                    // push
                    $newPlugins[] = $newPlugin;
                    $deletePlugins[] = $plugin['uid'];
                    $newStocks[] = $newStock;
                }

            }

            // push
            $newProducts[] = $newProduct;

        }

        //======================
        // series
        $sqlSeries = 'SELECT uid, name, short_name, description, deleted, hidden, tstamp, crdate 
            FROM tx_rkwbasics_domain_model_series
            WHERE 
                1 = 1
        ';

        $sthSeries = $this->pdo->prepare($sqlSeries);
        $sthSeries->execute();
        $series = $sthSeries->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($series as $serie) {

            $newProduct = [
                'uid' => $serie['uid'],
                'pid' => $pid,
                'tstamp' => $serie['tstamp'],
                'crdate' => $serie['crdate'],
                'deleted' => $serie['deleted'],
                'hidden' => $serie['hidden'],
                'title' => $serie['name'],
                'subtitle' => $serie['short_name'],
                'record_type' => '\\RKW\\RkwShop\\Domain\\Model\\ProductBundle',
                'allow_single_order' => false,
                'stock' => $serie['uid'],
                'backend_user' => (isset($seriesAdmins[$serie['uid']]) ? implode(',', array_unique($seriesAdmins[$serie['uid']])) : '')
            ];


            if (
                (strpos($serie['name'], 'RKW Magazin') === 0)
                || (strpos($serie['name'], 'ibr') === 0)
            ) {
                $newProduct['record_type'] = '\\RKW\\RkwShop\\Domain\\Model\\ProductSubscription';
                $newProduct['allow_single_order'] = true;
                $newProduct['stock'] = 0;

            } else {

                $newStock = [
                    'uid' => $newProduct['uid'],
                    'pid' => $pid,
                    'product' => $newProduct['uid'],
                    'amount' => 500,
                    'comment' => 'Initial stock',
                    'tstamp' => time(),
                    'crdate' => time(),
                ];

                // push
                $newStocks[] = $newStock;
            }

            // push
            $newProducts[] = $newProduct;
        }

        // ==============================
        // save products
        foreach ($newProducts as $newProduct) {
            $this->insert('tx_rkwshop_domain_model_product', $newProduct);
        }

        // save stocks
        foreach ($newStocks as $newStock) {
            $this->insert('tx_rkwshop_domain_model_stock', $newStock);
        }

        // save plugins
        foreach ($newPlugins as $newPlugin) {
            $this->insert('tt_content', $newPlugin);
        }

        // delete plugins
        foreach ($deletePlugins as $deletePlugin) {
            $this->delete('tt_content', $deletePlugin);
        }

        // save fileReferences
        foreach ($newFileReferences as $newFileReference) {
            $this->insert('sys_file_reference', $newFileReference);
        }
        // ==============================
        // Work through orders
        $sqlSeries = 'SELECT *
            FROM tx_rkworder_domain_model_order
            WHERE 
                1 = 1
        ';

        $sthSeries = $this->pdo->prepare($sqlSeries);
        $sthSeries->execute();
        $oldOrders = $sthSeries->fetchAll(\PDO::FETCH_ASSOC);


        $newOrders = [];
        $newOrderItems = [];
        $newShippingAddresses = [];

        $cnt = 0;
        foreach ($oldOrders as $oldOrder) {

            if (! $oldOrder['pages']) {
                continue;
            }

            $newOrder = [
                'uid' => $oldOrder['uid'],
                'pid' => $oldOrder['pid'],
                'tstamp' => $oldOrder['tstamp'],
                'crdate' => $oldOrder['crdate'],
                'hidden' => $oldOrder['hidden'],
                'deleted' => $oldOrder['deleted'],

                'email' => $oldOrder['email'],
                'frontend_user' => $oldOrder['frontend_user'],
                'remark' => $oldOrder['remark'],
                'status' => $oldOrder['status']
            ];

            $newShippingAddress = [
                'uid' => $newOrder['uid'] + 5000,
                'pid' => $pidShippingAddress,
                'gender' => $oldOrder['gender'],
                'first_name' => $oldOrder['first_name'],
                'last_name' => $oldOrder['last_name'],
                'company' => $oldOrder['company'],
                'address' => $oldOrder['address'],
                'zip' => $oldOrder['zip'],
                'city' => $oldOrder['city'],
                'frontend_user' => $oldOrder['frontend_user'],
                'tstamp' => $oldOrder['tstamp'],
                'crdate' => $oldOrder['crdate'],
                'hidden' => $oldOrder['hidden'],
                'deleted' => $oldOrder['deleted']
            ];

            // link and  push
            $newOrder['shipping_address'] = $newShippingAddress['uid'];
            $newShippingAddresses[] = $newShippingAddress;

            // build order item
            $newOrderItem = [
                'uid' => $oldOrder['uid'],
                'pid' => $pid,
                'ext_order' => $newOrder['uid'],
                'product' => $oldOrder['pages'],
                'amount' => $oldOrder['amount'],
                'tstamp' => $oldOrder['tstamp'],
                'crdate' => $oldOrder['crdate'],
                'deleted' => $oldOrder['deleted']
            ];

            // push and add
            $newOrderItems[] = $newOrderItem;
            $newOrder['order_item'] = 1;


            // check for subscribe or series
            if (
                ($oldOrder['subscribe'])
                || ($oldOrder['send_series'])
            ) {

                $sqlProduct = 'SELECT uid, product_bundle FROM tx_rkwshop_domain_model_product
                    WHERE uid = ? AND product_bundle > 0
                ';
                $sthProduct = $this->pdo->prepare($sqlProduct);
                $sthProduct->execute([$oldOrder['pages']]);
                if ($product = $sthProduct->fetch(\PDO::FETCH_ASSOC)) {

                    // build order item
                    $newOrderItem = [
                        'uid' => $oldOrder['uid'] + 5000,
                        'pid' => $pid,
                        'ext_order' => $newOrder['uid'],
                        'product' => $product['product_bundle'],
                        'amount' => 1,
                        'tstamp' => $oldOrder['tstamp'],
                        'crdate' => $oldOrder['crdate'],
                        'deleted' => $oldOrder['deleted']
                    ];

                    // push and add
                    $newOrder['order_item'] += 1;
                    $newOrderItems[] = $newOrderItem;
                }
            }

            // push
            $newOrders[] = $newOrder;
            $cnt++;
        }


        // save orders
        foreach ($newOrders as $newOrder) {
            $this->insert('tx_rkwshop_domain_model_order', $newOrder);
        }

        // save orderItems
        foreach ($newOrderItems as $newOrderItem) {
            $this->insert('tx_rkwshop_domain_model_orderitem', $newOrderItem);
        }

        // save shippingAdress
        foreach ($newShippingAddresses as $newShippingAddress) {
            $this->insert('tx_rkwregistration_domain_model_shippingaddress', $newShippingAddress);
        }

        exit();
    }


    /**
     * insert
     *
     * @param string $table
     * @param array $insertProperties
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
                return is_bool($value) ? (int) $value : $value;
            },
            $values
        );
        $sql = 'INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . $placeholder . ')';

        $sth = $this->pdo->prepare($sql);
        if ($result = $sth->execute($values)) {
            return $this->pdo->lastInsertId();
        } else {
            $error = $sth->errorInfo();
            throw new \Exception($error[2] . ' on execution of "' . $sth->queryString . '" with params ' .  print_r($insertProperties, true));
        }

    }


    /**
     * insert
     *
     * @param string $table
     * @param int $uid
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
            throw new \Exception($error[2] . ' on execution of "' . $sth->queryString . '" with params ' .  print_r($uid, true));
        }

    }

    /**
     * serialize flexform-data of tt_content element from string to array
     *
     * @param string $flexform
     * @return array
     */
    protected function serializeFlexformDataFromDb($flexform)
    {
        $flexformData = array();
        $xml = simplexml_load_string($flexform);
        if (
            (isset($xml))
            && (isset($xml->data))
            && (is_object($xml->data->sheet))
        ) {
            foreach ($xml->data->sheet as $sheet) {
                foreach ($sheet->language->field as $field) {
                    $flexformData[str_replace('', '', (string)$field->attributes())] = (string)$field->value;
                }
            }
        }


        return $flexformData;
        //===
    }


    /**
     * Return new flexform
     * @param array $productsUid
     * @return string
     */
    protected function getNewFlexform($products)
    {
        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.products">
                    <value index="vDEF">' . implode(',', $products) . '</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
    }
}

