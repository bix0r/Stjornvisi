<?php

namespace Stjornvisi\Service;

use DateTime;
use PDOException;

class Company extends AbstractService {

	const NAME = 'company';

    /**
     * Get one company.
     *
     * @param int $id
     * @return bool|\stdClass
     * @throws Exception
     */
    public function get( $id ){
        try{
            //COMPANY
            //  get company
            $statement = $this->pdo->prepare("SELECT * FROM Company C WHERE C.id = :id");
            $statement->execute(array(
                'id' => $id
            ));
            $company = $statement->fetchObject();

            //IF FOUND
            //  if company found
            if( $company ){
                $company->created = new DateTime($company->created);
                //GET MEMBERS
                //  get members of company
                $membersStatement = $this->pdo->prepare("
                  SELECT U.id, U.name, U.email, U.title, ChU.key_user FROM Company_has_User ChU
                    JOIN User U ON (ChU.user_id = U.id)
                    WHERE ChU.company_id = :id
                    ORDER BY ChU.key_user DESC, U.name;
                ");
                $membersStatement->execute(array(
                    'id' => $company->id
                ));
                $company->members = $membersStatement->fetchAll();
                $company->members = ($company->members)
                    ? $company->members
                    : array() ;
            }
            $this->getEventManager()->trigger('read', $this, array(
                get_class($this).'::'.__FUNCTION__
            ));
            return $company;
        }catch (PDOException $e ){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                    isset($membersStatement)?$membersStatement->queryString:null,
                )
            ));
            throw new Exception("Can't get company [{$id}]",0,$e);
        }
    }

    /**
     * Get list of all companies.
     * An optional parameter can be given, an array of
     * business_type to exclude from the list.
     *
     * @param array $exclude
     * @return array
     * @throws Exception
     */
    public function fetchAll( array $exclude = array() ){
        try{
            if( empty($exclude) ){
                $statement = $this->pdo->prepare("SELECT * FROM Company C ORDER BY C.name");
            }else{
                $statement = $this->pdo->prepare("
                SELECT * FROM Company C
                WHERE C.business_type NOT IN (".
                    implode(',',array_map(function($i){ return "'{$i}'"; },$exclude) ).
                    ")
                    ORDER BY C.name
                ");
            }
            $statement->execute();
            $companies =  $statement->fetchAll();
            foreach( $companies as $company ){
                $company->created = new DateTime($company->created);
            }
            $this->getEventManager()->trigger('read', $this, array( __FUNCTION__));
            return $companies;
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception'=>$e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't fetch all companies",0, $e);
        }

    }

    /**
     * Change the role of employee at a company.
     *
     * @param int $company_id
     * @param int $user_id
     * @param int $role 1|0
     * @return int row count
     * @throws Exception | \InvalidArgumentException
     */
    public function setEmployeeRole( $company_id, $user_id, $role = 0 ){
        //INSPECT ROLE ARGUMENT
        //
        if( !in_array((int)$role,array(0,1)) ){
            throw new \InvalidArgumentException(
                "role can only be 0 or 1, [{$role}] provided"
            );
        }
        //UPDATE
        //
        try{
            $statement = $this->pdo->prepare("
                UPDATE Company_has_User SET key_user = :role
                WHERE user_id = :user_id AND company_id = :company_id
            ");
            $statement->execute(array(
                'role' => $role,
                'user_id' => $user_id,
                'company_id' => $company_id
            ));
            $this->getEventManager()->trigger('update', $this, array(__FUNCTION__));
            return $statement->rowCount();
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null
                ),
            ));
            throw new Exception(
                "Can't set company's user role, company:[{$company_id}], ".
                "user:[{$user_id}], role:[{$role}]"
            ,0,$e);
        }
    }

    /**
     * Get company by user.
     *
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getByUser( $id ){
        try{
            $statement = $this->pdo->prepare("
              SELECT C.*, ChU.key_user FROM Company_has_User ChU
              JOIN Company C ON (C.id = ChU.company_id)
              WHERE ChU.user_id = :id;");
            $statement->execute(array('id'=>$id));
            $this->getEventManager()->trigger('read', $this, array(__FUNCTION__));
            return $statement->fetchAll();
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't get companies by user, user:[{$id}]",0,$e);
        }

    }

    /**
     * Update company.
     *
     * @param int $id
     * @param array $data
     * @return int affected rows
     * @throws Exception
     */
    public function update( $id, array $data ){
        try{
			unset($data['submit']);
            $updateString = $this->updateString('Company',$data, "id={$id}");
            $statement = $this->pdo->prepare($updateString);
            $statement->execute($data);
            $this->getEventManager()->trigger('update', $this, array(__FUNCTION__));
			$data['id'] = $id;
            $this->getEventManager()->trigger('index', $this, array(
				0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                'id' => $id,
				'name' => Company::NAME,
            ));
            return $statement->rowCount();
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Can't update company. company:[{$id}]",0,$e);
        }
    }

    /**
     * @param array $data
     * @return int last ID
     * @throws Exception
     */
    public function create( array $data ){
        try{
            unset($data['submit']);
            $data['created'] = date('Y-m-d H:i:s');

            $insertString = $this->insertString('Company',$data);
            $createStatement = $this->pdo->prepare($insertString);
            $createStatement->execute($data);

            $id = (int)$this->pdo->lastInsertId();

            $this->getEventManager()->trigger('create', $this, array(__FUNCTION__));
			$data['id'] = $id;
            $this->getEventManager()->trigger('index', $this, array(
				0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                'id' => $id,
				'name' => Company::NAME,
            ));
            return $id;
        }catch (PDOException $e){
            $this->getEventManager()->trigger('create', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($createStatement)?$createStatement->queryString:null,
                )
            ));
            throw new Exception("Can't create company",0,$e);
        }
    }

    /**
     * Connect user to company.
     *
     * @param $company_id
     * @param $user_id
     * @param int $role
     * @return int affected rows
     * @throws Exception
     */
    public function addUser( $company_id, $user_id, $role = 0 ){
        try{
            $statement = $this->pdo->prepare("
                INSERT INTO Company_has_User (user_id, company_id, key_user)
                VALUES (:user_id, :company_id, :role )
            ");
            $statement->execute(array(
                'user_id' => $user_id,
                'company_id' => $company_id,
                'role' => $role
            ));
            $this->getEventManager()->trigger('update', $this, array(__FUNCTION__));
            return $statement->rowCount();
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception(
                "Can't connect user to company. company:[{$company_id}], ".
                "user:[{$user_id}], role:[{$role}]",
                0,$e);
        }
    }

    /**
     * Delete company
     * @param int $id
     * @return int affected rows
     * @throws Exception
     */
    public function delete( $id ){
        try{
            $statement = $this->pdo->prepare("DELETE FROM Company WHERE id = :id");
            $statement->execute(array(
                'id' => $id
            ));
            $this->getEventManager()->trigger('delete', $this, array(__FUNCTION__));
            $this->getEventManager()->trigger('index', $this, array(
				0 => __NAMESPACE__ .':'.get_class($this).':'. __FUNCTION__,
                'id' => $id,
				'name' => Company::NAME,
            ));
            return $statement->rowCount();
        }catch (PDOException $e){
            $this->getEventManager()->trigger('error', $this, array(
                'exception' => $e->getTraceAsString(),
                'sql' => array(
                    isset($statement)?$statement->queryString:null,
                )
            ));
            throw new Exception("Cant delete company. company[{$id}]",0,$e);
        }
    }
}
