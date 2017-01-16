<?

require_once(__DIR__ . "/../logging.php");

class GoogleHomeController extends IPSModule {
    
   public function Create(){
        parent::Create();
        
        $this->RegisterPropertyBoolean ("Log", false );

		$this->RegisterPropertyString("Username", "");
		$this->RegisterPropertyString("Password", "");

   }

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		$ident="googlehome".$this->InstanceID;
		$name="GoogleHomeWebHook";
		$id = $this->RegisterScript($ident, $name, "<?\n//Do not modify!\nrequire_once(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\nrequire_once(\"../modules/Google-Home/Controller/module.php\");\n(new GoogleHomeController(".$this->InstanceID."))->HandleWebData();\n?>");
		$this->RegisterWebHook("/hook/googlehome", $id);
		
		$this->RegisterVariableString("buffer", "Buffer");
		
    }

	public function ForwardData($JSONString) {
		$response = json_decode($JSONString, true)['Buffer'];
				
		//IPS_LogMessage("Controller", "Got data from child: ".$response);
		
		//$bufferId = $this->GetIDForIdent("buffer");
		//SetValueString($bufferId, $response);
		
		$this->SetBuffer('buffer', $response);
		
		//IPS_LogMessage("Controller", "Set buffer to ".$response);
	}
	
    public function HandleWebData() {
		$log = new Logging($this->ReadPropertyBoolean("Log"), IPS_Getname($this->InstanceID));
		
		$username = IPS_GetProperty($this->InstanceID, "Username");
		$password = IPS_GetProperty($this->InstanceID, "Password");
		
		if($username!="" || $password!="") {
			if(!isset($_SERVER['PHP_AUTH_USER']))
				$_SERVER['PHP_AUTH_USER'] = "";
			if(!isset($_SERVER['PHP_AUTH_PW']))
				$_SERVER['PHP_AUTH_PW'] = "";

			if(($_SERVER['PHP_AUTH_USER'] != $username) || ($_SERVER['PHP_AUTH_PW'] != $password)) {
				header('WWW-Authenticate: Basic Realm="IP-Symcon"');
				header('HTTP/1.0 401 Unauthorized');
				echo "Authorization required to access IP-Symcons Google Home Module";
				$log->LogMessage("Authentication needed or invalid username/password!");
				return;
			} else
				$log->LogMessage("You are authenticated!");
		} else
			$log->LogMessage("No authentication needed");
		
		$username="";
		$password="";
		
		if(!$this->Lock("HandleWebData")) {
			$log->LogMessage("Waiting for unlock timed out!");
			return;
		}
		$log->LogMessage("The controller is locked");
				
		$jsonRequest = file_get_contents('php://input');
		$data = json_decode($jsonRequest, true);

		$log->LogMessage("Sending command to child device");
		
		//$bufferId = $this->GetIDForIdent("buffer");
		//SetValueString($bufferId, "");
		
		$this->SetBuffer('buffer', '');
		$this->SendDataToChildren(json_encode(Array("DataID" => "{11ACFC89-5700-4B2A-A93C-18CAB413839C}", "Buffer" => $jsonRequest)));

		$log->LogMessage("Waiting for response from child device...");
		
		$response="";
		for($x=0;$x<5;$x++) {
			//$response = GetValueString($bufferId);
			$response = $this->GetBuffer('buffer');
			
			if(strlen($response)>0) {
				IPS_LogMessage("Controller:", "Response from child: ".$response);
				break;
			} else
				IPS_LogMessage("Controller:", "Still waiting...");
				
			IPS_Sleep(1000);
		}
			
				
		if(strlen($response)==0) {
			$log->LogMessage("Waiting for response timed out!");
			$this->Unlock("HandleWebData");
			return;
		}
							
		header('Content-type: application/json');
		echo $response;
				
		$log->LogMessage("Received response from child device. Forwarded the response to Google");

		$this->Unlock("HandleWebData");
    }
	
	
    private function RegisterWebHook($Hook, $TargetId) {
		$id = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");

		if(sizeof($id)) {
			$hooks = json_decode(IPS_GetProperty($id[0], "Hooks"), true);

			$hookExists = false;
			$numHooks = sizeof($hooks);
			for($x=0;$x<$numHooks;$x++) {
				if($hooks[$x]['Hook']==$Hook) {
					if($hooks[$x]['TargetID']==$TargetId)
						return;
				$hookExists = true;
				$hooks[$x]['TargetID']= $TargetId;
					break;
				}
			}
				
			if(!$hookExists)
			   $hooks[] = Array("Hook" => $Hook, "TargetID" => $TargetId);
			   
			IPS_SetProperty($id[0], "Hooks", json_encode($hooks));
			IPS_ApplyChanges($id[0]);
		}
    }
		
	private function Lock($Ident) {
        $log = new Logging($this->ReadPropertyBoolean("Log"), IPS_Getname($this->InstanceID));
		for ($x=0;$x<200;$x++)
        {
            if (IPS_SemaphoreEnter("GH_".(string)$this->InstanceID.(string)$Ident, 1)){
                return true;
            }
            else {
  				if($x==0)
					$log->LogMessage("Waiting for controller to unlock...");
				IPS_Sleep(50);
            }
        }
        return false;
    }

    private function Unlock($Ident) {
        IPS_SemaphoreLeave("GH_".(string)$this->InstanceID.(string)$Ident);
		$log = new Logging($this->ReadPropertyBoolean("Log"), IPS_Getname($this->InstanceID));
		$log->LogMessage("The controller is unlocked");
    }
		
}

?>
