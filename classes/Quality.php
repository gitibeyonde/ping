<?php

class Quality {
    //high medium small tiny bad
    const A="GINI";
    const B="LINI";
    const C="HINI";
    const D="MINI";
    const E="SINI";
    const F="TINI";
    const G="BINI";
    
    
    public static function getNextQ($Q){
        if ($Q == Quality::A) return Quality::A;
        else if ($Q == Quality::B) return Quality::A;
        else if ($Q == Quality::C) return Quality::B;
        else if ($Q == Quality::D) return Quality::C;
        else if ($Q == Quality::E) return Quality::D;
        else if ($Q == Quality::F) return Quality::E;
        else if ($Q == Quality::G) return Quality::F;
        else return Quality::D;
    }
    
    public static function getPrevQ($Q){
        if ($Q == Quality::A) return Quality::B;
        else if ($Q == Quality::B) return Quality::C;
        else if ($Q == Quality::C) return Quality::D;
        else if ($Q == Quality::D) return Quality::E;
        else if ($Q == Quality::E) return Quality::F;
        else if ($Q == Quality::F) return Quality::G;
        else if ($Q == Quality::G) return Quality::G;
        else return Quality::G;
    }
    public static function getQuality($fifo, $topQi){
        $rel = $fifo->reliability();
        if ($rel > 90){
            return Quality::getNextQ($topQi);
        }
        else if ($rel > 30){
            return $topQi;
        }
        else {
            return Quality::getPrevQ($topQi);
        }
    }
}

?>