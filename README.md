# Apify Corona filter

A quick and dirty solution to a project me and my friends worked on together. 

Our goal was to display current Coronavirus situation data on a tiny ESP we received at one of Avast's CTF days. Unfortunately, the thing has little to no memory, and as we chose to use MicroPython, it was a pain to even initiate a HTTPS connection.

The result was me sitting down and scribbling down a little script that would act as a filter for the (relatively) big dataset returned by the original API, as well as being accessible over plane HTTP.

## Functionality
1. Returns specific json-encoded data from the backend API
2. Caches data by default for 10 minutes

## Requirements
1. The PHP Curl library (Under debian provided by the php-curl package)

## Deployment
Make the PHP file available under a publically accessible URL and make sure the script can write to /tmp

## ESP Display part
The other half of this project is the Python source code to display the filtered data on the ESP itself. That can be found here: https://github.com/eldan-dex/ESP32-corona-tracker
