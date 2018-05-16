<?php
/**
 * Created by PhpStorm.
 * User: Shrik
 * Date: 5/15/2018
 * Time: 2:06 PM
 */

class ConnxusApi
{
    private $status = "Error";
    private $code = 401;
    private $outputData;
    private $requestMethod;
    private $requiredVariables;
    private $allowedVariables;
    private $dbConnection;
    private $userData;

    /**
     * ConnxusApi constructor.
     */
    public function __construct()
    {
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        require_once "database.php";
        $this->dbConnection = $conn;
        $this->requestMethod = $_SERVER["REQUEST_METHOD"];
        $input = json_decode(file_get_contents('php://input'),true);
        $this->userData = empty($input) ? $_REQUEST : $input;
        //print_r($this->userData);exit();
        $this->requestFunction();
        $this->jsonResponse();
    }

    private function requestFunction(){
        //set up the variables for related request
        $this->setVariables();
        //Return for invalid variables
        if(!$this->validateVariables()){
            return;
        }
        switch ($this->requestMethod) {
            case "GET":
                $this->methodGETSql();
                break;
            case "POST":
                $this->methodPOSTSql();
                break;
            case "DELETE":
                $this->methodDELETESql();
                break;
            case "PUT":
                $this->methodPUTSql();
                break;
            default:
                $this->outputData = "Bad request : " . $this->requestMethod;
        }
    }

    private function setVariables(){
        switch ($this->requestMethod) {
            case "POST":
                $this->requiredVariables = array("name","address","city","state","zip");
                //keys are field name and values are size as defined in table
                $this->allowedVariables = array("name"=>255,"description"=>65535,"address"=>255,"address2"=>255,"city"=>255,"state"=>200,"zip"=>9);
                break;
            case "PUT":
                $this->requiredVariables = array("id","company");
                $this->allowedVariables = array("id"=>10,"company"=>0);
                break;
            default:
                // case GET and DELETE
                $this->requiredVariables = array("id");
                $this->allowedVariables = array("id"=>10);
                break;
        }
    }

    private function validateVariables(){
        //setting ID for GET, PUT and DELETE from given URL
        if(!$this->setIdParameter()){
            return false;
        }
        //checking required variables
        if(!$this->validateRequired()){
            return false;
        }
        //checking allowed variables
        if(!$this->validateAllowed($this->userData)){
            return false;
        }
        //Size validation as given in table
        if(!$this->validateSize($this->userData)){
            return false;
        }
        return true;
    }

    private function setIdParameter(){
        if ($this->requestMethod != 'POST') {
            $requestUrlId = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (empty($requestUrlId[0])) {
                $this->code = 404;
                $this->outputData = $this->requestMethod . " request should receive the id parameter from the path";
                return false;
            }
            $this->userData["id"] = $requestUrlId[0];
        }
        return true;
    }

    private function validateRequired(){
        $requiredNotExist = array();
        foreach ($this->requiredVariables as $variable) {
            if (!array_key_exists($variable, $this->userData)) {
                $requiredNotExist[] = $variable;
            }
        }

        if (!empty($requiredNotExist)) {
            $this->outputData = "Missing Required variable(s)  : " . implode(', ', $requiredNotExist);
            return false;
        }
        return true;
    }

    private function validateAllowed($givenArr){
        $extraValues = array();
        foreach ($givenArr as $key => $val) {
            if (!array_key_exists($key, $this->allowedVariables) && $key!='company') {
                $extraValues[] = $key;
            }
        }

        if (!empty($extraValues)) {
            $this->outputData = "Not allowed variable(s): " . implode(', ', $extraValues);
            return false;
        }
        return true;
    }

    private function validateSize($givenArr)
    {
        foreach ($this->allowedVariables as $key => $val) {
            if($key == 'company')
                continue;
            //checking size for address array in PUT method
            if(is_array($givenArr[$key])){
                foreach ($givenArr[$key] as $k => $v){
                    if (strlen($givenArr[$key][$k]) > $val) {
                        $this->outputData = "For $key\[$k\], size should not exceed $val";
                        return false;
                    }
                }
                continue;
            }
            if (strlen($givenArr[$key]) > $val) {
                $this->outputData = "For $key, size should not exceed $val";
                return false;
            }
        }
        return true;
    }

    private function methodGETSql(){
        $row = $this->getRecordForId($this->userData['id']);
        if (!empty($row)) {
            $this->code = 200;
            $this->status = 'Success';
            $this->outputData = array(
                'company_id' => $row['company_id'],
                'name' => $row['name'],
                'address' => array(
                    $row['address']
                ),
                'city' => $row['city'],
                'state' => $row['state'],
                'zip' => $row['zip']
            );
            !empty($row['description'])? $this->outputData['description'] = $row['description'] : '';
            !empty($row['address_2'])? $this->outputData['address'][] = $row['address_2'] : '';
            $this->jsonResponse("company");
        } else {
            $this->outputData = "No data exist for the given id : " . $this->userData['id'];
        }
    }

