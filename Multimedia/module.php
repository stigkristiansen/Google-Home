<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeThermostat extends IPSModule {
    
    public function Create(){
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
		$room = strtolower($data['result']['parameters']['rooms']);
		
		$selectedRoom = strtolower($this->ReadPropertyString("room"));

		$log->LogMessage("Action: ".$action);
		$log->LogMessage("Action filter: "."thermostatmode");
		$log->LogMessage("Room: ".$room);
		$log->LogMessage("Room filter: ".$selectedRoom);
		
		
		if($action==="thermostatmode" && $room===$selectedRoom) {
			$valueText = strtolower($data['result']['parameters']['light-action-switch1'][0]); 
			$value = ($valueText=="off"?false:true);
			
			$instance = $this->ReadPropertyInteger("instanceid");
			$switchType = $this->ReadPropertyString("switchtype");
			
			try{
				if($switchType=="z-wave") {
					$log->LogMessage("The system is z-wave");
					ZW_ThermostatSetPointSet($instance, 1, $value);
				} else if($switchType=="xcomfort"){
					$log->LogMessage("The system is xComfort");
					MXC_SetTemperature($instance, $value);
				}
				
				$logMessage = "The temperature was set to ".$value;	
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
