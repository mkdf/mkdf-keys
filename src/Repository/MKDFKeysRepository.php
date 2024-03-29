<?php

namespace MKDF\Keys\Repository;

use Zend\Db\Adapter\Adapter;
use MKDF\Core\Entity\Bucket;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSet;


class MKDFKeysRepository implements MKDFKeysRepositoryInterface
{
    private $_config;
    private $_adapter;
    private $_queries;

    public function __construct($config)
    {
        $this->_config = $config;
        $this->_adapter = new Adapter([
            'driver'   => 'Pdo_Mysql',
            'database' => $this->_config['db']['dbname'],
            'username' => $this->_config['db']['user'],
            'password' => $this->_config['db']['password'],
            'host'     => $this->_config['db']['host'],
            'port'     => $this->_config['db']['port']
        ]);
        $this->buildQueries();
    }
    

    private function fp($param) {
        return $this->_adapter->driver->formatParameterName($param);
    }
    private function qi($param) {
        return $this->_adapter->platform->quoteIdentifier($param);
    }
    private function buildQueries(){
        $this->_queries = [
            'isReady'           => 'SELECT ID FROM accesskey LIMIT 1',
            'allKeys'       => 'SELECT * FROM accesskey',
            'allUserKeys'   => 'SELECT * FROM accesskey WHERE user_id=' . $this->fp('userId'),
            'oneKey'        => 'SELECT id,name,description,uuid,user_id FROM accesskey WHERE id = ' . $this->fp('id').' AND user_id='.$this->fp('userId'),
            'uuidFromId'    => 'SELECT uuid FROM accesskey WHERE id = '. $this->fp('id'),
            'oneKeyFromUuid'        => 'SELECT id,name,description,uuid,user_id FROM accesskey WHERE uuid = ' . $this->fp('uuid').' AND user_id='.$this->fp('userId'),
            'userDatasetKeyCount' => 'SELECT COUNT(a.uuid) AS count_keys FROM accesskey_permissions ap, accesskey a WHERE ap.key_id = a.id AND a.user_id = '. $this->fp('user_id').
                ' AND ap.dataset_id = '. $this->fp('dataset_id'),
            'userDatasetKeys' => 'SELECT a.name, a.uuid, ap.permission, ap.id '.
                                    'FROM accesskey_permissions ap, accesskey a '.
                                    'WHERE ap.key_id = a.id '.
                                    'AND a.user_id = '.$this->fp('user_id'). ' '.
                                    'AND ap.dataset_id = '.$this->fp('dataset_id'). ' ',
                                    //'AND ((ap.permission = "a") OR (ap.permission = "r"))',
            'allDatasetKeys'=> 'SELECT a.id, a.name, a.uuid, a.user_id, u.email, u.full_name, ap.permission '.
                'FROM accesskey_permissions ap, accesskey a, user u '.
                'WHERE ap.key_id = a.id '.
                'AND a.user_id = u.id '.
                'AND ap.dataset_id = '.$this->fp('dataset_id'). ' ',
            'keyDatasets'   => 'SELECT d.id, d.title, p.permission, d.uuid FROM accesskey_permissions p, dataset d, accesskey k WHERE '.
                'p.dataset_id = d.id AND '.
                'p.key_id = k.id AND '.
	            'k.id=' . $this->fp('id').' AND '.
                'k.user_id = ' . $this->fp('userId'),
            'insertKey'      => 'INSERT INTO accesskey (name,description,uuid,user_id) VALUES (' .   $this->fp('name') . ', ' . $this->fp('description') . ', '.$this->fp('uuid').', ' . $this->fp('user_id') .')',
            'updateKey'      => 'UPDATE accesskey SET ' .
                $this->qi('name') . '=' . $this->fp('name') . ', ' .
                $this->qi('description') .'='. $this->fp('description').
                ' WHERE id = ' . $this->fp('id'),
            'deleteKey'      => 'DELETE FROM accesskey WHERE id = ' . $this->fp('id'),
            'findPermission' => 'SELECT id, permission FROM accesskey_permissions WHERE dataset_id = '.$this->fp('dataset_id').' AND key_id = '.$this->fp('key_id'),
            'createPermission' => 'INSERT INTO accesskey_permissions '.
                '(key_id,dataset_id,permission,date_created,date_modified) '.
                ' VALUES ('.$this->fp('key_id').','.$this->fp('dataset_id').','.$this->fp('permission').',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ',
            'updatePermission' => 'UPDATE accesskey_permissions SET '.
                'permission = '.$this->fp('permission').', date_modified = CURRENT_TIMESTAMP '.
                ' WHERE key_id = '.$this->fp('key_id').' AND dataset_id = '.$this->fp('dataset_id'),
            'removePermissionByUUID' => 'DELETE ap FROM accesskey_permissions ap, accesskey a WHERE a.uuid = '.$this->fp('key_uuid').
                ' AND ap.key_id = a.id AND ap.dataset_id = '.$this->fp('dataset_id'),
        ];
    }

