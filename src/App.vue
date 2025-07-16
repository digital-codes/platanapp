<script setup lang="ts">
import { ref } from 'vue'
import AudioIn from './components/AudioIn.vue'
import { onMounted } from 'vue'

import { Device } from '@capacitor/device';

const logDeviceInfo = async () => {
  const info = await Device.getInfo();

  console.log(info);
};

const hasAudio = ref<boolean>(false)
const hasText = ref<boolean>(false)
const transcript  = ref<string>("")
const hasResponse = ref<boolean>(false)
const modelResponse = ref<string>("")
const audioUrl = ref<string | null>(null)
const devInfo = ref<string | null>(null)
const model = ref<string>('granite3.3:2b') // Default model


const convertUrl = import.meta.env.MODE !== 'development'
  ? '/platane/php/convert.php'
  : 'https://llama.ok-lab-karlsruhe.de/platane/php/convert.php'

const whisperUrl = import.meta.env.MODE !== 'development'
  ? '/platane/php/whisper.php'
  : 'https://llama.ok-lab-karlsruhe.de/platane/php/whisper.php'

const modelUrl = import.meta.env.MODE !== 'development'
  ? '/platane/php/plataChat.php'
  : 'https://llama.ok-lab-karlsruhe.de/platane/php/plataChat.php'


async function handleUploadResult(payload: { success: boolean; data?: { filename: string } }): Promise<void> {
  // Catch the emitted event and handle the result here
  if (payload.success) {
    console.log('APP: Upload successful:', payload.data?.filename)
    hasAudio.value = true
    try {
      const response = await fetch(convertUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ filename: payload.data?.filename })
      })
      const data = await response.json()
      console.log('Converter response:', data)
      if (data.status !== "ok") {
        throw new Error('Convert failed ' + data.status)
      }

      try {
        const response = await fetch(whisperUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ filename: data.filename }) // Specify the model here
        })
        const data2 = await response.json()
        console.log('Whisper response:', data2)
        if (data2.status !== "ok") {
          throw new Error('Whisper failed ' + data2.status)
        }
        transcript.value = data2.text
        hasText.value = true

        try {
          const response = await fetch(modelUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ text: transcript.value, model: model.value }) // Specify the model here
          })
          const data3 = await response.json()
          console.log('Model response available') // , data3)
          if (data3.status !== "ok") {
            throw new Error('Model failed ' + data3.status)
          }
          console.log('Audio from ' + data3.synth) // , data3)
          modelResponse.value = data3.text
          hasResponse.value = true

          if (data3.audio) {
            const audioBase64 = data3.audio
            const audioBlob = new Blob([Uint8Array.from(atob(audioBase64), c => c.charCodeAt(0))], { type: 'audio/wav' })
            audioUrl.value = URL.createObjectURL(audioBlob)
            //const audio = new Audio(audioUrl)
            //audio.play()
          }
        } catch (error) {
          console.error('Error fetching model response:', error)
          modelResponse.value = "Error fetching model response."
        }


      } catch (error) {
        console.error('Error fetching whisper response:', error)
        modelResponse.value = "Error fetching whisper response."
      }


    } catch (error) {
      console.error('Error fetching model response:', error)
      modelResponse.value = "Error fetching model response."
    }


    // You can add further logic here
  } else {
    console.error('Upload failed')
  }
}

const resetAudio = () => {
  hasAudio.value = false
  hasText.value = false
  hasResponse.value = false
  transcript.value  = ""
  modelResponse.value = ""
  audioUrl.value = null
  console.log('Audio reset')
}

const updateModel = (event: Event) => {
  const selectElement = event.target as HTMLSelectElement
  const selectedModel = selectElement.value
  console.log('Selected model:', selectedModel)
  model.value = selectedModel
  // You can add logic here to handle the model change if needed
}

onMounted(async () => {
  await logDeviceInfo()
  devInfo.value = JSON.stringify(await Device.getInfo(), null, 2)
})

</script>

<template>
  <img src="./assets/llama.png" class="logo" alt="Logo" />
  <div class="modelselect">
  <select v-model="model" @change="updateModel">
    <option value="granite3.3:2b">Granite 3</option>
    <option value="gemma3:4b">Gemma 3</option>
    <option value="qwen3:4b">Qwen 3</option>
  </select>
</div>
  <AudioIn @upload-result="handleUploadResult" @reset="resetAudio" />
  <div v-if="hasAudio">
    <p>Audio is beeing transcribed ...</p>
  </div>
  <div v-if="hasText">
    <p>{{ transcript }}</p>
    <p>Asking Model ...</p>
  </div>
  <div v-if="hasResponse">
    <h2>Model says</h2>
    <p>{{ modelResponse }}</p>
    <div v-if="audioUrl">
      <audio :src="audioUrl" controls></audio>
    </div>
  </div>
  <div v-if="devInfo">
    <h2>Device Info</h2>
    <pre>{{ devInfo }}</pre>
  </div>

</template>

<style scoped>
.logo {
  height: 6em;
  padding: 1.5em;
  will-change: filter;
  transition: filter 300ms;
}

.logo:hover {
  filter: drop-shadow(0 0 2em #646cffaa);
}

.logo.vue:hover {
  filter: drop-shadow(0 0 2em #42b883aa);
}

.modelselect {
  margin: 1em;
}
  select {
  padding: 0.5em;
  font-size: 1em;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f9f9f9;
  cursor: pointer;
}
  select:hover {
  border-color: #42b883;
  background-color: #f0f0f0;
}
  select:focus {
  outline: none;    
  border-color: #42b883;
  box-shadow: 0 0 0 2px rgba(66, 184, 131, 0.2);
} 

</style>
