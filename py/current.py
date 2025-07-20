import requests
import random
from datetime import datetime, timedelta
import sys 
# Use correct UTC reference
py_version = sys.version_info
print(f"Running with Python {py_version.major}.{py_version.minor}")
if py_version >= (3, 11):
    from datetime import UTC
    current_time = datetime.now(UTC)
else:
    from datetime import timezone
    current_time = datetime.now(timezone.utc)

from typing import Dict, Any
import pandas as pd

def load_ka_weather_data(url: str) -> Dict[str, Any]:
    print("Loader url:", url)
    try:
        response = requests.get(url)
        if not response.ok:
            print("HTTP error! status:", response.status_code)
            raise ValueError("HTTP error")
        data = response.json()
        print("JSON data:", data)

        one_hour_ago = current_time - timedelta(hours=1)

        items = list(data.keys())
        if not items:
            raise ValueError("No data items found")

        body = data[items[0]]['body'][0]
        measured_at_str = body['measured_at']
        measured_at = datetime.fromisoformat(measured_at_str.replace("Z", "+00:00"))

        if measured_at < one_hour_ago:
            print("Data is older than 1 hour, using fallback values.")
            return {
                "date": measured_at,
                "temperature": 18 + random.random() * 8,
                "soilMoisture": 30 + random.random() * 40,
                "rainfall": random.random() * 25,
                "irradiation": 200 + random.random() * 300,
                "humidity": 40 + random.random() * 60,
                "fake": True
            }
        else:
            print("Fresh data received.")
            return {
                "date": measured_at,
                "temperature": body['data']['temperature'],
                "soilMoisture": 30 + random.random() * 40,  # still fake
                "rainfall": body['data']['rain'],
                "irradiation": body['data']['irradiation'],
                "humidity": body['data']['humidity'],
                "fake": False
            }

    except Exception as e:
        print("Error loading weather data:", e)
        return {
            "date": measured_at,
            "temperature": 18 + random.random() * 8,
            "soilMoisture": 30 + random.random() * 40,
            "rainfall": random.random() * 25,
            "irradiation": 200 + random.random() * 300,
            "humidity": 40 + random.random() * 60,
            "fake": True
        }

if __name__ == "__main__":
    url = "https://dashboard.daten.city/php/corsProxy.php?topic=kaiserplatz"
    weather_data = load_ka_weather_data(url)
    print("Weather data:", weather_data)
    # Create a DataFrame from the weather_data dictionary
    df = pd.DataFrame(weather_data, index=[0])

    df.index = [weather_data['date']]

    # Save the DataFrame to a CSV file
    df.to_csv("current_weather.csv", index=False)

