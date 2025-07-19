// load weather data from KA weather API


export type LoadKaWeatherDataResult = {
  [key: string]: any;
};

const loadKaWeatherData = async (url: string): Promise<LoadKaWeatherDataResult> => {
  console.log("Loader url:", url);
  const response = await fetch(url);
  if (!response.ok) {
    console.log("HTTP error! status:", response.status);
    return { 
        temperature : 18 + Math.random() * 8,
        soilMoisture : 30 + Math.random() * 40,
        rainfall : Math.random() * 25,
        irradiation: 200 + Math.random() * 300,
        humidity: 40 + Math.random() * 60,
        fake: true,
     }
  }
  const data: any = await response.json();
  console.log("JSON data:", data);

  const currentDate = new Date();
  const oneHourAgo = new Date(currentDate.getTime() - 60 * 60 * 1000); // Subtract 1 hour in milliseconds

  const items = Object.keys(data);
  const dataDate = new Date(data[items[0]].body[0].measured_at);
  if (dataDate < oneHourAgo) {
    console.warn("Data is older than 1 hour, resetting temperature values.");
    return { 
        temperature : 18 + Math.random() * 8,
        soilMoisture : 30 + Math.random() * 40,
        rainfall : Math.random() * 25,
        irradiation: 200 + Math.random() * 300,
        humidity: 40 + Math.random() * 60,
        fake: true,
     }
  } else {
    console.log("Data is fresh, processing data items.");
      return { 
        temperature : data[items[0]].body[0].data.temperature,
        soilMoisture : 30 + Math.random() * 40,
        rainfall : data[items[0]].body[0].data.rain,
        irradiation: data[items[0]].body[0].data.irradiation,
        humidity: data[items[0]].body[0].data.humidity,
        fake: false,
     }
  }

};

export { loadKaWeatherData };
