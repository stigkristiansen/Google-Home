<?

require_once(__DIR__ . "/../logging.php");

class Geofence extends IPSModule
{
    
    public function Create()
    {
        parent::Create();
        
        $this->RegisterPropertyBoolean ("log", false );

	$this->RegisterPropertyString("Username", "");
	$this->RegisterPropertyString("Password", "");
		
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();

	$sid = $this->RegisterScript("GeofenceHook", "GeofenceHook", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/Geofence/module/module.php\");\n(new Geofence(".$this->InstanceID."))->HandleWebData();");
	$this->RegisterHook("/hook/geofence", $sid);
        
        
    }


    private function RegisterWebHook($Hook, $TargetId) {
	$id = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");

	if(sizeof($id)) {
		$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);

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
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId === false) {
			try {
				$cId = IPS_CreateCategory();
			} catch (Exception $ex){
				$log->LogMessageError("Failed to register the device ".$Device." . Error: ".$ex->getMessage());
				return false;
			}
			
			IPS_SetParent($cId, $this->InstanceID);
			IPS_SetName($cId, $Device);
			IPS_SetIdent($cId, $ident);
			IPS_SetHidden($cId, true);
			
			$log->LogMessage("The device has been registered: ". $Device);
			return $cId;
		}
		
		$log->LogMessage("The device already exists: ". $Device);
		return $cId;
	}
	
	public function UnregisterDevice($Device) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));
		
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		if($cId !== false) {
			if(!IPS_HasChildren($cId)) {
				try {
					IPS_DeleteCategory($cId);
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to unregister the device ".$Device." . Error: ".$ex->getMessage());
					return false;
				}
				
				$log->LogMessage("Unregistered the device: ".$Device);
				return true;
			}
			$log->LogMessage("The device ".$Device." has registred commands. Remove all commands first");
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
	}
	
	public function RegisterCommand($Device, $Command, $IRCode) {
		$cId = $this->RegisterDevice($Device);
				
		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));		
				
		if($cId>0) {
			$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Command));
			$vId = @IPS_GetObjectIDByIdent($ident, $cId);
			if($vId === false) {
				try{
					$vId = IPS_CreateVariable(3); // Create String variable
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to register the command ".$Device.":".$Command." Error: ".$ex->getMessage());
					return false;
				}
				IPS_SetParent($vId, $cId);
				IPS_SetName($vId, $Command);
				IPS_SetIdent($vId, $ident);
				IPS_SetHidden($vId, true);
			}
			
			SetValueString($vId, $IRCode);
			$log->LogMessage("The command is registred: ".$Device.":".$Command);
			
			return $vId;
		}
		
		$log->LogMessage("Unable to register the command. Missing device: ".$Device.":".$Command);
		return 0;
	}
	
	public function UnregisterCommand($Device, $Command) {
		$cIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));

		$log = new Logging($this->ReadPropertyBoolean("log"), IPS_Getname($this->InstanceID));
		
		$cId = @IPS_GetObjectIDByIdent($cIdent, $this->InstanceID);
		if($cId !== false) {
			$vIdent = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Command));
			$vId = @IPS_GetObjectIDByIdent($vIdent, $cId);
			if($vId !== false) {
				try{
					IPS_DeleteVariable($vId);
				} catch (Exeption $ex) {
					$log->LogMessageError("Failed to unregister the command ".$Device.":".$Command." Error: ".$ex->getMessage());
					return false;
				}
				
				$log->LogMessage("The command has been unregistered: ".$Device.":".$Command);
				return true;
			}
			
			$log->LogMessage("The command does not exists: ".$Device.":".$Command);
			return false;
		}
		
		$log->LogMessage("The device does not exists: ".$Device);
		return false;
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
