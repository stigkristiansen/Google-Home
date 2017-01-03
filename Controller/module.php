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
		$name="GoogleHome".$this->InstanceID."Hook";
		$id = $this->RegisterScript($ident, $name, "<?\n//Do not modify!\nrequire_once(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\nrequire_once(\"../modules/Google-Home/Controller/module.php\");\n(new GoogleHomeController(".$this->InstanceID."))->HandleWebData();\n?>");
		$this->RegisterWebHook("/hook/".$ident, $id);
		
		
		
    }

    public function HandleWebData() {
		//IPS_LogMessage("Debug", "Inside HandleWebData");
		
		$log = new Logging($this->ReadPropertyBoolean("Log"), IPS_Getname($this->InstanceID));
		
		$username = IPS_GetProperty($this->InstanceID, "Username");
		$password = IPS_GetProperty($this->InstanceID, "Password");
		
		//IPS_LogMessage("User is ".$username);
		
		if($username!="" || $password!="") {
			if(!isset($_SERVER['PHP_AUTH_USER']))
				$_SERVER['PHP_AUTH_USER'] = "";
			if(!isset($_SERVER['PHP_AUTH_PW']))
				$_SERVER['PHP_AUTH_PW'] = "";

			if(($_SERVER['PHP_AUTH_USER'] != $username) || ($_SERVER['PHP_AUTH_PW'] != $password)) {
				header('WWW-Authenticate: Basic Realm="IP-Symcon"');
				header('HTTP/1.0 401 Unauthorized');
				echo "Authorization required to access Symcon Google Home Module";
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
		
		$jsonRequest    = file_get_contents('php://input');
		IPS_LogMessage("Controller", $jsonRequest);

		$data           = json_decode($jsonRequest, true);


		try{		

			$this->SendDataToChildren(json_encode(Array("DataID" => "{11ACFC89-5700-4B2A-A93C-18CAB413839C}", "Buffer" => "Test")));

		} catch (Exeption $ex) {

			IPS_LogMessage("Controller","Error");
		}

		header('Content-type: application/json');
		$response =  '{ "speech": "The lightning was changed", "DisplayText": "The lightning was changed", "Source": "IP-Symcon"}';

		echo $response;

				
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
		for ($x=0;$x<100;$x++)
        {
            if (IPS_SemaphoreEnter("GH_".(string)$this->InstanceID.(string)$Ident, 1)){
                return true;
            }
            else {
  				if($x==0)
					$log->LogMessage("Waiting for controller to unlock...");
				IPS_Sleep(mt_rand(1, 5));
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
