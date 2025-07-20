# Platanen ChatBot

This is the prototype of a very simple chat bot inspired by https://kiwilab.de/platane.html 

The entire system runs on a middle-class VM (8 cores, 32GB RAM, no GPU) with acceptable speed for testing. To scale for production fast hardware would be needed, or access to remote services like https://deepinfra.com/

# Demo

https://llama.ok-lab-karlsruhe.de/platane/

**Note: the demo uses GERMAN language for input**


# Structure
Implements the following tasks

 * User input, currently voice 
 * Transscribe voice to text
 * Generate LLM prompt from system prompt and user input
 * Prompt LLM, various models
 * Synthesize LLM response to audio
 * Present repsonse text and audio to user

# Composition

## Front End

Vue3 application

  * Audio input for user questions
  * Display of the results of the various steps
  * Audio output with controls

Calls appropriate HTTP routes (see below)

## Back End

### HTTP

Set of simple PHP scripts for:

 * Upload audio
 * Transscribe
 * LLM interaction
 * Synthesize

### Services

All services run on the local (actually hosted) VM

 * Speech Recognition
   * Whisper 
      * Whisper.cpp, with medium multilingual model
 * LLM
   * Ollama, with Granite3, Qwen3, Gemma 3 models
 * Speech Synthesis
   * Coqui, https://github.com/coqui-ai
   * espeak-ng

Coqui produces much better results but is no longer maintained. Works up to Python3.11 and is usable as a local service.

All services could be replaced by cloud services, e.g. from deepinfra:

  * Transscribe: https://deepinfra.com/openai/whisper-large-v3-turbo/api
  * LLM: https://deepinfra.com/Qwen/Qwen3-14B
  * Synthesize: https://deepinfra.com/hexgrad/Kokoro-82M



# Build

> git clone 
> npm install 

Test/devel:

> npm run dev

dev mode always calls PHP routines at demonstrator site!

Build: 

Check target subdomain: "base" in vite.config.js 

> npm run build
> scp -r dist/* <your host directory>
> scp -r php <your host directory>



# Extensions
## Climate/Wheather data

get local current conditions, recent conditions (week and month) and long term
climate conditions and prepare for prompt input.

long term: get files from DWD below and run "averages.py" once and copy "montly_averages.csv" to server

couple of pythons scripts together with crontab:

```
*/10 * * * * cd /var/www/html/llama/platane/py && /usr/bin/python3 current.py >> cron.log 2>&1
0 3 * * * cd /var/www/html/llama/platane/py && /usr/bin/python3 recent.py >> recent.log 2>&1
```
run compare.py after each current.py

```
www-data@vmd98182:~/html/llama/platane/py$ python3 compare.py 
Running with Python 3.10
Current month: 7
                               date  temperature  soilMoisture  rainfall  irradiation  humidity   fake
0  2025-07-20 05:26:08.240324+00:00         19.3     50.073544         0           90      75.4  False
Current temperature: 19.30000000000001
Checks: {'tmean_7d': -1.04999999999999, 'tmin_7d': 5.610000000000012, 'tmax_7d': -7.439999999999987, 'tmean_30d': -2.899999999999988, 'tmin_30d': 4.630000000000011, 'tmax_30d': -9.609999999999989, 'tmean_longTerm': -0.2796491228070046, 'tmin_longTerm': 5.90982456140352, 'tmax_longTerm': -6.161578947368408, 'rmean_7d': -2.0, 'rmean_30d': -1.84, 'rmean_longTerm': -77.37321428571428, 'rmax_longTerm': -20.86428571428572, 'tmean_7dlt': 0.7703508771929855, 'tmin_7dlt': 0.29982456140350777, 'tmax_7dlt': 1.278421052631579, 'rmean_7dlt': -75.37321428571428}
comparison summary saved to climate_indicator_summary.csv
```

todo: prepare propmt input from comparison


### Local
local weather at Kaiserplatz from transparenzportal

later via sensors (option)

### DWD, Rheinstetten
rheinstetten 
Rheinstetten;48.97;8.33;118;Germany, Höhe 116m

ID: 4177;


temperatur now:
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/air_temperature/now/10minutenwerte_TU_04177_now.zip
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/air_temperature/meta_data/Meta_Daten_zehn_min_tu_04177.zip


wind now:
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/wind/now/10minutenwerte_wind_04177_now.zip
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/wind/meta_data/Meta_Daten_zehn_min_ff_04177.zip



rain now:
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/precipitation/now/10minutenwerte_nieder_04177_now.zip
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/10_minutes/precipitation/meta_data/Meta_Daten_zehn_min_rr_04177.zip

daily (2 jahre bei temp, etc):
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/soil_temperature/recent/EB_Tageswerte_Beschreibung_Stationen.txt
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/soil_temperature/recent/tageswerte_EB_04177_akt.zip

https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/kl/recent/tageswerte_KL_04177_akt.zip
https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/kl/recent/KL_Tageswerte_Beschreibung_Stationen.txt



