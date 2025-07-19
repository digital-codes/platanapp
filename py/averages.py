import pandas as pd
import numpy as np
from io import StringIO
from sklearn.linear_model import LinearRegression
import matplotlib.pyplot as plt
from statsmodels.tsa.seasonal import seasonal_decompose
import os


dwdfile = "produkt_klima_monat_19440701_20241231_04177.txt"
df = pd.read_csv(dwdfile, sep=';', na_values='-999')

# Date processing
df['date'] = pd.to_datetime(df['MESS_DATUM_BEGINN'], format='%Y%m%d')
df.set_index('date', inplace=True)
df.sort_index(inplace=True)

# Drop unused columns
# drop non-measuremens. drop bedeckungsgrad and wind
df.drop(columns=['STATIONS_ID', 'MESS_DATUM_BEGINN', 'MESS_DATUM_ENDE', 'QN_4', 'QN_6', 'eor', "MO_N", "MO_FK", "MX_TX", "MX_FX", "MX_TN"], inplace=True)
df.rename(columns={"MO_RR":"rainfall", "MX_RS":"maxrain","MO_TT":"temperature", "MO_TN":"mintemp", "MO_TX":"maxtemp","MO_SD_S":"sunhours"}, inplace=True)
# Monthly climatology
monthly_climatology = df.groupby(df.index.month).mean(numeric_only=True)

#monthly_climatology.rename(columns={"date":"month"},inplace=True)
monthly_climatology.index.name = "month"
monthly_climatology.to_csv("monthly_averages.csv", index=True)

# Trend estimation
def compute_trend(series):
    series = series.dropna()
    if len(series) < 2:
        return np.nan
    X = np.arange(len(series)).reshape(-1, 1)
    y = series.values
    model = LinearRegression().fit(X, y)
    return model.coef_[0]

trends = {
    col: compute_trend(df[col].resample('M').mean())
    for col in df.columns
}

print("Monthly Climatology:\n", monthly_climatology)
print("\nLinear Trends (slope per month):")
for k, v in trends.items():
    print(f"{k}: {v:.4f}")

# Seasonal decomposition & anomaly detection

def decompose_and_detect_anomalies(ts, var_name, model='additive', period=12):
    ts = ts.resample('M').mean()
    ts = ts.interpolate(limit_direction='both')  # Fill short gaps
    # Exclude known data gap
    ts = ts[~((ts.index >= '1985-06') & (ts.index <= '2008-10'))]
    #print(f"Processing {var_name} with {len(ts)} valid points after resampling and interpolation.")

    if ts.isna().sum() > 0 or len(ts) < 2 * period:
        print(f"Not enough data for decomposition of {var_name} ({len(ts)} valid points).")
        return None, None

    decomposition = seasonal_decompose(ts, model=model, period=period)
    residual = decomposition.resid.dropna()
    std_resid = residual.std()
    mean_resid = residual.mean()

    # Thresholds for anomaly detection
    upper_threshold = mean_resid + 2 * std_resid
    lower_threshold = mean_resid - 2 * std_resid

    # Anomalies where residual exceeds thresholds
    anomalies = residual[(residual > upper_threshold) | (residual < lower_threshold)]

    # Save decomposition plot
    fig = decomposition.plot()
    fig.set_size_inches(10, 8)
    fig_path = os.path.join(output_dir, f"{var_name}_decomposition.png")
    fig.savefig(fig_path)
    plt.close(fig)

    # Save anomalies plot
    plt.figure(figsize=(10, 4))
    plt.plot(ts, label='Original')
    plt.scatter(anomalies.index, ts[anomalies.index], color='red', label='Anomaly')
    plt.title(f"{var_name} Anomalies")
    plt.legend()
    anomaly_path = os.path.join(output_dir, f"{var_name}_anomalies.png")
    plt.savefig(anomaly_path)
    plt.close()

    # Annotated text descriptions
    descriptions = []
    for date in anomalies.index:
        value = ts.loc[date]
        res_val = residual.loc[date]
        if res_val > upper_threshold:
            tag = "above expected maximum"
        elif res_val < lower_threshold:
            tag = "below expected minimum"
        else:
            tag = "within expected range"
        descriptions.append(f"{date.strftime('%Y-%m')}: {value:.2f} ({tag})")

    return anomalies, descriptions

# Create output folder
output_dir = "anomaly_plots"
os.makedirs(output_dir, exist_ok=True)
# Run decomposition and anomaly detection on a few parameters
results = {}
for col in ['temperature', "mintemp", "maxtemp", 'rainfall', "maxrain", "sunhours"]:
    print(f"\nDecomposing and analyzing anomalies for: {col}")
    #anomalies = decompose_and_detect_anomalies(df[col])
    anomalies, description = decompose_and_detect_anomalies(df[col], var_name=col)
    if description:
        print(f"{len(description)} anomalies detected in {col}.")
        results[col] = description

with open("anomaly_descriptions.txt", "w") as f:
    for var, desc_list in results.items():
        f.write(f"Anomalies in {var}:\n")
        for desc in desc_list:
            f.write(f"  {desc}\n")
        f.write("\n")
