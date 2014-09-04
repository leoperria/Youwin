<?php

  function prob($p) {
    return mt_rand()<=$p*mt_getrandmax();
  }


   /**
   * Enter description here...
   *
   */
  function calibrate() {
    $nv=0;
    $n=100;
    for($i=0;$i<$n;$i++) {
      $vinto=prob(0.01);
      if ($vinto) {
        $nv++;
      }
    }
    return $nv/$n;
  }


  echo calibrate();