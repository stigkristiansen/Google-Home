<?

require_once(__DIR__ . "/../logging.php");

class GeofenceController extends IPSModule {
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyBoolean ("log", false );

		$this->RegisterPropertyString("Username", "");
		$this->RegisterPropertyString("Password", "");
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		$ident="geofence".$this->InstanceID;
		$name="Geofence".$this->InstanceID."Hook";
		$id = $this->RegisterScript($ident, $name, "<?\n//Do not modify!\nrequire_once(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\nrequire_once(\"../modules/Geofence/module/module.php\");\n(new GeofenceController(".$this->InstanceID."))->HandleWebData();\n?>");
		$this->RegisterWebHook("/hook/".$ident, $id);
		
		$this->RegisterVariableBoolean( "Presence", "Presence", "~Presence", false );
        
    }

    public function HandleWebData() {
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$username = IPS_GetProperty($this->InstanceID, "Username");
		$password = IPS_GetProperty($this->InstanceID, "Password");
		
		if($username!="" || $password!="") {
			if(!isset($_SERVER['PHP_AUTH_USER']))
				$_SERVER['PHP_AUTH_USER'] = "";
			if(!isset($_SERVER['PHP_AUTH_PW']))
				$_SERVER['PHP_AUTH_PW'] = "";

			if(($_SERVER['PHP_AUTH_USER'] != $username) || ($_SERVER['PHP_AUTH_PW'] != $password)) {
				header('WWW-Authenticate: Basic Realm="Geofence"');
				header('HTTP/1.0 401 Unauthorized');
				echo "Authorization required to access Symcon";
		
				return;
			}
		}
		
		$log->LogMessage("You are authenticated!");
		
		if (array_key_exists('action', $_GET))
			$action=strtolower($_GET['action']);
				
		if (array_key_exists('user', $_GET))
			$user=strtolower($_GET['user']);
		
		if($action!="" && $user!="") {
			$presenceId = IPS_GetObjectIDByIdent("Presence", $this->InstanceID);
			if($action=="arrival")
				SetValue($presenceId, true);
			else
				SetValue($presenceId, false);
			
		} else
			$log->LogMessage("Invalid or missing \"user\" or \"action\" in URL");

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

	public function RegisterUser($User) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $User));

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		
	}
	
	public function UnregisterUser($User) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $User));
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		
	}
	
	
	private function CreateVariable($Parent, $Ident, $Name, $Type, $Profile = "") {
		$id = @IPS_GetObjectIDByIdent($ident, $Parent);
		if($id === false) {
			$id = IPS_CreateVariable($Type);
			IPS_SetParent($id, $Parent);
			IPS_SetName($id, $Name);
			IPS_SetIdent($id, $Ident);
			if($Profile != "")
				IPS_SetVariableCustomProfile($id, $Profile);
		}
		
		return $id;
	}
	
	
	private function CreateInstance($Parent, $Ident, $Name, $ModuleId = "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {
		$id = @IPS_GetObjectIDByIdent($Ident, $Parent);
		if($id === false) {
			$id = IPS_CreateInstance($ModuleId);
			IPS_SetParent($id, $Parent);
			IPS_SetName($id, $Name);
			IPS_SetIdent($id, $Ident);
			
			return $id;
		} else
			return false;
		
		
	}
	
}

?>
