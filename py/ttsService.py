import argparse 
import os
import json
import numpy as np
from joblib import Parallel, delayed
from flask import Flask, request, jsonify

from TTS.api import TTS
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
            tts.tts_to_file(text=text, file_path=outFile)
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
    args = parser.parse_args()

    app.config['basedir'] = args.basedir
    app.run(host='localhost', port=args.port)


