<?php

/**
 * Используйте эти классы, если не умеете или не хотите работать с `composer`
 * и использовать библиотеку [dadata-php](https://github.com/hflabs/dadata-php/).
 * 
 * Классы не имеют внешних зависимостей, кроме `curl`. Примеры вызова внизу файла.
 */


/*
$ch = curl_init('https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=176.59.5.42');
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Token 3365840a169dd67828ab9127365d75c1becdeb62'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
$res = curl_exec($ch);
curl_close($ch); 
$res = json_decode($res, true);
var_dump($res);
*/


//echo"321";

class TooManyRequests extends Exception
{
}

class Dadata
{
    private $clean_url = "https://cleaner.dadata.ru/api/v1/clean";
    private $suggest_url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
    private $token;
    private $secret;
    private $handle;

    public function __construct($token, $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    /**
     * Initialize connection.
     */

    public function init()
    {

        //var_dump($this->handle);
        //var_dump($this->secret);

        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $this->token,
            "X-Secret: " . $this->secret,
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);

        //var_dump($this->handle);
        //$rez = curl_exec($this->handle);
        //var_dump($rez);
    }

    /**
     * Clean service.
     * See for details:
     *   - https://dadata.ru/api/clean/address
     *   - https://dadata.ru/api/clean/phone
     *   - https://dadata.ru/api/clean/passport
     *   - https://dadata.ru/api/clean/name
     * 
     * (!) This is a PAID service. Not included in free or other plans.
     */

    public function clean($type, $value)
    {

        //var_dump($type);
        //var_dump($value);

        $url = $this->clean_url."/".$type;
        $fields = array($value);

        //var_dump($url);
       
        //var_dump($this->executeRequest($url, $fields));

        return $this->executeRequest($url, $fields);


    }

    /**
     * Find by ID service.
     * See for details:
     *   - https://dadata.ru/api/find-party/
     *   - https://dadata.ru/api/find-bank/
     *   - https://dadata.ru/api/find-address/
     */
    public function findById($type, $fields)
    {
        $url = $this->suggest_url . "/findById/$type";
        return $this->executeRequest($url, $fields);
    }

    /**
     * Reverse geolocation service.
     * See https://dadata.ru/api/geolocate/ for details.
     */

    public function geolocate($lat, $lon, $count = 10, $radius_meters = 100)
    {
        $url = $this->suggest_url . "/geolocate/address";
        $fields = array(
            "lat" => $lat,
            "lon" => $lon,
            "count" => $count,
            "radius_meters" => $radius_meters
        );
        return $this->executeRequest($url, $fields);
    }

    /**
     * Detect city by IP service.
     * See https://dadata.ru/api/iplocate/ for details.
     */

    public function iplocate($ip)
    {
        $url = $this->suggest_url . "/iplocate/address";
        $fields = array(
            "ip" => $ip
        );
        return $this->executeRequest($url, $fields);
    }

    /**
     * Suggest service.
     * See for details:
     *   - https://dadata.ru/api/suggest/address
     *   - https://dadata.ru/api/suggest/party
     *   - https://dadata.ru/api/suggest/bank
     *   - https://dadata.ru/api/suggest/name
     *   - ...
     */
    public function suggest($type, $fields)
    {
        $url = $this->suggest_url . "/suggest/$type";
        return $this->executeRequest($url, $fields);
    }

    /**
     * Close connection.
     */

    public function close()
    {
        curl_close($this->handle);
    }

    private function executeRequest($url, $fields)
    {

        //var_dump($url);
        //var_dump($this->handle);
        //var_dump($fields);

        curl_setopt($this->handle, CURLOPT_URL, $url);
        if ($fields != null) {
            curl_setopt($this->handle, CURLOPT_POST, 1);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        } else {
            curl_setopt($this->handle, CURLOPT_POST, 0);
        }
        $result = $this->exec();

        //var_dump($result);

        $result = json_decode($result, true);

        //var_dump($result);

        return $result;
    }

    private function exec()
    {


        $result = curl_exec($this->handle);

        //var_dump($result);

        $info = curl_getinfo($this->handle);


        //var_dump($this->handle);

        //echo"<pre>";
        //var_dump($info);
        //echo"</pre>";

        if ($info['http_code'] == 429) {
            throw new TooManyRequests();
        } else if ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}


// Метод init() следует вызвать один раз в начале,
// затем можно сколько угодно раз вызывать отдельные методы clean(), suggest() и т.п.
// и в конце следует один раз вызвать метод close().
//
// За счёт этого не создаются новые сетевые соединения на каждый запрос,
// а переиспользуется существующее.

$token = "3365840a169dd67828ab9127365d75c1becdeb62";
$secret = "e78c73c94ebb24b107f62ce9b8fb62f5e136353d";

$dadata = new Dadata($token, $secret);
$dadata->init();

//var_dump($_POST);

// Стандартизовать ФИО
$result = $dadata->clean("name", $_POST['user_name']." ".$_POST['user_second_name']." ".$_POST['user_last_name']);

//echo '<pre>';
//print_r($result);
//echo '</pre>';

echo json_encode($result, JSON_UNESCAPED_UNICODE);

$dadata->close();
