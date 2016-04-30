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
				echo "Authorization required to access Symcon and Geofence";
				$log->LogMessage("Authentication needed or invalid username/password!");
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
				$presenceId = $this->CreateVariable($user, "Presence", "Presence", 0, "~Presence");
				if($action=="arrival")
					$presence = true;
				else
					$presence = false;
				SetValue($presenceId, $presence);
				$log->LogMessage("Updated Presence for user ".IPS_GetName($user)." to ".$this->GetProfileValueName(IPS_GetVariable($presenceId)['VariableCustomProfile'], $presence));
				
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
				
				$commonPresenceId = $this->CreateVariable($this->InstanceID, "Presence", "Presence", 0, "~Presence");
				SetValue($commonPresenceId, $commonPresence);
				$log->LogMessage("Updated Common Presence to ".$this->GetProfileValueName(IPS_GetVariable($commonPresenceId)['VariableCustomProfile'], $commonPresence));
			} else
				$log->LogMessage("Unknown user");
			
		} else
			$log->LogMessage("Invalid or missing \"user\" or \"action\" in URL");

    }
	
	public function UnregisterUser($Username) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Username));
		$id = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($id!==false) {
			$vId = IPS_GetObjectIDByIdent("Presence", $id);
			if($vId!==false)
				IPS_DeleteVariable($vId);
			return IPS_DeleteInstance($id);
		}
		
		IPS_GetObjectIDByName($Username, $this->InstanceID);
		if($id!==false) {
			$vId = IPS_GetObjectIDByName("Presence", $id);
			if($vId!==false)
				IPS_DeleteVariable($vId);
			return IPS_DeleteInstance($id);
		}
		
		return false;
	}

	public function RegisterUser($Username) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Username));
		$id = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($id===false) {
			$id = IPS_GetInstanceIDByName($Username, $this->InstanceID);
			if($id===false) {
				$id = IPS_CreateInstance("{C4A1F68D-A34E-4A3A-A5EC-DCBC73532E2C}");
				IPS_SetName($id,$Username);
				IPS_SetParent($id,$this->InstanceID);
				IPS_SetIdent($id, $ident);
				return true;
			} else {
				IPS_SetIdent($id, $ident);
				
				return true;
			}
		} 
						
		return false;
	}
	
	private function GetProfileValueName($Profile, $Value) {
		$associations = IPS_GetVariableProfile($Profile)['Associations'];
		
		$size=sizeof($associations);
		for($x=0;$x<$size;$x++) {
		   if($associations[$x]['Value']==$Value) {
				return $associations[$x]['Name'];
			}
		}

		return "Invalid";

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
