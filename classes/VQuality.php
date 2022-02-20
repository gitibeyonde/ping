<?php

class VQuality {
    
    const A="VA";
    const B="VB";
    const C="VC";
    const D="VD";
    const E="VE";
    const F="VF";
    const G="VG";
    const H="VH";
    const I="VI";
    const J="VJ";
    const K="VK";
    const L="VL";
    const M="VM";
    const N="VN";
    const O="VO";
    const P="VP";
    
    const QUALITY = array(VQuality::P, VQuality::O, VQuality::N, VQuality::M, VQuality::L, VQuality::K, VQuality::J, VQuality::I, VQuality::H, 
            VQuality::G, VQuality::F, VQuality::E, VQuality::D, VQuality::C, VQuality::B, VQuality::A);
    
    private $success_count = array(VQuality::P => 1, VQuality::O => 1, VQuality::N => 1, VQuality::M => 1, VQuality::L => 1, VQuality::K => 1, VQuality::J => 1, VQuality::I => 1, VQuality::H => 1, 
            VQuality::G => 1, VQuality::F => 1, VQuality::E => 1, VQuality::D => 1, VQuality::C => 1, VQuality::B => 1, VQuality::A => 1);
    
    private $failure_count = array(VQuality::P => 1, VQuality::O => 1, VQuality::N => 1, VQuality::M => 1, VQuality::L => 1, VQuality::K => 1, VQuality::J => 1, VQuality::I => 1, VQuality::H => 1,
            VQuality::G => 1, VQuality::F => 1, VQuality::E => 1, VQuality::D => 1, VQuality::C => 1, VQuality::B => 1, VQuality::A => 1);
    
    private $current_quality = VQuality::M;
    
    
    public static function qualityValueExists($q){
        return in_array($q, VQuality::QUALITY);
    }
    
    public function getQuality($result, $topQ, $curQ){
        $result == 1 ? $this->success_count[$curQ]++ :   $this->failure_count[$curQ]++;
        //error_log($topQ." curq ". $curQ . " success " .$this->success_count[$curQ] . " failure ".$this->failure_count[$curQ]);
        
        if ($this->success_count[$curQ] > 2 * $this->failure_count[$curQ]){
            $q = $this->getNext($curQ);
            $q_key = array_search($q, VQuality::QUALITY);
            $topQ_key = array_search($topQ, VQuality::QUALITY);
            //error_log($topQ." topQ key ".$topQ_key." Q ". $q. " key ". $q_key);
            if ($q_key < $topQ_key){
                return $q;
            }
            else {
                return $topQ;
            }
        }
        elseif ($this->failure_count[$curQ] > $this->success_count[$curQ]){
            $q = $this->getPrev($curQ);
            return $q;
        }
        else {
            return $curQ;
        }
        
    }
    
    private function getNext($curQ){
        $i=2;
        for ( ;$i<count(VQuality::QUALITY); $i++){
            if (VQuality::QUALITY[$i] == $curQ){
                break;
            }
        }
        return $i >= count(VQuality::QUALITY) ? VQuality::QUALITY[count(VQuality::QUALITY)-1] : VQuality::QUALITY[++$i];
    }
    
    private function getPrev($curQ){
        $i=2;
        for ( ;$i<count(VQuality::QUALITY); $i++){
            if (VQuality::QUALITY[$i] == $curQ){
                break;
            }
        }
        return $i <= 2 ? VQuality::QUALITY[2] : VQuality::QUALITY[--$i];
    }
}
?>