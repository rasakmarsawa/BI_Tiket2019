<?php

class data {
  function get_all_pemesanan(){
   $query = "
   SELECT pemesanan.tw_pemesanan AS date,
   COUNT(*) AS value
   FROM pemesanan
   GROUP BY pemesanan.tw_pemesanan
   ";
   return $this->get_data($query, '');
 }

 function get_pemesanan_monthly(){
   $query = "
   SELECT ADDDATE(pemesanan.tw_pemesanan, -DAY(pemesanan.tw_pemesanan)+1) AS date,
   COUNT(*) AS value
   FROM pemesanan
   GROUP BY MONTH(pemesanan.tw_pemesanan), YEAR(pemesanan.tw_pemesanan)
   ORDER BY YEAR(pemesanan.tw_pemesanan),MONTH(pemesanan.tw_pemesanan)
   ";
   return $this->get_data($query, '');
 }

 function get_data($query, $param){
    try{
      global $pdo;
      $req = $pdo->prepare($query);
      if($param == ''){
        $req->execute();
      }else{
        $req->execute($param);
      }
      $rows = $req->rowCount();
      $status = false;
      if($rows > 0){
        $status = true;
        $result=$req->fetchAll();
      }
      return array('status' => $status, 'rows' => $rows, 'data' => $result);
    }catch(PDOException $e){
      echo "Error! gagal mengambil data: ".$e->getMessage();
    }
}

function export_csv($in){
  // open the file "data.csv" for writing
$file = fopen('data.csv', 'w');

// save the column headers
fputcsv($file, array('date','value'));
// Sample data. This can be fetched from mysql too
for($i=0;$i<$in['rows'];$i++){
 $data[$i] = array($in['data'][$i]['date'],$in['data'][$i]['value']);
}

// save each row of the data
foreach ($data as $row)
{
fputcsv($file, $row);
}

// Close the file
fclose($file);

}
}

?>