    private function methodPOSTSql(){
        $sqlCompany =  "INSERT INTO `company` (name,description) VALUES ('{$this->userData['name']}','{$this->userData['description']}')";
        if ($this->dbConnection ->query($sqlCompany) === TRUE) {
            $lastId = $this->dbConnection ->insert_id;
            $sqlCompanyAddress =  "INSERT INTO `company_address` (company_id,address,address_2,city,state,zip) ".
                "VALUES ($lastId,'{$this->userData['address']}','{$this->userData['address2']}','{$this->userData['city']}','{$this->userData['state']}','{$this->userData['zip']}')";
            if ($this->dbConnection ->query($sqlCompanyAddress) === TRUE) {
                $this->code = 200;
                $this->status = 'Success';
                $this->outputData = array(
                    'company_id' => $lastId,
                    'name' => $this->userData['name'],
                    'address' => array(
                        $this->userData['address']
                    ),
                    'city' => $this->userData['city'],
                    'state' => $this->userData['state'],
                    'zip' => $this->userData['zip']
                );
                !empty($this->userData['description'])? $this->outputData['description'] = $this->userData['description'] : '';
                !empty($this->userData['address2'])? $this->outputData['address'][] = $this->userData['address2'] : '';
                $this->jsonResponse("company");
            } else {
                $this->outputData = "Failed to insert into Company Address table";
            }
        } else {
            $this->outputData = "Failed to insert into Company table";
        }
    }

    private function methodDELETESql(){
        $row = $this->getRecordForId($this->userData['id']);
        if (!empty($row)) {
            $addressTableSql = "DELETE FROM `company_address` WHERE company_id=".$this->userData['id'];
            $companyTableSql = "DELETE FROM `company` WHERE id=".$this->userData['id'];
            if ($this->dbConnection->query($addressTableSql) === TRUE && $this->dbConnection->query($companyTableSql) === TRUE ) {
                $this->code = 200;
                $this->status = 'Success';
                $this->outputData = "Record deleted successfully for given id : " . $this->userData['id'];
            } else {
                $this->outputData = "Failed to delete the data for given id : " . $this->userData['id'];
            }
        } else {
            $this->code = 404;
            $this->outputData = "No data exist for the given id : " . $this->userData['id'];
        }
    }

    private function methodPUTSql(){
        $row = $this->getRecordForId($this->userData['id']);
        if (!empty($row)) {
            $data = $this->userData['company'];
            $this->allowedVariables = array("name"=>255,"description"=>65535,"address"=>255,"city"=>255,"state"=>200,"zip"=>9);
            //checking allowed variables and there size
            if(!$this->validateAllowed($data) || !$this->validateSize($data)){
                return;
            } else {
                $companyTableSql = "UPDATE `company` SET id = ".$this->userData['id'];
                array_key_exists("name",$data) && !(empty($data['name']))? $companyTableSql .=  " ,name='" . $data['name'] . "'": "" ;
                array_key_exists("description",$data) &&!(empty($data['description']))? $companyTableSql .=  " ,description='" . $data['description'] . "'" : "" ;
                $companyTableSql .= " WHERE id=" . $this->userData['id'];
                if ($this->dbConnection ->query($companyTableSql) === TRUE) {
                    $addressTableSql = "UPDATE `company_address` SET company_id = ".$this->userData['id'];
                    array_key_exists("address",$data) && !(empty($data['address'][0]))? $addressTableSql .=  " ,address='" . $data['address'][0] . "'": "" ;
                    array_key_exists("address",$data) && !(empty($data['address'][1]))? $addressTableSql .=  " ,address_2='" . $data['address'][1] . "'": "" ;
                    array_key_exists("city",$data) && !(empty($data['city']))? $addressTableSql .=  " ,city='" . $data['city'] . "'": "" ;
                    array_key_exists("state",$data) && !(empty($data['state']))? $addressTableSql .=  " ,state='" . $data['state'] . "'": "" ;
                    array_key_exists("zip",$data) && !(empty($data['zip']))? $addressTableSql .=  " ,zip='" . $data['zip'] . "'": "" ;
                    $addressTableSql .= " WHERE company_id=" . $this->userData['id'];
                    if ($this->dbConnection ->query($addressTableSql) === TRUE) {
                        $this->code = 200;
                        $this->status = 'Success';
                        $this->outputData = "Record Updated successfully for given id : " . $this->userData['id'];
                    } else {
                        $this->outputData = "Failed to Update into Company Address table for id : " . $this->userData['id'];
                    }
                } else {
                    $this->outputData = "Failed to Update into Company table for id : " . $this->userData['id'];
                }
            }

        } else {
            $this->code = 404;
            $this->outputData = "No data exist for the given id : " . $this->userData['id'];
        }
    }

    private function getRecordForId($id){
        $sqlCompany =  "SELECT * FROM `company` INNER JOIN `company_address` ON company.id = company_address.company_id WHERE company.id = $id";
        $result =$this->dbConnection->query($sqlCompany);
        $row = mysqli_fetch_assoc($result);
        return $row;
    }

    private function jsonResponse($data = 'data'){
        $respone = array(
            "code" => "$this->code",
            "status" => $this->status,
            $data => $this->outputData
        );
        http_response_code($this->code);
        echo json_encode($respone);
        exit();
    }
}

$instance = new ConnxusApi();