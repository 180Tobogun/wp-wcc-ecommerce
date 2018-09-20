<?php


    function process_transaction_code(){
    
        // check transaction code
        $tranCode = $_POST["TranCode"];
        echo "TranCode=$tranCode";
        echo "<br />";
        
        if ($tranCode != '000') { 
            echo "Bad Transaction Response : TranCode = $tranCode"; 
            exit;
        }
        
    }
    
    function process_signatures(){
    
        $ok = verify_signatures();
        
        echo $ok.'<br>';
        
        if ($ok == 1) { 
            echo ">>> GOOD!!"; 
            
            // exit;
        }
        elseif ($ok == 0) { 
            echo "bad"; 
            exit;
        } 
        else { 
            echo "ugly, error checking signature";
            exit;
        }
        
    }
    
    function verify_signatures(){
        
        $email = $_POST["Email"];
        echo "Email=$email";
        echo "<br />";
        
        $tranCode = $_POST["TranCode"];
        echo "OK=$tranCode";
        echo "<br />";
        
        $totalAmount = $_POST["TotalAmount"];
        echo "TotalAmount=$totalAmount";
        echo "<br />";
        
        $merchantID = $_POST["MerchantID"];
        echo "MerchantID=$merchantID";
        echo "<br />";
        
        $orderID = $_POST["OrderID"];
        echo "OrderID=$orderID";
        echo "<br />";
        
        $approvalCode = $_POST["ApprovalCode"];
        echo "ApprovalCode=$approvalCode";
        echo "<br />";
        
        $proxyPan = $_POST["ProxyPan"];
        echo "ProxyPan=$proxyPan";
        echo "<br />";
        
        $rrn = $_POST["Rrn"];
        echo "Rrn=$rrn";
        echo "<br />";
        
        $purchaseTime = $_POST["PurchaseTime"];
        echo "PurchaseTime=$purchaseTime";
        echo "<br />";
        
        $sd = $_POST["SD"];
        echo "SD=$sd";
        echo "<br />";
        
        $xid = $_POST["XID"];
        echo "XID=$xid";
        echo "<br />";
        
        $currency = $_POST["Currency"];
        echo "Currency=$currency";
        echo "<br />";
        
        $signature = $_POST["Signature"];
        echo "Signature=$signature";
        echo "<br />";
        
        $terminalID = $_POST["TerminalID"];
        echo "TerminalID=$terminalID";
        echo "<br />";
        
        //  ===================================
        
        $data = "$merchantID;$terminalID;$purchaseTime;$orderID;$xid;$currency;$totalAmount;$sd;$tranCode;$approvalCode;";
        
        echo "<br />";
        echo "from_gateway=".$data;
        echo "<br /><br />";
        
        //  ===================================
        
        $signature = base64_decode($signature) ;
        
        // $pubFile     = __DIR__.'/keys/'.$merchantID.'.pub';
    	$pubFile     = __DIR__.'/keys/'.'work-server.key';
        
        echo "pubFile : $pubFile";
        echo "<br /><br />";
        
        // извлечь сертификат
        $fp = fopen($pubFile, "r");
        $cert = fread($fp, 8192);
        fclose($fp);
        
        if(true)
            echo $cert;
        
        $pubkeyid = openssl_get_publickey($cert);
        // проверка подписи
        $ok = openssl_verify($data, $signature, $pubkeyid); 
        
        
        // free the key from memory
        openssl_free_key($pubkeyid);
        
        echo "<br /><br />";
        echo "openssl_verify=$ok";
        echo "<br />";
        
        return $ok;
    }
            
    function path2url($file, $Protocol='http://') {
        return $Protocol.$_SERVER['HTTP_HOST'].str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
    }
    
?>