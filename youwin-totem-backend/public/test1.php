<?php

msg("Questo è il messaggio visivo che deve essere");







$rr["help0.1"]=array(
  "codeMsg"=>true,
  "it"=>"dsdasldkjadslkajsdlaksd",
  "en"=>"fsfdsf",
  "es"=>"fdsdsds"
);

$rr["Questo è il messaggio visivo che deve essere tradotto"]=array(
   "en"=>"this is e....",
   "es"=>"esto ...."
);

$rr["Prova microfono"]=array(
   "en"=>"microphone test",
   "es"=>"test dello microfono"
);


$currentLang="it";
$homeLang="it";

function msg($chiave,$code=false){
  global $currentLang;
  global $homeLang;
  global $rr;


  if (!$currentLang==$homeLang){
    return $chiave;
  }else{
    if (isset($rr[$chiave])){
      return $rr[$chiave][$currentLang];
    }else{
      return "T:".$chiave;
    }
  }

}