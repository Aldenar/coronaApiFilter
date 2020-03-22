<?php

    function fetchFromApi(){
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_USERAGENT, 'CoronaTrackerFilterAPI/1.0');
        curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_URL, 'https://api.apify.com/v2/key-value-stores/K373S4uCFR9W1K8ei/records/LATEST?disableRedirect=true');

        $jsonData = curl_exec($curlSession);
        
        if (curl_errno($curlSession)) {
            curl_close($curlSession);
            return 1;
        }
        curl_close($curlSession);
        return $jsonData;
    }

    function getPayload(){
        $DEBUG = 0;
		$cacheStaleTime = 360;
        $cacheFile = "/tmp/coronaCache";
        $timestamp = time();
   
        if (file_exists($cacheFile) && is_readable($cacheFile))
        {
            if ($DEBUG) echo "CACHE READABLE".PHP_EOL;

            $json=json_decode(file_get_contents($cacheFile));
            if ( ($timestamp - $json->recvAt) > $cacheStaleTime) {
                if ($DEBUG) echo "CACHE STALE".PHP_EOL;
                # If fetching new data failed, return stale cache
                $newJson = fetchFromApi();
                if ($newJson == 1) {
                    return $json;
                }
                #Else prepare new data, replace stale cache and return it
                $jsonData = json_decode($newJson);
                $data = new \stdClass();
            
                $data->infected = $jsonData->infected;
                $data->totalTested = $jsonData->totalTested;
                $data->recovered = $jsonData->recovered;
                $data->lastUpdatedAtSource = $jsonData->lastUpdatedAtSource;
                $data->dead = $jsonData->deceased;
	            foreach ($jsonData->infectedByRegion as $region)
			    {
	        		if ($region->name == "Hlavní město Praha")
	        		{
	            		$data->infectedPrague = $region->value;
                        break;
	        		}
	    		}
                $data->recvAt = $timestamp;
                file_put_contents($cacheFile, json_encode($data));
                return $data;
            } else {
                # Horay, our cache is still current! Simply return it then
                return $json;
            }
        } else { #If we have no cache present yet...
            $json = fetchFromApi();
            if ($json == 1)
            {
                #If we got an error fetching data here... No way to recover but to return an error...
                echo '{"error":"REMOTE_API_ERROR"}';
                exit(1);
            }
            #Else we parse the new data, save it to cache and return it
			$jsonData = json_decode($json);
            $data = new \stdClass();

            $data->infected = $jsonData->infected;
            $data->totalTested = $jsonData->totalTested;
            $data->recovered = $jsonData->recovered;
            $data->dead = $jsonData->deceased;
            $data->lastUpdatedAtSource = $jsonData->lastUpdatedAtSource;
            foreach ($jsonData->infectedByRegion as $region)
		    {
        		if ($region->name == "Hlavní město Praha")
        		{
                    $data->infectedPrague = $region->value;
                    break;
        		}
    		}

            $data->recvAt = $timestamp;
            file_put_contents($cacheFile, json_encode($data));
            return $data;
        }
    }

    header('Content-Type: application/json');

    $jsonData = getPayload();
    echo json_encode($jsonData)
    ?>