    private function addQueryLimit($query, $limit) {
        return $query . ' LIMIT ' . $limit;
    }

    private function getQuery($query){
        return $this->_queries[$query];
    }

    private function genUuid () {
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * @return array returns an array of Bucket
     */
    public function findAllUserKeys($userId, $limit = 0){
        $keys = [];
        $query = $this->getQuery('allUserKeys');
        if ($limit > 0) {
            $query = $this->addQueryLimit($query, $limit);
        }
        $statement = $this->_adapter->createStatement($query);
        $result    = $statement->execute(['userId'=>$userId]);
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            foreach ($resultSet as $row) {
                $b = new Bucket();
                $b->setProperties($row);
                array_push($keys, $b);
            }
            return $keys;
        }
        return [];
    }
    
    /**
     * @param int $id collection id
     * @return Bucket
     */
    public function findKey($id, $userID){
        $statement = $this->_adapter->createStatement($this->getQuery('oneKey'));
        $result    = $statement->execute(['id'=>$id, 'userId'=>$userID]);
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            if ($result->count() > 0) {
                $b = new Bucket();
                $b->setProperties($result->current());
                return $b;
            }
        }
        return null;
    }

    public function findKeyFromUuid($keyUuid,$user_id) {
        $statement = $this->_adapter->createStatement($this->getQuery('oneKeyFromUuid'));
        $result    = $statement->execute(['uuid'=>$keyUuid, 'userId'=>$user_id]);
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            if ($result->count() > 0) {
                $b = new Bucket();
                $b->setProperties($result->current());
                return $b;
            }
        }
        return null;
    }

    public function getKeyUuidFromId($id) {
        $statement = $this->_adapter->createStatement($this->getQuery('uuidFromId'));
        $result    = $statement->execute(['id'=>$id]);
        $keys = [];
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            foreach ($resultSet as $row) {
                $item = [
                    'uuid' => $row['uuid'],
                ];
                $keys[] = $item;
            }
        }
        return $keys[0];
    }

    public function findKeyDatasets($id, $userID) {
        $datasets = [];
        $statement = $this->_adapter->createStatement($this->getQuery('keyDatasets'));
        $result    = $statement->execute(['id'=>$id, 'userId'=>$userID]);
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            foreach ($resultSet as $row) {
                $b = new Bucket();
                $b->setProperties($row);
                array_push($datasets, $row);
            }
        }
        return $datasets;
    }

    public function userHasDatasetKey($userID, $datasetID) {
        $statement = $this->_adapter->createStatement($this->getQuery('userDatasetKeyCount'));
        $result    = $statement->execute(['user_id'=>$userID, 'dataset_id'=>$datasetID]);
        $keyCount = 0;
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $currentResult = $result->current();
            $keyCount = (int)$currentResult['count_keys'];
        }
        if ($keyCount > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public function userDatasetKeys($userID, $datasetID) {
        $statement = $this->_adapter->createStatement($this->getQuery('userDatasetKeys'));
        $result    = $statement->execute(['user_id'=>$userID, 'dataset_id'=>$datasetID]);
        $keys = [];
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            foreach ($resultSet as $row) {
                $item = [
                    'keyName' => $row['name'],
                    'keyUUID' => $row['uuid'],
                    'permission' => $row['permission'],
                ];
                $keys[] = $item;
            }
        }
        return $keys;
    }

    //Return all keys registered for access on a dataset (for use by dataset owner)
    public function allDatasetKeys($datasetID) {
        $statement = $this->_adapter->createStatement($this->getQuery('allDatasetKeys'));
        $result    = $statement->execute(['dataset_id'=>$datasetID]);
        $keys = [];
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            foreach ($resultSet as $row) {
                $item = [
                    'keyID' => $row['id'],
                    'keyName' => $row['name'],
                    'keyUUID' => $row['uuid'],
                    'userID'  => $row['user_id'],
                    'userEmail' => $row['email'],
                    'userFullname' => $row['full_name'],
                    'permission' => $row['permission']
                ];
                $keys[] = $item;
            }
        }
        return $keys;
    }

    public function insertKey($data) {
        $data['uuid'] = $this->genUuid();
        $statement = $this->_adapter->createStatement($this->getQuery('insertKey'));
        $statement->execute($data);
        $id = $this->_adapter->getDriver()->getLastGeneratedValue();
        return $id;
    }

    public function updateKey($id, $name, $description) {
        $statement = $this->_adapter->createStatement($this->getQuery('updateKey'));
        $statement->execute(['id'=>$id,'name'=>$name,'description'=>$description]);
        // FIXME Need to decide whether return anything useful
        return true;
    }

    public function deleteKey($id){
        $statement = $this->_adapter->createStatement($this->getQuery('deleteKey'));
        $outcome = $statement->execute(['id'=>$id]);
        return true;
    }

    public function removeKeyUUIDPermission($key_uuid, $dataset_id) {
        $statement = $this->_adapter->createStatement($this->getQuery('removePermissionByUUID'));
        $statement->execute(['key_uuid'=>$key_uuid,'dataset_id'=>$dataset_id]);
        return true;
    }

    /*
     * DISABLED key permissions remember their original state by becoming an uppercase version of
     * their original self. ie a->A r->R w->W
     * Restoring original permissions is a case of checking that the current permission is in
     * disabled state and converting it to lower case.
     */
    public function restoreKeyPermission($keyId, $datasetId) {
        $statement = $this->_adapter->createStatement($this->getQuery('findPermission'));
        $result    = $statement->execute(['dataset_id'=>$datasetId, 'key_id'=>$keyId]);
        $found = false;
        $existingPermission = null;
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            if (count($resultSet) > 0){
                $found = true;
                $currentResult = $result->current();
                $existingPermission = $currentResult['permission'];
            }
        }
        if ($found && (ctype_upper($existingPermission))) {
            $newPermission = strtolower($existingPermission);
            $this->setKeyPermission($keyId, $datasetId, $newPermission);
            return $newPermission;
        }
        else {
            throw new \Exception("Attempted reactive a key that wasn't disabled");
        }
    }

    public function setKeyPermission($keyId, $datasetId, $permission) {
        //First check if the key/dataset pairing exists
        $statement = $this->_adapter->createStatement($this->getQuery('findPermission'));
        $result    = $statement->execute(['dataset_id'=>$datasetId, 'key_id'=>$keyId]);
        $found = false;
        $existingPermission = null;
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
            if (count($resultSet) > 0){
                $found = true;
                $currentResult = $result->current();
                $existingPermission = $currentResult['permission'];
            }
        }
        //Then either create or amend a key/dataset pairing
        if ($found){
            //update the permission entry
            if ((!is_null($existingPermission)) && ($permission == 'd')) {
                $permission = strtoupper($existingPermission);
            }
            $statement = $this->_adapter->createStatement($this->getQuery('updatePermission'));
            $statement->execute(['dataset_id'=>$datasetId, 'key_id'=>$keyId, 'permission'=>$permission]);
        }
        else {
            //create a new permission entry
            $statement = $this->_adapter->createStatement($this->getQuery('createPermission'));
            $statement->execute(['dataset_id'=>$datasetId, 'key_id'=>$keyId, 'permission'=>$permission]);
        }
    }
    
    public function init(){
        try {
            $statement = $this->_adapter->createStatement($this->getQuery('isReady'));
            $result    = $statement->execute();
            return false;
        } catch (\Exception $e) {
            // XXX Maybe raise a warning here?
        }
        $sql = file_get_contents(dirname(__FILE__) . '/../../sql/setup.sql');
        $this->_adapter->getDriver()->getConnection()->execute($sql);
        return true;
    }
}
