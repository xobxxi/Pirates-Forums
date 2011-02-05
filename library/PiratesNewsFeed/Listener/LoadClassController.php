<?php

class PiratesNewsFeed_Listener_LoadClassController
{
        public static function loadClassListener($class, &$extend)
        {

                if ($class == 'XenForo_ControllerPublic_Forum')
                {
                        $extend[] = 'PiratesNewsFeed_ControllerPublic_PiratesNewsFeed';
                }

        }

}
