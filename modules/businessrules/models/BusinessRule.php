<?php

interface BusinessRule {
    
    public function execute($traffic, $businessRule, $accountedTraffic);
    
}

