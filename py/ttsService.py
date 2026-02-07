import argparse 
import os
import json
import numpy as np
from joblib import Parallel, delayed
from flask import Flask, request, jsonify
import scipy.signal as sps
import soundfile as sf
from TTS.api import TTS


# ------------------------------------------------------------------
# 1️⃣ Helper: convert any waveform (list/ndarray) → 8 kHz, mono, int16
# ------------------------------------------------------------------
def to_8k_mono_16bit(wav, src_sr: int, target_sr: int = 8000) -> np.ndarray:
    """Return a 1‑D int16 array at 8 kHz mono."""
    wav = np.asarray(wav)                     # list/tuple → ndarray

    # ----- make mono ------------------------------------------------
    if wav.ndim == 1:                         # already mono
        wav_mono = wav.astype(np.float32)
    elif wav.ndim == 2:
        # Accept (channels, N) or (N, channels)
        if wav.shape[0] < wav.shape[1]:
            wav = wav.T
        wav_mono = wav.mean(axis=0).astype(np.float32)
    else:
        raise ValueError(f"Audio must be 1‑D or 2‑D, got ndim={wav.ndim}")

    # ----- normalise to [-1, 1] ------------------------------------
    if wav_mono.dtype.kind in {"i", "u"}:     # integer PCM → float
        max_val = np.iinfo(wav_mono.dtype).max
        wav_mono = wav_mono.astype(np.float32) / max_val
    else:
        wav_mono = np.clip(wav_mono, -1.0, 1.0)

    # ----- resample -------------------------------------------------
    if src_sr != target_sr:
        gcd = np.gcd(src_sr, target_sr)
        up = target_sr // gcd
        down = src_sr // gcd
        wav_resampled = sps.resample_poly(wav_mono, up, down)
    else:
        wav_resampled = wav_mono

    # ----- convert to 16‑bit PCM ------------------------------------
    wav_int16 = np.int16(np.round(wav_resampled * 32767))
    return wav_int16



device = "cpu"
tts = TTS(model_name="tts_models/de/thorsten/tacotron2-DDC", progress_bar=False).to(device)
#tts.tts_to_file(text="Ich bin eine Testnachricht.", file_path="output.wav")

app = Flask(__name__)


@app.route('/transscribe', methods=["GET",'POST'])
def handle_request():
    if request.method == 'GET':
        return jsonify({"status": "Service is up and running"}), 200
    else:
        try:
            # Parse JSON request
            req_data = request.get_json()
            
            # Extract parameters
            text = req_data.get('text', None)
            file = req_data.get('file', None)
            if text is None or not isinstance(text, str):
                return jsonify({"error": "'text' key is required and must be a string."}), 400  
            if file is None or not isinstance(file, str):
                return jsonify({"error": "'file' key is required and must be a string."}), 400  

            outFile = os.path.join(app.config['basedir'], file)
            
            if not app.config['small']:
                tts.tts_to_file(text=text, file_path=outFile)
            else:
                result = tts.tts(text=text)
                wav_8k = to_8k_mono_16bit(result, src_sr=22050, target_sr=8000)
                sf.write(outFile, wav_8k, 8000, subtype='PCM_16', format='WAV')
            response = {
                "status": "ok",
                "filename": outFile,
            }
            return jsonify(response), 200

        except Exception as e:
            return jsonify({"status":"error","error": str(e)}), 500

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-p', '--port', type=int, default=9010)  
    parser.add_argument('-b', '--basedir', type=str, default="/var/www/html/llama/platane/php/audio")  
    parser.add_argument('-s', '--small', type=bool, default=True, help='Use small model variant')  
    args = parser.parse_args()

    app.config['basedir'] = args.basedir
    app.config['small'] = args.small
    app.run(host='localhost', port=args.port)


