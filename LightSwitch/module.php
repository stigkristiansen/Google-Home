<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeLightSwitch extends IPSModule {
    
    public function Create(){
        parent::Create();
		$this->ConnectParent("{11B64703-256F-4E7F-8DD5-960D6A6C0DBB}");
        
        $this->RegisterPropertyBoolean ("log", false );
		$this->RegisterPropertyInteger("instanceid",0);	
		$this->RegisterPropertyString("switchtype", "z-wave");

    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		
		
        
    }

    public function ReceiveData($JSONString) {
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));

		$data = json_decode(json_decode($JSONString, true)['Buffer'], true);
	
		$room = $data['result']['parameters']['rooms'];
		$valueText = $data['result']['parameters']['light-action-switch1']; 
		$value = ($valueText=="off"?false:true);
	
		$logMessage = "Switching light ".$valueText." in ".$room;;	
		$log->LogMessage(logMessage);

    }


	
}

?>
