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

		$data = json_decode(json_decode($JSONString, true)['Buffer'], true);
	
		$action = strtolower($data['result']['action']);
		$room = strtolower($data['result']['parameters']['rooms']);
		
		
		
		$selectedRoom = $this->ReadPropertyString("room");
		
		if($action=="dimmingmode" && $room=$selectedRoom) {
			$defaultStep = 10;
			
			if(array_key_exists('number', $data['result']['parameters']['dimming'][0]))
				$value = $data['result']['parameters']['dimming'][0]['number'];
			else
				$value = $defaultStep;

			if(array_key_exists('dim-direction', $data['result']['parameters']['dimming'][0]))	
				$direction = $data['result']['parameters']['dimming'][0]['dim-direction'];
			else
				$direction = "preset";
				
					
			$instance = $this->ReadPropertyInteger("instanceid");
			$switchType = $this->ReadPropertyString("switchtype");
			
			try{
				if($switchType=="z-wave") {
					$log->LogMessage("The system chosen is z-wave");
					ZW_ZW_DimSet($instance, $value);
				} else if($switchType=="xcomfort"){
					$log->LogMessage("The system chosen is xComfort");
					MXC_DimSet($instance, $value);
				}
				
				$logMessage = ($direction=='preset'?"Dimming light to preset value ".$value:"Dimming light ".$direction." to ".$value);
				$log->LogMessage($logMessage);
				
				$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
					
				$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
				
				$log->LogMessage("Sendt response back to parent");
			
			} catch(exeption $ex) {
				$log->LogMessage("The switch command failed: XYZ_SwitchMode(".$instance.", ".$value.")");
			}
			
			
		} else {
			
		}

    }


	
}

?>
