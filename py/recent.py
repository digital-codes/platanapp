import pandas as pd
import numpy as np
from io import StringIO
from sklearn.linear_model import LinearRegression
import matplotlib.pyplot as plt
from statsmodels.tsa.seasonal import seasonal_decompose
import os
from datetime import datetime, timedelta

import zipfile
import requests
import io

# URL of the DWD ZIP file
url = "https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/kl/recent/tageswerte_KL_04177_akt.zip"

# Download the ZIP file into memory
response = requests.get(url)
if response.status_code != 200:
    raise Exception(f"Download failed with status code {response.status_code}")

# Open the ZIP file from memory
with zipfile.ZipFile(io.BytesIO(response.content)) as z:
    # Find the correct .txt file
    for filename in z.namelist():
        if filename.startswith("produkt_klima_tag") and filename.endswith(".txt"):
            with z.open(filename) as f:
                # Read as pandas DataFrame
                df = pd.read_csv(f, sep=';', na_values='-999', encoding='latin1')
                break
    else:
        raise Exception("Expected data file not found in ZIP.")


# keep only some cols
df = df[['MESS_DATUM', ' RSK', " TMK", " TNK", " TXK", " SDK"]]
df.rename(columns={" RSK": "rainfall", " TMK": "temperature", " TNK": "mintemp", " TXK": "maxtemp", " SDK": "sunhours"}, inplace=True)

# Convert date
df['MESS_DATUM'] = pd.to_datetime(df['MESS_DATUM'], format='%Y%m%d')

# Set date as index and sort
df.set_index('MESS_DATUM', inplace=True)
df.sort_index(inplace=True)

# Ensure data is recent (max date not older than 2 days ago)
latest_date = df.index.max()
now = pd.Timestamp.now().normalize()

if (pd.Timestamp.now() - latest_date) > pd.Timedelta(days=3):
    raise ValueError(f"Data is too old: last date is {latest_date.date()}, more than 3 days ago.")


# Use only numeric columns
df = df.apply(pd.to_numeric, errors='coerce')

# Define analysis windows
# 🧮 Analysis function
def analyze_window(recent: pd.DataFrame, days: int) -> pd.DataFrame:
    end_date = recent.index.max()
    start_date = end_date - pd.Timedelta(days=days)  # FIXED

    recent = df.loc[start_date:end_date]

    data = {
        'mean': {},
        'trend': {}
    }

    for col in recent.columns:
        series = recent[col].dropna()
        if len(series) < 2:
            continue

        # Mean
        avg = series.mean()

        # Trend (slope of linear regression)
        X = np.arange(len(series)).reshape(-1, 1)
        y = series.values
        model = LinearRegression().fit(X, y)
        slope = model.coef_[0]

        # Store in dictionary
        data['mean'][col] = round(avg, 2)
        data['trend'][col] = round(slope, 4)

    # Convert to DataFrame with "type" as a column
    df_result = pd.DataFrame(data).T.reset_index()
    df_result.rename(columns={'index': 'type'}, inplace=True)
    return df_result

# 🔄 Analyze both 7- and 30-day windows
results = analyze_window(df,7)
# Export to CSV
results.to_csv("climate_recent_analysis_week.csv", index=False)

results = analyze_window(df,30)
results.to_csv("climate_recent_analysis_month.csv", index=False)

print("Analysis complete and saved to climate_recent_analysis_week.csv and climate_recent_analysis_month.csv")

