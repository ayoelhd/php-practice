<?php
abstract class Customer {
    private $id = 1;
    private $name;
    protected $email;
    private $balance;

    public function __construct($id, $name, $email, $balance){
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->balance = $balance;
    }

    abstract public function getEmail();
}



// $customer = new Customer(1, 'Johnathan Doevard', 'johnathan.doevard@example.com', 1000.00);


class Subscriber extends Customer {
    private $plan;
    public function __construct($id, $name, $email, $balance, $plan){
        parent::__construct($id, $name, $email, $balance);
        $this->plan = $plan;
    }

    public function getEmail(){
        return $this->email;
    }
}


$subscriber = new Subscriber(2, 'Jane Smith', 'jane.smith@example.com', 500.00, 'Premium');
echo $subscriber->getEmail(); // Output:


?>