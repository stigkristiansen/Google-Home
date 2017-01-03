<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeLightSwitch extends IPSModule {
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyBoolean ("log", false );
	$this->RegisterPropertyInteger("instanceid",0);	
	$this->RegisterPropertyString("switchtype", "z-wave");

    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		
		
        
    }

    public function ReceiveData($JSONString) {
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$data = json_decode($JSONString);
		$log->LogMessage("Got data: ".$data);

    }


	
}

?>
