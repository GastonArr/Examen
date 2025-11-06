<?php 
/*esta funcion de PHP hace que al ejecutar este archivo, tome la hora de la ciudad mencionada */
date_default_timezone_set("America/Argentina/Cordoba");
//pueden usarla si lo desean en el trabajo para que calcule la fecha correctamente. 
//la copian al principio del archivo donde trabajen con la fecha. 

//analicen estos procesos para saber como trabajar en lo pedido:
$Fecha_viaje="2025-11-02"; //esto será el valor que venga de la BD, pongan la fecha que quieran probar
$Fecha_viaje="2025-11-03"; //esto será el valor que venga de la BD, pongan la fecha que quieran probar
$Fecha_viaje="2025-10-20"; //esto será el valor que venga de la BD, pongan la fecha que quieran probar
//$Fecha_viaje="2025-11-05"; //esto será el valor que venga de la BD, pongan la fecha que quieran probar

//defino la fecha de hoy
$Fecha_actual = date("Y-m-d"); 

//de esta manera sabemos cual es la fecha de mañana (sumamos un dia a hoy)
$Maniana= date("Y-m-d",strtotime($Fecha_actual."+ 1 day"));   

//con ambos datos, podemos preguntar si la fecha del viaje es mañana?
if ($Fecha_viaje == $Maniana){
    echo "El viaje es mañana!  <br /> 
    Fecha viaje:  $Fecha_viaje  / mañana será $Maniana <hr / >
    Formato Español: Fecha viaje: ".date('d/m/Y',strtotime($Fecha_viaje))."  /  mañana será  ".date('d/m/Y',strtotime($Maniana)); 
    echo "<hr / >";
}else if ($Fecha_viaje > $Fecha_actual){
//la fecha del viaje es mayor a hoy?

    echo "El viaje despues de mañana, en adelante. <br /> Fecha viaje:  $Fecha_viaje  (hoy es $Fecha_actual)"; 
}

//la fecha del viaje es para hoy?
if ($Fecha_viaje ==$Fecha_actual){
    echo "El viaje es hoy! <br /> 
    Fecha viaje:  $Fecha_viaje  / mañana será $Fecha_actual <hr / >
    Formato Español: Fecha viaje: ".date('d/m/Y',strtotime($Fecha_viaje))."  /  mañana será  ".date('d/m/Y',strtotime($Fecha_actual)); 
    echo "<hr / >";
}
//la fecha del viaje es menor a hoy?
if ($Fecha_viaje < $Fecha_actual){
    echo "El viaje ya se hizo. <br /> Fecha viaje:  $Fecha_viaje  (hoy es $Fecha_actual)"; 
    
}


?>