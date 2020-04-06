<?php

function fetchFromApi($api="https://api.apify.com/v2/key-value-stores/K373S4uCFR9W1K8ei/records/LATEST?disableRedirect=true"){
    try {
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_USERAGENT, 'CoronaTrackerFilterAPI/1.0');
        curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_URL, $api);

        $jsonData = curl_exec($curlSession);
        
        if (curl_errno($curlSession)) {
            curl_close($curlSession);
            return 1;
        }
        curl_close($curlSession);
        return $jsonData;

        } catch (Exception $e) {
            return 1;
        }
    }


function getData(){
    $timestamp = time();
    $apifyJson = fetchFromApi();
    $seznamJson = fetchFromApi("https://trending.seznam.cz/covid19");
    if (($apifyJson == 1) || ($seznamJson == 1))
    {
        return json_encode('{"error":"Error fetching data from API", "apifyReturn":\''.$apifyJson.'\',\'seznamReturn\':\''.$seznamJson.'\'}');
    }

    $apifyData = json_decode($apifyJson);
    $seznamData = json_decode($seznamJson);
    $data = new \stdClass();

    $data->infected = $apifyData->infected;
    $data->totalTested = $apifyData->totalTested;
    $data->recovered = $apifyData->recovered;
    $data->r = $seznamData->r;
    $data->dead = $apifyData->deceased;
    $data->lastUpdatedAtSource = $apifyData->lastUpdatedAtSource;
    foreach ($apifyData->infectedByRegion as $region)
    {
		if ($region->name == "Hlavní město Praha")
		{
            $data->infectedPrague = $region->value;
            break;
		}
	}

    $data->recvAt = $timestamp;
    return $data;
}


function getPayload(){
        $DEBUG = 1;
        $cacheStaleTime = 360;
        if ($DEBUG) 
            $cacheFile = "/tmp/coronaCacheDebug"; 
        else 
            $cacheFile = "/tmp/coronaCache";

        $timestamp = time();
   
        if (file_exists($cacheFile) && is_readable($cacheFile))
        {
            if ($DEBUG) echo "CACHE READABLE".PHP_EOL;

            $json=json_decode(file_get_contents($cacheFile));
            if ( ($timestamp - $json->recvAt) > $cacheStaleTime) {
                if ($DEBUG) echo "CACHE STALE".PHP_EOL;
                # If fetching new data failed, return stale cache
                $data = getData();
                if (isset(json_encode($data)->error)) {
                    return $json;
                }
                #Otherwise save new data and return it
                file_put_contents($cacheFile, $data);
                return $data;
            } else {
                # Horay, our cache is still current! Simply return it then
                return $json;
            }
        } else { #If we have no cache present yet...
            $data = getData();
            if (isset(json_encode($data)->error))
                return $data;
            file_put_contents($cacheFile, json_encode($data));
            return $data;
        }
    }

    header('Content-Type: application/json');

    $jsonData = getPayload();
    echo json_encode($jsonData)
?>
