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
		$id = $this->RegisterScript($ident, $name, "<?\n//Do not modify!\nrequire_once(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\nrequire_once(\"../modules/Geofence/Controller/module.php\");\n(new GeofenceController(".$this->InstanceID."))->HandleWebData();\n?>");
		$this->RegisterWebHook("/hook/".$ident, $id);
		
		$this->CreateVariable($this->InstanceID, "Presence", "Presence", 0, "~Presence");
        
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
			$children = IPS_GetChildrenIDs($this->InstanceID);
			
			$size = sizeof($children);
			$userExists = false;
			for($x=0;$x<$size;$x++) {
				if($children[$x]==$user) {
					$userExists=true;
					break;
				}
			}
			
			if($userExists) {
				$log->LogMessage("Updating Presence for user ".IPS_GetName($user));
				$presenceId = $this->CreateVariable($user, "Presence", "Presence", 0, "~Presence");
				if($action=="arrival")
					SetValue($presenceId, true);
				else
					SetValue($presenceId, false);
				
				$log->LogMessage("Updating Common Presence");
				$commonPresence = false;
				$users=IPS_GetInstanceListByModuleID("{C4A1F68D-A34E-4A3A-A5EC-DCBC73532E2C}");
				$size=sizeof($users);
				for($x=0;$x<$size;$x++){
					if(IPS_GetParent($users[$x])==$this->InstanceID) {
						$presenceId=$this->CreateVariable($users[$x], "Presence", "Presence", 0, "~Presence");
						
						if(GetValue($presenceId)) {
							$commonPresence = true;
							break;
						}
						
					}
				}
				
				$log->LogMessage("Updating Common Presence to ".$commonPresence);
				$commonPresenceId = $this->CreateVariable($this->InstanceID, "Presence", "Presence", 0, "~Presence");
				SetValue($commonPresenceId, $commonPresence);
			} else
				$log->LogMessage("Unknown user");
			
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

		
	private function CreateVariable($Parent, $Ident, $Name, $Type, $Profile = "") {
		$id = @IPS_GetObjectIDByIdent($Ident, $Parent);
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
		
		
}

?>