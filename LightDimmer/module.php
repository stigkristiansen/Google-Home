<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeLightDimmer extends IPSModule {
    
    public function Create(){
        parent::Create();
		$this->ConnectParent("{11B64703-256F-4E7F-8DD5-960D6A6C0DBB}");
        
        $this->RegisterPropertyBoolean ("log", false );
		$this->RegisterPropertyInteger("instanceid",0);	
		$this->RegisterPropertyString("switchtype", "z-wave");
		$this->RegisterPropertyString("filter", "");
		$this->RegisterPropertyString("room", "Bedroom");
		$this->RegisterPropertyInteger("defaultsteps", 10);
		$this->RegisterPropertyInteger("defaultpreset", 50);
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		//$filter = $this->ReadPropertyString("filter"); //"(?=.*\bSwitchMode\b).*";                     
		
		$room = strtolower($this->ReadPropertyString("room"));
		$filter = "(?=.*light)(?=.*".$room.").*(AdjustMode|SwitchMode).*";
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
		$log->LogMessage("Action filter: "."switchmode||adjustmode");
		$log->LogMessage("Room: ".$room);
		$log->LogMessage("Room filter: ".$selectedRoom);
		$log->LogMessage("Component: ".$component);
		$log->LogMessage("Component filter: "."light");
						
		if(($action==="switchmode" || $action==="adjustmode") && $room===$selectedRoom && $component==='light') {
			if($action==="adjustmode") {
				
				$defaultSteps = $this->ReadPropertyInteger('defaultsteps');
				$switchType = $this->ReadPropertyString("switchtype");
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
						$oldTemp = GetValueInteger(IPS_GetVariableIdByName('Intensity', $instance)); 
						$value+= intval($oldTemp);
						if($value>100)
							$value=100;
					}
					if($direction==='down') {
						$oldTemp = GetValueInteger(IPS_GetVariableIdByName('Intensity', $instance)); 
						$value=intval($oldTemp)-$value;
						if($value<0)
							$value=0;
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
				}
				
				if($validState) {
					   
					try{
						if($switchType=="z-wave") {
							$log->LogMessage("The system is z-wave");
							ZW_DimSet($instance, $value);
						} else if($switchType=="xcomfort"){
							$log->LogMessage("The system is xComfort");
							MXC_DimSet($instance, $value);
						}
						$logMessage = ($direction=='preset'?"Adjusting light to ".$value." percent":"Adjusting light ".$direction." to ".$value." percent");
						
					} catch(exeption $ex) {
						$logMessage="The command failed!";
						$log->LogMessage("The Dim command failed for Instance ".$instance);
					}
					
				} else
					$logMessage = "Invalid state";
			
				$log->LogMessage($logMessage);
					
				$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
					
				$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
				
				$log->LogMessage("Sendt response back to parent");	
			}

			if($action==="switchmode") {
				$validState = false;
				if(array_key_exists('state', $data['result']['parameters'])) {
					$valueText = strtolower($data['result']['parameters']['state']); 
					switch($valueText) {
						case 'off':
							$validState = true;
							$value = false;
							break;
						case 'on':
							$validState = true;
							$value = true;
							break;
					}
				}
				
				if($validState) {
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
					} catch(exeption $ex) {
						$logMessage = "The command failed!";
						$log->LogMessage("The Switch command failed for Instance ".$instance);
					}
					
				} else
					$logMessage = "Invalid state";
				
				$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
					
				$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
				
				$log->LogMessage("Sendt the response back to the controller");

			}
		} else {
			$log->LogMessage("Did not pass the filter test");	
		}

    }


	
}

?>
