<?

require_once(__DIR__ . "/../logging.php");

class Geofence extends IPSModule
{
    
    public function Create()
    {
        parent::Create();
        
        $this->RegisterPropertyBoolean ("log", false );
		
    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
        
        
    }

    

	public function RegisterDevice($Device) {
		$ident = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $Device));

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

	
	
}

?>
