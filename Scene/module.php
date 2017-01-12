<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeScene extends IPSModule {
    
    public function Create(){
	    //Stig
        parent::Create();
		$this->ConnectParent("{11B64703-256F-4E7F-8DD5-960D6A6C0DBB}");
        
        $this->RegisterPropertyBoolean ("log", false );
		$this->RegisterPropertyInteger("scriptid",0);	
		$this->RegisterPropertyString("filter", "");
		$this->RegisterPropertyString("scene", "");
    
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
		$component = strtolower($data['result']['parameters']['component']);
		$scene = strtolower($data['result']['parameters']['scene']);
		
		$selectedScene = strtolower($this->ReadPropertyString("scene"));

		$log->LogMessage("Action: ".$action);
		$log->LogMessage("Action filter: "."switchmode");
		$log->LogMessage("Scene: ".$scene);
		$log->LogMessage("Scene filter: ".$selectedScene);
		$log->LogMessage("Component: ".$component);
		$log->LogMessage("Component filter: "."scene");
		
		if($action==="scenemode" && $component==='scene' && $scene===$selectedScene) {
			$scriptId = $this->ReadPropertyInteger("scriptid");

			IPS_RunScript($scriptId);
			
			$logMessage = 'The scene '.$scene.' was executed ';	
			$log->LogMessage($logMessage);
			
			$response = '{ "speech": "'.$logMessage.'", "DisplayText": "'.$logMessage.'", "Source": "IP-Symcon"}';
				
			$result = $this->SendDataToParent(json_encode(Array("DataID" => "{8A83D53D-934E-4DD7-8054-A794D0723FED}", "Buffer" => $response)));
			
			$log->LogMessage("Sendt response back to parent");

		}  else {
			$log->LogMessage("Did not pass the filter test");	
		}

    }


	

?>
