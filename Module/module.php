<?
	/**
	
		Author: Timo Beyel
		
		Description:
		
		This module will simulate presence by setting the status of a group of objects. 
		The module will create the groups automatically and for each group a delay (in seconds) can be specified.
		Additionally randomness can be added by specifying the number of random seconds which should be added or removed from the delay.
		
		Every group contains a category "Links", which must only contain links to variables or instances which should be turned on or off.
		
		The "Turn on" property specifies, whether this group should turn the linked items on or off.
	*/
	
	class Absence extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyInteger("Groups", 0);
			$this->RegisterPropertyInteger("Random", 60);
			$this->RegisterPropertyInteger("Speed", 1);
			$this->RegisterPropertyBoolean("Active",true);
			$this->RegisterPropertyInteger("CurrentGroup",0);
			$this->RegisterTimer("AbsenceTimer",5000,'ABSENCE_ProcessAbsence($_IPS[\'TARGET\']);');
		}
	
		public function createGroupWithID($name,$id,$parentID) {
			$group=@IPS_GetObjectIDByIdent($id,$parentID);
			if (!$group) {
				$group=IPS_CreateCategory();
				IPS_SetName($group,$name);
				IPS_SetParent($group,$parentID);
				IPS_SetIdent($group,$id);
				
				$variable=IPS_CreateVariable(0);
				IPS_SetParent($variable,$group);
				IPS_SetName($variable,"Turn on");
				IPS_SetIdent($variable,"state_".$id);
				SetValue($variable,true);
				
				$variable=IPS_CreateVariable(1);
				IPS_SetParent($variable,$group);
				IPS_SetName($variable,"Delay");
				IPS_SetIdent($variable,"delay_".$id);
				SetValue($variable,3600);
				
				$childs=IPS_CreateCategory();
				IPS_SetName($childs,"Links");
				IPS_SetParent($childs,$group);
				IPS_SetIdent($childs,"childs_".$id);
			}
			
			return $group;
		}
		
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			$groups=$this->ReadPropertyInteger("Groups");
			
			for ($i=0;$i<$groups;$i++) {
					$this->createGroupWithID("Action Group ".$i,"group_".$i,$this->InstanceID);
			}
			
			$active=$this->ReadPropertyBoolean("Active");
			$this->SetTimerInterval("AbsenceTimer",$active ? 1000: 0);
		}
		
		private function processChilds($childCategory,$state) {
			$childs=IPS_GetChildrenIDs($childCategory);
			foreach ($childs as $child) {
				$link=@IPS_GetLink($child);
				if ($link) {
					$variableID=$link["TargetID"];
					$variableObject=IPS_GetObject($variableID);
					$variable = IPS_GetVariable($variableID);
					$ipsValue = $state;

					// request associated action for the specified variable and value
					if ($variable["VariableCustomAction"] > 0) {
						IPS_RunScriptEx($variable["VariableCustomAction"], array("VARIABLE" => $variableID, "VALUE" => $ipsValue));
					}
					else {
						IPS_RequestAction($variableObject["ParentID"], $variableObject["ObjectIdent"], $ipsValue);
					}
				}
			}
		}
		
		public function ProcessAbsence() {
			$groups= $this->ReadPropertyInteger("Groups");
			
			if ($groups==0) {
				return;
			}
			
			$currentGroup=$this->ReadPropertyInteger("CurrentGroup");
			$groupID=IPS_GetObjectIDByIdent("group_".$currentGroup,$this->InstanceID);
			$speed=Max(1,$this->ReadPropertyInteger("Speed"));
			$random=$this->ReadPropertyInteger("Random");
			
			IPS_LogMessage("Absence","Processing Group $currentGroup with ID $groupID");
		
			$delayVariable=IPS_GetObjectIDByIdent("delay_group_".$currentGroup,$groupID);
			$stateVariable=IPS_GetObjectIDByIdent("state_group_".$currentGroup,$groupID);
			$childVariable=IPS_GetObjectIDByIdent("childs_group_".$currentGroup,$groupID);
			$state=GetValue($stateVariable);
			$this->processChilds($childVariable,$state);
			
			/** Recalculate the next event time*/
			$random_delay=rand(-1*$random,1*$random);
			$delay=Max(1000,(GetValue($delayVariable)+$random_delay)*1000/$speed);
			$this->SetTimerInterval("AbsenceTimer",$delay);
			$currentGroup=($currentGroup+1) % $groups;
			IPS_SetProperty($this->InstanceID,"CurrentGroup",$currentGroup);
		}
		
		
	
		
	
	}

?>
