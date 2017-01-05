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
		
		$this->SetReceiveDataFilter(".*SwitchMode.*");
		IPS_LogMessage("LightSwitch", "Applied seettings");
		
		        
    }

    public function ReceiveData($JSONString) {
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));

		$data = json_decode(json_decode($JSONString, true)['Buffer'], true);
	
		$room = $data['result']['parameters']['rooms'];
		$valueText = $data['result']['parameters']['light-action-switch1']; 
		$value = ($valueText=="off"?false:true);
	
		$logMessage = "The light was switched ".$valueText." in ".$room;;	
		$log->LogMessage($logMessage);
		
		$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
		
		
		$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
		
		$log->LogMessage("Sendt response back to parent");

    }


	
}

?>
