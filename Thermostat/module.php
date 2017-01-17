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
		$this->RegisterPropertyInteger("defaultsteps", 5);
		$this->RegisterPropertyInteger("defaultpreset", 22);
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		$room = strtolower($this->ReadPropertyString("room"));
		$filter = ".*(?=.*temperature)(?=.*AdjustMode)(?=.*".$room.").*";
		$this->SetReceiveDataFilter($filter);
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("Set the ReceiveFilter to ".$filter);
		
    }

    public function ReceiveData($JSONString) {
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));

		$log->LogMessage("Received json: ".json_decode($JSONString, true)['Buffer']);
		
		$data = json_decode(json_decode($JSONString, true)['Buffer'], true);
	
		$action = "<missing information>";
		$room = "<missing information>";
		$component = "<missing information>";
		
		if(array_key_exists('action', $data['result']))
			$action = strtolower($data['result']['action']);
				
		if(array_key_exists('location', $data['result']['parameters']))
			$room = strtolower($data['result']['parameters']['location']);
		
		if(array_key_exists('component', $data['result']['parameters']))
			$component = strtolower($data['result']['parameters']['component']);
			
		$selectedRoom = strtolower($this->ReadPropertyString("room"));
		
		$log->LogMessage("Action: ".$action);
		$log->LogMessage("Action filter: "."adjustmode");
		$log->LogMessage("Room: ".$room);
		$log->LogMessage("Room filter: ".$selectedRoom);
		$log->LogMessage("Component: ".$component);
		$log->LogMessage("Component filter: "."temperature");
						
		if($action==="adjustmode" && $room===$selectedRoom && $component==='temperature') {
		
			$defaultSteps = $this->ReadPropertyInteger('defaultsteps');
			$instance = $this->ReadPropertyInteger("instanceid");
			$defaultPreset = $this->ReadPropertyInteger("defaultpreset");
		
			$validState = false;
			if(array_key_exists('direction', $data['result']['parameters']['state'][0])){
				$direction = strtolower($data['result']['parameters']['state'][0]['direction']);
				
				switch($direction) {
					case 'up':
					case 'down':
					case 'up to':
					case 'down to':
						$validState=true;
						break;
				}
				
				if(array_key_exists('number', $data['result']['parameters']['state'][0]))
					$value = $data['result']['parameters']['state'][0]['number'];
				else
					$value = $defaultSteps;
				
				if($direction==='up') {
					$value+=GetValueInteger(IPS_GetVariableIdByName('Intensity', $instance));
					if($value>30)
						$value=30;
				}
				if($direction==='down') { 
					$value=GetValueInteger(IPS_GetVariableIdByName('Intensity', $instance))-$value;
					if($value<5)
						$value=5;
				}
				
				if($direction==='up to'||$direction==='down to')
					$direction='preset';
			
			} else { 
				if(array_key_exists('number', $data['result']['parameters']['state'][0]))
					$value = $data['result']['parameters']['state'][0]['number'];
				else
					$value = $defaultPreset;		
				
				$direction="preset";
				$validState=true;
			} else {
				$value = $defaultPreset;
				$validState=true;
				$direction="preset";
			}
			
			if($validState) {
				$switchType = $this->ReadPropertyString("switchtype");
					   
				try{
					if($switchType=="z-wave") {
						$log->LogMessage("The system is z-wave");
						ZW_ThermostatSetPointSet($instance, 1, $value);
					} else if($switchType=="xcomfort"){
						$log->LogMessage("The system is xComfort");
						XC_SetTemperature($instance, $value);
					}
					$logMessage = ($direction=='preset'?"Adjusting temperasture to ".$value." degrees":"Adjusting temperature ".$direction." to ".$value." degrees");
					
				} catch(exeption $ex) {
					$logMessage="The command failed!";
					$log->LogMessage("The SetPoint command failed: XYZ_SetPoint(".$instance.", ".$value.")");
				}
				
			} else
				$logMessage = "Invalid state";
			
			$log->LogMessage($logMessage);
				
			$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
				
			$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
			
			$log->LogMessage("Sendt response back to parent");
		
		} else 
			$log->LogMessage("Did not pass the filter test");	

	}
}

?>
