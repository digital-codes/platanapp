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

### Current values, karlsruhe.
last 10 minutes

Download from TP, https://transparenz.karlsruhe.de/dataset/21a50a64-fbdc-4ba5-94fb-0b1f27fb81b6/resource/78e9816b-0f18-47b8-845b-905f50d9513d/download/lubw.csv

> measured_at	winddir	windspeed	temperature	pressure	irradiation	rain	humidity
  2025-07-19T10:31:00	79.4827	3.5	27.6	993.05	148867	0	42.05
  2025-07-19T10:32:00	94.9086	3.65	27.55	993.1	148883	0	42.05
  2025-07-19T10:33:00	109552	2.95	27.5	993.1	150083	0	42.2
  2025-07-19T10:34:00	118791	2.36667	27.45	993.1	149383	0	42.2
  2025-07-19T10:35:00	94.8237	2.63333	27.4	993.15	150	0	42.1
  2025-07-19T10:36:00	93.5831	3.93333	27.4	993.2	151.85	0	42.15
  2025-07-19T10:37:00	90.9102	3.18333	27.35	993.2	155817	0	41.95
  2025-07-19T10:38:00	93.3147	4.26667	27.3	993.2	157667	0	41.65
  2025-07-19T10:39:00	85.5184	2.6	27.25	993.2	157	0	41.8
  2025-07-19T10:40:00	100.1	3.53333	27.2	993.2	155567	0	42.05



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



