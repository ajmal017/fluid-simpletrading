<?php

/*header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 3600');
die("MAINTENANCE MODE");*/


// CONFIG
require_once('config/config.inc.php');

// UTILS AND THIRD PARTY
require_once('functions.inc.php'); 
require_once('config/kraken.api.config.php'); 
require_once('config/nma.api.config.php'); 

// Class
require_once('class/error.class.php'); 
require_once('class/history.class.php'); 
require_once('class/ledger.class.php'); 
require_once('class/alert.class.php');

// API
require_once('api/kraken.api.php');
require_once('api/nma.api.php');


// Open SQL connection
$db = connecti();

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = $_GET['debug']; }
else $debug = 0;


/*
 * CRON CONFIG
 * 30 seconds
 * * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/demo/trade/pulse.php > /kunden/homepages/23/d202161969/htdocs/demo/trade/log/cron.log 2>&1
/*

/*
 * PULSE
 * Recommended cycle : 1 min
 */


/*
 * TICKER
 * QUERY Current Price
 */
$Exchange = new Exchange();
if($Exchange->Ticker() === true) {
    $price = $Exchange->price;

    echo "Ticker:".TRADE_PAIR."=$price\n";

    $History = new History();
    $History->add($price);
}

/*
 * ALERT
 * SEND Notifications
 */

$Alert = new Alert();
$Alert->select($price);

if(is_array($Alert->List) && count($Alert->List) > 0) {
    foreach($Alert->List as $id => $detail) {
        echo "Alert:send=$detail->price($id)\n";
        
        $Alert->send($id, $price);
    }
}

/*
 * LEDGER
 * BUY / SELL at StopLoss or TakeProfit
 */
 $Ledger = new Ledger();
 $Ledger->selectScalp($price);
 //krumo($Ledger);

 if(is_array($Ledger->List) && count($Ledger->List) > 0) {
    foreach($Ledger->List as $id => $detail) {
        echo "Ledger:scalp=sell $detail->volume-$price($id)\n";

        $Ledger->closeScalp($id);

        $Ledger->parentid       = $id;
        $Ledger->orderAction    = 'sell';
        $Ledger->type           = 'market';
        $Ledger->priceWish      = $price;
        $Ledger->volume         = $detail->volume;

        $Ledger->add();
        $Ledger->reference = 'XXXX'; // DEBUG
        $Ledger->close($Ledger->id, $price);
    }
}



/*
 * AUTOMATIC ORDER
 * STOP-LOSS / TAKE-PROFIT
 */







// Display Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_finish = $loading_time;
$loading_total_time = round(($loading_finish - $loading_start), 4);
?>

Last execution:<?php echo date('Y-m-d H:i:s'); ?> - Load:<?php echo $loading_total_time; ?>s