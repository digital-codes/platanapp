# Platanen ChatBot

This is the prototype of a very simple chat bot inspired by https://kiwilab.de/platane.html 

The entire system runs on a middle-class VM (8 cores, 32GB RAM, no GPU) with acceptable speed for testing. To scale for production fast hardware would be needed, or access to remote services like https://deepinfra.com/

# Demo

https://papperlapp.netlify.app/

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





