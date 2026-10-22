<?php
class Customer {
    public $id = 1;
    public $name;
    public $email;
    public $balance;

    public function __construct(){
        echo 'The Constructor Ran';
    }

    public function getCustomer($id){
        $this->id = $id;
        return 'Johnathan Doevard';
    }
    

    public function __destruct(){
        echo 'The Destructor Ran';
    }


}

$customer = new Customer;
echo $customer->getCustomer(1);
 


?>