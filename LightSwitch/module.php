<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeLightSwitch extends IPSModule {
    
    public function Create(){
	    //Stig
        parent::Create();
		$this->ConnectParent("{11B64703-256F-4E7F-8DD5-960D6A6C0DBB}");
        
        $this->RegisterPropertyBoolean ("log", false );
		$this->RegisterPropertyInteger("instanceid",0);	
		$this->RegisterPropertyString("switchtype", "z-wave");
		$this->RegisterPropertyString("filter", "");
		$this->RegisterPropertyString("room", "Bedroom");
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		$filter = $this->ReadPropertyString("filter"); //"(?=.*\bSwitchMode\b).*";                     
		
		$this->SetReceiveDataFilter($filter);
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Set the ReceiveFilter to ".$filter);
		
    }

    public function ReceiveData($JSONString) {
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));

		$log->LogMessage("Received json: ".json_decode($JSONString, true)['Buffer']);
		
		$data = json_decode(json_decode($JSONString, true)['Buffer'], true);
	
		$action = strtolower($data['result']['action']);
		$room = strtolower($data['result']['parameters']['location']);
		$component = strtolower($data['result']['parameters']['component']);
		$selectedRoom = strtolower($this->ReadPropertyString("room"));

		$log->LogMessage("Action: ".$action);
		$log->LogMessage("Action filter: "."switchmode");
		$log->LogMessage("Room: ".$room);
		$log->LogMessage("Room filter: ".$selectedRoom);
		$log->LogMessage("Component: ".$component;
		$log->LogMessage("Component filter: "."switch");
		
		if($action==="switchmode" && $component==='light' && $room===$selectedRoom) {
			$valueText = strtolower($data['result']['parameters']['state']); 
			$value = ($valueText=="off"?false:true);
			
			$instance = $this->ReadPropertyInteger("instanceid");
			$switchType = $this->ReadPropertyString("switchtype");
			
			try{
				if($switchType=="z-wave") {
					$log->LogMessage("The system is z-wave");
					ZW_SwitchMode($instance, $value);
				} else if($switchType=="xcomfort"){
					$log->LogMessage("The system is xComfort");
					MXC_SwitchMode($instance, $value);
				}
				
				$logMessage = "The light was switched ".$valueText;	
				$log->LogMessage($logMessage);
				
				$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
					
				$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
				
				$log->LogMessage("Sendt response back to parent");
			
			} catch(exeption $ex) {
				$log->LogMessage("The switch command failed: XY_SwitchMode(".$instance.", ".$value.")");
			}
			
			
		}  else {
			$log->LogMessage("Did not pass the filter test");	
		}

    }


	
}

?>
