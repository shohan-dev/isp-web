C:\Users\SHOHAN\Documents\GitHub\isppaybd\isp-core\app\Config\Autoload.php 
line no 45
 'Zapi' => ROOTPATH . 'zapi',






in the routes.phjp 
udpate file 
$zapiRoutesPath = ROOTPATH . 'zapi/config/api_routes.php';
if (is_file($zapiRoutesPath)) {
    require_once $zapiRoutesPath;
}


filters.php file udpate 
