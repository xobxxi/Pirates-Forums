<?php

class ChildProof_Listener
{
		public static function loadClassModel($model, &$extend) 
		{
			
			if ($model == 'XenForo_Model_UserProfile')
				$extend[] = 'ChildProof_Model_UserProfile';
		}
}