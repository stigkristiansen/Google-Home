<?

require_once(__DIR__ . "/../logging.php");

class GeofenceUser extends IPSModule {
    
    public function Create(){
        parent::Create();
        
        $this->RegisterPropertyBoolean ("log", false );

    
	}

    public function ApplyChanges(){
        parent::ApplyChanges();
		
		
		$this->RegisterVariableBoolean( "Presence", "Presence", "~Presence", false );
        
    }

	public function GetURLs() {
		$parent = IPS_GetParent($this->InstanceID);
		$allParents = IPS_GetInstanceListByModuleID("{C5271BF2-DDC9-4EA7-8467-A8C645500263}");
		
		$size=sizeof($allParents);
		$foundParent = false;
		for($x=0;$x<$size;$x++) {
			if($allParents[$x]==$parent) {
				$foundParent = true;
				break;
			}
		}
		
		if($foundParent) {
			$message = "http(s)://<server>/hook/geofence".$parent."?action=arrival&user=".$this->InstanceID;	
		} else
			$message = "This user do not has a Geofence Controller as parent!";
		
		return $message;
	}

	
}

?>
