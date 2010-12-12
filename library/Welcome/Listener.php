<?php

class Welcome_Listener
{
        public static function loadClassController($class, &$extend)
        {

			if ($class == 'XenForo_ControllerPublic_Register') 
                $extend[] = 'Welcome_ControllerPublic_Register';
        }
}